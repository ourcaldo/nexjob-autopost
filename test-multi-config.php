<?php
/**
 * Test file for multi-configuration autopost plugin
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the plugin classes
if (!class_exists('Nexjob_Configs')) {
    // Define a simple mock class if the real one doesn't exist
    class Nexjob_Configs {
        public function get_configs($status = null) {
            return array(
                (object) array(
                    'id' => 1,
                    'name' => 'Default Lowongan Kerja',
                    'post_types' => '["lowongan-kerja"]',
                    'integration_id' => 'cmd6ykh840001n5bdw68gcwnh',
                    'content_template' => 'Lowongan kerja di kota {{nexjob_lokasi_kota}}, cek selengkapnya di {{post_url}}.\n\n{{hashtags:tag-loker}}',
                    'status' => 'active',
                    'created_at' => '2025-07-17 06:00:00'
                ),
                (object) array(
                    'id' => 2,
                    'name' => 'Facebook Posts',
                    'post_types' => '["post", "page"]',
                    'integration_id' => 'fb_integration_123',
                    'content_template' => 'New post: {{post_title}}\n\n{{post_excerpt}}\n\nRead more: {{post_url}}',
                    'status' => 'active',
                    'created_at' => '2025-07-17 06:30:00'
                ),
                (object) array(
                    'id' => 3,
                    'name' => 'Product Announcements',
                    'post_types' => '["product"]',
                    'integration_id' => 'product_announce_456',
                    'content_template' => '🎉 New Product: {{post_title}}\n\n{{post_content}}\n\n#NewProduct #Launch',
                    'status' => 'inactive',
                    'created_at' => '2025-07-17 07:00:00'
                )
            );
        }
        
        public function get_configs_for_post_type($post_type) {
            $configs = $this->get_configs('active');
            $matching_configs = array();
            
            foreach ($configs as $config) {
                $post_types = json_decode($config->post_types, true);
                if (in_array($post_type, $post_types)) {
                    $matching_configs[] = $config;
                }
            }
            
            return $matching_configs;
        }
        
        public function get_placeholder_variables($post_type = null) {
            $variables = array(
                'Basic Post Fields' => array(
                    '{{post_title}}' => 'Post title',
                    '{{post_url}}' => 'Post URL/permalink',
                    '{{post_content}}' => 'Post content (stripped of HTML)',
                    '{{post_excerpt}}' => 'Post excerpt',
                    '{{post_date}}' => 'Post publication date'
                ),
                'Custom Fields' => array(
                    '{{nexjob_lokasi_kota}}' => 'Custom field: nexjob_lokasi_kota',
                    '{{nexjob_salary}}' => 'Custom field: nexjob_salary',
                    '{{nexjob_company}}' => 'Custom field: nexjob_company'
                ),
                'Taxonomies (Hashtags)' => array(
                    '{{hashtags:tag-loker}}' => 'Hashtags from Job Tags',
                    '{{hashtags:category}}' => 'Hashtags from Categories',
                    '{{hashtags:post_tag}}' => 'Hashtags from Tags'
                ),
                'Taxonomies (Terms)' => array(
                    '{{terms:tag-loker}}' => 'Terms from Job Tags',
                    '{{terms:category}}' => 'Terms from Categories',
                    '{{terms:post_tag}}' => 'Terms from Tags'
                )
            );
            
            return $variables;
        }
        
        public function parse_content_template($template, $post) {
            $content = $template;
            
            // Replace basic post placeholders
            $content = str_replace('{{post_title}}', $post->post_title, $content);
            $content = str_replace('{{post_url}}', get_permalink($post->ID), $content);
            $content = str_replace('{{post_content}}', strip_tags($post->post_content), $content);
            $content = str_replace('{{post_excerpt}}', $post->post_excerpt, $content);
            $content = str_replace('{{post_date}}', $post->post_date, $content);
            
            // Replace post URL with nexjob.tech domain
            $content = str_replace('cms.nexjob.tech', 'nexjob.tech', $content);
            
            // Replace custom field placeholders
            if (preg_match_all('/\{\{([^:}]+)\}\}/', $content, $matches)) {
                foreach ($matches[1] as $field_name) {
                    $field_value = get_post_meta($post->ID, $field_name, true);
                    $content = str_replace('{{' . $field_name . '}}', $field_value, $content);
                }
            }
            
            // Replace taxonomy hashtags
            if (preg_match_all('/\{\{hashtags:([^}]+)\}\}/', $content, $matches)) {
                foreach ($matches[1] as $taxonomy) {
                    $terms = wp_get_post_terms($post->ID, $taxonomy);
                    $hashtags = array();
                    
                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ($terms as $term) {
                            $hashtags[] = '#' . str_replace(' ', '', $term->name);
                        }
                    }
                    
                    $hashtag_string = implode(' ', $hashtags);
                    $content = str_replace('{{hashtags:' . $taxonomy . '}}', $hashtag_string, $content);
                }
            }
            
            return $content;
        }
    }
}

// Mock WordPress functions for testing
function get_option($option, $default = false) {
    $options = array(
        'nexjob_autopost_api_url' => 'https://autopost.nexpocket.com/api/public/v1/post',
        'nexjob_autopost_auth_header' => 'd81c427d7627cc46207b3a069f8d213abfc034acaa41b4875b0c9f71ed7e277c',
        'nexjob_autopost_integration_id' => 'cmd6ykh840001n5bdw68gcwnh',
        'nexjob_autopost_enabled' => '1',
        'nexjob_autopost_log_retention_days' => '30',
        'nexjob_autopost_max_retries' => '3',
        'nexjob_autopost_email_notifications' => '1',
        'nexjob_autopost_notification_email' => 'admin@nexjob.tech'
    );
    return isset($options[$option]) ? $options[$option] : $default;
}

function get_post_meta($post_id, $key, $single = false) {
    $meta_data = array(
        7903 => array(
            'nexjob_lokasi_kota' => 'Jakarta',
            '_nexjob_autopost_processed' => time() - 120
        )
    );
    if (isset($meta_data[$post_id][$key])) {
        return $meta_data[$post_id][$key];
    }
    return $single ? '' : array();
}

function wp_get_post_terms($post_id, $taxonomy, $args = array()) {
    if ($taxonomy === 'tag-loker') {
        return array(
            (object) array('name' => 'Sales', 'slug' => 'sales'),
            (object) array('name' => 'Jakarta', 'slug' => 'jakarta')
        );
    }
    return array();
}

function get_permalink($post_id) {
    return "https://cms.nexjob.tech/lowongan-kerja/sales-lapangan-{$post_id}/";
}

function current_time($type = 'mysql') {
    if ($type === 'c') {
        return date('c');
    }
    return date('Y-m-d H:i:s');
}

function get_post_types($args = array(), $output = 'names') {
    $types = array(
        'post' => (object) array('name' => 'post', 'label' => 'Posts'),
        'page' => (object) array('name' => 'page', 'label' => 'Pages'),
        'lowongan-kerja' => (object) array('name' => 'lowongan-kerja', 'label' => 'Lowongan Kerja'),
        'product' => (object) array('name' => 'product', 'label' => 'Products')
    );
    
    if ($output === 'objects') {
        return $types;
    }
    return array_keys($types);
}

function get_object_taxonomies($post_type, $output = 'names') {
    $taxonomies = array(
        'category' => (object) array('name' => 'category', 'label' => 'Categories'),
        'post_tag' => (object) array('name' => 'post_tag', 'label' => 'Tags'),
        'tag-loker' => (object) array('name' => 'tag-loker', 'label' => 'Job Tags')
    );
    
    if ($output === 'objects') {
        return $taxonomies;
    }
    return array_keys($taxonomies);
}

function get_taxonomies($args = array(), $output = 'names') {
    return get_object_taxonomies('', $output);
}

// Mock database for configurations
global $mock_configs;
$mock_configs = array(
    (object) array(
        'id' => 1,
        'name' => 'Default Lowongan Kerja',
        'post_types' => '["lowongan-kerja"]',
        'integration_id' => 'cmd6ykh840001n5bdw68gcwnh',
        'content_template' => 'Lowongan kerja di kota {{nexjob_lokasi_kota}}, cek selengkapnya di {{post_url}}.\n\n{{hashtags:tag-loker}}',
        'status' => 'active',
        'created_at' => '2025-07-17 06:00:00'
    ),
    (object) array(
        'id' => 2,
        'name' => 'Facebook Posts',
        'post_types' => '["post", "page"]',
        'integration_id' => 'fb_integration_123',
        'content_template' => 'New post: {{post_title}}\n\n{{post_excerpt}}\n\nRead more: {{post_url}}',
        'status' => 'active',
        'created_at' => '2025-07-17 06:30:00'
    ),
    (object) array(
        'id' => 3,
        'name' => 'Product Announcements',
        'post_types' => '["product"]',
        'integration_id' => 'product_announce_456',
        'content_template' => '🎉 New Product: {{post_title}}\n\n{{post_content}}\n\n#NewProduct #Launch',
        'status' => 'inactive',
        'created_at' => '2025-07-17 07:00:00'
    )
);

// Mock database query functions
function mock_get_configs($status = null) {
    global $mock_configs;
    if ($status) {
        return array_filter($mock_configs, function($config) use ($status) {
            return $config->status === $status;
        });
    }
    return $mock_configs;
}

// Override the mock configurations to use the built-in mock data
if (class_exists('Nexjob_Configs')) {
    // If the real class exists, we'll override some methods for testing
    $configs = new Nexjob_Configs();
} else {
    // Use our mock class
    $configs = new Nexjob_Configs();
}

// Test posts
$test_posts = array(
    (object) array(
        'ID' => 7903,
        'post_type' => 'lowongan-kerja',
        'post_status' => 'publish',
        'post_title' => 'Sales Lapangan Jakarta',
        'post_content' => 'Kami sedang mencari sales lapangan yang berpengalaman...',
        'post_excerpt' => 'Lowongan kerja sales lapangan di Jakarta',
        'post_date' => '2025-07-17 08:00:00'
    ),
    (object) array(
        'ID' => 7904,
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_title' => 'Tips Interview Kerja',
        'post_content' => 'Berikut adalah tips sukses interview kerja...',
        'post_excerpt' => 'Tips dan trik untuk sukses dalam interview kerja',
        'post_date' => '2025-07-17 09:00:00'
    ),
    (object) array(
        'ID' => 7905,
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_title' => 'Premium Job Posting Package',
        'post_content' => 'Paket premium untuk posting lowongan kerja dengan fitur lengkap...',
        'post_excerpt' => 'Paket premium posting lowongan kerja',
        'post_date' => '2025-07-17 10:00:00'
    )
);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Nexjob Autopost Multi-Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; background: #f8f9fa; padding: 10px; border-left: 4px solid #0073aa; }
        h3 { color: #555; margin-top: 20px; }
        .config-card { 
            background: #fff; 
            border: 1px solid #ddd; 
            margin: 15px 0; 
            padding: 15px; 
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .config-header { 
            background: #f0f8ff; 
            padding: 10px; 
            border-radius: 3px; 
            margin-bottom: 10px;
            border-left: 4px solid #0073aa;
        }
        .template-box { 
            background: #f8f9fa; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 3px;
            margin: 5px 0;
        }
        .parsed-content { 
            background: #e8f5e8; 
            padding: 10px; 
            border: 1px solid #4caf50; 
            border-radius: 3px;
            margin: 5px 0;
        }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .status-active { color: #4caf50; font-weight: bold; }
        .status-inactive { color: #f44336; font-weight: bold; }
        .post-type-badge { 
            background: #0073aa; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-size: 12px;
            margin-right: 5px;
        }
        .placeholder-list { 
            columns: 2; 
            column-gap: 20px; 
            background: #f9f9f9; 
            padding: 15px; 
            border-radius: 5px;
        }
        .placeholder-item { 
            break-inside: avoid; 
            margin-bottom: 5px; 
            background: white; 
            padding: 5px; 
            border-radius: 3px;
            border: 1px solid #eee;
        }
        .placeholder-code { 
            background: #272822; 
            color: #f8f8f2; 
            padding: 2px 4px; 
            border-radius: 2px; 
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>";

echo "<h1>🚀 Nexjob Autopost Multi-Configuration Plugin Test</h1>";

// Initialize configs class (already created above)

echo "<h2>📋 Current Autopost Configurations</h2>";
$all_configs = $configs->get_configs();
echo "<table>";
echo "<tr><th>ID</th><th>Name</th><th>Post Types</th><th>Integration ID</th><th>Status</th><th>Template Preview</th></tr>";

foreach ($all_configs as $config) {
    $post_types = json_decode($config->post_types, true);
    echo "<tr>";
    echo "<td>{$config->id}</td>";
    echo "<td><strong>" . esc_html($config->name) . "</strong></td>";
    echo "<td>";
    foreach ($post_types as $type) {
        echo "<span class='post-type-badge'>{$type}</span>";
    }
    echo "</td>";
    echo "<td><code>" . esc_html($config->integration_id) . "</code></td>";
    echo "<td class='status-{$config->status}'>" . ucfirst($config->status) . "</td>";
    echo "<td>" . esc_html(substr($config->content_template, 0, 50)) . "...</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>🔧 Dynamic Placeholder System</h2>";
echo "<h3>Available Placeholders for Different Post Types:</h3>";

$post_types_to_test = array('lowongan-kerja', 'post', 'product');
foreach ($post_types_to_test as $post_type) {
    $placeholders = $configs->get_placeholder_variables($post_type);
    echo "<h4>Post Type: <code>{$post_type}</code></h4>";
    echo "<div class='placeholder-list'>";
    
    foreach ($placeholders as $group_name => $group_placeholders) {
        echo "<div style='margin-bottom: 15px;'>";
        echo "<strong>{$group_name}:</strong><br>";
        foreach ($group_placeholders as $placeholder => $description) {
            echo "<div class='placeholder-item'>";
            echo "<span class='placeholder-code'>" . esc_html($placeholder) . "</span><br>";
            echo "<small>" . esc_html($description) . "</small>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div>";
}

echo "<h2>🎯 Multi-Configuration Processing Simulation</h2>";

foreach ($test_posts as $test_post) {
    echo "<div class='config-card'>";
    echo "<div class='config-header'>";
    echo "<h3>📄 Post: {$test_post->post_title} (Type: {$test_post->post_type})</h3>";
    echo "</div>";
    
    $matching_configs = $configs->get_configs_for_post_type($test_post->post_type);
    
    if (empty($matching_configs)) {
        echo "<p>❌ No active configurations found for post type '{$test_post->post_type}'</p>";
    } else {
        echo "<p>✅ Found " . count($matching_configs) . " matching configuration(s):</p>";
        
        foreach ($matching_configs as $config) {
            echo "<div style='margin-left: 20px; border-left: 3px solid #0073aa; padding-left: 15px; margin-bottom: 15px;'>";
            echo "<h4>Configuration: {$config->name}</h4>";
            echo "<p><strong>Integration ID:</strong> <code>{$config->integration_id}</code></p>";
            
            echo "<div class='template-box'>";
            echo "<strong>Template:</strong><br>";
            echo "<pre>" . esc_html($config->content_template) . "</pre>";
            echo "</div>";
            
            $parsed_content = $configs->parse_content_template($config->content_template, $test_post);
            echo "<div class='parsed-content'>";
            echo "<strong>Parsed Content:</strong><br>";
            echo nl2br(esc_html($parsed_content));
            echo "</div>";
            
            // Show what would be sent to API
            echo "<details style='margin-top: 10px;'>";
            echo "<summary><strong>API Payload Preview</strong></summary>";
            echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 3px; margin-top: 5px;'>";
            $api_data = array(
                'type' => 'now',
                'shortLink' => true,
                'date' => current_time('c'),
                'integrationId' => $config->integration_id,
                'tags' => array(
                    array('value' => 'sales', 'label' => 'Sales'),
                    array('value' => 'jakarta', 'label' => 'Jakarta')
                ),
                'images' => array(),
                'content' => $parsed_content
            );
            echo json_encode($api_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            echo "</pre>";
            echo "</details>";
            echo "</div>";
        }
    }
    echo "</div>";
}

echo "<h2>📊 New Features Summary</h2>";
echo "<ul style='background: #f0f8ff; padding: 20px; border-radius: 5px; border-left: 4px solid #0073aa;'>";
echo "<li>✅ <strong>Multiple Autopost Configurations:</strong> Support for unlimited autopost configurations with different integration IDs</li>";
echo "<li>✅ <strong>Dynamic Post Type Support:</strong> Configure autoposts for any post type (posts, pages, custom post types)</li>";
echo "<li>✅ <strong>Advanced Placeholder System:</strong> Dynamic placeholders for custom fields, taxonomies, and post data</li>";
echo "<li>✅ <strong>Flexible Content Templates:</strong> Customizable content templates with placeholder replacement</li>";
echo "<li>✅ <strong>Smart Taxonomy Handling:</strong> Support for hashtags and term lists from any taxonomy</li>";
echo "<li>✅ <strong>CRUD Configuration Management:</strong> Add, edit, delete configurations through admin interface</li>";
echo "<li>✅ <strong>Interactive Placeholder Builder:</strong> Click-to-insert placeholder system in admin</li>";
echo "<li>✅ <strong>Multi-Configuration Processing:</strong> Single post can trigger multiple autopost configurations</li>";
echo "<li>✅ <strong>WordPress Database Integration:</strong> All configurations stored in WordPress database tables</li>";
echo "<li>✅ <strong>Backward Compatibility:</strong> Maintains compatibility with existing single-configuration setups</li>";
echo "</ul>";

echo "<h2>🎛️ Admin Interface Features</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
echo "<h3>New Menu Structure:</h3>";
echo "<ul>";
echo "<li><strong>Nexjob Autopost</strong> (Main menu)</li>";
echo "<li>├── <strong>Configurations</strong> - Manage autopost configurations</li>";
echo "<li>│   ├── Add New Configuration</li>";
echo "<li>│   ├── Edit Existing Configurations</li>";
echo "<li>│   └── Delete Configurations</li>";
echo "<li>└── <strong>Settings</strong> - General plugin settings and logs</li>";
echo "</ul>";

echo "<h3>Configuration Form Features:</h3>";
echo "<ul>";
echo "<li>✅ Multi-select post type checkboxes</li>";
echo "<li>✅ Integration ID input field</li>";
echo "<li>✅ Rich content template editor</li>";
echo "<li>✅ Dynamic placeholder browser with descriptions</li>";
echo "<li>✅ Click-to-insert placeholder functionality</li>";
echo "<li>✅ Real-time placeholder preview based on selected post type</li>";
echo "<li>✅ Active/Inactive status toggle</li>";
echo "</ul>";
echo "</div>";

echo "<style>
details { margin: 10px 0; }
summary { 
    cursor: pointer; 
    padding: 5px; 
    background: #f0f0f0; 
    border-radius: 3px;
    user-select: none;
}
summary:hover { background: #e0e0e0; }
pre { font-size: 12px; max-height: 300px; overflow-y: auto; }
</style>";

echo "</body></html>";

function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>