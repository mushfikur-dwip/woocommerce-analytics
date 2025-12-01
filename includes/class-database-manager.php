<?php
/**
 * Database Manager Class
 * 
 * Handles all database operations and table creation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Database_Manager {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0';
    
    /**
     * Create all plugin tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create SKU costs table
        self::create_sku_costs_table($charset_collate);
        
        // Create customer LTV table
        self::create_customer_ltv_table($charset_collate);
        
        // Create attribution table
        self::create_attribution_table($charset_collate);
        
        // Create courier performance table
        self::create_courier_performance_table($charset_collate);
        
        // Update database version
        update_option('wc_analytics_db_version', self::DB_VERSION);
    }
    
    /**
     * Create SKU costs table
     */
    private static function create_sku_costs_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_analytics_sku_costs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            variation_id bigint(20) DEFAULT 0,
            sku varchar(100) NOT NULL,
            product_name text NOT NULL,
            cost_price decimal(10,2) NOT NULL DEFAULT 0.00,
            selling_price decimal(10,2) NOT NULL DEFAULT 0.00,
            profit_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            profit_margin decimal(10,2) NOT NULL DEFAULT 0.00,
            additional_costs decimal(10,2) DEFAULT 0.00,
            shipping_cost decimal(10,2) DEFAULT 0.00,
            tax_amount decimal(10,2) DEFAULT 0.00,
            currency varchar(10) DEFAULT 'USD',
            order_id bigint(20) DEFAULT NULL,
            order_date datetime DEFAULT NULL,
            quantity int(11) DEFAULT 1,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY variation_id (variation_id),
            KEY sku (sku),
            KEY order_id (order_id),
            KEY order_date (order_date),
            KEY date_created (date_created),
            KEY profit_margin (profit_margin)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create customer LTV table
     */
    private static function create_customer_ltv_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_analytics_customer_ltv';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_name varchar(200) DEFAULT NULL,
            total_orders int(11) DEFAULT 0,
            total_spent decimal(10,2) DEFAULT 0.00,
            total_profit decimal(10,2) DEFAULT 0.00,
            average_order_value decimal(10,2) DEFAULT 0.00,
            average_profit_per_order decimal(10,2) DEFAULT 0.00,
            first_order_date datetime DEFAULT NULL,
            last_order_date datetime DEFAULT NULL,
            lifetime_value decimal(10,2) DEFAULT 0.00,
            predicted_ltv decimal(10,2) DEFAULT 0.00,
            days_since_last_order int(11) DEFAULT 0,
            order_frequency decimal(10,2) DEFAULT 0.00,
            customer_segment varchar(50) DEFAULT 'bronze',
            is_active tinyint(1) DEFAULT 1,
            currency varchar(10) DEFAULT 'USD',
            date_calculated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY customer_id (customer_id),
            KEY customer_email (customer_email),
            KEY lifetime_value (lifetime_value),
            KEY last_order_date (last_order_date),
            KEY customer_segment (customer_segment),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create attribution table
     */
    private static function create_attribution_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_analytics_attribution';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            customer_id bigint(20) DEFAULT NULL,
            utm_source varchar(255) DEFAULT NULL,
            utm_medium varchar(255) DEFAULT NULL,
            utm_campaign varchar(255) DEFAULT NULL,
            utm_term varchar(255) DEFAULT NULL,
            utm_content varchar(255) DEFAULT NULL,
            referrer_url text DEFAULT NULL,
            landing_page text DEFAULT NULL,
            device_type varchar(50) DEFAULT NULL,
            browser varchar(100) DEFAULT NULL,
            order_total decimal(10,2) DEFAULT 0.00,
            order_profit decimal(10,2) DEFAULT 0.00,
            marketing_spend decimal(10,2) DEFAULT 0.00,
            roi decimal(10,2) DEFAULT 0.00,
            conversion_date datetime NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY utm_source (utm_source(191)),
            KEY utm_campaign (utm_campaign(191)),
            KEY utm_medium (utm_medium(191)),
            KEY conversion_date (conversion_date)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Create courier performance table
     */
    private static function create_courier_performance_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_analytics_courier_performance';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            courier_name varchar(100) NOT NULL,
            shipping_method varchar(100) NOT NULL,
            tracking_number varchar(100) DEFAULT NULL,
            customer_city varchar(100) DEFAULT NULL,
            customer_state varchar(100) DEFAULT NULL,
            order_placed_date datetime NOT NULL,
            dispatch_date datetime DEFAULT NULL,
            estimated_delivery_date datetime DEFAULT NULL,
            actual_delivery_date datetime DEFAULT NULL,
            delivery_time_days int(11) DEFAULT NULL,
            delay_days int(11) DEFAULT 0,
            on_time_delivery tinyint(1) DEFAULT 0,
            delivery_status varchar(50) DEFAULT 'pending',
            shipping_cost decimal(10,2) DEFAULT 0.00,
            notes text DEFAULT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY courier_name (courier_name),
            KEY delivery_status (delivery_status),
            KEY dispatch_date (dispatch_date),
            KEY actual_delivery_date (actual_delivery_date),
            KEY on_time_delivery (on_time_delivery),
            KEY order_placed_date (order_placed_date)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Check if table exists
     */
    public static function table_exists($table_name) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_name;
        return $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    }
    
    /**
     * Drop all plugin tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'wc_analytics_sku_costs',
            $wpdb->prefix . 'wc_analytics_customer_ltv',
            $wpdb->prefix . 'wc_analytics_attribution',
            $wpdb->prefix . 'wc_analytics_courier_performance'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Delete options
        delete_option('wc_analytics_db_version');
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'wc_analytics_' . $table;
    }
}
