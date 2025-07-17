<?php
/**
 * Plugin Name: Nexjob Autopost
 * Plugin URI: https://nexjob.tech
 * Description: Automatically sends POST requests to external API when new lowongan-kerja posts are published
 * Version: 1.1.0
 * Author: Nexjob Team
 * License: GPL v2 or later
 * Text Domain: nexjob-autopost
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NEXJOB_AUTOPOST_VERSION', '1.1.0');
define('NEXJOB_AUTOPOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEXJOB_AUTOPOST_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEXJOB_AUTOPOST_PLUGIN_FILE', __FILE__);

// Include required files
require_once NEXJOB_AUTOPOST_PLUGIN_DIR . 'includes/class-nexjob-autopost.php';
require_once NEXJOB_AUTOPOST_PLUGIN_DIR . 'includes/class-nexjob-admin.php';
require_once NEXJOB_AUTOPOST_PLUGIN_DIR . 'includes/class-nexjob-logger.php';
require_once NEXJOB_AUTOPOST_PLUGIN_DIR . 'includes/class-nexjob-configs.php';

/**
 * Main plugin class initialization
 */
function nexjob_autopost_init() {
    $plugin = new Nexjob_Autopost();
    $admin = new Nexjob_Admin();
    $logger = new Nexjob_Logger();
    
    // Initialize components
    $plugin->init();
    $admin->init();
    $logger->init();
}

// Initialize plugin after WordPress loads
add_action('plugins_loaded', 'nexjob_autopost_init');

/**
 * Plugin activation hook
 */
function nexjob_autopost_activate() {
    // Create custom table for logging
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        post_title varchar(255) NOT NULL,
        api_url varchar(500) NOT NULL,
        request_data longtext NOT NULL,
        response_data longtext NOT NULL,
        response_code int(11) NOT NULL,
        status varchar(20) NOT NULL,
        request_type varchar(20) DEFAULT 'auto',
        retry_count int(11) DEFAULT 0,
        autopost_config_id mediumint(9) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY status (status),
        KEY request_type (request_type),
        KEY autopost_config_id (autopost_config_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Create autopost configurations table
    $configs_table = $wpdb->prefix . 'nexjob_autopost_configs';
    
    $configs_sql = "CREATE TABLE $configs_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        post_types text NOT NULL,
        integration_id varchar(255) NOT NULL,
        content_template longtext NOT NULL,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status (status)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($configs_sql);
    
    // Create default autopost configuration
    $default_config = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $configs_table"));
    if ($default_config == 0) {
        $wpdb->insert(
            $configs_table,
            array(
                'name' => 'Default Lowongan Kerja',
                'post_types' => json_encode(array('lowongan-kerja')),
                'integration_id' => 'cmd6ykh840001n5bdw68gcwnh',
                'content_template' => 'Lowongan kerja di kota {{nexjob_lokasi_kota}}, cek selengkapnya di {{post_url}}.\n\n{{hashtags:tag-loker}}',
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    // Set default options
    $default_options = array(
        'api_url' => 'https://autopost.nexpocket.com/api/public/v1/post',
        'auth_header' => 'd81c427d7627cc46207b3a069f8d213abfc034acaa41b4875b0c9f71ed7e277c',
        'integration_id' => 'cmd6ykh840001n5bdw68gcwnh',
        'enabled' => '1',
        'log_retention_days' => '30',
        'max_retries' => '3',
        'email_notifications' => '0',
        'notification_email' => get_option('admin_email')
    );
    
    foreach ($default_options as $key => $value) {
        if (get_option('nexjob_autopost_' . $key) === false) {
            add_option('nexjob_autopost_' . $key, $value);
        }
    }
    
    // Schedule log cleanup
    if (!wp_next_scheduled('nexjob_autopost_cleanup_logs')) {
        wp_schedule_event(time(), 'daily', 'nexjob_autopost_cleanup_logs');
    }
}

/**
 * Plugin deactivation hook
 */
function nexjob_autopost_deactivate() {
    // Clear scheduled events
    wp_clear_scheduled_hook('nexjob_autopost_cleanup_logs');
}

/**
 * Plugin uninstall hook
 */
function nexjob_autopost_uninstall() {
    global $wpdb;
    
    // Drop custom tables
    $logs_table = $wpdb->prefix . 'nexjob_autopost_logs';
    $configs_table = $wpdb->prefix . 'nexjob_autopost_configs';
    $wpdb->query("DROP TABLE IF EXISTS $logs_table");
    $wpdb->query("DROP TABLE IF EXISTS $configs_table");
    
    // Remove options
    $options = array(
        'nexjob_autopost_api_url',
        'nexjob_autopost_auth_header',
        'nexjob_autopost_integration_id',
        'nexjob_autopost_enabled',
        'nexjob_autopost_log_retention_days',
        'nexjob_autopost_max_retries',
        'nexjob_autopost_email_notifications',
        'nexjob_autopost_notification_email'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
}

register_activation_hook(__FILE__, 'nexjob_autopost_activate');
register_deactivation_hook(__FILE__, 'nexjob_autopost_deactivate');
register_uninstall_hook(__FILE__, 'nexjob_autopost_uninstall');
?>
