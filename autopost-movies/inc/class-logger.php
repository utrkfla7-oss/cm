<?php
/**
 * Logger for AutoPost Movies plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class APM_Logger {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'apm_logs';
    }
    
    /**
     * Log a message
     */
    public function log($level, $message, $context = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'level' => $level,
                'message' => $message,
                'context' => json_encode($context),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        // Clean old logs (keep only last 1000 entries)
        $this->cleanup_old_logs();
    }
    
    /**
     * Get logs
     */
    public function get_logs($limit = 50, $level = null) {
        global $wpdb;
        
        $where = '';
        $prepare_args = array($limit);
        
        if ($level) {
            $where = 'WHERE level = %s';
            array_unshift($prepare_args, $level);
        }
        
        $query = "SELECT * FROM {$this->table_name} {$where} ORDER BY timestamp DESC LIMIT %d";
        
        if ($level) {
            $results = $wpdb->get_results($wpdb->prepare($query, $prepare_args));
        } else {
            $results = $wpdb->get_results($wpdb->prepare($query, $limit));
        }
        
        // Decode context for each log entry
        foreach ($results as &$log) {
            $log->context = json_decode($log->context, true);
        }
        
        return $results;
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    /**
     * Clean up old logs
     */
    private function cleanup_old_logs() {
        global $wpdb;
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        if ($count > 1000) {
            $wpdb->query("DELETE FROM {$this->table_name} ORDER BY timestamp ASC LIMIT " . ($count - 1000));
        }
    }
    
    /**
     * Get log statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total logs
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Logs by level
        $levels = $wpdb->get_results("SELECT level, COUNT(*) as count FROM {$this->table_name} GROUP BY level");
        foreach ($levels as $level) {
            $stats['by_level'][$level->level] = $level->count;
        }
        
        // Recent logs (last 24 hours)
        $stats['recent'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        
        return $stats;
    }
}