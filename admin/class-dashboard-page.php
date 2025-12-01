<?php
/**
 * Dashboard Page Class
 * 
 * Handles all admin page rendering
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Dashboard_Page {
    
    /**
     * Render main dashboard
     */
    public static function render_dashboard() {
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-analytics'));
        }
        
        include WC_ANALYTICS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render SKU profits page
     */
    public static function render_sku_profits() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-analytics'));
        }
        
        include WC_ANALYTICS_PLUGIN_DIR . 'admin/views/sku-profits.php';
    }
    
    /**
     * Render customer LTV page
     */
    public static function render_customer_ltv() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-analytics'));
        }
        
        include WC_ANALYTICS_PLUGIN_DIR . 'admin/views/customer-ltv.php';
    }
    
    /**
     * Render marketing ROI page
     */
    public static function render_marketing_roi() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-analytics'));
        }
        
        include WC_ANALYTICS_PLUGIN_DIR . 'admin/views/marketing-roi.php';
    }
    
    /**
     * Render courier performance page
     */
    public static function render_courier_performance() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-analytics'));
        }
        
        include WC_ANALYTICS_PLUGIN_DIR . 'admin/views/courier-performance.php';
    }
    
    /**
     * Get date range from request
     */
    public static function get_date_range() {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        return array(
            'start' => $start_date,
            'end' => $end_date
        );
    }
    
    /**
     * Render date filter form
     */
    public static function render_date_filter($page) {
        $date_range = self::get_date_range();
        ?>
        <form method="get" class="wc-analytics-date-filter" style="margin: 20px 0;">
            <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
            <label for="start_date"><?php _e('From:', 'woocommerce-analytics'); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($date_range['start']); ?>">
            
            <label for="end_date"><?php _e('To:', 'woocommerce-analytics'); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($date_range['end']); ?>">
            
            <button type="submit" class="button button-primary"><?php _e('Filter', 'woocommerce-analytics'); ?></button>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page)); ?>" class="button"><?php _e('Reset', 'woocommerce-analytics'); ?></a>
        </form>
        <?php
    }
    
    /**
     * Export to CSV
     */
    public static function export_csv($data, $filename, $headers) {
        // Security check
        check_admin_referer('wc_analytics_export_csv');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Unauthorized', 'woocommerce-analytics'));
        }
        
        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // UTF-8 BOM for Excel
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // Output headers
        fputcsv($output, $headers);
        
        // Output data
        foreach ($data as $row) {
            fputcsv($output, (array) $row);
        }
        
        fclose($output);
        exit;
    }
}

// Register AJAX handlers
add_action('admin_post_wc_analytics_export_profits', 'wc_analytics_export_profits_csv');
add_action('admin_post_wc_analytics_export_ltv', 'wc_analytics_export_ltv_csv');
add_action('admin_post_wc_analytics_export_roi', 'wc_analytics_export_roi_csv');
add_action('admin_post_wc_analytics_export_courier', 'wc_analytics_export_courier_csv');

/**
 * Export SKU profits to CSV
 */
function wc_analytics_export_profits_csv() {
    $date_range = WC_Analytics_Dashboard_Page::get_date_range();
    $data = WC_Analytics_SKU_Manager::get_profit_data(array(
        'start_date' => $date_range['start'],
        'end_date' => $date_range['end'],
        'limit' => 10000
    ));
    
    $headers = array('Product ID', 'SKU', 'Product Name', 'Cost Price', 'Selling Price', 'Profit', 'Margin %', 'Quantity', 'Order Date');
    
    WC_Analytics_Dashboard_Page::export_csv($data, 'sku-profits-' . date('Y-m-d') . '.csv', $headers);
}

/**
 * Export customer LTV to CSV
 */
function wc_analytics_export_ltv_csv() {
    $data = WC_Analytics_LTV_Calculator::get_top_customers(array('limit' => 10000));
    
    $headers = array('Customer ID', 'Email', 'Name', 'Total Orders', 'Total Spent', 'Lifetime Value', 'Segment', 'Last Order Date');
    
    WC_Analytics_Dashboard_Page::export_csv($data, 'customer-ltv-' . date('Y-m-d') . '.csv', $headers);
}

/**
 * Export marketing ROI to CSV
 */
function wc_analytics_export_roi_csv() {
    $date_range = WC_Analytics_Dashboard_Page::get_date_range();
    $data = WC_Analytics_Attribution_Tracker::get_channel_performance($date_range['start'], $date_range['end']);
    
    $headers = array('Source', 'Medium', 'Campaign', 'Conversions', 'Revenue', 'Profit', 'Spend', 'ROI %');
    
    WC_Analytics_Dashboard_Page::export_csv($data, 'marketing-roi-' . date('Y-m-d') . '.csv', $headers);
}

/**
 * Export courier performance to CSV
 */
function wc_analytics_export_courier_csv() {
    $date_range = WC_Analytics_Dashboard_Page::get_date_range();
    $data = WC_Analytics_Courier_Manager::get_courier_summary($date_range['start'], $date_range['end']);
    
    $headers = array('Courier', 'Total Deliveries', 'Avg Delivery Time', 'On Time %', 'Delivered', 'In Transit', 'Pending');
    
    WC_Analytics_Dashboard_Page::export_csv($data, 'courier-performance-' . date('Y-m-d') . '.csv', $headers);
}
