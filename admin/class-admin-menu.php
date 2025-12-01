<?php
/**
 * Admin Menu Class
 * 
 * Handles admin menu registration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Admin_Menu {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
    }
    
    /**
     * Register admin menu
     */
    public function register_menu() {
        // Check permissions
        $capability = 'manage_woocommerce';
        
        // Main menu page
        add_menu_page(
            __('WC Analytics', 'woocommerce-analytics'),
            __('WC Analytics', 'woocommerce-analytics'),
            $capability,
            'wc-analytics-dashboard',
            array('WC_Analytics_Dashboard_Page', 'render_dashboard'),
            'dashicons-chart-line',
            56
        );
        
        // Dashboard submenu
        add_submenu_page(
            'wc-analytics-dashboard',
            __('Dashboard', 'woocommerce-analytics'),
            __('Dashboard', 'woocommerce-analytics'),
            $capability,
            'wc-analytics-dashboard',
            array('WC_Analytics_Dashboard_Page', 'render_dashboard')
        );
        
        // SKU Profits submenu
        add_submenu_page(
            'wc-analytics-dashboard',
            __('SKU Profits', 'woocommerce-analytics'),
            __('SKU Profits', 'woocommerce-analytics'),
            $capability,
            'wc-analytics-sku-profits',
            array('WC_Analytics_Dashboard_Page', 'render_sku_profits')
        );
        
        // Customer LTV submenu
        add_submenu_page(
            'wc-analytics-dashboard',
            __('Customer LTV', 'woocommerce-analytics'),
            __('Customer LTV', 'woocommerce-analytics'),
            $capability,
            'wc-analytics-customer-ltv',
            array('WC_Analytics_Dashboard_Page', 'render_customer_ltv')
        );
        
        // Courier Performance submenu
        add_submenu_page(
            'wc-analytics-dashboard',
            __('Courier Performance', 'woocommerce-analytics'),
            __('Courier Performance', 'woocommerce-analytics'),
            $capability,
            'wc-analytics-courier-performance',
            array('WC_Analytics_Dashboard_Page', 'render_courier_performance')
        );
    }
}
