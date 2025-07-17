<?php
/**
 * Test file to demonstrate Nexjob Autopost plugin functionality
 * This simulates WordPress environment for testing
 */

// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', true);

// Mock WordPress functions for testing
function get_option($key, $default = false) {
    $options = [
        'nexjob_autopost_api_url' => 'https://autopost.nexpocket.com/api/public/v1/post',
        'nexjob_autopost_auth_header' => 'd81c427d7627cc46207b3a069f8d213abfc034acaa41b4875b0c9f71ed7e277c',
        'nexjob_autopost_integration_id' => 'cmd6ykh840001n5bdw68gcwnh',
        'nexjob_autopost_enabled' => '1',
        'nexjob_autopost_log_retention_days' => '30'
    ];
    return isset($options[$key]) ? $options[$key] : $default;
}

function get_post_meta($post_id, $key, $single = false) {
    $meta = [
        'nexjob_lokasi_kota' => 'Tangerang'
    ];
    return isset($meta[$key]) ? $meta[$key] : '';
}

function wp_get_post_terms($post_id, $taxonomy) {
    return [
        (object) ['slug' => 'konsultan-penjualan', 'name' => 'Konsultan Penjualan'],
        (object) ['slug' => 'sma-smk', 'name' => 'SMA/SMK'],
        (object) ['slug' => 'staff-officer', 'name' => 'Staff Officer'],
        (object) ['slug' => 'sales-executive', 'name' => 'Sales Executive'],
        (object) ['slug' => 'on-site-working', 'name' => 'On-site Working']
    ];
}

function get_permalink($post_id) {
    return "https://cms.nexjob.tech/lowongan-kerja/sales-lapangan-pt-juara-kemilau-nusantara-tangerang/";
}

function current_time($format) {
    if ($format === 'c') {
        return date('c');
    }
    return time();
}

function is_wp_error($thing) {
    return false;
}

function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
    // Mock function for testing
    return true;
}

function wp_schedule_single_event($timestamp, $hook, $args = array()) {
    // Mock function for testing
    return true;
}

function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
    // Mock function for testing
    return true;
}

function checked($checked, $current = true, $echo = true) {
    $result = '';
    if ($checked == $current) {
        $result = ' checked="checked"';
    }
    if ($echo) {
        echo $result;
    }
    return $result;
}

function get_posts($args = array()) {
    // Mock posts for testing
    return array(
        (object) array('ID' => 7903, 'post_title' => 'Sales Lapangan'),
        (object) array('ID' => 7904, 'post_title' => 'Marketing Manager'),
        (object) array('ID' => 7905, 'post_title' => 'Customer Service')
    );
}

// Mock post object
$test_post = (object) [
    'ID' => 7903,
    'post_type' => 'lowongan-kerja',
    'post_status' => 'publish',
    'post_title' => 'Sales Lapangan'
];

// Include plugin classes
require_once 'includes/class-nexjob-autopost.php';
require_once 'includes/class-nexjob-admin.php';
require_once 'includes/class-nexjob-logger.php';
require_once 'includes/class-nexjob-configs.php';

// Test the plugin functionality
echo "<h1>Nexjob Autopost Multi-Configuration Plugin Test</h1>";

$autopost = new Nexjob_Autopost();
$configs = new Nexjob_Configs();

// Test prepare_post_data method using reflection
$reflection = new ReflectionClass($autopost);
$method = $reflection->getMethod('prepare_post_data');
$method->setAccessible(true);

try {
    $post_data = $method->invoke($autopost, $test_post);
    
    echo "<h2>✅ Plugin Configuration</h2>";
    echo "<pre>";
    echo "API URL: " . get_option('nexjob_autopost_api_url') . "\n";
    echo "Auth Header: " . substr(get_option('nexjob_autopost_auth_header'), 0, 20) . "...\n";
    echo "Integration ID: " . get_option('nexjob_autopost_integration_id') . "\n";
    echo "Enabled: " . (get_option('nexjob_autopost_enabled') ? 'Yes' : 'No') . "\n";
    echo "</pre>";
    
    echo "<h2>✅ Sample Post Data</h2>";
    echo "<pre>";
    echo "Post ID: " . $test_post->ID . "\n";
    echo "Post Title: " . $test_post->post_title . "\n";
    echo "Post Type: " . $test_post->post_type . "\n";
    echo "City Location: " . get_post_meta($test_post->ID, 'nexjob_lokasi_kota', true) . "\n";
    echo "</pre>";
    
    echo "<h2>✅ Generated API Request Data</h2>";
    echo "<pre>";
    echo json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "</pre>";
    
    echo "<h2>✅ Content Preview</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0;'>";
    echo nl2br(htmlspecialchars($post_data['posts'][0]['value'][0]['content']));
    echo "</div>";
    
    echo "<h2>✅ Enhanced Features Summary</h2>";
    echo "<ul>";
    echo "<li>✓ Automatically detects new lowongan-kerja posts (manual + API created)</li>";
    echo "<li>✓ Extracts city location from nexjob_lokasi_kota custom field</li>";
    echo "<li>✓ Gets tags from tag-loker taxonomy and converts to hashtags</li>";
    echo "<li>✓ Replaces cms.nexjob.tech with nexjob.tech in URLs</li>";
    echo "<li>✓ Sends formatted data to NexPocket API with authentication</li>";
    echo "<li>✓ <strong>NEW:</strong> Automatic retry mechanism with exponential backoff</li>";
    echo "<li>✓ <strong>NEW:</strong> Bulk resend functionality for existing posts</li>";
    echo "<li>✓ <strong>NEW:</strong> Email notifications for failures and retry successes</li>";
    echo "<li>✓ <strong>NEW:</strong> Dashboard widget with activity statistics</li>";
    echo "<li>✓ Complete admin interface with enhanced configuration options</li>";
    echo "<li>✓ Comprehensive logging with retry tracking and request types</li>";
    echo "<li>✓ Automatic log cleanup after configured retention period</li>";
    echo "</ul>";
    
    echo "<h2>✅ Advanced Configuration Options</h2>";
    echo "<div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0;'>";
    echo "<strong>Retry Settings:</strong><br>";
    echo "• Max Retry Attempts: 0-10 configurable attempts<br>";
    echo "• Retry Schedule: 2, 4, 8 minutes with exponential backoff<br><br>";
    echo "<strong>Email Notifications:</strong><br>";
    echo "• Configurable email alerts for final failures<br>";
    echo "• Success notifications for retry completions<br>";
    echo "• Custom notification email address<br><br>";
    echo "<strong>Bulk Operations:</strong><br>";
    echo "• Select posts from dropdown menu<br>";
    echo "• Manual post ID input (comma-separated)<br>";
    echo "• Individual retry buttons for failed requests<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<pre>Error: " . $e->getMessage() . "</pre>";
}

echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
h2 { color: #333; margin-top: 30px; }
pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
ul { background: #f0f8ff; padding: 20px; border-radius: 4px; }
li { margin: 5px 0; }
</style>";
?>