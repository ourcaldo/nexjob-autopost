<?php
/**
 * Settings page - general plugin settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('nexjob_autopost_settings', 'nexjob_autopost_nonce')) {
    update_option('nexjob_autopost_api_url', sanitize_text_field($_POST['api_url']));
    update_option('nexjob_autopost_auth_header', sanitize_text_field($_POST['auth_header']));
    update_option('nexjob_autopost_enabled', isset($_POST['enabled']) ? '1' : '0');
    update_option('nexjob_autopost_log_retention_days', intval($_POST['log_retention_days']));
    update_option('nexjob_autopost_max_retries', intval($_POST['max_retries']));
    update_option('nexjob_autopost_email_notifications', isset($_POST['email_notifications']) ? '1' : '0');
    update_option('nexjob_autopost_notification_email', sanitize_email($_POST['notification_email']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current values
$api_url = get_option('nexjob_autopost_api_url', 'https://autopost.nexpocket.com/api/public/v1/post');
$auth_header = get_option('nexjob_autopost_auth_header', '');
$enabled = get_option('nexjob_autopost_enabled', '1');
$log_retention_days = get_option('nexjob_autopost_log_retention_days', '30');
$max_retries = get_option('nexjob_autopost_max_retries', '3');
$email_notifications = get_option('nexjob_autopost_email_notifications', '0');
$notification_email = get_option('nexjob_autopost_notification_email', get_option('admin_email'));
?>

<div class="wrap nexjob-admin-wrap">
    <h1>Nexjob Autopost - Settings</h1>
    
    <div class="nexjob-admin-content">
        <!-- General Settings -->
        <div class="nexjob-card">
            <h2>🔧 General Settings</h2>
            <form method="post" action="">
                <?php wp_nonce_field('nexjob_autopost_settings', 'nexjob_autopost_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enabled">Enable Plugin</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="enabled" name="enabled" value="1" <?php checked($enabled, '1'); ?> />
                                Enable automatic posting for all configurations
                            </label>
                            <p class="description">When disabled, no automatic posts will be sent regardless of individual configuration settings.</p>
                        </td>
                    </tr>
                </table>
                
                <h3>API Configuration</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_url">API Endpoint URL</label>
                        </th>
                        <td>
                            <input type="url" id="api_url" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" required />
                            <p class="description">The API endpoint URL for posting data (e.g., https://autopost.nexpocket.com/api/public/v1/post)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="auth_header">Authorization Token</label>
                        </th>
                        <td>
                            <input type="text" id="auth_header" name="auth_header" value="<?php echo esc_attr($auth_header); ?>" class="regular-text" required />
                            <p class="description">The authorization token for API requests (without "Bearer " prefix)</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Logging & Retention</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="log_retention_days">Log Retention (Days)</label>
                        </th>
                        <td>
                            <input type="number" id="log_retention_days" name="log_retention_days" value="<?php echo esc_attr($log_retention_days); ?>" min="1" max="365" />
                            <p class="description">Number of days to keep logs before automatic cleanup (1-365 days)</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Retry Configuration</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_retries">Max Retry Attempts</label>
                        </th>
                        <td>
                            <input type="number" id="max_retries" name="max_retries" value="<?php echo esc_attr($max_retries); ?>" min="0" max="10" />
                            <p class="description">Number of retry attempts for failed API requests (0 = no retries, max 10)</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Email Notifications</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email_notifications">Email Notifications</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="email_notifications" name="email_notifications" value="1" <?php checked($email_notifications, '1'); ?> />
                                Send email notifications for failed requests after all retries are exhausted
                            </label>
                            <p class="description">Get notified when API requests fail permanently</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="notification_email">Notification Email</label>
                        </th>
                        <td>
                            <input type="email" id="notification_email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text" />
                            <p class="description">Email address to receive notifications (defaults to admin email)</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Settings" />
                    <button type="button" id="test-api-btn" class="button">Test API Connection</button>
                </p>
            </form>
            
            <div id="api-test-result" style="display: none;"></div>
        </div>
        
        <!-- System Information -->
        <div class="nexjob-card">
            <h2>ℹ️ System Information</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Plugin Version</th>
                    <td><?php echo NEXJOB_AUTOPOST_VERSION; ?></td>
                </tr>
                <tr>
                    <th scope="row">WordPress Version</th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th scope="row">PHP Version</th>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <th scope="row">Database Tables</th>
                    <td>
                        <?php 
                        global $wpdb;
                        $logs_table = $wpdb->prefix . 'nexjob_autopost_logs';
                        $configs_table = $wpdb->prefix . 'nexjob_autopost_configs';
                        $logs_count = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");
                        $configs_count = $wpdb->get_var("SELECT COUNT(*) FROM $configs_table");
                        ?>
                        <strong>Logs:</strong> <?php echo $logs_count; ?> entries<br>
                        <strong>Configurations:</strong> <?php echo $configs_count; ?> entries
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Quick Actions -->
        <div class="nexjob-card">
            <h2>🚀 Quick Actions</h2>
            <div class="quick-actions">
                <a href="<?php echo admin_url('admin.php?page=nexjob-autopost'); ?>" class="button">View Dashboard</a>
                <a href="<?php echo admin_url('admin.php?page=nexjob-configs'); ?>" class="button">Manage Configurations</a>
                <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=add'); ?>" class="button button-primary">Add New Configuration</a>
            </div>
        </div>
    </div>
</div>