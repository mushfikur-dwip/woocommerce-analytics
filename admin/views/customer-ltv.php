<?php
/**
 * Customer LTV View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$segment = isset($_GET['segment']) ? sanitize_text_field($_GET['segment']) : null;
$customers = WC_Analytics_LTV_Calculator::get_top_customers(array(
    'segment' => $segment,
    'limit' => 100
));

$summary = WC_Analytics_LTV_Calculator::get_ltv_summary();

// Get loyalty tiers
require_once(WC_ANALYTICS_PLUGIN_DIR . 'admin/class-loyalty-settings.php');
$loyalty_tiers = WC_Analytics_Loyalty_Settings::get_loyalty_tiers();
?>

<div class="wrap">
    <h1><?php _e('Customer Lifetime Value', 'woocommerce-analytics'); ?></h1>
    
    <!-- Segment Filter -->
    <div style="margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=wc-analytics-customer-ltv'); ?>" class="button <?php echo !$segment ? 'button-primary' : ''; ?>">
            <?php _e('All', 'woocommerce-analytics'); ?>
        </a>
        <?php foreach (array_reverse($loyalty_tiers) as $tier): 
            $tier_slug = strtolower($tier['name']);
            $count_key = $tier_slug . '_customers';
            $count = $summary->$count_key ?? 0;
        ?>
            <a href="<?php echo admin_url('admin.php?page=wc-analytics-customer-ltv&segment=' . urlencode($tier_slug)); ?>" 
               class="button <?php echo $segment === $tier_slug ? 'button-primary' : ''; ?>"
               style="<?php echo $segment === $tier_slug ? 'background-color: ' . esc_attr($tier['color']) . '; border-color: ' . esc_attr($tier['color']) . ';' : ''; ?>">
                <?php printf('%s (%d)', esc_html($tier['name']), $count); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Total Customers', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($summary->total_customers ?? 0); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Active Customers', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo number_format($summary->active_customers ?? 0); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Total LTV', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold;"><?php echo wc_price($summary->total_ltv ?? 0); ?></div>
        </div>
        <div style="background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="color: #666; font-size: 12px;"><?php _e('Average LTV', 'woocommerce-analytics'); ?></div>
            <div style="font-size: 24px; font-weight: bold;"><?php echo wc_price($summary->average_ltv ?? 0); ?></div>
        </div>
    </div>
    
    <!-- Export Button -->
    <p>
        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wc_analytics_export_ltv'), 'wc_analytics_export_csv'); ?>" class="button button-secondary">
            <?php _e('Export to CSV', 'woocommerce-analytics'); ?>
        </a>
    </p>
    
    <!-- Customers Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Customer', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Email', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Segment', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Orders', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Total Spent', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Lifetime Value', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Avg Order', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Last Order', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Status', 'woocommerce-analytics'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($customers): ?>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><strong><?php echo esc_html($customer->customer_name); ?></strong></td>
                        <td><?php echo esc_html($customer->customer_email); ?></td>
                        <td>
                            <?php
                            // Get tier color dynamically
                            $tier_color = '#666';
                            foreach ($loyalty_tiers as $tier) {
                                if (strtolower($tier['name']) === strtolower($customer->customer_segment)) {
                                    $tier_color = $tier['color'];
                                    break;
                                }
                            }
                            ?>
                            <span style="background: <?php echo esc_attr($tier_color); ?>; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">
                                <?php echo esc_html(ucfirst($customer->customer_segment)); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($customer->total_orders); ?></td>
                        <td><?php echo wc_price($customer->total_spent); ?></td>
                        <td><strong><?php echo wc_price($customer->lifetime_value); ?></strong></td>
                        <td><?php echo wc_price($customer->average_order_value); ?></td>
                        <td>
                            <?php echo $customer->last_order_date ? date_i18n(get_option('date_format'), strtotime($customer->last_order_date)) : '-'; ?>
                            <br><small><?php echo $customer->days_since_last_order; ?> <?php _e('days ago', 'woocommerce-analytics'); ?></small>
                        </td>
                        <td>
                            <?php if ($customer->is_active): ?>
                                <span style="color: #46b450;">●</span> <?php _e('Active', 'woocommerce-analytics'); ?>
                            <?php else: ?>
                                <span style="color: #dc3232;">●</span> <?php _e('Inactive', 'woocommerce-analytics'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">
                        <?php _e('No customer data available.', 'woocommerce-analytics'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
