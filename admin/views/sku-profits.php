<?php
/**
 * SKU Profits View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$date_range = WC_Analytics_Dashboard_Page::get_date_range();
$profits_data = WC_Analytics_SKU_Manager::get_profit_data(array(
    'start_date' => $date_range['start'],
    'end_date' => $date_range['end'],
    'limit' => 100
));

$summary = WC_Analytics_SKU_Manager::get_profit_summary($date_range['start'], $date_range['end']);
?>

<div class="wrap">
    <h1><?php _e('SKU Profit Analysis', 'woocommerce-analytics'); ?></h1>
    
    <?php WC_Analytics_Dashboard_Page::render_date_filter('wc-analytics-sku-profits'); ?>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Total Revenue', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold;"><?php echo wc_price($summary->total_revenue ?? 0); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Total Profit', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo wc_price($summary->total_profit ?? 0); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Average Margin', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($summary->average_margin ?? 0, 2); ?>%</div>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Items Sold', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($summary->total_items_sold ?? 0); ?></div>
        </div>
    </div>
    
    <!-- Export Button -->
    <p>
        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wc_analytics_export_profits&start_date=' . $date_range['start'] . '&end_date=' . $date_range['end']), 'wc_analytics_export_csv'); ?>" class="button button-secondary">
            <?php _e('Export to CSV', 'woocommerce-analytics'); ?>
        </a>
    </p>
    
    <!-- Profits Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('SKU', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Product', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Cost', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Price', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Profit', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Margin', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Qty', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Total Profit', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Order Date', 'woocommerce-analytics'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($profits_data): ?>
                <?php foreach ($profits_data as $row): ?>
                    <tr>
                        <td><code><?php echo esc_html($row->sku); ?></code></td>
                        <td>
                            <strong><?php echo esc_html($row->product_name); ?></strong><br>
                            <small><?php printf(__('Order #%d', 'woocommerce-analytics'), $row->order_id); ?></small>
                        </td>
                        <td><?php echo wc_price($row->cost_price); ?></td>
                        <td><?php echo wc_price($row->selling_price); ?></td>
                        <td>
                            <span style="color: <?php echo $row->profit_amount >= 0 ? '#46b450' : '#dc3232'; ?>;">
                                <?php echo wc_price($row->profit_amount); ?>
                            </span>
                        </td>
                        <td>
                            <span style="color: <?php echo $row->profit_margin >= 0 ? '#46b450' : '#dc3232'; ?>;">
                                <?php echo number_format($row->profit_margin, 2); ?>%
                            </span>
                        </td>
                        <td><?php echo $row->quantity; ?></td>
                        <td>
                            <strong style="color: <?php echo ($row->profit_amount * $row->quantity) >= 0 ? '#46b450' : '#dc3232'; ?>;">
                                <?php echo wc_price($row->profit_amount * $row->quantity); ?>
                            </strong>
                        </td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($row->order_date)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">
                        <?php _e('No profit data available for the selected date range.', 'woocommerce-analytics'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
