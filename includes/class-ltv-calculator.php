<?php
/**
 * LTV Calculator Class
 * 
 * Calculates and tracks customer lifetime value
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_LTV_Calculator {
    
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
        // Hook into order completion
        add_action('woocommerce_payment_complete', array($this, 'update_customer_ltv'));
        add_action('woocommerce_order_status_completed', array($this, 'update_customer_ltv'));
        
        // Hook into new customer registration
        add_action('woocommerce_created_customer', array($this, 'initialize_customer_ltv'));
        
        // Add LTV info to user profile
        add_action('show_user_profile', array($this, 'display_ltv_on_profile'));
        add_action('edit_user_profile', array($this, 'display_ltv_on_profile'));
    }
    
    /**
     * Initialize LTV tracking for new customer
     */
    public function initialize_customer_ltv($customer_id) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('customer_ltv');
        
        $customer = new WC_Customer($customer_id);
        
        // Check if customer already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE customer_id = %d", $customer_id)
        );
        
        if (!$exists) {
            $wpdb->insert(
                $table_name,
                array(
                    'customer_id' => $customer_id,
                    'customer_email' => $customer->get_email(),
                    'customer_name' => $customer->get_first_name() . ' ' . $customer->get_last_name(),
                    'date_calculated' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Update customer LTV when order is completed/paid
     */
    public function update_customer_ltv($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Skip refunds - they don't have billing info
        if (is_a($order, 'WC_Order_Refund')) {
            return;
        }
        
        $customer_id = $order->get_customer_id();
        $phone = $this->format_phone_number($order->get_billing_phone());
        
        // Track by phone number (for both guest and registered customers)
        if ($phone) {
            $this->calculate_and_save_ltv_by_phone($phone, $customer_id);
        }
        
        // Also track by customer ID if registered
        if ($customer_id) {
            $this->calculate_and_save_ltv($customer_id);
        }
    }
    
    /**
     * Format phone number to international format (+880)
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
     * Calculate and save LTV by phone number (for guest customers)
     */
    private function calculate_and_save_ltv_by_phone($phone, $customer_id = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_analytics_customer_ltv';
        
        // Get local format (without +880)
        $local_phone = str_replace('+880', '0', $phone);
        $numeric_only = str_replace('+880', '', $phone);
        
        // Get all orders with this phone number (search multiple formats)
        $args = array(
            'limit' => -1,
            'status' => array('completed', 'processing'),
        );
        
        // Get all orders and filter by phone manually for better matching
        $all_orders = wc_get_orders(array(
            'limit' => -1,
            'status' => array('completed', 'processing'),
        ));
        
        $orders = array();
        foreach ($all_orders as $order) {
            $billing_phone = $order->get_billing_phone();
            $formatted_billing = $this->format_phone_number($billing_phone);
            
            // Match if phones are the same in any format
            if ($formatted_billing === $phone || 
                $billing_phone === $phone || 
                $billing_phone === $local_phone ||
                preg_replace('/[^0-9]/', '', $billing_phone) === $numeric_only) {
                $orders[] = $order;
            }
        }
        
        if (empty($orders)) {
            return;
        }
        
        // Calculate metrics
        $total_orders = count($orders);
        $total_spent = 0;
        $first_order_date = null;
        $last_order_date = null;
        $customer_email = '';
        $customer_name = '';
        
        foreach ($orders as $order) {
            $total_spent += $order->get_total();
            
            if (empty($customer_email)) {
                $customer_email = $order->get_billing_email();
            }
            if (empty($customer_name)) {
                $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            }
            
            $date_created = $order->get_date_created();
            if ($date_created) {
                if (!$first_order_date) {
                    $first_order_date = $date_created->date('Y-m-d H:i:s');
                }
                $last_order_date = $date_created->date('Y-m-d H:i:s');
            }
        }
        
        $average_order_value = $total_orders > 0 ? $total_spent / $total_orders : 0;
        
        // Calculate total profit from SKU table  
        $order_ids = array_map(function($order) { return $order->get_id(); }, $orders);
        $order_ids_placeholder = implode(',', array_fill(0, count($order_ids), '%d'));
        
        $total_profit = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(profit_amount * quantity) 
                FROM {$wpdb->prefix}wc_analytics_sku_costs 
                WHERE order_id IN ($order_ids_placeholder)",
                $order_ids
            )
        );
        
        $total_profit = $total_profit ? floatval($total_profit) : 0;
        $average_profit_per_order = $total_orders > 0 ? $total_profit / $total_orders : 0;
        
        // Calculate days since last order
        $days_since_last_order = 0;
        if ($last_order_date) {
            $last_date = new DateTime($last_order_date);
            $now = new DateTime();
            $days_since_last_order = $now->diff($last_date)->days;
        }
        
        // Calculate order frequency (orders per month)
        $order_frequency = 0;
        if ($first_order_date && $last_order_date && $first_order_date !== $last_order_date) {
            $first_date = new DateTime($first_order_date);
            $last_date = new DateTime($last_order_date);
            $months = max(1, $first_date->diff($last_date)->m + ($first_date->diff($last_date)->y * 12));
            $order_frequency = $total_orders / $months;
        }
        
        // Determine customer segment using custom tiers
        require_once(WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php');
        $customer_segment = WC_Analytics_Loyalty_Settings::get_customer_tier($total_spent);
        
        // Predicted LTV
        $predicted_ltv = $total_spent * 1.5;
        
        // Check if record exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE customer_phone = %s",
                $phone
            )
        );
        
        $data = array(
            'customer_id' => $customer_id,
            'customer_email' => $customer_email,
            'customer_phone' => $phone,
            'customer_name' => $customer_name,
            'total_orders' => $total_orders,
            'total_spent' => $total_spent,
            'total_profit' => $total_profit,
            'average_order_value' => $average_order_value,
            'average_profit_per_order' => $average_profit_per_order,
            'first_order_date' => $first_order_date,
            'last_order_date' => $last_order_date,
            'lifetime_value' => $total_spent,
            'predicted_ltv' => $predicted_ltv,
            'days_since_last_order' => $days_since_last_order,
            'order_frequency' => $order_frequency,
            'customer_segment' => strtolower($customer_segment),
            'is_active' => $days_since_last_order < 90 ? 1 : 0,
            'date_calculated' => current_time('mysql')
        );
        
        if ($existing) {
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id),
                array('%d', '%s', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%d', '%f', '%s', '%d', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table_name,
                $data,
                array('%d', '%s', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%d', '%f', '%s', '%d', '%s')
            );
        }
    }
    
    /**
     * Calculate and save customer LTV
     */
    public function calculate_and_save_ltv($customer_id) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('customer_ltv');
        $sku_table = WC_Analytics_Database_Manager::get_table_name('sku_costs');
        
        $customer = new WC_Customer($customer_id);
        
        // Get all customer orders
        $orders = wc_get_orders(array(
            'customer_id' => $customer_id,
            'status' => array('wc-completed', 'wc-processing'),
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        ));
        
        if (empty($orders)) {
            return;
        }
        
        // Calculate metrics
        $total_orders = count($orders);
        $total_spent = 0;
        $first_order_date = null;
        $last_order_date = null;
        
        foreach ($orders as $order) {
            $total_spent += $order->get_total();
            
            $date_created = $order->get_date_created();
            if ($date_created) {
                if (!$first_order_date) {
                    $first_order_date = $date_created->date('Y-m-d H:i:s');
                }
                $last_order_date = $date_created->date('Y-m-d H:i:s');
            }
        }
        
        $average_order_value = $total_orders > 0 ? $total_spent / $total_orders : 0;
        
        // Calculate total profit from SKU table
        $total_profit = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(profit_amount * quantity) 
                FROM $sku_table 
                WHERE order_id IN (
                    SELECT ID FROM {$wpdb->prefix}posts 
                    WHERE post_type = 'shop_order' 
                    AND post_author = %d
                    OR ID IN (
                        SELECT post_id FROM {$wpdb->prefix}postmeta 
                        WHERE meta_key = '_customer_user' 
                        AND meta_value = %d
                    )
                )",
                $customer_id,
                $customer_id
            )
        );
        
        if ($total_profit === null) {
            $total_profit = 0;
        }
        
        $average_profit_per_order = $total_orders > 0 ? $total_profit / $total_orders : 0;
        
        // Calculate days since last order
        $last_order_timestamp = strtotime($last_order_date);
        $days_since_last_order = floor((time() - $last_order_timestamp) / (60 * 60 * 24));
        
        // Calculate order frequency (orders per month)
        if ($first_order_date && $last_order_date) {
            $first_timestamp = strtotime($first_order_date);
            $days_active = floor(($last_order_timestamp - $first_timestamp) / (60 * 60 * 24));
            $months_active = $days_active > 0 ? $days_active / 30 : 1;
            $order_frequency = $total_orders / $months_active;
        } else {
            $order_frequency = 0;
        }
        
        // Determine customer segment using custom tiers
        require_once(WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php');
        $customer_segment = WC_Analytics_Loyalty_Settings::get_customer_tier($total_spent);
        
        // Predict future LTV (simple model: current LTV * predicted future orders)
        $predicted_ltv = $total_spent;
        if ($order_frequency > 0 && $days_since_last_order < 180) {
            // Active customer - predict 12 more months
            $predicted_future_orders = $order_frequency * 12;
            $predicted_ltv = $total_spent + ($average_order_value * $predicted_future_orders);
        }
        
        // Determine if customer is active
        $is_active = $days_since_last_order < 180 ? 1 : 0;
        
        // Check if record exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE customer_id = %d", $customer_id)
        );
        
        $data = array(
            'customer_id' => $customer_id,
            'customer_email' => $customer->get_email(),
            'customer_name' => $customer->get_first_name() . ' ' . $customer->get_last_name(),
            'total_orders' => $total_orders,
            'total_spent' => $total_spent,
            'total_profit' => $total_profit,
            'average_order_value' => $average_order_value,
            'average_profit_per_order' => $average_profit_per_order,
            'first_order_date' => $first_order_date,
            'last_order_date' => $last_order_date,
            'lifetime_value' => $total_spent,
            'predicted_ltv' => $predicted_ltv,
            'days_since_last_order' => $days_since_last_order,
            'order_frequency' => $order_frequency,
            'customer_segment' => $customer_segment,
            'is_active' => $is_active,
            'currency' => get_woocommerce_currency(),
            'date_calculated' => current_time('mysql')
        );
        
        $format = array('%d', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%f', '%f', '%d', '%f', '%s', '%d', '%s', '%s');
        
        if ($exists) {
            $wpdb->update(
                $table_name,
                $data,
                array('customer_id' => $customer_id),
                $format,
                array('%d')
            );
        } else {
            $wpdb->insert($table_name, $data, $format);
        }
    }
    
    /**
     * Determine customer segment based on spending
     */
    private function get_customer_segment($total_spent, $total_orders) {
        // Customize these thresholds based on your business
        if ($total_spent >= 5000 || $total_orders >= 20) {
            return 'platinum';
        } elseif ($total_spent >= 2000 || $total_orders >= 10) {
            return 'gold';
        } elseif ($total_spent >= 500 || $total_orders >= 5) {
            return 'silver';
        } else {
            return 'bronze';
        }
    }
    
    /**
     * Get top customers by LTV
     */
    public static function get_top_customers($args = array()) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('customer_ltv');
        
        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'segment' => null,
            'is_active' => null,
            'orderby' => 'lifetime_value',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if ($args['segment']) {
            $where[] = $wpdb->prepare('customer_segment = %s', $args['segment']);
        }
        
        if ($args['is_active'] !== null) {
            $where[] = $wpdb->prepare('is_active = %d', $args['is_active']);
        }
        
        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $args['limit'], $args['offset'])
        );
    }
    
    /**
     * Get LTV summary statistics
     */
    public static function get_ltv_summary() {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('customer_ltv');
        
        // Get custom tiers
        require_once(WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php');
        $tiers = WC_Analytics_Loyalty_Settings::get_loyalty_tiers();
        
        // Build dynamic tier counting
        $tier_counts = '';
        foreach ($tiers as $tier) {
            $tier_slug = strtolower($tier['name']);
            $tier_counts .= "COUNT(CASE WHEN customer_segment = '{$tier_slug}' THEN 1 END) as {$tier_slug}_customers,\n            ";
        }
        
        $query = "SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_customers,
            SUM(lifetime_value) as total_ltv,
            AVG(lifetime_value) as average_ltv,
            AVG(total_orders) as average_orders,
            AVG(average_order_value) as average_order_value,
            {$tier_counts}
            SUM(total_profit) as total_profit
        FROM $table_name";
        
        return $wpdb->get_row($query);
    }
    
    /**
     * Recalculate all customer LTVs (for manual refresh)
     */
    public static function recalculate_all_ltvs() {
        $customers = get_users(array(
            'role' => 'customer',
            'fields' => 'ID'
        ));
        
        $instance = self::get_instance();
        
        foreach ($customers as $customer_id) {
            $instance->calculate_and_save_ltv($customer_id);
        }
        
        return count($customers);
    }
    
    /**
     * Display LTV information on user profile page
     */
    public function display_ltv_on_profile($user) {
        // Only show for customers
        if (!in_array('customer', $user->roles) && !in_array('administrator', $user->roles)) {
            return;
        }
        
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('customer_ltv');
        
        // Get LTV data
        $ltv_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE customer_id = %d", $user->ID)
        );
        
        // If no data, try to calculate
        if (!$ltv_data) {
            $this->calculate_and_save_ltv($user->ID);
            $ltv_data = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_name WHERE customer_id = %d", $user->ID)
            );
        }
        
        ?>
        <h2><?php _e('Customer Analytics', 'woocommerce-analytics'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Customer Segment', 'woocommerce-analytics'); ?></th>
                <td>
                    <?php
                    if ($ltv_data) {
                        $segment_colors = array(
                            'platinum' => '#9b51e0',
                            'gold' => '#f1c40f',
                            'silver' => '#95a5a6',
                            'bronze' => '#d35400'
                        );
                        $color = $segment_colors[$ltv_data->customer_segment] ?? '#666';
                        ?>
                        <span style="background: <?php echo esc_attr($color); ?>; color: #fff; padding: 5px 15px; border-radius: 3px; font-size: 14px; font-weight: bold; text-transform: uppercase; display: inline-block;">
                            <?php echo esc_html(ucfirst($ltv_data->customer_segment)); ?>
                        </span>
                        <?php
                    } else {
                        echo '<span style="color: #999;">' . __('No data available', 'woocommerce-analytics') . '</span>';
                    }
                    ?>
                </td>
            </tr>
            
            <?php if ($ltv_data): ?>
            <tr>
                <th><?php _e('Lifetime Value', 'woocommerce-analytics'); ?></th>
                <td>
                    <strong style="font-size: 18px; color: #0073aa;">
                        <?php echo wc_price($ltv_data->lifetime_value); ?>
                    </strong>
                </td>
            </tr>
            
            <tr>
                <th><?php _e('Total Orders', 'woocommerce-analytics'); ?></th>
                <td><?php echo number_format($ltv_data->total_orders); ?></td>
            </tr>
            
            <tr>
                <th><?php _e('Total Spent', 'woocommerce-analytics'); ?></th>
                <td><?php echo wc_price($ltv_data->total_spent); ?></td>
            </tr>
            
            <tr>
                <th><?php _e('Average Order Value', 'woocommerce-analytics'); ?></th>
                <td><?php echo wc_price($ltv_data->average_order_value); ?></td>
            </tr>
            
            <tr>
                <th><?php _e('Customer Status', 'woocommerce-analytics'); ?></th>
                <td>
                    <?php if ($ltv_data->is_active): ?>
                        <span style="color: #46b450; font-weight: bold;">● <?php _e('Active', 'woocommerce-analytics'); ?></span>
                    <?php else: ?>
                        <span style="color: #dc3232; font-weight: bold;">● <?php _e('Inactive', 'woocommerce-analytics'); ?></span>
                    <?php endif; ?>
                    <br>
                    <small style="color: #666;">
                        <?php printf(__('Last order: %d days ago', 'woocommerce-analytics'), $ltv_data->days_since_last_order); ?>
                    </small>
                </td>
            </tr>
            
            <tr>
                <th><?php _e('First Order', 'woocommerce-analytics'); ?></th>
                <td>
                    <?php echo $ltv_data->first_order_date ? date_i18n(get_option('date_format'), strtotime($ltv_data->first_order_date)) : '-'; ?>
                </td>
            </tr>
            
            <tr>
                <th><?php _e('Last Order', 'woocommerce-analytics'); ?></th>
                <td>
                    <?php echo $ltv_data->last_order_date ? date_i18n(get_option('date_format'), strtotime($ltv_data->last_order_date)) : '-'; ?>
                </td>
            </tr>
            
            <tr>
                <th><?php _e('Predicted LTV', 'woocommerce-analytics'); ?></th>
                <td>
                    <?php echo wc_price($ltv_data->predicted_ltv); ?>
                    <br>
                    <small style="color: #666;">
                        <?php _e('Based on purchase patterns', 'woocommerce-analytics'); ?>
                    </small>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
}
