/**
 * Nexjob Autopost Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Test API Connection
    $('#test-api-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $result = $('#api-test-result');
        
        $btn.addClass('loading').text('Testing...');
        $result.hide();
        
        var data = {
            action: 'nexjob_test_api',
            nonce: nexjob_ajax.nonce,
            api_url: $('input[name="api_url"]').val(),
            auth_header: $('input[name="auth_header"]').val()
        };
        
        $.post(nexjob_ajax.ajax_url, data, function(response) {
            $btn.removeClass('loading').text('Test API Connection');
            
            if (response.status === 'success') {
                $result.removeClass('error').addClass('success')
                       .html('<strong>Success!</strong> API connection test successful. HTTP Code: ' + response.http_code)
                       .show();
            } else {
                $result.removeClass('success').addClass('error')
                       .html('<strong>Error!</strong> API connection failed. HTTP Code: ' + response.http_code + '<br>Response: ' + response.response)
                       .show();
            }
        }).fail(function(xhr) {
            $btn.removeClass('loading').text('Test API Connection');
            $result.removeClass('success').addClass('error')
                   .html('<strong>Error!</strong> Request failed: ' + xhr.responseText)
                   .show();
        });
    });
    
    // Filter logs
    $('#filter-logs-btn').on('click', function() {
        var status = $('#status-filter').val();
        var url = new URL(window.location);
        
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        
        url.searchParams.delete('log_page'); // Reset to first page
        window.location.href = url.toString();
    });
    
    // Select all logs
    $('#select-all-logs').on('change', function() {
        $('.log-checkbox').prop('checked', this.checked);
    });
    
    // Update select all when individual checkboxes change
    $('.log-checkbox').on('change', function() {
        var total = $('.log-checkbox').length;
        var checked = $('.log-checkbox:checked').length;
        $('#select-all-logs').prop('checked', total === checked);
    });
    
    // Delete selected logs
    $('#delete-selected-btn').on('click', function() {
        var selectedLogs = $('.log-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedLogs.length === 0) {
            alert('Please select logs to delete.');
            return;
        }
        
        if (!confirm('Are you sure you want to delete the selected logs? This action cannot be undone.')) {
            return;
        }
        
        var $btn = $(this);
        $btn.addClass('loading').text('Deleting...');
        
        var data = {
            action: 'nexjob_delete_logs',
            nonce: nexjob_ajax.nonce,
            log_ids: selectedLogs
        };
        
        $.post(nexjob_ajax.ajax_url, data, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error deleting logs: ' + response.data);
                $btn.removeClass('loading').text('Delete Selected');
            }
        }).fail(function() {
            alert('Error deleting logs. Please try again.');
            $btn.removeClass('loading').text('Delete Selected');
        });
    });
    
    // Export logs
    $('#export-logs-btn').on('click', function() {
        var $btn = $(this);
        $btn.addClass('loading').text('Exporting...');
        
        // Create form and submit
        var form = $('<form>', {
            'method': 'POST',
            'action': nexjob_ajax.ajax_url
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'nexjob_export_logs'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': nexjob_ajax.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        setTimeout(function() {
            $btn.removeClass('loading').text('Export CSV');
        }, 2000);
    });
    
    // View log details
    $(document).on('click', '.view-details-btn', function() {
        var $btn = $(this);
        var logId = $btn.data('log-id');
        
        if (!logId) {
            // Fallback to old method if no log ID
            var requestData = $btn.data('request');
            var responseData = $btn.data('response');
            
            // Format JSON
            try {
                if (requestData) {
                    requestData = JSON.stringify(JSON.parse(requestData), null, 2);
                }
            } catch (e) {
                // Keep original if not valid JSON
            }
            
            $('#log-request-data').text(requestData || 'No request data');
            $('#log-response-data').text(responseData || 'No response data');
            $('#log-details-modal').show();
            return;
        }
        
        // Use new AJAX method
        $btn.prop('disabled', true).text('Loading...');
        
        $.post(nexjob_ajax.ajax_url, {
            action: 'nexjob_get_log_details',
            nonce: nexjob_ajax.nonce,
            log_id: logId
        }, function(response) {
            if (response.success) {
                $('#log-request-data').text(response.data.request_data || 'No request data');
                $('#log-response-data').text(response.data.response_data || 'No response data');
                $('#log-details-modal').show();
            } else {
                alert('Error loading log details: ' + response.data);
            }
        }).fail(function() {
            alert('Error loading log details. Please try again.');
        }).always(function() {
            $btn.prop('disabled', false).text('View Details');
        });
    });
    
    // Close modal
    $('.nexjob-modal-close, .nexjob-modal').on('click', function(e) {
        if (e.target === this) {
            $('#log-details-modal').hide();
        }
    });
    
    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // Escape key
            $('#log-details-modal').hide();
        }
    });
    
    // Auto-refresh stats every 30 seconds (optional)
    setInterval(function() {
        if ($('.nexjob-stats').length > 0) {
            // Only refresh if we're on the admin page and no modal is open
            if (!$('#log-details-modal').is(':visible')) {
                // You could implement AJAX refresh here if needed
            }
        }
    }, 30000);
    
    // Status filter change handler
    $('#status-filter').on('change', function() {
        if ($(this).val() !== '') {
            $('#filter-logs-btn').click();
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        var apiUrl = $('input[name="api_url"]').val();
        var authHeader = $('input[name="auth_header"]').val();
        
        if (!apiUrl || !authHeader) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        // Validate URL format
        try {
            new URL(apiUrl);
        } catch (e) {
            e.preventDefault();
            alert('Please enter a valid API URL.');
            return false;
        }
    });
    
    // Retry single post
    $('.retry-btn').on('click', function() {
        var $btn = $(this);
        var postId = $btn.data('post-id');
        
        if (!confirm('Are you sure you want to retry sending this post to the API?')) {
            return;
        }
        
        $btn.addClass('loading').text('Retrying...');
        
        var data = {
            action: 'nexjob_retry_single',
            nonce: nexjob_ajax.nonce,
            post_id: postId
        };
        
        $.post(nexjob_ajax.ajax_url, data, function(response) {
            if (response.success) {
                alert('Post resent successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data);
                $btn.removeClass('loading').text('Retry');
            }
        }).fail(function() {
            alert('Error retrying post. Please try again.');
            $btn.removeClass('loading').text('Retry');
        });
    });
    
    // Bulk resend functionality
    $('#bulk-resend-btn').on('click', function() {
        var $btn = $(this);
        var selectedPosts = $('#bulk-post-select').val() || [];
        var manualIds = $('#bulk-post-ids').val().trim();
        
        var postIds = [];
        
        // Add selected posts
        if (selectedPosts.length > 0) {
            postIds = postIds.concat(selectedPosts);
        }
        
        // Add manual IDs
        if (manualIds) {
            var manualArray = manualIds.split(',').map(function(id) {
                return id.trim();
            }).filter(function(id) {
                return id && !isNaN(id);
            });
            postIds = postIds.concat(manualArray);
        }
        
        // Remove duplicates
        postIds = [...new Set(postIds)];
        
        if (postIds.length === 0) {
            alert('Please select posts or enter post IDs to resend.');
            return;
        }
        
        if (!confirm('Are you sure you want to resend ' + postIds.length + ' posts to the API?')) {
            return;
        }
        
        $btn.addClass('loading').text('Processing...');
        $('#bulk-results').hide();
        
        var data = {
            action: 'nexjob_bulk_resend',
            nonce: nexjob_ajax.nonce,
            post_ids: postIds
        };
        
        $.post(nexjob_ajax.ajax_url, data, function(response) {
            $btn.removeClass('loading').text('Bulk Resend Selected Posts');
            
            if (response.success) {
                var results = response.data;
                var html = '<div class="nexjob-notification success">';
                html += '<strong>Bulk Resend Complete!</strong><br>';
                html += 'Success: ' + results.success + ' posts<br>';
                html += 'Failed: ' + results.failed + ' posts';
                
                if (results.errors.length > 0) {
                    html += '<br><br><strong>Errors:</strong><ul>';
                    results.errors.forEach(function(error) {
                        html += '<li>' + error + '</li>';
                    });
                    html += '</ul>';
                }
                
                html += '</div>';
                $('#bulk-results').html(html).show();
                
                // Clear selections
                $('#bulk-post-select').val([]);
                $('#bulk-post-ids').val('');
                
            } else {
                $('#bulk-results').html('<div class="nexjob-notification error"><strong>Error:</strong> ' + response.data + '</div>').show();
            }
        }).fail(function() {
            $btn.removeClass('loading').text('Bulk Resend Selected Posts');
            $('#bulk-results').html('<div class="nexjob-notification error"><strong>Error:</strong> Request failed. Please try again.</div>').show();
        });
    });
    
    // Clear selection
    $('#clear-selection-btn').on('click', function() {
        $('#bulk-post-select').val([]);
        $('#bulk-post-ids').val('');
        $('#bulk-results').hide();
    });
    
    // Enable/disable email notifications field based on checkbox
    $('input[name="email_notifications"]').on('change', function() {
        var $emailField = $('input[name="notification_email"]');
        if (this.checked) {
            $emailField.prop('disabled', false).closest('tr').show();
        } else {
            $emailField.prop('disabled', true).closest('tr').hide();
        }
    }).trigger('change');
    
});
