<?php
/**
 * Configurations page - manage autopost configurations
 */

if (!defined('ABSPATH')) {
    exit;
}

$configs = new Nexjob_Configs();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$config_id = isset($_GET['config_id']) ? intval($_GET['config_id']) : 0;

switch ($action) {
    case 'add':
        show_config_form();
        break;
    case 'edit':
        show_config_form($config_id);
        break;
    default:
        show_configs_list();
        break;
}

/**
 * Show configurations list
 */
function show_configs_list() {
    $configs = new Nexjob_Configs();
    $all_configs = $configs->get_configs();
    ?>
    <div class="wrap nexjob-admin-wrap">
        <h1>Autopost Configurations 
            <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=add'); ?>" class="page-title-action">Add New</a>
        </h1>
        
        <div class="nexjob-admin-content">
            <div class="nexjob-card">
                <h2>📋 All Configurations</h2>
                <p>Manage your autopost configurations. Each configuration can target different post types and use different integration IDs.</p>
                
                <?php if (!empty($all_configs)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Name</th>
                            <th style="width: 20%;">Post Types</th>
                            <th style="width: 20%;">Integration ID</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 10%;">Created</th>
                            <th style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_configs as $config): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($config->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=edit&config_id=' . $config->id); ?>">Edit</a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $post_types = json_decode($config->post_types, true);
                                if (is_array($post_types)) {
                                    foreach ($post_types as $post_type) {
                                        echo '<span class="post-type-badge">' . esc_html($post_type) . '</span> ';
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <code><?php echo esc_html($config->integration_id); ?></code>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($config->status); ?>">
                                    <?php echo ucfirst($config->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($config->created_at)); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=edit&config_id=' . $config->id); ?>" class="button button-small">Edit</a>
                                <button class="button button-small button-link-delete delete-config" data-id="<?php echo $config->id; ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-configs">
                    <p>No autopost configurations found.</p>
                    <p><a href="<?php echo admin_url('admin.php?page=nexjob-configs&action=add'); ?>" class="button button-primary">Create your first configuration</a></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Help Section -->
            <div class="nexjob-card">
                <h2>💡 Configuration Tips</h2>
                <ul>
                    <li><strong>Integration ID:</strong> Each configuration should have its own unique integration ID from the NexPocket service.</li>
                    <li><strong>Post Types:</strong> You can target multiple post types with a single configuration.</li>
                    <li><strong>Content Templates:</strong> Use placeholders like <code>{{post_title}}</code>, <code>{{post_url}}</code>, and <code>{{hashtags:taxonomy}}</code>.</li>
                    <li><strong>Status:</strong> Only "active" configurations will automatically post content.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.delete-config').on('click', function() {
            if (confirm('Are you sure you want to delete this configuration? This action cannot be undone.')) {
                var configId = $(this).data('id');
                $.post(ajaxurl, {
                    action: 'nexjob_delete_config',
                    config_id: configId,
                    nonce: nexjob_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting configuration: ' + response.data);
                    }
                });
            }
        });
    });
    </script>
    <?php
}

/**
 * Show configuration form
 */
