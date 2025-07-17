<?php
/**
 * Main plugin page - shows statistics, logs, and configurations
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get logs and stats
$logger = new Nexjob_Logger();
$configs = new Nexjob_Configs();
$stats = $logger->get_stats();
$current_page = isset($_GET['log_page']) ? intval($_GET['log_page']) : 1;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$logs_data = $logger->get_logs($current_page, 10, $status_filter);
$recent_errors = $logger->get_recent_errors();
$all_configs = $configs->get_configs();
?>

<div class="wrap nexjob-admin-wrap">
    <h1>Nexjob Autopost - Dashboard</h1>
    
    <div class="nexjob-admin-content">
        
        <!-- Statistics Section -->
        <div class="nexjob-card">
            <h2>📊 Statistics Overview</h2>
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

        <!-- Autopost Configurations Section -->
        <div class="nexjob-card">
            <h2>⚙️ Autopost Configurations</h2>
            <div class="config-actions">
                <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=add'); ?>" class="button button-primary">Add New Configuration</a>
                <a href="<?php echo admin_url('admin.php?page=nexjob-configs'); ?>" class="button">Manage All Configurations</a>
            </div>
            
            <?php if (!empty($all_configs)): ?>
            <div class="config-summary">
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Configuration Name</th>
                            <th>Post Types</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($all_configs, 0, 5) as $config): ?>
                        <tr>
                            <td><strong><?php echo esc_html($config->name); ?></strong></td>
                            <td>
                                <?php 
                                $post_types = json_decode($config->post_types, true);
                                echo esc_html(implode(', ', $post_types));
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $config->status; ?>">
                                    <?php echo ucfirst($config->status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=edit&config_id=' . $config->id); ?>" class="button button-small">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (count($all_configs) > 5): ?>
                <p class="config-more">
                    <a href="<?php echo admin_url('admin.php?page=nexjob-configs'); ?>">View all <?php echo count($all_configs); ?> configurations →</a>
                </p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p class="no-configs">No autopost configurations found. <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=add'); ?>">Create your first configuration</a></p>
            <?php endif; ?>
        </div>

        <!-- Recent Errors Section -->
        <?php if (!empty($recent_errors)): ?>
        <div class="nexjob-card">
            <h2>⚠️ Recent Errors</h2>
            <div class="nexjob-errors">
                <?php foreach ($recent_errors as $error): ?>
                <div class="error-item">
                    <strong><?php echo esc_html($error->post_title); ?></strong>
                    <span class="error-time"><?php echo esc_html($error->created_at); ?></span>
                    <div class="error-message"><?php echo esc_html($error->response_data); ?></div>
                    <div class="error-actions">
                        <button type="button" class="button button-small retry-btn" data-post-id="<?php echo $error->post_id; ?>">Retry</button>
                        <button type="button" class="button button-small view-details-btn" 
                                data-log-id="<?php echo $error->id; ?>"
                                data-request="<?php echo esc_attr($error->request_data); ?>"
                                data-response="<?php echo esc_attr($error->response_data); ?>">
                            View Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Request Logs Section -->
        <div class="nexjob-card">
            <h2>📋 Request Logs</h2>
            
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
                $base_url = admin_url('admin.php?page=nexjob-autopost');
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
        <div class="nexjob-card">
            <h2>🔄 Bulk Actions</h2>
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