<?php
/**
 * Bulk Actions Page - Nexjob Autopost Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get recent posts for bulk selection
$recent_posts = get_posts(array(
    'post_type' => 'lowongan-kerja',
    'post_status' => 'publish',
    'numberposts' => 100,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get recent failed posts
$logger = new Nexjob_Logger();
$failed_logs = $logger->get_logs(1, 50, 'error');
?>

<div class="wrap">
    <h1>🔄 Nexjob Autopost - Bulk Actions</h1>
    <p>Perform bulk operations on posts and manage failed requests.</p>
    
    <div class="nexjob-admin-container">
        <!-- Bulk Resend Section -->
        <div class="nexjob-card">
            <h2>🔄 Bulk Resend Posts</h2>
            <p>Resend multiple posts to the API at once. Select posts from the dropdown below or enter post IDs manually.</p>
            
            <div class="bulk-post-selector">
                <label for="bulk-post-select"><strong>Select Posts:</strong></label>
                <select id="bulk-post-select" multiple style="height: 150px; width: 100%; max-width: 600px;">
                    <?php foreach ($recent_posts as $post): ?>
                        <option value="<?php echo $post->ID; ?>"><?php echo $post->ID; ?> - <?php echo esc_html($post->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Hold Ctrl (Cmd on Mac) to select multiple posts</p>
            </div>
            
            <div class="bulk-manual-input">
                <label for="bulk-post-ids"><strong>Or enter Post IDs manually:</strong></label>
                <input type="text" id="bulk-post-ids" placeholder="e.g. 123,456,789" class="regular-text" style="width: 100%; max-width: 600px;" />
                <p class="description">Comma-separated list of post IDs</p>
            </div>
            
            <div class="bulk-actions-buttons">
                <button type="button" id="bulk-resend-btn" class="button button-primary">Bulk Resend Selected Posts</button>
                <button type="button" id="clear-selection-btn" class="button">Clear Selection</button>
            </div>
            
            <div id="bulk-results" style="display: none; margin-top: 15px;"></div>
        </div>
        
        <!-- Failed Requests Section -->
        <?php if (!empty($failed_logs['logs'])): ?>
        <div class="nexjob-card">
            <h2>❌ Recent Failed Requests</h2>
            <p>Quickly retry failed requests or view their details.</p>
            
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
                        <?php foreach ($failed_logs['logs'] as $log): ?>
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
                                <button type="button" class="button button-small retry-btn" 
                                        data-post-id="<?php echo $log->post_id; ?>">
                                    Retry
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="error-actions">
                <button type="button" id="retry-all-failed" class="button button-primary">Retry All Failed Requests</button>
                <a href="<?php echo admin_url('admin.php?page=nexjob-logs&status=failed'); ?>" class="button">View All Failed Logs</a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions Section -->
        <div class="nexjob-card">
            <h2>⚡ Quick Actions</h2>
            <p>Perform common maintenance and testing tasks.</p>
            
            <div class="quick-actions">
                <button type="button" id="clear-old-logs" class="button">🗑️ Clear Old Logs</button>
                <button type="button" id="test-api-connection" class="button">🔗 Test API Connection</button>
                <button type="button" id="resend-latest-posts" class="button">📤 Resend Latest 10 Posts</button>
            </div>
            
            <div id="quick-action-results" style="display: none; margin-top: 15px;"></div>
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
jQuery(document).ready(function($) {
    // Retry all failed requests
    $('#retry-all-failed').on('click', function() {
        if (!confirm('Are you sure you want to retry all failed requests?')) {
            return;
        }
        
        var $btn = $(this);
        $btn.addClass('loading').text('Processing...');
        
        // Get all failed post IDs
        var failedPostIds = [];
        $('.retry-btn').each(function() {
            failedPostIds.push($(this).data('post-id'));
        });
        
        var data = {
            action: 'nexjob_bulk_resend',
            nonce: nexjob_ajax.nonce,
            post_ids: failedPostIds
        };
        
        $.post(nexjob_ajax.ajax_url, data, function(response) {
            $btn.removeClass('loading').text('Retry All Failed Requests');
            
            if (response.success) {
                var results = response.data;
                var html = '<div class="nexjob-notification success">';
                html += '<strong>Bulk Retry Complete!</strong><br>';
                html += 'Success: ' + results.success + ' posts<br>';
                html += 'Failed: ' + results.failed + ' posts';
                html += '</div>';
                
                $('#quick-action-results').html(html).show();
                
                // Refresh page after 2 seconds
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                $('#quick-action-results').html('<div class="nexjob-notification error"><strong>Error:</strong> ' + response.data + '</div>').show();
            }
        });
    });
    
    // Clear old logs
    $('#clear-old-logs').on('click', function() {
        if (!confirm('Are you sure you want to clear old logs? This action cannot be undone.')) {
            return;
        }
        
        var $btn = $(this);
        $btn.addClass('loading').text('Clearing...');
        
        $.post(nexjob_ajax.ajax_url, {
            action: 'nexjob_delete_logs',
            nonce: nexjob_ajax.nonce,
            delete_type: 'old'
        }, function(response) {
            $btn.removeClass('loading').text('Clear Old Logs');
            
            if (response.success) {
                $('#quick-action-results').html('<div class="nexjob-notification success"><strong>Success:</strong> Old logs cleared successfully.</div>').show();
            } else {
                $('#quick-action-results').html('<div class="nexjob-notification error"><strong>Error:</strong> ' + response.data + '</div>').show();
            }
        });
    });
    
    // Test API connection
    $('#test-api-connection').on('click', function() {
        var $btn = $(this);
        $btn.addClass('loading').text('Testing...');
        
        $.post(nexjob_ajax.ajax_url, {
            action: 'nexjob_test_api',
            nonce: nexjob_ajax.nonce
        }, function(response) {
            $btn.removeClass('loading').text('Test API Connection');
            
            if (response.success) {
                $('#quick-action-results').html('<div class="nexjob-notification success"><strong>Success:</strong> API connection test successful.</div>').show();
            } else {
                $('#quick-action-results').html('<div class="nexjob-notification error"><strong>Error:</strong> ' + response.data + '</div>').show();
            }
        });
    });
    
    // Resend latest posts
    $('#resend-latest-posts').on('click', function() {
        if (!confirm('Are you sure you want to resend the latest 10 posts?')) {
            return;
        }
        
        var $btn = $(this);
        $btn.addClass('loading').text('Processing...');
        
        // Get latest 10 post IDs
        var latestPostIds = [];
        $('#bulk-post-select option').slice(0, 10).each(function() {
            latestPostIds.push($(this).val());
        });
        
        var data = {
            action: 'nexjob_bulk_resend',
            nonce: nexjob_ajax.nonce,
            post_ids: latestPostIds
        };
        
        $.post(nexjob_ajax.ajax_url, data, function(response) {
            $btn.removeClass('loading').text('Resend Latest 10 Posts');
            
            if (response.success) {
                var results = response.data;
                var html = '<div class="nexjob-notification success">';
                html += '<strong>Latest Posts Resent!</strong><br>';
                html += 'Success: ' + results.success + ' posts<br>';
                html += 'Failed: ' + results.failed + ' posts';
                html += '</div>';
                
                $('#quick-action-results').html(html).show();
            } else {
                $('#quick-action-results').html('<div class="nexjob-notification error"><strong>Error:</strong> ' + response.data + '</div>').show();
            }
        });
    });
});
</script>