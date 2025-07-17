<?php
/**
 * Main plugin class for handling autopost functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexjob_Autopost {
    
    private $configs;
    
    public function __construct() {
        $this->configs = new Nexjob_Configs();
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Hook into post save/publish events
        add_action('save_post', array($this, 'handle_post_save'), 10, 3);
        add_action('wp_insert_post', array($this, 'handle_post_insert'), 10, 3);
        
        // Hook for cleanup logs
        add_action('nexjob_autopost_cleanup_logs', array($this, 'cleanup_old_logs'));
        
        // Hook for retry mechanism
        add_action('nexjob_autopost_retry_failed', array($this, 'retry_failed_request'));
        
        // Hook for email notifications
        add_action('nexjob_autopost_send_notification', array($this, 'send_email_notification'));
    }
    
    /**
     * Handle post save event
     */
    public function handle_post_save($post_id, $post, $update) {
        // Only process published posts
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Check if plugin is enabled
        if (get_option('nexjob_autopost_enabled') !== '1') {
            return;
        }
        
        // Avoid infinite loops and duplicates
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Get configurations for this post type
        $configs = $this->configs->get_configs_for_post_type($post->post_type);
        
        if (empty($configs)) {
            return; // No configurations for this post type
        }
        
        // Check if we already processed this post recently
        $processed_meta = get_post_meta($post_id, '_nexjob_autopost_processed', true);
        $current_time = current_time('timestamp');
        
        if ($processed_meta && ($current_time - $processed_meta) < 60) {
            return; // Skip if processed within last minute
        }
        
        // Process the post with all matching configurations
        foreach ($configs as $config) {
            $this->process_post_with_config($post_id, $post, $config);
        }
        
        // Mark as processed
        update_post_meta($post_id, '_nexjob_autopost_processed', $current_time);
    }
    
    /**
     * Handle post insert event (for API-created posts)
     */
    public function handle_post_insert($post_id, $post, $update) {
        // Only process new posts (not updates)
        if ($update) {
            return;
        }
        
        // Only process published posts
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Check if plugin is enabled
        if (get_option('nexjob_autopost_enabled') !== '1') {
            return;
        }
        
        // Get configurations for this post type
        $configs = $this->configs->get_configs_for_post_type($post->post_type);
        
        if (empty($configs)) {
            return; // No configurations for this post type
        }
        
        // Delay processing slightly to ensure all meta data is saved
        wp_schedule_single_event(time() + 5, 'nexjob_autopost_delayed_process', array($post_id));
    }
    
    /**
     * Process the post with specific configuration and send to API
     */
    public function process_post_with_config($post_id, $post, $config) {
        if (!$post) {
            $post = get_post($post_id);
        }
        
        if (!$post) {
            return;
        }
        
        try {
            // Parse content template with placeholders
            $content = $this->configs->parse_content_template($config->content_template, $post);
            
            // Get tags for API
            $tags = wp_get_post_terms($post_id, 'tag-loker');
            $tag_data = array();
            
            if (!is_wp_error($tags) && !empty($tags)) {
                foreach ($tags as $tag) {
                    $tag_data[] = array(
                        'value' => $tag->slug,
                        'label' => $tag->name
                    );
                }
            }
            
            // Prepare API data in correct format
            $post_data = array(
                'type' => 'now',
                'shortLink' => true,
                'date' => current_time('c'),
                'tags' => $tag_data,
                'posts' => array(
                    array(
                        'integration' => array(
                            'id' => $config->integration_id
                        ),
                        'value' => array(
                            array(
                                'content' => $content
                            )
                        ),
                        'group' => 'nexjob-autopost-group',
                        'settings' => new stdClass()
                    )
                )
            );
            
            // Send to API
            $response = $this->send_to_api($post_data);
            
            // Log the request
            $log_id = $this->log_request($post_id, $post->post_title, $post_data, $response, $config->id);
            
            // If request failed, schedule retry
            if ($response['http_code'] < 200 || $response['http_code'] >= 300) {
                $this->schedule_retry($log_id, $post_id, 1);
            }
            
        } catch (Exception $e) {
            // Log error
            $log_id = $this->log_error($post_id, $post->post_title, $e->getMessage(), $config->id);
            
            // Schedule retry for exception
            $this->schedule_retry($log_id, $post_id, 1);
        }
    }
    
    /**
     * Process the post and send to API (backward compatibility)
     */
    public function process_post($post_id, $post = null) {
        if (!$post) {
            $post = get_post($post_id);
        }
        
        if (!$post) {
            return;
        }
        
        // Get configurations for this post type
        $configs = $this->configs->get_configs_for_post_type($post->post_type);
        
        foreach ($configs as $config) {
            $this->process_post_with_config($post_id, $post, $config);
        }
    }
    

    
    /**
     * Send data to external API
     */
    private function send_to_api($data) {
        $api_url = get_option('nexjob_autopost_api_url', 'https://autopost.nexpocket.com/api/public/v1/post');
        $auth_header = get_option('nexjob_autopost_auth_header', '');
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: ' . $auth_header
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        return array(
            'http_code' => $http_code,
            'response' => $response
        );
    }
    
    /**
     * Schedule retry for failed request
     */
    private function schedule_retry($log_id, $post_id, $attempt) {
        $max_retries = get_option('nexjob_autopost_max_retries', 3);
        
        if ($attempt <= $max_retries) {
            // Schedule retry with exponential backoff
            $delay = pow(2, $attempt) * 60; // 2, 4, 8 minutes
            wp_schedule_single_event(time() + $delay, 'nexjob_autopost_retry_failed', array($log_id, $post_id, $attempt));
            
            // Update log status to retry
            global $wpdb;
            $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
            $wpdb->update(
                $table_name,
                array('status' => 'retry', 'retry_count' => $attempt),
                array('id' => $log_id),
                array('%s', '%d'),
                array('%d')
            );
        } else {
            // Send email notification for final failure
            wp_schedule_single_event(time() + 60, 'nexjob_autopost_send_notification', array($post_id, 'final_failure'));
        }
    }
    
    /**
     * Retry failed request
     */
    public function retry_failed_request($log_id, $post_id, $attempt) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'lowongan-kerja') {
            return;
        }
        
        try {
            // Get post data
            $post_data = $this->prepare_post_data($post);
            
            // Send to API
            $response = $this->send_to_api($post_data);
            
            // Update existing log
            global $wpdb;
            $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
            
            $status = ($response['http_code'] >= 200 && $response['http_code'] < 300) ? 'success' : 'error';
            
            $wpdb->update(
                $table_name,
                array(
                    'response_data' => $response['response'],
                    'response_code' => $response['http_code'],
                    'status' => $status,
                    'retry_count' => $attempt
                ),
                array('id' => $log_id),
                array('%s', '%d', '%s', '%d'),
                array('%d')
            );
            
            if ($status === 'success') {
                // Send success notification
                wp_schedule_single_event(time() + 60, 'nexjob_autopost_send_notification', array($post_id, 'retry_success'));
            } else {
                // Schedule next retry
                $this->schedule_retry($log_id, $post_id, $attempt + 1);
            }
            
        } catch (Exception $e) {
            // Update log with error
            global $wpdb;
            $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
            
            $wpdb->update(
                $table_name,
                array(
                    'response_data' => $e->getMessage(),
                    'response_code' => 0,
                    'status' => 'error',
                    'retry_count' => $attempt
                ),
                array('id' => $log_id),
                array('%s', '%d', '%s', '%d'),
                array('%d')
            );
            
            // Schedule next retry
            $this->schedule_retry($log_id, $post_id, $attempt + 1);
        }
    }
    
    /**
     * Send email notification
     */
    public function send_email_notification($post_id, $type) {
        if (!get_option('nexjob_autopost_email_notifications', '0')) {
            return;
        }
        
        $email = get_option('nexjob_autopost_notification_email', get_option('admin_email'));
        $post = get_post($post_id);
        
        if (!$post) {
            return;
        }
        
        $subject = '';
        $message = '';
        
        switch ($type) {
            case 'final_failure':
                $subject = 'Nexjob Autopost: Final Failure for Post #' . $post_id;
                $message = "The post '{$post->post_title}' (ID: {$post_id}) failed to send to the API after all retry attempts.\n\n";
                $message .= "Please check the logs in the WordPress admin for more details.\n\n";
                $message .= "Post URL: " . get_permalink($post_id);
                break;
                
            case 'retry_success':
                $subject = 'Nexjob Autopost: Retry Success for Post #' . $post_id;
                $message = "The post '{$post->post_title}' (ID: {$post_id}) was successfully sent to the API on retry.\n\n";
                $message .= "Post URL: " . get_permalink($post_id);
                break;
        }
        
        if ($subject && $message) {
            wp_mail($email, $subject, $message);
        }
    }
    
    /**
     * Bulk resend posts
     */
    public function bulk_resend_posts($post_ids) {
        $results = array('success' => 0, 'failed' => 0, 'errors' => array());
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            
            if (!$post || $post->post_type !== 'lowongan-kerja') {
                $results['failed']++;
                $results['errors'][] = "Post ID {$post_id} not found or not a lowongan-kerja post";
                continue;
            }
            
            try {
                // Get post data
                $post_data = $this->prepare_post_data($post);
                
                // Send to API
                $response = $this->send_to_api($post_data);
                
                // Log the request
                $this->log_request($post_id, $post->post_title, $post_data, $response, null, 'bulk_resend');
                
                if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Post ID {$post_id}: HTTP {$response['http_code']}";
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Post ID {$post_id}: " . $e->getMessage();
                $this->log_error($post_id, $post->post_title, $e->getMessage(), null, 'bulk_resend');
            }
        }
        
        return $results;
    }
    
    /**
     * Log API request
     */
    private function log_request($post_id, $post_title, $request_data, $response, $config_id = null, $type = 'auto') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        
        $status = ($response['http_code'] >= 200 && $response['http_code'] < 300) ? 'success' : 'error';
        
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'api_url' => get_option('nexjob_autopost_api_url'),
                'request_data' => json_encode($request_data),
                'response_data' => $response['response'],
                'response_code' => $response['http_code'],
                'status' => $status,
                'request_type' => $type,
                'retry_count' => 0,
                'autopost_config_id' => $config_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Log error
     */
    private function log_error($post_id, $post_title, $error_message, $config_id = null, $type = 'auto') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'api_url' => get_option('nexjob_autopost_api_url'),
                'request_data' => '',
                'response_data' => $error_message,
                'response_code' => 0,
                'status' => 'error',
                'request_type' => $type,
                'retry_count' => 0,
                'autopost_config_id' => $config_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = get_option('nexjob_autopost_log_retention_days', 30);
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
    }
}

// Add delayed processing hook
add_action('nexjob_autopost_delayed_process', function($post_id) {
    $autopost = new Nexjob_Autopost();
    $autopost->process_post($post_id);
});
?>
