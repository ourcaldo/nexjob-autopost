<?php
/**
 * Logs Page - Nexjob Autopost Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get logs data
$logger = new Nexjob_Logger();
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$current_page = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
$logs_per_page = 20;

// Get logs with pagination
$logs_data = $logger->get_logs($current_page, $logs_per_page, $status_filter);

// Get settings for retry functionality
$max_retries = get_option('nexjob_autopost_max_retries', 3);
?>

<div class="wrap">
    <h1>📋 Nexjob Autopost - Logs</h1>
    <p>View and manage all API request logs.</p>
    
    <div class="nexjob-admin-container">
        <!-- Logs Section -->
        <div class="nexjob-card">
            <div class="nexjob-logs-header">
                <h2>📋 Request Logs</h2>
                <div class="nexjob-logs-controls">
                    <div class="filter-controls">
                        <select id="status-filter" onchange="filterLogs()">
                            <option value="">All Status</option>
                            <option value="success" <?php selected($status_filter, 'success'); ?>>Success</option>
                            <option value="failed" <?php selected($status_filter, 'failed'); ?>>Failed</option>
                            <option value="error" <?php selected($status_filter, 'error'); ?>>Error</option>
                        </select>
                        <button type="button" class="button" onclick="refreshLogs()">🔄 Refresh</button>
                    </div>
                    <div class="action-controls">
                        <button type="button" id="delete-selected-logs" class="button">🗑️ Delete Selected</button>
                        <button type="button" id="export-logs-csv" class="button">📤 Export CSV</button>
                    </div>
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
                $base_url = admin_url('admin.php?page=nexjob-logs');
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

<script>
function filterLogs() {
    const status = document.getElementById('status-filter').value;
    const baseUrl = '<?php echo admin_url('admin.php?page=nexjob-logs'); ?>';
    let url = baseUrl;
    if (status) {
        url += '&status=' + encodeURIComponent(status);
    }
    window.location.href = url;
}

function refreshLogs() {
    window.location.reload();
}
</script>