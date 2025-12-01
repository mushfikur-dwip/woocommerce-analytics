<?php
/**
 * Courier Manager Class
 * 
 * Tracks courier performance and delivery times
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Courier_Manager {
    
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
        // Add courier metabox to orders
        add_action('add_meta_boxes', array($this, 'add_courier_metabox'));
        add_action('save_post_shop_order', array($this, 'save_courier_data'), 10, 2);
        
        // Hook into order status changes
        add_action('woocommerce_order_status_changed', array($this, 'track_delivery_status'), 10, 4);
        
        // Initialize tracking on new orders
        add_action('woocommerce_new_order', array($this, 'initialize_courier_tracking'));
    }
    
    /**
     * Add courier tracking metabox
     */
    public function add_courier_metabox() {
        add_meta_box(
            'wc_analytics_courier_tracking',
            __('Courier Tracking & Performance', 'woocommerce-analytics'),
            array($this, 'render_courier_metabox'),
            'shop_order',
            'side',
            'default'
        );
        
        // Also add to HPOS orders if enabled
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && 
            Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            add_meta_box(
                'wc_analytics_courier_tracking',
                __('Courier Tracking & Performance', 'woocommerce-analytics'),
                array($this, 'render_courier_metabox'),
                'woocommerce_page_wc-orders',
                'side',
                'default'
            );
        }
    }
    
    /**
     * Render courier tracking metabox
     */
    public function render_courier_metabox($post_or_order) {
        // Get order object
        if (is_a($post_or_order, 'WP_Post')) {
            $order_id = $post_or_order->ID;
            $order = wc_get_order($order_id);
        } else {
            $order = $post_or_order;
            $order_id = $order->get_id();
        }
        
        if (!$order) {
            return;
        }
        
        // Get saved values
        $courier_name = get_post_meta($order_id, '_wc_analytics_courier_name', true);
        $tracking_number = get_post_meta($order_id, '_wc_analytics_tracking_number', true);
        $dispatch_date = get_post_meta($order_id, '_wc_analytics_dispatch_date', true);
        $estimated_delivery = get_post_meta($order_id, '_wc_analytics_estimated_delivery', true);
        $actual_delivery = get_post_meta($order_id, '_wc_analytics_actual_delivery', true);
        $notes = get_post_meta($order_id, '_wc_analytics_courier_notes', true);
        
        // Nonce for security
        wp_nonce_field('wc_analytics_courier_nonce', 'wc_analytics_courier_nonce');
        
        ?>
        <div class="wc-analytics-courier-fields">
            <p>
                <label for="wc_analytics_courier_name">
                    <strong><?php _e('Courier Name:', 'woocommerce-analytics'); ?></strong>
                </label>
                <select id="wc_analytics_courier_name" name="wc_analytics_courier_name" style="width: 100%;">
                    <option value=""><?php _e('Select Courier', 'woocommerce-analytics'); ?></option>
                    <?php
                    $couriers = array(
                        'Pathao' => 'Pathao',
                        'Redx' => 'Redx',
                        'Steadfast' => 'Steadfast',
                        'eCourier' => 'eCourier',
                        'Paperfly' => 'Paperfly',
                        'Sundarban' => 'Sundarban',
                        'SA Paribahan' => 'SA Paribahan',
                        'Other' => 'Other'
                    );
                    foreach ($couriers as $value => $label) {
                        echo '<option value="' . esc_attr($value) . '" ' . selected($courier_name, $value, false) . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </p>
            
            <p>
                <label for="wc_analytics_tracking_number">
                    <strong><?php _e('Tracking Number:', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="text" 
                    id="wc_analytics_tracking_number" 
                    name="wc_analytics_tracking_number" 
                    value="<?php echo esc_attr($tracking_number); ?>" 
                    style="width: 100%;"
                    placeholder="<?php _e('Enter tracking number', 'woocommerce-analytics'); ?>"
                />
            </p>
            
            <p>
                <label for="wc_analytics_dispatch_date">
                    <strong><?php _e('Dispatch Date:', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="datetime-local" 
                    id="wc_analytics_dispatch_date" 
                    name="wc_analytics_dispatch_date" 
                    value="<?php echo esc_attr($dispatch_date); ?>" 
                    style="width: 100%;"
                />
            </p>
            
            <p>
                <label for="wc_analytics_estimated_delivery">
                    <strong><?php _e('Estimated Delivery:', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="datetime-local" 
                    id="wc_analytics_estimated_delivery" 
                    name="wc_analytics_estimated_delivery" 
                    value="<?php echo esc_attr($estimated_delivery); ?>" 
                    style="width: 100%;"
                />
            </p>
            
            <p>
                <label for="wc_analytics_actual_delivery">
                    <strong><?php _e('Actual Delivery:', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="datetime-local" 
                    id="wc_analytics_actual_delivery" 
                    name="wc_analytics_actual_delivery" 
                    value="<?php echo esc_attr($actual_delivery); ?>" 
                    style="width: 100%;"
                />
            </p>
            
            <p>
                <label for="wc_analytics_courier_notes">
                    <strong><?php _e('Notes:', 'woocommerce-analytics'); ?></strong>
                </label>
                <textarea 
                    id="wc_analytics_courier_notes" 
                    name="wc_analytics_courier_notes" 
                    rows="3"
                    style="width: 100%;"
                    placeholder="<?php _e('Delivery notes or issues', 'woocommerce-analytics'); ?>"
                ><?php echo esc_textarea($notes); ?></textarea>
            </p>
            
            <?php
            // Display delivery performance if available
            if ($dispatch_date && $actual_delivery) {
                $dispatch_timestamp = strtotime($dispatch_date);
                $delivery_timestamp = strtotime($actual_delivery);
                $estimated_timestamp = $estimated_delivery ? strtotime($estimated_delivery) : 0;
                
                $delivery_days = round(($delivery_timestamp - $dispatch_timestamp) / (60 * 60 * 24), 1);
                $on_time = false;
                
                if ($estimated_timestamp && $delivery_timestamp <= $estimated_timestamp) {
                    $on_time = true;
                }
                ?>
                <div style="background: #f0f0f1; padding: 10px; margin-top: 10px; border-radius: 4px;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Delivery Time:', 'woocommerce-analytics'); ?></strong> 
                        <?php echo $delivery_days; ?> <?php _e('days', 'woocommerce-analytics'); ?>
                    </p>
                    <?php if ($estimated_timestamp): ?>
                    <p style="margin: 0;">
                        <strong><?php _e('Status:', 'woocommerce-analytics'); ?></strong> 
                        <span style="color: <?php echo $on_time ? '#46b450' : '#dc3232'; ?>;">
                            <?php echo $on_time ? __('On Time', 'woocommerce-analytics') : __('Delayed', 'woocommerce-analytics'); ?>
                        </span>
                    </p>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Save courier data
     */
    public function save_courier_data($order_id, $post) {
        // Security checks
        if (!isset($_POST['wc_analytics_courier_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['wc_analytics_courier_nonce'], 'wc_analytics_courier_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_shop_order', $order_id)) {
            return;
        }
        
        // Save courier fields
        if (isset($_POST['wc_analytics_courier_name'])) {
            update_post_meta($order_id, '_wc_analytics_courier_name', sanitize_text_field($_POST['wc_analytics_courier_name']));
        }
        
        if (isset($_POST['wc_analytics_tracking_number'])) {
            update_post_meta($order_id, '_wc_analytics_tracking_number', sanitize_text_field($_POST['wc_analytics_tracking_number']));
        }
        
        if (isset($_POST['wc_analytics_dispatch_date'])) {
            update_post_meta($order_id, '_wc_analytics_dispatch_date', sanitize_text_field($_POST['wc_analytics_dispatch_date']));
        }
        
        if (isset($_POST['wc_analytics_estimated_delivery'])) {
            update_post_meta($order_id, '_wc_analytics_estimated_delivery', sanitize_text_field($_POST['wc_analytics_estimated_delivery']));
        }
        
        if (isset($_POST['wc_analytics_actual_delivery'])) {
            update_post_meta($order_id, '_wc_analytics_actual_delivery', sanitize_text_field($_POST['wc_analytics_actual_delivery']));
        }
        
        if (isset($_POST['wc_analytics_courier_notes'])) {
            update_post_meta($order_id, '_wc_analytics_courier_notes', sanitize_textarea_field($_POST['wc_analytics_courier_notes']));
        }
        
        // Update database record
        $this->update_courier_tracking($order_id);
    }
    
    /**
     * Initialize courier tracking for new order
     */
    public function initialize_courier_tracking($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('courier_performance');
        
        // Check if already exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE order_id = %d", $order_id)
        );
        
        if (!$exists) {
            $wpdb->insert(
                $table_name,
                array(
                    'order_id' => $order_id,
                    'courier_name' => '',
                    'shipping_method' => $order->get_shipping_method(),
                    'customer_city' => $order->get_shipping_city(),
                    'customer_state' => $order->get_shipping_state(),
                    'order_placed_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'shipping_cost' => $order->get_shipping_total(),
                    'delivery_status' => 'pending',
                    'date_created' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s')
            );
        }
    }
    
    /**
     * Update courier tracking in database
     */
    public function update_courier_tracking($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('courier_performance');
        
        // Get courier data from order meta
        $courier_name = get_post_meta($order_id, '_wc_analytics_courier_name', true);
        $tracking_number = get_post_meta($order_id, '_wc_analytics_tracking_number', true);
        $dispatch_date = get_post_meta($order_id, '_wc_analytics_dispatch_date', true);
        $estimated_delivery = get_post_meta($order_id, '_wc_analytics_estimated_delivery', true);
        $actual_delivery = get_post_meta($order_id, '_wc_analytics_actual_delivery', true);
        $notes = get_post_meta($order_id, '_wc_analytics_courier_notes', true);
        
        // Calculate delivery metrics
        $delivery_time_days = null;
        $delay_days = 0;
        $on_time_delivery = 0;
        $delivery_status = 'pending';
        
        if ($dispatch_date && $actual_delivery) {
            $dispatch_timestamp = strtotime($dispatch_date);
            $delivery_timestamp = strtotime($actual_delivery);
            $delivery_time_days = round(($delivery_timestamp - $dispatch_timestamp) / (60 * 60 * 24), 1);
            $delivery_status = 'delivered';
            
            if ($estimated_delivery) {
                $estimated_timestamp = strtotime($estimated_delivery);
                if ($delivery_timestamp <= $estimated_timestamp) {
                    $on_time_delivery = 1;
                } else {
                    $delay_days = round(($delivery_timestamp - $estimated_timestamp) / (60 * 60 * 24), 1);
                }
            }
        } elseif ($dispatch_date) {
            $delivery_status = 'in_transit';
        }
        
        // Check if record exists
        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table_name WHERE order_id = %d", $order_id)
        );
        
        $data = array(
            'courier_name' => $courier_name,
            'tracking_number' => $tracking_number,
            'dispatch_date' => $dispatch_date ? date('Y-m-d H:i:s', strtotime($dispatch_date)) : null,
            'estimated_delivery_date' => $estimated_delivery ? date('Y-m-d H:i:s', strtotime($estimated_delivery)) : null,
            'actual_delivery_date' => $actual_delivery ? date('Y-m-d H:i:s', strtotime($actual_delivery)) : null,
            'delivery_time_days' => $delivery_time_days,
            'delay_days' => $delay_days,
            'on_time_delivery' => $on_time_delivery,
            'delivery_status' => $delivery_status,
            'notes' => $notes
        );
        
        $format = array('%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%s', '%s');
        
        if ($exists) {
            $wpdb->update(
                $table_name,
                $data,
                array('order_id' => $order_id),
                $format,
                array('%d')
            );
        } else {
            // Initialize first
            $this->initialize_courier_tracking($order_id);
            // Then update
            $wpdb->update(
                $table_name,
                $data,
                array('order_id' => $order_id),
                $format,
                array('%d')
            );
        }
    }
    
    /**
     * Track delivery status changes
     */
    public function track_delivery_status($order_id, $old_status, $new_status, $order) {
        // Auto-set delivery date when order is completed
        if ($new_status === 'completed') {
            $actual_delivery = get_post_meta($order_id, '_wc_analytics_actual_delivery', true);
            
            if (!$actual_delivery) {
                update_post_meta($order_id, '_wc_analytics_actual_delivery', current_time('Y-m-d\TH:i'));
                $this->update_courier_tracking($order_id);
            }
        }
    }
    
    /**
     * Get courier performance data
     */
    public static function get_courier_performance($args = array()) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('courier_performance');
        
        $defaults = array(
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-d'),
            'courier_name' => null,
            'delivery_status' => null,
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $where[] = $wpdb->prepare('order_placed_date >= %s', $args['start_date']);
        $where[] = $wpdb->prepare('order_placed_date <= %s', $args['end_date'] . ' 23:59:59');
        
        if ($args['courier_name']) {
            $where[] = $wpdb->prepare('courier_name = %s', $args['courier_name']);
        }
        
        if ($args['delivery_status']) {
            $where[] = $wpdb->prepare('delivery_status = %s', $args['delivery_status']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY order_placed_date DESC LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $args['limit'], $args['offset'])
        );
    }
    
    /**
     * Get courier summary statistics
     */
    public static function get_courier_summary($start_date, $end_date, $courier_name = null) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('courier_performance');
        
        $where = array();
        $where[] = $wpdb->prepare('order_placed_date >= %s', $start_date);
        $where[] = $wpdb->prepare('order_placed_date <= %s', $end_date . ' 23:59:59');
        
        if ($courier_name) {
            $where[] = $wpdb->prepare('courier_name = %s', $courier_name);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT 
            courier_name,
            COUNT(*) as total_deliveries,
            AVG(delivery_time_days) as avg_delivery_time,
            AVG(delay_days) as avg_delay,
            SUM(on_time_delivery) as on_time_count,
            (SUM(on_time_delivery) / COUNT(*)) * 100 as on_time_percentage,
            COUNT(CASE WHEN delivery_status = 'delivered' THEN 1 END) as delivered_count,
            COUNT(CASE WHEN delivery_status = 'in_transit' THEN 1 END) as in_transit_count,
            COUNT(CASE WHEN delivery_status = 'pending' THEN 1 END) as pending_count
        FROM $table_name
        WHERE $where_clause
        GROUP BY courier_name
        ORDER BY total_deliveries DESC";
        
        return $wpdb->get_results($query);
    }
}
