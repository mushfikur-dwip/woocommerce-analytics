<?php
/**
 * Plugin Name: WooCommerce Performance Analytics
 * Description: Comprehensive analytics plugin for WooCommerce with profit tracking, customer LTV, marketing ROI, and courier performance monitoring
 * Version: 1.0.0
 * Author: Mushfikur Rahman
 * Author URI: https://fb.me/mushfikur.a.k
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-analytics
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_ANALYTICS_VERSION', '1.0.0');
define('WC_ANALYTICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_ANALYTICS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_ANALYTICS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Check if WooCommerce is active
 */
function wc_analytics_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_analytics_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function wc_analytics_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce Performance Analytics requires WooCommerce to be installed and active. Plugin has been deactivated.', 'woocommerce-analytics'); ?></p>
    </div>
    <?php
}

/**
 * Plugin activation hook
 */
function wc_analytics_activate() {
    // Check WooCommerce
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('WooCommerce Performance Analytics requires WooCommerce to be installed and active.', 'woocommerce-analytics'));
    }
    
    // Force migration by resetting version
    delete_option('wc_analytics_db_version');
    
    // Create database tables
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-database-manager.php';
    WC_Analytics_Database_Manager::create_tables();
    
    // Set default options
    add_option('wc_analytics_version', WC_ANALYTICS_VERSION);
    add_option('wc_analytics_install_date', current_time('mysql'));
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wc_analytics_activate');

/**
 * Plugin deactivation hook
 */
function wc_analytics_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wc_analytics_deactivate');

/**
 * Initialize the plugin
 */
function wc_analytics_init() {
    // Check WooCommerce
    if (!wc_analytics_check_woocommerce()) {
        return;
    }
    
    // Load text domain
    load_plugin_textdomain('woocommerce-analytics', false, dirname(WC_ANALYTICS_PLUGIN_BASENAME) . '/languages');
    
    // Include core files
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-analytics-core.php';
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-database-manager.php';
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-sku-manager.php';
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-ltv-calculator.php';
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-attribution-tracker.php';
    require_once WC_ANALYTICS_PLUGIN_DIR . 'includes/class-courier-manager.php';
    
    // Include admin files
    if (is_admin()) {
        require_once WC_ANALYTICS_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once WC_ANALYTICS_PLUGIN_DIR . 'admin/class-dashboard-page.php';
        require_once WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php';
        require_once WC_ANALYTICS_PLUGIN_DIR . 'admin/class-order-meta-box.php';
        require_once WC_ANALYTICS_PLUGIN_DIR . 'admin/class-order-columns.php';
        
        // Initialize admin classes
        new WC_Analytics_Loyalty_Settings();
        new WC_Analytics_Order_Meta_Box();
        new WC_Analytics_Order_Columns();
    }
    
    // Initialize core class
    WC_Analytics_Core::get_instance();
}
add_action('plugins_loaded', 'wc_analytics_init');

/**
 * Add settings link on plugin page
 */
function wc_analytics_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-analytics-dashboard') . '">' . __('Dashboard', 'woocommerce-analytics') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . WC_ANALYTICS_PLUGIN_BASENAME, 'wc_analytics_plugin_action_links');
