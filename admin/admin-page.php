<?php
/**
 * Admin page template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('nexjob_autopost_settings', 'nexjob_autopost_nonce')) {
    update_option('nexjob_autopost_api_url', sanitize_text_field($_POST['api_url']));
    update_option('nexjob_autopost_auth_header', sanitize_text_field($_POST['auth_header']));
    update_option('nexjob_autopost_integration_id', sanitize_text_field($_POST['integration_id']));
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
$integration_id = get_option('nexjob_autopost_integration_id', 'cmd6ykh840001n5bdw68gcwnh');
$enabled = get_option('nexjob_autopost_enabled', '1');
$log_retention_days = get_option('nexjob_autopost_log_retention_days', '30');
$max_retries = get_option('nexjob_autopost_max_retries', '3');
$email_notifications = get_option('nexjob_autopost_email_notifications', '0');
$notification_email = get_option('nexjob_autopost_notification_email', get_option('admin_email'));

// Get logs and stats
$logger = new Nexjob_Logger();
$stats = $logger->get_stats();
$current_page = isset($_GET['log_page']) ? intval($_GET['log_page']) : 1;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$logs_data = $logger->get_logs($current_page, 10, $status_filter);
$recent_errors = $logger->get_recent_errors();
?>

<div class="wrap nexjob-admin-wrap">
    <h1>Nexjob Autopost Settings</h1>
    
    <div class="nexjob-admin-content">
        <!-- Settings Section -->
        <div class="nexjob-card">
            <h2>API Configuration</h2>
            <form method="post" action="">
                <?php wp_nonce_field('nexjob_autopost_settings', 'nexjob_autopost_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Plugin</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enabled" value="1" <?php checked($enabled, '1'); ?> />
                                Enable automatic posting
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">API URL</th>
                        <td>
                            <input type="url" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" required />
                            <p class="description">The API endpoint URL for posting data</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Authorization Header</th>
                        <td>
                            <input type="text" name="auth_header" value="<?php echo esc_attr($auth_header); ?>" class="regular-text" required />
                            <p class="description">The authorization token for API requests</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Integration ID</th>
                        <td>
                            <input type="text" name="integration_id" value="<?php echo esc_attr($integration_id); ?>" class="regular-text" required />
                            <p class="description">The integration ID for the API</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Log Retention (Days)</th>
                        <td>
                            <input type="number" name="log_retention_days" value="<?php echo esc_attr($log_retention_days); ?>" min="1" max="365" />
                            <p class="description">Number of days to keep logs before automatic cleanup</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Max Retry Attempts</th>
                        <td>
                            <input type="number" name="max_retries" value="<?php echo esc_attr($max_retries); ?>" min="0" max="10" />
                            <p class="description">Number of retry attempts for failed API requests (0 = no retries)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_notifications" value="1" <?php checked($email_notifications, '1'); ?> />
                                Send email notifications for failed requests
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Notification Email</th>
                        <td>
                            <input type="email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text" />
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
        
        <!-- Statistics Section -->
        <div class="nexjob-card">
            <h2>Statistics</h2>
            <div class="nexjob-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['total']; ?></span>
                    <span class="stat-label">Total Requests</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['success']; ?></span>
                    <span class="stat-label">Successful</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['error']; ?></span>
                    <span class="stat-label">Errors</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['today']; ?></span>
                    <span class="stat-label">Today</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['week']; ?></span>
                    <span class="stat-label">This Week</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['month']; ?></span>
                    <span class="stat-label">This Month</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Errors Section -->
        <?php if (!empty($recent_errors)): ?>
        <div class="nexjob-card">
            <h2>Recent Errors</h2>
            <div class="nexjob-errors">
                <?php foreach ($recent_errors as $error): ?>
                <div class="error-item">
                    <strong><?php echo esc_html($error->post_title); ?></strong>
                    <span class="error-time"><?php echo esc_html($error->created_at); ?></span>
                    <div class="error-message"><?php echo esc_html($error->response_data); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Logs Section -->
        <div class="nexjob-card">
            <h2>Request Logs</h2>
            
            <div class="nexjob-logs-controls">
                <div class="filter-controls">
                    <select id="status-filter">
                        <option value="">All Status</option>
                        <option value="success" <?php selected($status_filter, 'success'); ?>>Success</option>
                        <option value="error" <?php selected($status_filter, 'error'); ?>>Error</option>
                    </select>
                    <button type="button" id="filter-logs-btn" class="button">Filter</button>
                </div>
                
                <div class="action-controls">
                    <button type="button" id="export-logs-btn" class="button">Export CSV</button>
                    <button type="button" id="delete-selected-btn" class="button button-secondary">Delete Selected</button>
                </div>
            </div>
            
            <?php if (!empty($logs_data['logs'])): ?>
            <div class="nexjob-logs-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" id="select-all-logs" />
                            </td>
                            <th>Post</th>
                            <th>Status</th>
                            <th>Response Code</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs_data['logs'] as $log): ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" class="log-checkbox" value="<?php echo $log->id; ?>" />
                            </th>
                            <td>
                                <strong><?php echo esc_html($log->post_title); ?></strong>
                                <div class="post-id">ID: <?php echo $log->post_id; ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $log->status; ?>">
                                    <?php echo ucfirst($log->status); ?>
                                </span>
                            </td>
                            <td><?php echo $log->response_code; ?></td>
                            <td><?php echo esc_html($log->created_at); ?></td>
                            <td>
                                <button type="button" class="button button-small view-details-btn" 
                                        data-log-id="<?php echo $log->id; ?>"
                                        data-request="<?php echo esc_attr($log->request_data); ?>"
                                        data-response="<?php echo esc_attr($log->response_data); ?>">
                                    View Details
                                </button>
                                <?php if ($log->status === 'error'): ?>
                                <button type="button" class="button button-small retry-btn" 
                                        data-post-id="<?php echo $log->post_id; ?>">
                                    Retry
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($logs_data['total_pages'] > 1): ?>
            <div class="nexjob-pagination">
                <?php
                $base_url = admin_url('options-general.php?page=nexjob-autopost');
                if ($status_filter) {
                    $base_url .= '&status=' . urlencode($status_filter);
                }
                
                for ($i = 1; $i <= $logs_data['total_pages']; $i++):
                    $class = ($i == $current_page) ? 'current' : '';
                    $url = $base_url . '&log_page=' . $i;
                ?>
                <a href="<?php echo esc_url($url); ?>" class="page-number <?php echo $class; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <p>No logs found.</p>
            <?php endif; ?>
        </div>
        
        <!-- Bulk Actions Section -->
        <div class="bulk-actions-section">
            <h3>Bulk Actions</h3>
            <p>Resend multiple posts to the API at once. Select posts from the dropdown below or enter post IDs manually.</p>
            
            <div class="bulk-post-selector">
                <label for="bulk-post-select"><strong>Select Posts:</strong></label>
                <select id="bulk-post-select" multiple style="height: 100px;">
                    <?php
                    $recent_posts = get_posts(array(
                        'post_type' => 'lowongan-kerja',
                        'post_status' => 'publish',
                        'numberposts' => 50,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ));
                    
                    foreach ($recent_posts as $post) {
                        echo '<option value="' . $post->ID . '">' . $post->ID . ' - ' . esc_html($post->post_title) . '</option>';
                    }
                    ?>
                </select>
                <p class="description">Hold Ctrl (Cmd on Mac) to select multiple posts</p>
            </div>
            
            <div class="bulk-manual-input">
                <label for="bulk-post-ids"><strong>Or enter Post IDs manually:</strong></label>
                <input type="text" id="bulk-post-ids" placeholder="e.g. 123,456,789" class="regular-text" />
                <p class="description">Comma-separated list of post IDs</p>
            </div>
            
            <div class="bulk-actions-buttons">
                <button type="button" id="bulk-resend-btn" class="button button-primary">Bulk Resend Selected Posts</button>
                <button type="button" id="clear-selection-btn" class="button">Clear Selection</button>
            </div>
            
            <div id="bulk-results" style="display: none; margin-top: 15px;"></div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div id="log-details-modal" class="nexjob-modal" style="display: none;">
    <div class="nexjob-modal-content">
        <div class="nexjob-modal-header">
            <h3>Log Details</h3>
            <span class="nexjob-modal-close">&times;</span>
        </div>
        <div class="nexjob-modal-body">
            <div class="log-section">
                <h4>Request Data</h4>
                <pre id="log-request-data"></pre>
            </div>
            <div class="log-section">
                <h4>Response Data</h4>
                <pre id="log-response-data"></pre>
            </div>
        </div>
    </div>
</div>
