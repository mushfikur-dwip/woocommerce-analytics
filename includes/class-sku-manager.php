<?php
/**
 * SKU Manager Class
 * 
 * Handles product cost tracking and profit calculations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_SKU_Manager {
    
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
        // Add product metabox
        add_action('add_meta_boxes', array($this, 'add_product_cost_metabox'));
        add_action('save_post_product', array($this, 'save_product_cost_meta'), 10, 2);
        
        // Hook into order processing
        add_action('woocommerce_checkout_order_processed', array($this, 'process_order_profits'), 10, 3);
        add_action('woocommerce_payment_complete', array($this, 'finalize_order_profits'));
    }
    
    /**
     * Add product cost metabox
     */
    public function add_product_cost_metabox() {
        add_meta_box(
            'wc_analytics_product_costs',
            __('Product Costs & Profit Settings', 'woocommerce-analytics'),
            array($this, 'render_product_cost_metabox'),
            'product',
            'side',
            'default'
        );
    }
    
    /**
     * Render product cost metabox
     */
    public function render_product_cost_metabox($post) {
        // Get saved values
        $cost_price = get_post_meta($post->ID, '_wc_analytics_cost_price', true);
        $additional_costs = get_post_meta($post->ID, '_wc_analytics_additional_costs', true);
        $shipping_cost = get_post_meta($post->ID, '_wc_analytics_shipping_cost', true);
        
        // Nonce for security
        wp_nonce_field('wc_analytics_product_cost_nonce', 'wc_analytics_product_cost_nonce');
        
        ?>
        <div class="wc-analytics-cost-fields">
            <p>
                <label for="wc_analytics_cost_price">
                    <strong><?php _e('Cost Price (COGS):', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="wc_analytics_cost_price" 
                    name="wc_analytics_cost_price" 
                    value="<?php echo esc_attr($cost_price); ?>" 
                    style="width: 100%;"
                    placeholder="0.00"
                />
                <span class="description"><?php _e('Base cost of goods sold', 'woocommerce-analytics'); ?></span>
            </p>
            
            <p>
                <label for="wc_analytics_additional_costs">
                    <strong><?php _e('Additional Costs:', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="wc_analytics_additional_costs" 
                    name="wc_analytics_additional_costs" 
                    value="<?php echo esc_attr($additional_costs); ?>" 
                    style="width: 100%;"
                    placeholder="0.00"
                />
                <span class="description"><?php _e('Processing, packaging, etc.', 'woocommerce-analytics'); ?></span>
            </p>
            
            <p>
                <label for="wc_analytics_shipping_cost">
                    <strong><?php _e('Shipping Cost:', 'woocommerce-analytics'); ?></strong>
                </label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="wc_analytics_shipping_cost" 
                    name="wc_analytics_shipping_cost" 
                    value="<?php echo esc_attr($shipping_cost); ?>" 
                    style="width: 100%;"
                    placeholder="0.00"
                />
                <span class="description"><?php _e('Per-unit shipping cost', 'woocommerce-analytics'); ?></span>
            </p>
            
            <?php
            // Calculate and display profit margin if product has a price
            $product = wc_get_product($post->ID);
            if ($product && $product->get_price() && $cost_price) {
                $selling_price = floatval($product->get_price());
                $total_cost = floatval($cost_price) + floatval($additional_costs) + floatval($shipping_cost);
                $profit = $selling_price - $total_cost;
                $margin = $selling_price > 0 ? ($profit / $selling_price) * 100 : 0;
                ?>
                <div style="background: #f0f0f1; padding: 10px; margin-top: 10px; border-radius: 4px;">
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Selling Price:', 'woocommerce-analytics'); ?></strong> 
                        <?php echo wc_price($selling_price); ?>
                    </p>
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Total Cost:', 'woocommerce-analytics'); ?></strong> 
                        <?php echo wc_price($total_cost); ?>
                    </p>
                    <p style="margin: 0 0 5px 0;">
                        <strong><?php _e('Profit:', 'woocommerce-analytics'); ?></strong> 
                        <span style="color: <?php echo $profit >= 0 ? '#46b450' : '#dc3232'; ?>;">
                            <?php echo wc_price($profit); ?>
                        </span>
                    </p>
                    <p style="margin: 0;">
                        <strong><?php _e('Margin:', 'woocommerce-analytics'); ?></strong> 
                        <span style="color: <?php echo $margin >= 0 ? '#46b450' : '#dc3232'; ?>;">
                            <?php echo number_format($margin, 2); ?>%
                        </span>
                    </p>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Save product cost meta
     */
    public function save_product_cost_meta($post_id, $post) {
        // Security checks
        if (!isset($_POST['wc_analytics_product_cost_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['wc_analytics_product_cost_nonce'], 'wc_analytics_product_cost_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save cost fields
        if (isset($_POST['wc_analytics_cost_price'])) {
            update_post_meta($post_id, '_wc_analytics_cost_price', sanitize_text_field($_POST['wc_analytics_cost_price']));
        }
        
        if (isset($_POST['wc_analytics_additional_costs'])) {
            update_post_meta($post_id, '_wc_analytics_additional_costs', sanitize_text_field($_POST['wc_analytics_additional_costs']));
        }
        
        if (isset($_POST['wc_analytics_shipping_cost'])) {
            update_post_meta($post_id, '_wc_analytics_shipping_cost', sanitize_text_field($_POST['wc_analytics_shipping_cost']));
        }
    }
    
    /**
     * Process order profits when order is created
     */
    public function process_order_profits($order_id, $posted_data, $order) {
        if (!$order_id) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('sku_costs');
        
        // Process each order item
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            // Get product costs
            $cost_price = floatval(get_post_meta($product_id, '_wc_analytics_cost_price', true));
            $additional_costs = floatval(get_post_meta($product_id, '_wc_analytics_additional_costs', true));
            $shipping_cost = floatval(get_post_meta($product_id, '_wc_analytics_shipping_cost', true));
            
            // Calculate profit
            $selling_price = floatval($item->get_total()) / $item->get_quantity();
            $total_cost = $cost_price + $additional_costs + $shipping_cost;
            $profit_amount = $selling_price - $total_cost;
            $profit_margin = $selling_price > 0 ? ($profit_amount / $selling_price) * 100 : 0;
            
            // Get tax amount
            $tax_amount = floatval($item->get_total_tax()) / $item->get_quantity();
            
            // Insert into database
            $wpdb->insert(
                $table_name,
                array(
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'sku' => $product->get_sku() ? $product->get_sku() : '',
                    'product_name' => $product->get_name(),
                    'cost_price' => $total_cost,
                    'selling_price' => $selling_price,
                    'profit_amount' => $profit_amount,
                    'profit_margin' => $profit_margin,
                    'additional_costs' => $additional_costs,
                    'shipping_cost' => $shipping_cost,
                    'tax_amount' => $tax_amount,
                    'currency' => $order->get_currency(),
                    'order_id' => $order_id,
                    'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'quantity' => $item->get_quantity(),
                    'date_created' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%d', '%s', '%d', '%s')
            );
        }
    }
    
    /**
     * Finalize order profits after payment complete
     */
    public function finalize_order_profits($order_id) {
        // Additional processing if needed
        do_action('wc_analytics_order_profits_finalized', $order_id);
    }
    
    /**
     * Get profit data for reports
     */
    public static function get_profit_data($args = array()) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('sku_costs');
        
        $defaults = array(
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-d'),
            'product_id' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'profit_amount',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        $where[] = $wpdb->prepare('order_date >= %s', $args['start_date']);
        $where[] = $wpdb->prepare('order_date <= %s', $args['end_date'] . ' 23:59:59');
        
        if ($args['product_id']) {
            $where[] = $wpdb->prepare('product_id = %d', $args['product_id']);
        }
        
        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM $table_name WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $args['limit'], $args['offset'])
        );
    }
    
    /**
     * Get total profit summary
     */
    public static function get_profit_summary($start_date, $end_date) {
        global $wpdb;
        $table_name = WC_Analytics_Database_Manager::get_table_name('sku_costs');
        
        $query = $wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT order_id) as total_orders,
                SUM(quantity) as total_items_sold,
                SUM(selling_price * quantity) as total_revenue,
                SUM(cost_price * quantity) as total_costs,
                SUM(profit_amount * quantity) as total_profit,
                AVG(profit_margin) as average_margin
            FROM $table_name
            WHERE order_date >= %s AND order_date <= %s",
            $start_date,
            $end_date . ' 23:59:59'
        );
        
        return $wpdb->get_row($query);
    }
}