function show_config_form($config_id = 0) {
    $configs = new Nexjob_Configs();
    
    $config = null;
    $is_edit = false;
    
    if ($config_id > 0) {
        $config = $configs->get_config($config_id);
        $is_edit = true;
    }
    
    // Get all post types
    $post_types = get_post_types(array('public' => true), 'objects');
    $custom_post_types = get_post_types(array('public' => false, '_builtin' => false), 'objects');
    $all_post_types = array_merge($post_types, $custom_post_types);
    
    // Get current values
    $name = $config ? $config->name : '';
    $selected_post_types = $config ? json_decode($config->post_types, true) : array();
    $integration_id = $config ? $config->integration_id : '';
    $content_template = $config ? $config->content_template : 'Check out this new post: {{post_title}}\n\n{{post_url}}\n\n{{hashtags:category}}';
    $status = $config ? $config->status : 'active';
    
    ?>
    <div class="wrap nexjob-admin-wrap">
        <h1>
            <?php echo $is_edit ? 'Edit Configuration' : 'Add New Configuration'; ?>
            <a href="<?php echo admin_url('admin.php?page=nexjob-configs'); ?>" class="page-title-action">Back to Configurations</a>
        </h1>
        
        <div class="nexjob-admin-content">
            <div class="nexjob-card">
                <h2><?php echo $is_edit ? '✏️ Edit Configuration' : '➕ Create New Configuration'; ?></h2>
                
                <form id="config-form" method="post" action="">
                    <input type="hidden" name="action" value="nexjob_save_config" />
                    <input type="hidden" name="config_id" value="<?php echo $config_id; ?>" />
                    <?php wp_nonce_field('nexjob_save_config', 'nexjob_config_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="config_name">Configuration Name</label>
                            </th>
                            <td>
                                <input type="text" id="config_name" name="config_name" value="<?php echo esc_attr($name); ?>" class="regular-text" required />
                                <p class="description">A descriptive name for this configuration</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label>Post Types</label>
                            </th>
                            <td>
                                <div class="post-types-selection">
                                    <?php foreach ($all_post_types as $post_type => $post_type_obj): ?>
                                    <label>
                                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type); ?>" 
                                               <?php checked(in_array($post_type, $selected_post_types)); ?> />
                                        <?php echo esc_html($post_type_obj->labels->name); ?> 
                                        <small>(<?php echo esc_html($post_type); ?>)</small>
                                    </label><br>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">Select which post types should trigger this autopost configuration</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="integration_id">Integration ID</label>
                            </th>
                            <td>
                                <input type="text" id="integration_id" name="integration_id" value="<?php echo esc_attr($integration_id); ?>" class="regular-text" required />
                                <p class="description">The unique integration ID from NexPocket for this configuration</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="content_template">Content Template</label>
                            </th>
                            <td>
                                <textarea id="content_template" name="content_template" rows="8" cols="80" class="large-text"><?php echo esc_textarea($content_template); ?></textarea>
                                <p class="description">Use placeholders to create dynamic content</p>
                                
                                <div class="placeholder-helper">
                                    <strong>Available Placeholders:</strong>
                                    <div class="placeholder-buttons">
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{post_title}}">Post Title</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{post_url}}">Post URL</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{post_excerpt}}">Post Excerpt</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{author_name}}">Author Name</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{hashtags:category}}">Category Hashtags</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{hashtags:tag}}">Tag Hashtags</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{terms:category}}">Category Terms</button>
                                        <button type="button" class="button button-small insert-placeholder" data-placeholder="{{custom_field_name}}">Custom Field</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="config_status">Status</label>
                            </th>
                            <td>
                                <select id="config_status" name="config_status">
                                    <option value="active" <?php selected($status, 'active'); ?>>Active</option>
                                    <option value="inactive" <?php selected($status, 'inactive'); ?>>Inactive</option>
                                </select>
                                <p class="description">Only active configurations will automatically post content</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="<?php echo $is_edit ? 'Update Configuration' : 'Create Configuration'; ?>" />
                        <a href="<?php echo admin_url('admin.php?page=nexjob-configs'); ?>" class="button">Cancel</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Insert placeholder buttons
        $('.insert-placeholder').on('click', function() {
            var placeholder = $(this).data('placeholder');
            var textarea = $('#content_template');
            var cursorPos = textarea.prop('selectionStart');
            var textAreaValue = textarea.val();
            var textBefore = textAreaValue.substring(0, cursorPos);
            var textAfter = textAreaValue.substring(cursorPos);
            
            textarea.val(textBefore + placeholder + textAfter);
            textarea.focus();
            textarea.prop('selectionStart', cursorPos + placeholder.length);
            textarea.prop('selectionEnd', cursorPos + placeholder.length);
        });
        
        // Handle form submission
        $('#config-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            var originalText = $submitBtn.val();
            
            $submitBtn.val('Saving...').prop('disabled', true);
            
            var formData = {
                action: 'nexjob_save_config',
                nonce: nexjob_ajax.nonce,
                config_id: $form.find('input[name="config_id"]').val(),
                config_name: $form.find('input[name="config_name"]').val(),
                post_types: $form.find('input[name="post_types[]"]:checked').map(function() { return this.value; }).get(),
                integration_id: $form.find('input[name="integration_id"]').val(),
                content_template: $form.find('textarea[name="content_template"]').val(),
                config_status: $form.find('select[name="config_status"]').val()
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        window.location.href = '<?php echo admin_url('admin.php?page=nexjob-configs'); ?>';
                    } else {
                        alert('Error saving configuration: ' + response.data);
                        $submitBtn.val(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Error saving configuration. Please try again.');
                    $submitBtn.val(originalText).prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}
?>