<?php
/**
 * Marketing ROI View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$date_range = WC_Analytics_Dashboard_Page::get_date_range();
$channels = WC_Analytics_Attribution_Tracker::get_channel_performance($date_range['start'], $date_range['end']);
?>

<div class="wrap">
    <h1><?php _e('Marketing ROI Analysis', 'woocommerce-analytics'); ?></h1>
    
    <?php WC_Analytics_Dashboard_Page::render_date_filter('wc-analytics-marketing-roi'); ?>
    
    <!-- Export Button -->
    <p>
        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wc_analytics_export_roi&start_date=' . $date_range['start'] . '&end_date=' . $date_range['end']), 'wc_analytics_export_csv'); ?>" class="button button-secondary">
            <?php _e('Export to CSV', 'woocommerce-analytics'); ?>
        </a>
    </p>
    
    <!-- Channels Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Source', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Medium', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Campaign', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Conversions', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Revenue', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Profit', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Spend', 'woocommerce-analytics'); ?></th>
                <th><?php _e('ROI', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Cost per Conversion', 'woocommerce-analytics'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($channels): ?>
                <?php foreach ($channels as $channel): ?>
                    <?php
                    $cost_per_conversion = $channel->total_spend > 0 && $channel->conversions > 0 
                        ? $channel->total_spend / $channel->conversions 
                        : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($channel->utm_source ?? 'Direct'); ?></strong></td>
                        <td><?php echo esc_html($channel->utm_medium ?? '-'); ?></td>
                        <td><?php echo esc_html($channel->utm_campaign ?? '-'); ?></td>
                        <td><?php echo number_format($channel->conversions); ?></td>
                        <td><?php echo wc_price($channel->revenue); ?></td>
                        <td><?php echo wc_price($channel->profit); ?></td>
                        <td><?php echo wc_price($channel->total_spend); ?></td>
                        <td>
                            <?php if ($channel->total_spend > 0): ?>
                                <strong style="color: <?php echo $channel->roi_percentage >= 0 ? '#46b450' : '#dc3232'; ?>;">
                                    <?php echo number_format($channel->roi_percentage, 2); ?>%
                                </strong>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo wc_price($cost_per_conversion); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">
                        <?php _e('No marketing attribution data available for the selected date range.', 'woocommerce-analytics'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Help Text -->
    <div class="notice notice-info" style="margin-top: 20px;">
        <p><strong><?php _e('How Marketing Attribution Works:', 'woocommerce-analytics'); ?></strong></p>
        <ul>
            <li><?php _e('UTM parameters are automatically captured when customers visit your site with tracking links', 'woocommerce-analytics'); ?></li>
            <li><?php _e('Example URL: yoursite.com/?utm_source=facebook&utm_medium=cpc&utm_campaign=spring_sale', 'woocommerce-analytics'); ?></li>
            <li><?php _e('Marketing spend data needs to be manually updated in the database or via API integration', 'woocommerce-analytics'); ?></li>
            <li><?php _e('ROI is calculated as: (Profit / Spend) × 100', 'woocommerce-analytics'); ?></li>
        </ul>
    </div>
</div>
