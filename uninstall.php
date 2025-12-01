<?php
/**
 * Uninstall script
 * 
 * Fires when the plugin is uninstalled
 */

// Exit if accessed directly or not in uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load database manager
require_once plugin_dir_path(__FILE__) . 'includes/class-database-manager.php';

// Drop all tables
WC_Analytics_Database_Manager::drop_tables();

// Delete all options
delete_option('wc_analytics_version');
delete_option('wc_analytics_install_date');
delete_option('wc_analytics_db_version');

// Delete transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_analytics_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wc_analytics_%'");

// Clear any cached data
wp_cache_flush();
