<?php
/**
 * Order Meta Box Class
 * 
 * Displays customer loyalty information on order edit page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Order_Meta_Box {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_loyalty_meta_box'));
    }
    
    /**
     * Add loyalty meta box to order edit page
     */
    public function add_loyalty_meta_box() {
        add_meta_box(
            'wc_analytics_customer_loyalty',
            __('Customer Loyalty Info', 'wc-analytics'),
            array($this, 'render_loyalty_meta_box'),
            'shop_order',
            'side',
            'high'
        );
        
        // For HPOS (High-Performance Order Storage)
        add_meta_box(
            'wc_analytics_customer_loyalty',
            __('Customer Loyalty Info', 'wc-analytics'),
            array($this, 'render_loyalty_meta_box'),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }
    
    /**
     * Render loyalty meta box
     */
    public function render_loyalty_meta_box($post_or_order_object) {
        global $wpdb;
        
        $order = ($post_or_order_object instanceof WP_Post) 
            ? wc_get_order($post_or_order_object->ID) 
            : $post_or_order_object;
        
        if (!$order) {
            echo '<p>' . __('Order not found.', 'wc-analytics') . '</p>';
            return;
        }
        
        $customer_id = $order->get_customer_id();
        $phone = $order->get_billing_phone();
        
        // Format phone number
        $formatted_phone = $this->format_phone_number($phone);
        
        // Get customer LTV data by phone
        $table_name = $wpdb->prefix . 'wc_analytics_customer_ltv';
        $ltv_data = null;
        
        if ($formatted_phone) {
            $ltv_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE customer_phone = %s",
                    $formatted_phone
                )
            );
        }
        
        // If not found by phone, try by customer ID
        if (!$ltv_data && $customer_id) {
            $ltv_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE customer_id = %d",
                    $customer_id
                )
            );
        }
        
        if ($ltv_data) {
            $this->display_loyalty_info($ltv_data, $formatted_phone);
        } else {
            echo '<div class="wc-analytics-no-data">';
            echo '<p>' . __('No loyalty data available yet.', 'wc-analytics') . '</p>';
            if ($formatted_phone) {
                echo '<p><small>' . __('Phone:', 'wc-analytics') . ' ' . esc_html($formatted_phone) . '</small></p>';
            }
            echo '<p><em>' . __('Complete this order to start tracking.', 'wc-analytics') . '</em></p>';
            echo '</div>';
        }
    }
    
    /**
     * Display loyalty information
     */
    private function display_loyalty_info($ltv_data, $phone) {
        require_once(WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php');
        $tiers = WC_Analytics_Loyalty_Settings::get_loyalty_tiers();
        
        // Find tier details
        $tier_name = ucfirst($ltv_data->customer_segment);
        $tier_color = '#000000';
        
        foreach ($tiers as $tier) {
            if (strtolower($tier['name']) === strtolower($ltv_data->customer_segment)) {
                $tier_name = $tier['name'];
                $tier_color = $tier['color'];
                break;
            }
        }
        
        ?>
        <style>
        .wc-analytics-loyalty-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            color: #fff;
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            margin: 10px 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .wc-analytics-loyalty-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .wc-analytics-loyalty-info p {
            margin: 8px 0;
            font-size: 13px;
        }
        .wc-analytics-loyalty-info strong {
            color: #2271b1;
        }
        .wc-analytics-loyalty-label {
            color: #666;
            font-size: 12px;
            margin-right: 5px;
        }
        .wc-analytics-no-data {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }
        </style>
        
        <div class="wc-analytics-loyalty-container">
            <div style="text-align: center;">
                <div class="wc-analytics-loyalty-badge" style="background-color: <?php echo esc_attr($tier_color); ?>;">
                    <?php echo esc_html($tier_name); ?> Customer
                </div>
            </div>
            
            <div class="wc-analytics-loyalty-info">
                <p>
                    <span class="wc-analytics-loyalty-label"><?php _e('Total Orders:', 'wc-analytics'); ?></span>
                    <strong><?php echo esc_html($ltv_data->total_orders); ?></strong>
                </p>
                <p>
                    <span class="wc-analytics-loyalty-label"><?php _e('Total Spent:', 'wc-analytics'); ?></span>
                    <strong><?php echo wc_price($ltv_data->total_spent); ?></strong>
                </p>
                <p>
                    <span class="wc-analytics-loyalty-label"><?php _e('Lifetime Value:', 'wc-analytics'); ?></span>
                    <strong><?php echo wc_price($ltv_data->lifetime_value); ?></strong>
                </p>
                <p>
                    <span class="wc-analytics-loyalty-label"><?php _e('Avg Order:', 'wc-analytics'); ?></span>
                    <strong><?php echo wc_price($ltv_data->average_order_value); ?></strong>
                </p>
                <p>
                    <span class="wc-analytics-loyalty-label"><?php _e('Last Order:', 'wc-analytics'); ?></span>
                    <strong><?php echo esc_html($ltv_data->days_since_last_order); ?> <?php _e('days ago', 'wc-analytics'); ?></strong>
                </p>
                <?php if ($phone): ?>
                <p>
                    <span class="wc-analytics-loyalty-label"><?php _e('Phone:', 'wc-analytics'); ?></span>
                    <strong><?php echo esc_html($phone); ?></strong>
                </p>
                <?php endif; ?>
                <p style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <small style="color: #666;">
                        <?php echo $ltv_data->is_active ? 
                            '<span style="color: #28a745;">● ' . __('Active Customer', 'wc-analytics') . '</span>' : 
                            '<span style="color: #dc3545;">● ' . __('Inactive Customer', 'wc-analytics') . '</span>'; 
                        ?>
                    </small>
                </p>
            </div>
        </div>
        <?php
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
}
