<?php
/**
 * Main Dashboard View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$date_range = WC_Analytics_Dashboard_Page::get_date_range();

// Get summary data
$profit_summary = WC_Analytics_SKU_Manager::get_profit_summary($date_range['start'], $date_range['end']);
$ltv_summary = WC_Analytics_LTV_Calculator::get_ltv_summary();
$top_channels = WC_Analytics_Attribution_Tracker::get_top_channels($date_range['start'], $date_range['end'], 5);
$courier_summary = WC_Analytics_Courier_Manager::get_courier_summary($date_range['start'], $date_range['end']);
?>

<div class="wrap wc-analytics-dashboard">
    <h1><?php _e('WooCommerce Performance Analytics Dashboard', 'woocommerce-analytics'); ?></h1>
    
    <?php WC_Analytics_Dashboard_Page::render_date_filter('wc-analytics-dashboard'); ?>
    
    <!-- Summary Cards -->
    <div class="wc-analytics-summary-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <!-- Total Revenue Card -->
        <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php _e('Total Revenue', 'woocommerce-analytics'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #0073aa;">
                <?php echo wc_price($profit_summary->total_revenue ?? 0); ?>
            </div>
            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                <?php printf(__('%d orders', 'woocommerce-analytics'), $profit_summary->total_orders ?? 0); ?>
            </p>
        </div>
        
        <!-- Total Profit Card -->
        <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php _e('Total Profit', 'woocommerce-analytics'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #46b450;">
                <?php echo wc_price($profit_summary->total_profit ?? 0); ?>
            </div>
            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                <?php printf(__('%.2f%% margin', 'woocommerce-analytics'), $profit_summary->average_margin ?? 0); ?>
            </p>
        </div>
        
        <!-- Total Customers Card -->
        <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php _e('Total Customers', 'woocommerce-analytics'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #9b51e0;">
                <?php echo number_format($ltv_summary->total_customers ?? 0); ?>
            </div>
            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                <?php printf(__('%d active', 'woocommerce-analytics'), $ltv_summary->active_customers ?? 0); ?>
            </p>
        </div>
        
        <!-- Average LTV Card -->
        <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php _e('Average LTV', 'woocommerce-analytics'); ?></h3>
            <div style="font-size: 32px; font-weight: bold; color: #f56e28;">
                <?php echo wc_price($ltv_summary->average_ltv ?? 0); ?>
            </div>
            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                <?php printf(__('%.1f avg orders', 'woocommerce-analytics'), $ltv_summary->average_orders ?? 0); ?>
            </p>
        </div>
        
    </div>
    
    <!-- Charts Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px; margin: 30px 0;">
        
        <!-- Revenue & Profit Chart -->
        <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3><?php _e('Revenue & Profit Trend', 'woocommerce-analytics'); ?></h3>
            <canvas id="revenueProfitChart" style="max-height: 300px;"></canvas>
        </div>
        
        <!-- Top Channels Chart -->
        <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3><?php _e('Top Marketing Channels', 'woocommerce-analytics'); ?></h3>
            <canvas id="channelsChart" style="max-height: 300px;"></canvas>
        </div>
        
    </div>
    
    <!-- Quick Links -->
    <div class="wc-analytics-quick-links" style="margin: 30px 0;">
        <h2><?php _e('Quick Access', 'woocommerce-analytics'); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            
            <a href="<?php echo admin_url('admin.php?page=wc-analytics-sku-profits'); ?>" class="button button-primary button-large" style="text-align: center;">
                <?php _e('View SKU Profits', 'woocommerce-analytics'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=wc-analytics-customer-ltv'); ?>" class="button button-primary button-large" style="text-align: center;">
                <?php _e('View Customer LTV', 'woocommerce-analytics'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=wc-analytics-courier-performance'); ?>" class="button button-primary button-large" style="text-align: center;">
                <?php _e('View Courier Performance', 'woocommerce-analytics'); ?>
            </a>
            
        </div>
    </div>
    
    <!-- Top Couriers Table -->
    <?php if ($courier_summary): ?>
    <div class="wc-analytics-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
        <h3><?php _e('Courier Performance Summary', 'woocommerce-analytics'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Courier', 'woocommerce-analytics'); ?></th>
                    <th><?php _e('Total Deliveries', 'woocommerce-analytics'); ?></th>
                    <th><?php _e('Avg Delivery Time', 'woocommerce-analytics'); ?></th>
                    <th><?php _e('On Time %', 'woocommerce-analytics'); ?></th>
                    <th><?php _e('Status', 'woocommerce-analytics'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courier_summary as $courier): ?>
                    <?php if (empty($courier->courier_name)) continue; ?>
                    <tr>
                        <td><strong><?php echo esc_html($courier->courier_name); ?></strong></td>
                        <td><?php echo number_format($courier->total_deliveries); ?></td>
                        <td><?php echo number_format($courier->avg_delivery_time, 1); ?> <?php _e('days', 'woocommerce-analytics'); ?></td>
                        <td>
                            <span style="color: <?php echo $courier->on_time_percentage >= 80 ? '#46b450' : '#dc3232'; ?>;">
                                <?php echo number_format($courier->on_time_percentage, 1); ?>%
                            </span>
                        </td>
                        <td>
                            <?php printf(
                                __('%d delivered, %d in transit, %d pending', 'woocommerce-analytics'),
                                $courier->delivered_count,
                                $courier->in_transit_count,
                                $courier->pending_count
                            ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Revenue & Profit Chart
    var revenueProfitCtx = document.getElementById('revenueProfitChart').getContext('2d');
    new Chart(revenueProfitCtx, {
        type: 'bar',
        data: {
            labels: ['<?php echo $date_range['start']; ?> - <?php echo $date_range['end']; ?>'],
            datasets: [{
                label: '<?php _e('Revenue', 'woocommerce-analytics'); ?>',
                data: [<?php echo $profit_summary->total_revenue ?? 0; ?>],
                backgroundColor: 'rgba(0, 115, 170, 0.5)',
                borderColor: 'rgba(0, 115, 170, 1)',
                borderWidth: 1
            }, {
                label: '<?php _e('Profit', 'woocommerce-analytics'); ?>',
                data: [<?php echo $profit_summary->total_profit ?? 0; ?>],
                backgroundColor: 'rgba(70, 180, 80, 0.5)',
                borderColor: 'rgba(70, 180, 80, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Top Channels Chart
    <?php if ($top_channels): ?>
    var channelsCtx = document.getElementById('channelsChart').getContext('2d');
    new Chart(channelsCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($top_channels as $channel): ?>
                    '<?php echo esc_js($channel->utm_source ?? 'Direct'); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($top_channels as $channel): ?>
                        <?php echo $channel->revenue; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    'rgba(0, 115, 170, 0.7)',
                    'rgba(70, 180, 80, 0.7)',
                    'rgba(245, 110, 40, 0.7)',
                    'rgba(155, 81, 224, 0.7)',
                    'rgba(220, 50, 50, 0.7)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>
