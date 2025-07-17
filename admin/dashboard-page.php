<?php
/**
 * Dashboard Page - Nexjob Autopost Plugin
 * Shows only statistics and latest 50 logs
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get stats and latest logs only
$logger = new Nexjob_Logger();
$stats = $logger->get_stats();
$logs_data = $logger->get_logs(1, 50, '');
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
        
        <!-- Latest 50 Logs Section -->
        <div class="nexjob-card">
            <h2>📋 Latest 50 Request Logs</h2>
            <p>Most recent API requests. <a href="<?php echo admin_url('admin.php?page=nexjob-logs'); ?>">View all logs with filters</a></p>
            
            <?php if (!empty($logs_data['logs'])): ?>
            <div class="nexjob-logs-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
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
            
            <div style="margin-top: 15px; text-align: center;">
                <a href="<?php echo admin_url('admin.php?page=nexjob-logs'); ?>" class="button">View All Logs</a>
                <a href="<?php echo admin_url('admin.php?page=nexjob-bulk'); ?>" class="button">Bulk Actions</a>
                <a href="<?php echo admin_url('admin.php?page=nexjob-configs'); ?>" class="button">Manage Configurations</a>
            </div>
            
            <?php else: ?>
            <p>No logs found.</p>
            <?php endif; ?>
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