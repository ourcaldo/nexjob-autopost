<?php
/**
 * Logger class for Nexjob Autopost plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nexjob_Logger {
    
    /**
     * Initialize logger
     */
    public function init() {
        // Logger is initialized, methods available for use
    }
    
    /**
     * Get logs with pagination
     */
    public function get_logs($page = 1, $per_page = 20, $status = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        $offset = ($page - 1) * $per_page;
        
        $where = '';
        $where_values = array();
        
        if (!empty($status)) {
            $where = ' WHERE status = %s';
            $where_values[] = $status;
        }
        
        // Get total count
        $total_query = "SELECT COUNT(*) FROM $table_name" . $where;
        if (!empty($where_values)) {
            $total = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
        } else {
            $total = $wpdb->get_var($total_query);
        }
        
        // Get logs
        $query = "SELECT * FROM $table_name" . $where . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($per_page, $offset));
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        return array(
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    /**
     * Get log statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        
        $stats = array();
        
        // Total logs
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Success count
        $stats['success'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'success'");
        
        // Error count
        $stats['error'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'error'");
        
        // Today's logs
        $stats['today'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()");
        
        // This week's logs
        $stats['week'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        
        // This month's logs
        $stats['month'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        return $stats;
    }
    
    /**
     * Delete old logs
     */
    public function cleanup_logs($days = null) {
        global $wpdb;
        
        if ($days === null) {
            $days = get_option('nexjob_autopost_log_retention_days', 30);
        }
        
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        return $deleted;
    }
    
    /**
     * Get recent errors
     */
    public function get_recent_errors($limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nexjob_autopost_logs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = 'error' ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }
}
?>
