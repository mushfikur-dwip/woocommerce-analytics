<?php
/**
 * Order Columns Class
 * 
 * Adds custom loyalty column to WooCommerce orders list
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Order_Columns {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add column to orders list
        add_filter('manage_edit-shop_order_columns', array($this, 'add_loyalty_column'), 20);
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_loyalty_column'), 20);
        
        // Populate column content
        add_action('manage_shop_order_posts_custom_column', array($this, 'populate_loyalty_column'), 20, 2);
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'populate_loyalty_column_hpos'), 20, 2);
        
        // Make column sortable
        add_filter('manage_edit-shop_order_sortable_columns', array($this, 'make_loyalty_column_sortable'));
        
        // Add custom CSS for the column
        add_action('admin_head', array($this, 'add_column_styles'));
    }
    
    /**
     * Add loyalty column to orders list
     */
    public function add_loyalty_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Add loyalty column after order status
            if ($key === 'order_status') {
                $new_columns['customer_loyalty'] = __('Customer Loyalty', 'wc-analytics');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate loyalty column for traditional posts
     */
    public function populate_loyalty_column($column, $post_id) {
        if ($column === 'customer_loyalty') {
            $order = wc_get_order($post_id);
            if ($order) {
                $this->display_loyalty_badge($order);
            }
        }
    }
    
    /**
     * Populate loyalty column for HPOS
     */
    public function populate_loyalty_column_hpos($column, $order) {
        if ($column === 'customer_loyalty') {
            if (is_numeric($order)) {
                $order = wc_get_order($order);
            }
            if ($order) {
                $this->display_loyalty_badge($order);
            }
        }
    }
    
    /**
     * Display loyalty badge
     */
    private function display_loyalty_badge($order) {
        global $wpdb;
        
        if (!$order) {
            echo '<span style="color: #999;">—</span>';
            return;
        }
        
        // Skip refunds - they don't have billing info
        if (is_a($order, 'WC_Order_Refund')) {
            echo '<span style="color: #999;">—</span>';
            return;
        }
        
        $customer_id = $order->get_customer_id();
        $phone = $order->get_billing_phone();
        
        // Format phone number
        $formatted_phone = $this->format_phone_number($phone);
        
        // Get customer LTV data
        $table_name = $wpdb->prefix . 'wc_analytics_customer_ltv';
        $ltv_data = null;
        
        // Try by phone first
        if ($formatted_phone) {
            $ltv_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT customer_segment, total_orders, lifetime_value FROM $table_name WHERE customer_phone = %s",
                    $formatted_phone
                )
            );
        }
        
        // If not found by phone, try by customer ID
        if (!$ltv_data && $customer_id) {
            $ltv_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT customer_segment, total_orders, lifetime_value FROM $table_name WHERE customer_id = %d",
                    $customer_id
                )
            );
        }
        
        if ($ltv_data) {
            // Get tier details
            require_once(WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php');
            $tiers = WC_Analytics_Loyalty_Settings::get_loyalty_tiers();
            
            $tier_name = ucfirst($ltv_data->customer_segment);
            $tier_color = '#666666';
            
            foreach ($tiers as $tier) {
                if (strtolower($tier['name']) === strtolower($ltv_data->customer_segment)) {
                    $tier_name = $tier['name'];
                    $tier_color = $tier['color'];
                    break;
                }
            }
            
            echo '<div class="wc-analytics-loyalty-badge-wrapper">';
            echo '<span class="wc-analytics-loyalty-badge" style="background-color: ' . esc_attr($tier_color) . '; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; display: inline-block; text-transform: uppercase; white-space: nowrap;">';
            echo esc_html($tier_name);
            echo '</span>';
            echo '<div class="wc-analytics-loyalty-tooltip">';
            echo '<strong>' . esc_html($tier_name) . ' Customer</strong><br>';
            echo 'Orders: ' . esc_html($ltv_data->total_orders) . '<br>';
            echo 'LTV: ' . wc_price($ltv_data->lifetime_value);
            echo '</div>';
            echo '</div>';
        } else {
            echo '<span style="color: #999; font-size: 11px;">No Data</span>';
        }
    }
    
    /**
     * Make loyalty column sortable
     */
    public function make_loyalty_column_sortable($columns) {
        $columns['customer_loyalty'] = 'customer_loyalty';
        return $columns;
    }
    
    /**
     * Format phone number to international format
     */
    private function format_phone_number($phone) {
        if (empty($phone)) {
            return null;
        }
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If starts with 0, replace with +880
        if (substr($phone, 0, 1) === '0') {
            $phone = '+880' . substr($phone, 1);
        }
        // If doesn't start with +, add +880
        elseif (substr($phone, 0, 1) !== '+') {
            $phone = '+880' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Add custom CSS for loyalty column
     */
    public function add_column_styles() {
        global $pagenow, $typenow;
        
        // Only add on orders page
        if (($pagenow === 'edit.php' && $typenow === 'shop_order') || 
            ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders')) {
            ?>
            <style>
                .column-customer_loyalty {
                    width: 120px;
                }
                
                .wc-analytics-loyalty-badge-wrapper {
                    position: relative;
                    display: inline-block;
                }
                
                .wc-analytics-loyalty-badge {
                    cursor: help;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    transition: all 0.2s ease;
                }
                
                .wc-analytics-loyalty-badge:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
                }
                
                .wc-analytics-loyalty-tooltip {
                    visibility: hidden;
                    opacity: 0;
                    position: absolute;
                    z-index: 1000;
                    bottom: 125%;
                    left: 50%;
                    transform: translateX(-50%);
                    background-color: #333;
                    color: #fff;
                    text-align: left;
                    padding: 10px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    line-height: 1.6;
                    white-space: nowrap;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    transition: opacity 0.3s, visibility 0.3s;
                    pointer-events: none;
                }
                
                .wc-analytics-loyalty-tooltip::after {
                    content: "";
                    position: absolute;
                    top: 100%;
                    left: 50%;
                    margin-left: -5px;
                    border-width: 5px;
                    border-style: solid;
                    border-color: #333 transparent transparent transparent;
                }
                
                .wc-analytics-loyalty-badge-wrapper:hover .wc-analytics-loyalty-tooltip {
                    visibility: visible;
                    opacity: 1;
                }
                
                /* Mobile responsive */
                @media screen and (max-width: 782px) {
                    .column-customer_loyalty {
                        display: none;
                    }
                }
            </style>
            <?php
        }
    }
}
