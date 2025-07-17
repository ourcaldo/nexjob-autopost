<?php
/**
 * Admin functionality for Nexjob Autopost plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexjob_Admin {

    private $configs;

    public function __construct() {
        $this->configs = new Nexjob_Configs();
    }

    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_nexjob_test_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_nexjob_delete_logs', array($this, 'delete_logs'));
        add_action('wp_ajax_nexjob_export_logs', array($this, 'export_logs'));
        add_action('wp_ajax_nexjob_bulk_resend', array($this, 'bulk_resend'));
        add_action('wp_ajax_nexjob_retry_single', array($this, 'retry_single'));
        add_action('wp_ajax_nexjob_save_config', array($this, 'save_config'));
        add_action('wp_ajax_nexjob_delete_config', array($this, 'delete_config'));
        add_action('wp_ajax_nexjob_get_placeholders', array($this, 'get_placeholders'));
        add_action('wp_ajax_nexjob_get_log_details', array($this, 'get_log_details'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Nexjob Autopost',
            'Nexjob Autopost',
            'manage_options',
            'nexjob-autopost',
            array($this, 'admin_page'),
            'dashicons-share',
            30
        );

        add_submenu_page(
            'nexjob-autopost',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'nexjob-autopost',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'nexjob-autopost',
            'Logs',
            'Logs',
            'manage_options',
            'nexjob-logs',
            array($this, 'logs_page')
        );

        add_submenu_page(
            'nexjob-autopost',
            'Bulk Actions',
            'Bulk Actions',
            'manage_options',
            'nexjob-bulk',
            array($this, 'bulk_page')
        );

        add_submenu_page(
            'nexjob-autopost',
            'Configurations',
            'Configurations',
            'manage_options',
            'nexjob-configs',
            array($this, 'configs_page')
        );

        add_submenu_page(
            'nexjob-autopost',
            'Settings',
            'Settings',
            'manage_options',
            'nexjob-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_api_url');
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_auth_header');
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_enabled');
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_log_retention_days');
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_max_retries');
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_email_notifications');
        register_setting('nexjob_autopost_settings', 'nexjob_autopost_notification_email');
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (!in_array($hook, ['toplevel_page_nexjob-autopost', 'nexjob-autopost_page_nexjob-logs', 'nexjob-autopost_page_nexjob-bulk', 'nexjob-autopost_page_nexjob-configs', 'nexjob-autopost_page_nexjob-settings'])) {
            return;
        }

        wp_enqueue_style('nexjob-admin-style', NEXJOB_AUTOPOST_PLUGIN_URL . 'admin/css/admin-style.css', array(), NEXJOB_AUTOPOST_VERSION);
        wp_enqueue_script('nexjob-admin-script', NEXJOB_AUTOPOST_PLUGIN_URL . 'admin/js/admin-script.js', array('jquery'), NEXJOB_AUTOPOST_VERSION, true);

        wp_localize_script('nexjob-admin-script', 'nexjob_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nexjob_admin_nonce')
        ));
    }

    /**
     * Display admin page
     */
    public function admin_page() {
        include NEXJOB_AUTOPOST_PLUGIN_DIR . 'admin/dashboard-page.php';
    }

    /**
     * Display logs page
     */
    public function logs_page() {
        include NEXJOB_AUTOPOST_PLUGIN_DIR . 'admin/logs-page.php';
    }

    /**
     * Display bulk actions page
     */
    public function bulk_page() {
        include NEXJOB_AUTOPOST_PLUGIN_DIR . 'admin/bulk-page.php';
    }

    /**
     * Test API connection
     */
    public function test_api_connection() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $api_url = sanitize_text_field($_POST['api_url']);
        $auth_header = sanitize_text_field($_POST['auth_header']);

        // Test data
        $test_data = array(
            'type' => 'now',
            'shortLink' => true,
            'date' => current_time('c'),
            'tags' => array(),
            'posts' => array(
                array(
                    'integration' => array(
                        'id' => get_option('nexjob_autopost_integration_id', 'cmd6ykh840001n5bdw68gcwnh')
                    ),
                    'value' => array(
                        array(
                            'content' => 'Test connection from Nexjob Autopost plugin'
                        )
                    ),
                    'group' => 'nexjob-test-group',
                    'settings' => new stdClass()
                )
            )
        );

        $headers = array(
            'Content-Type: application/json',
            'Authorization: ' . $auth_header
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            wp_send_json_error('cURL Error: ' . $error);
        }

        $status = ($http_code >= 200 && $http_code < 300) ? 'success' : 'error';

        wp_send_json(array(
            'status' => $status,
            'http_code' => $http_code,
            'response' => $response
        ));
    }

    /**
     * Delete logs
     */
    public function delete_logs() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';

        $log_ids = array_map('intval', $_POST['log_ids']);

        if (!empty($log_ids)) {
            $placeholders = implode(',', array_fill(0, count($log_ids), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id IN ($placeholders)", $log_ids));
        }

        wp_send_json_success('Logs deleted successfully');
    }

    /**
     * Export logs
     */
    public function export_logs() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';

        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="nexjob-autopost-logs-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        if (!empty($logs)) {
            fputcsv($output, array_keys($logs[0]));
            foreach ($logs as $log) {
                fputcsv($output, $log);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Bulk resend posts
     */
    public function bulk_resend() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $post_ids = array_map('intval', $_POST['post_ids']);

        if (empty($post_ids)) {
            wp_send_json_error('No posts selected');
        }

        $autopost = new Nexjob_Autopost();
        $results = $autopost->bulk_resend_posts($post_ids);

        wp_send_json_success($results);
    }

    /**
     * Retry single post
     */
    public function retry_single() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);

        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        $autopost = new Nexjob_Autopost();
        $results = $autopost->bulk_resend_posts(array($post_id));

        if ($results['success'] > 0) {
            wp_send_json_success('Post resent successfully');
        } else {
            wp_send_json_error('Failed to resend post: ' . implode(', ', $results['errors']));
        }
    }

    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'nexjob_autopost_dashboard',
                'Nexjob Autopost Activity',
                array($this, 'dashboard_widget_content')
            );
        }
    }

    /**
     * Dashboard widget content
     */
    public function dashboard_widget_content() {
        $logger = new Nexjob_Logger();
        $stats = $logger->get_stats();
        $recent_errors = $logger->get_recent_errors(3);

        echo '<div class="nexjob-dashboard-widget">';

        // Stats
        echo '<div class="dashboard-stat">';
        echo '<span class="dashboard-stat-label">Total Requests:</span>';
        echo '<span class="dashboard-stat-value">' . $stats['total'] . '</span>';
        echo '</div>';

        echo '<div class="dashboard-stat">';
        echo '<span class="dashboard-stat-label">Success Rate:</span>';
        $success_rate = $stats['total'] > 0 ? round(($stats['success'] / $stats['total']) * 100, 1) : 0;
        echo '<span class="dashboard-stat-value">' . $success_rate . '%</span>';
        echo '</div>';

        echo '<div class="dashboard-stat">';
        echo '<span class="dashboard-stat-label">Today:</span>';
        echo '<span class="dashboard-stat-value">' . $stats['today'] . '</span>';
        echo '</div>';

        echo '<div class="dashboard-stat">';
        echo '<span class="dashboard-stat-label">This Week:</span>';
        echo '<span class="dashboard-stat-value">' . $stats['week'] . '</span>';
        echo '</div>';

        // Recent errors
        if (!empty($recent_errors)) {
            echo '<div style="margin-top: 15px; border-top: 1px solid #f0f0f1; padding-top: 10px;">';
            echo '<strong>Recent Errors:</strong>';
            foreach ($recent_errors as $error) {
                echo '<div style="margin: 5px 0; font-size: 12px; color: #d63638;">';
                echo esc_html(substr($error->post_title, 0, 30) . '...');
                echo ' <span style="color: #666;">(' . esc_html($error->created_at) . ')</span>';
                echo '</div>';
            }
        }

        echo '<div style="margin-top: 15px;">';
        echo '<a href="' . admin_url('admin.php?page=nexjob-autopost') . '" class="button button-small">View Full Logs</a>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Display configurations page
     */
    public function configs_page() {
        include NEXJOB_AUTOPOST_PLUGIN_DIR . 'admin/configs-page.php';
    }

    /**
     * Display settings page
     */
    public function settings_page() {
        include NEXJOB_AUTOPOST_PLUGIN_DIR . 'admin/settings-page.php';
    }





    /**
     * Save configuration AJAX handler
     */
    public function save_config() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $config_id = intval($_POST['config_id']);
        $data = array(
            'name' => sanitize_text_field($_POST['config_name']),
            'post_types' => isset($_POST['post_types']) ? $_POST['post_types'] : array(),
            'integration_id' => sanitize_text_field($_POST['integration_id']),
            'content_template' => wp_kses_post($_POST['content_template']),
            'status' => sanitize_text_field($_POST['config_status'])
        );

        // Validate required fields
        if (empty($name) || empty($post_types) || empty($integration_id) || empty($content_template)) {
            wp_die('All fields must be filled.');
        }

        if ($config_id > 0) {
            $result = $this->configs->update_config($config_id, $data);
        } else {
            $result = $this->configs->create_config($data);
        }

        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to save configuration');
        }
    }

    /**
     * Delete configuration AJAX handler
     */
    public function delete_config() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $config_id = intval($_POST['config_id']);
        $result = $this->configs->delete_config($config_id);

        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete configuration');
        }
    }

    /**
     * Get placeholders AJAX handler
     */
    public function get_placeholders() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $post_type = sanitize_text_field($_POST['post_type']);
        $placeholders = $this->configs->get_placeholder_variables($post_type);

        wp_send_json_success($placeholders);
    }

    /**
     * Get log details AJAX handler
     */
    public function get_log_details() {
        check_ajax_referer('nexjob_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $log_id = intval($_POST['log_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';

        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $log_id));

        if (!$log) {
            wp_send_json_error('Log not found');
        }

        // Try to format JSON data
        $request_data = $log->request_data;
        $response_data = $log->response_data;

        if ($this->is_json($request_data)) {
            $request_data = json_encode(json_decode($request_data), JSON_PRETTY_PRINT);
        }

        if ($this->is_json($response_data)) {
            $response_data = json_encode(json_decode($response_data), JSON_PRETTY_PRINT);
        }

        wp_send_json_success(array(
            'request_data' => $request_data,
            'response_data' => $response_data,
            'post_title' => $log->post_title,
            'status' => $log->status,
            'response_code' => $log->response_code,
            'created_at' => $log->created_at
        ));
    }

    /**
     * Check if string is valid JSON
     */
    private function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

   /**
     * Sanitize hashtags to separate words joined by hyphens and slashes
     *
     * @param string $tags_string The original string of tags.
     * @return string The sanitized string of tags.
     */
    public static function sanitize_hashtags($tags_string) {
        $tags = explode(' ', $tags_string);
        $sanitized_tags = [];

        foreach ($tags as $tag) {
            $split_by_slash = explode('/', $tag);
            $temp_tags = [];

            foreach ($split_by_slash as $slash_part) {
                $split_by_hyphen = explode('-', $slash_part);
                $temp_tags = array_merge($temp_tags, $split_by_hyphen);
            }

            foreach ($temp_tags as $single_tag) {
                $single_tag = trim($single_tag);
                if (!empty($single_tag)) {
                    $sanitized_tags[] = '#' . $single_tag;
                }
            }
        }

        return implode(' ', $sanitized_tags);
    }
}
?>