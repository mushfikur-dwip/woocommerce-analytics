<?php
/**
 * Core Analytics Class
 * 
 * Main plugin class that initializes all components
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Core {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        add_action('init', array($this, 'init_components'));
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Initialize all plugin components
     */
    public function init_components() {
        // Initialize SKU manager
        if (class_exists('WC_Analytics_SKU_Manager')) {
            WC_Analytics_SKU_Manager::get_instance();
        }
        
        // Initialize LTV calculator
        if (class_exists('WC_Analytics_LTV_Calculator')) {
            WC_Analytics_LTV_Calculator::get_instance();
        }
        
        // Initialize attribution tracker
        if (class_exists('WC_Analytics_Attribution_Tracker')) {
            WC_Analytics_Attribution_Tracker::get_instance();
        }
        
        // Initialize courier manager
        if (class_exists('WC_Analytics_Courier_Manager')) {
            WC_Analytics_Courier_Manager::get_instance();
        }
        
        // Initialize admin menu
        if (is_admin() && class_exists('WC_Analytics_Admin_Menu')) {
            WC_Analytics_Admin_Menu::get_instance();
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'wc-analytics') === false) {
            return;
        }
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'wc-analytics-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );
        
        // Enqueue custom admin styles
        wp_enqueue_style(
            'wc-analytics-admin',
            WC_ANALYTICS_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            WC_ANALYTICS_VERSION
        );
        
        // Enqueue custom admin scripts
        wp_enqueue_script(
            'wc-analytics-admin',
            WC_ANALYTICS_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array('jquery', 'wc-analytics-chartjs'),
            WC_ANALYTICS_VERSION,
            true
        );
        
        // Enqueue AJAX handler
        wp_enqueue_script(
            'wc-analytics-ajax',
            WC_ANALYTICS_PLUGIN_URL . 'assets/js/ajax-handler.js',
            array('jquery'),
            WC_ANALYTICS_VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('wc-analytics-ajax', 'wcAnalytics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_analytics_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'woocommerce-analytics'),
                'error' => __('An error occurred. Please try again.', 'woocommerce-analytics'),
                'success' => __('Success!', 'woocommerce-analytics')
            )
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Frontend tracking script for UTM parameters
        wp_enqueue_script(
            'wc-analytics-tracking',
            WC_ANALYTICS_PLUGIN_URL . 'assets/js/tracking.js',
            array('jquery'),
            WC_ANALYTICS_VERSION,
            true
        );
        
        wp_localize_script('wc-analytics-tracking', 'wcAnalyticsTracking', array(
            'cookieExpiry' => 30 // days
        ));
    }
}
