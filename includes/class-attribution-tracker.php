<?php
/**
 * Attribution Tracker Class
 * 
 * Tracks marketing attribution and calculates ROI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Attribution_Tracker {
    
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
        // Hook into checkout to save attribution
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_order_attribution'), 10, 1);
        
        // Hook into order completion
        add_action('woocommerce_payment_complete', array($this, 'track_conversion'));
    }
    
    /**
     * Save attribution data to order meta
     */
    public function save_order_attribution($order_id) {
        if (!$order_id) {
            return;
        }
        
        // Get attribution from cookies (set by tracking.js)
        $utm_source = isset($_COOKIE['wc_analytics_utm_source']) ? sanitize_text_field($_COOKIE['wc_analytics_utm_source']) : '';
        $utm_medium = isset($_COOKIE['wc_analytics_utm_medium']) ? sanitize_text_field($_COOKIE['wc_analytics_utm_medium']) : '';
        $utm_campaign = isset($_COOKIE['wc_analytics_utm_campaign']) ? sanitize_text_field($_COOKIE['wc_analytics_utm_campaign']) : '';
        $utm_term = isset($_COOKIE['wc_analytics_utm_term']) ? sanitize_text_field($_COOKIE['wc_analytics_utm_term']) : '';
        $utm_content = isset($_COOKIE['wc_analytics_utm_content']) ? sanitize_text_field($_COOKIE['wc_analytics_utm_content']) : '';
        $referrer = isset($_COOKIE['wc_analytics_referrer']) ? esc_url_raw($_COOKIE['wc_analytics_referrer']) : '';
        $landing_page = isset($_COOKIE['wc_analytics_landing']) ? esc_url_raw($_COOKIE['wc_analytics_landing']) : '';
        
        // Save as order meta
        if ($utm_source) update_post_meta($order_id, '_wc_analytics_utm_source', $utm_source);
        if ($utm_medium) update_post_meta($order_id, '_wc_analytics_utm_medium', $utm_medium);
        if ($utm_campaign) update_post_meta($order_id, '_wc_analytics_utm_campaign', $utm_campaign);
        if ($utm_term) update_post_meta($order_id, '_wc_analytics_utm_term', $utm_term);
        if ($utm_content) update_post_meta($order_id, '_wc_analytics_utm_content', $utm_content);
        if ($referrer) update_post_meta($order_id, '_wc_analytics_referrer', $referrer);
        if ($landing_page) update_post_meta($order_id, '_wc_analytics_landing_page', $landing_page);
    }
    
    /**
     * Track conversion when order is completed
     */
    public function track_conversion($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('attribution');
        
        // Check if already tracked
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE order_id = %d", $order_id)
        );
        
        if ($exists) {
            return;
        }
        
        // Get attribution from order meta
        $utm_source = get_post_meta($order_id, '_wc_analytics_utm_source', true);
        $utm_medium = get_post_meta($order_id, '_wc_analytics_utm_medium', true);
        $utm_campaign = get_post_meta($order_id, '_wc_analytics_utm_campaign', true);
        $utm_term = get_post_meta($order_id, '_wc_analytics_utm_term', true);
        $utm_content = get_post_meta($order_id, '_wc_analytics_utm_content', true);
        $referrer = get_post_meta($order_id, '_wc_analytics_referrer', true);
        $landing_page = get_post_meta($order_id, '_wc_analytics_landing_page', true);
        
        // Get order profit from SKU table
        $sku_table = WC_Analytics_Database_Manager::get_table_name('sku_costs');
        $order_profit = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(profit_amount * quantity) FROM $sku_table WHERE order_id = %d",
                $order_id
            )
        );
        
        if ($order_profit === null) {
            $order_profit = 0;
        }
        
        // Detect device type and browser from user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $device_type = $this->detect_device_type($user_agent);
        $browser = $this->detect_browser($user_agent);
        
        // Insert attribution data
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'customer_id' => $order->get_customer_id(),
                'utm_source' => $utm_source,
                'utm_medium' => $utm_medium,
                'utm_campaign' => $utm_campaign,
                'utm_term' => $utm_term,
                'utm_content' => $utm_content,
                'referrer_url' => $referrer,
                'landing_page' => $landing_page,
                'device_type' => $device_type,
                'browser' => $browser,
                'order_total' => $order->get_total(),
                'order_profit' => $order_profit,
                'conversion_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                'date_created' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s')
        );
    }
    
    /**
     * Detect device type from user agent
     */
    private function detect_device_type($user_agent) {
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
            return 'tablet';
        }
        return 'desktop';
    }
    
    /**
     * Detect browser from user agent
     */
    private function detect_browser($user_agent) {
        if (preg_match('/edge/i', $user_agent)) {
            return 'Edge';
        } elseif (preg_match('/chrome/i', $user_agent)) {
            return 'Chrome';
        } elseif (preg_match('/safari/i', $user_agent)) {
            return 'Safari';
        } elseif (preg_match('/firefox/i', $user_agent)) {
            return 'Firefox';
        } elseif (preg_match('/msie|trident/i', $user_agent)) {
            return 'Internet Explorer';
        }
        return 'Other';
    }
    
    /**
     * Get attribution data for reports
     */
    public static function get_attribution_data($args = array()) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('attribution');
        
        $defaults = array(
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-d'),
            'utm_source' => null,
            'utm_campaign' => null,
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $where[] = $wpdb->prepare('conversion_date >= %s', $args['start_date']);
        $where[] = $wpdb->prepare('conversion_date <= %s', $args['end_date'] . ' 23:59:59');
        
        if ($args['utm_source']) {
            $where[] = $wpdb->prepare('utm_source = %s', $args['utm_source']);
        }
        
        if ($args['utm_campaign']) {
            $where[] = $wpdb->prepare('utm_campaign = %s', $args['utm_campaign']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY conversion_date DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $args['limit'], $args['offset'])
        );
    }
    
    /**
     * Get channel performance summary
     */
    public static function get_channel_performance($start_date, $end_date) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('attribution');
        
        $query = $wpdb->prepare(
            "SELECT 
                utm_source,
                utm_medium,
                utm_campaign,
                COUNT(*) as conversions,
                SUM(order_total) as revenue,
                SUM(order_profit) as profit,
                AVG(order_total) as avg_order_value,
                SUM(marketing_spend) as total_spend,
                CASE 
                    WHEN SUM(marketing_spend) > 0 THEN (SUM(order_profit) / SUM(marketing_spend)) * 100
                    ELSE 0
                END as roi_percentage
            FROM $table_name
            WHERE conversion_date >= %s AND conversion_date <= %s
            AND utm_source IS NOT NULL AND utm_source != ''
            GROUP BY utm_source, utm_medium, utm_campaign
            ORDER BY revenue DESC",
            $start_date,
            $end_date . ' 23:59:59'
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Update marketing spend for a campaign
     */
    public static function update_campaign_spend($campaign_data) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('attribution');
        
        // Update all orders from this campaign
        $wpdb->update(
            $table_name,
            array('marketing_spend' => $campaign_data['spend']),
            array(
                'utm_source' => $campaign_data['utm_source'],
                'utm_campaign' => $campaign_data['utm_campaign']
            ),
            array('%f'),
            array('%s', '%s')
        );
        
        // Recalculate ROI
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table_name 
                SET roi = CASE 
                    WHEN marketing_spend > 0 THEN (order_profit / marketing_spend) * 100
                    ELSE 0
                END
                WHERE utm_source = %s AND utm_campaign = %s",
                $campaign_data['utm_source'],
                $campaign_data['utm_campaign']
            )
        );
    }
    
    /**
     * Get top performing channels
     */
    public static function get_top_channels($start_date, $end_date, $limit = 10) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('attribution');
        
        $query = $wpdb->prepare(
            "SELECT 
                utm_source,
                COUNT(*) as conversions,
                SUM(order_total) as revenue,
                SUM(order_profit) as profit,
                SUM(marketing_spend) as spend
            FROM $table_name
            WHERE conversion_date >= %s 
            AND conversion_date <= %s
            AND utm_source IS NOT NULL 
            AND utm_source != ''
            GROUP BY utm_source
            ORDER BY revenue DESC
            LIMIT %d",
            $start_date,
            $end_date . ' 23:59:59',
            $limit
        );
        
        return $wpdb->get_results($query);
    }
}
