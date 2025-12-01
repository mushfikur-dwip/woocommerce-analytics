<?php
/**
 * Courier Performance View
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$date_range = WC_Analytics_Dashboard_Page::get_date_range();
$courier_summary = WC_Analytics_Courier_Manager::get_courier_summary($date_range['start'], $date_range['end']);
?>

<div class="wrap">
    <h1><?php _e('Courier Performance Analysis', 'woocommerce-analytics'); ?></h1>
    
    <?php WC_Analytics_Dashboard_Page::render_date_filter('wc-analytics-courier-performance'); ?>
    
    <!-- Export Button -->
    <p>
        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wc_analytics_export_courier&start_date=' . $date_range['start'] . '&end_date=' . $date_range['end']), 'wc_analytics_export_csv'); ?>" class="button button-secondary">
            <?php _e('Export to CSV', 'woocommerce-analytics'); ?>
        </a>
    </p>
    
    <!-- Courier Performance Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Courier Name', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Total Deliveries', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Avg Delivery Time', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Avg Delay', 'woocommerce-analytics'); ?></th>
                <th><?php _e('On-Time Delivery', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Delivered', 'woocommerce-analytics'); ?></th>
                <th><?php _e('In Transit', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Pending', 'woocommerce-analytics'); ?></th>
                <th><?php _e('Rating', 'woocommerce-analytics'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($courier_summary): ?>
                <?php foreach ($courier_summary as $courier): ?>
                    <?php if (empty($courier->courier_name)) continue; ?>
                    <?php
                    // Calculate rating (1-5 stars based on on-time percentage)
                    $rating = round(($courier->on_time_percentage / 100) * 5, 1);
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($courier->courier_name); ?></strong></td>
                        <td><?php echo number_format($courier->total_deliveries); ?></td>
                        <td>
                            <?php echo number_format($courier->avg_delivery_time, 1); ?> 
                            <?php _e('days', 'woocommerce-analytics'); ?>
                        </td>
                        <td>
                            <?php if ($courier->avg_delay > 0): ?>
                                <span style="color: #dc3232;">
                                    +<?php echo number_format($courier->avg_delay, 1); ?> 
                                    <?php _e('days', 'woocommerce-analytics'); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #46b450;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color: <?php echo $courier->on_time_percentage >= 80 ? '#46b450' : ($courier->on_time_percentage >= 60 ? '#f56e28' : '#dc3232'); ?>;">
                                <?php echo number_format($courier->on_time_percentage, 1); ?>%
                            </strong>
                            <br><small><?php echo number_format($courier->on_time_count); ?> / <?php echo number_format($courier->total_deliveries); ?></small>
                        </td>
                        <td>
                            <span style="color: #46b450;">
                                <?php echo number_format($courier->delivered_count); ?>
                            </span>
                        </td>
                        <td>
                            <span style="color: #f56e28;">
                                <?php echo number_format($courier->in_transit_count); ?>
                            </span>
                        </td>
                        <td>
                            <span style="color: #999;">
                                <?php echo number_format($courier->pending_count); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <?php
                                $full_stars = floor($rating);
                                $half_star = ($rating - $full_stars) >= 0.5;
                                
                                for ($i = 0; $i < $full_stars; $i++) {
                                    echo '<span style="color: #f1c40f; font-size: 16px;">★</span>';
                                }
                                if ($half_star) {
                                    echo '<span style="color: #f1c40f; font-size: 16px;">☆</span>';
                                }
                                for ($i = $full_stars + ($half_star ? 1 : 0); $i < 5; $i++) {
                                    echo '<span style="color: #ddd; font-size: 16px;">★</span>';
                                }
                                ?>
                                <span style="margin-left: 5px; color: #666;"><?php echo number_format($rating, 1); ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">
                        <?php _e('No courier performance data available for the selected date range.', 'woocommerce-analytics'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Performance Chart -->
    <?php if ($courier_summary): ?>
    <div style="background: #fff; padding: 20px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3><?php _e('Courier On-Time Performance Comparison', 'woocommerce-analytics'); ?></h3>
        <canvas id="courierPerformanceChart" style="max-height: 400px;"></canvas>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var ctx = document.getElementById('courierPerformanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($courier_summary as $courier): ?>
                        <?php if (empty($courier->courier_name)) continue; ?>
                        '<?php echo esc_js($courier->courier_name); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: '<?php _e('On-Time Delivery %', 'woocommerce-analytics'); ?>',
                    data: [
                        <?php foreach ($courier_summary as $courier): ?>
                            <?php if (empty($courier->courier_name)) continue; ?>
                            <?php echo $courier->on_time_percentage; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        <?php foreach ($courier_summary as $courier): ?>
                            <?php if (empty($courier->courier_name)) continue; ?>
                            '<?php echo $courier->on_time_percentage >= 80 ? "rgba(70, 180, 80, 0.7)" : ($courier->on_time_percentage >= 60 ? "rgba(245, 110, 40, 0.7)" : "rgba(220, 50, 50, 0.7)"); ?>',
                        <?php endforeach; ?>
                    ],
                    borderColor: [
                        <?php foreach ($courier_summary as $courier): ?>
                            <?php if (empty($courier->courier_name)) continue; ?>
                            '<?php echo $courier->on_time_percentage >= 80 ? "rgba(70, 180, 80, 1)" : ($courier->on_time_percentage >= 60 ? "rgba(245, 110, 40, 1)" : "rgba(220, 50, 50, 1)"); ?>',
                        <?php endforeach; ?>
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
    </script>
    <?php endif; ?>
    
    <!-- Help Text -->
    <div class="notice notice-info" style="margin-top: 20px;">
        <p><strong><?php _e('How to Track Courier Performance:', 'woocommerce-analytics'); ?></strong></p>
        <ul>
            <li><?php _e('Go to any WooCommerce order and find the "Courier Tracking & Performance" metabox', 'woocommerce-analytics'); ?></li>
            <li><?php _e('Enter the courier name, tracking number, and dispatch date', 'woocommerce-analytics'); ?></li>
            <li><?php _e('Set the estimated delivery date for performance tracking', 'woocommerce-analytics'); ?></li>
            <li><?php _e('When the order is completed, the actual delivery date is automatically recorded', 'woocommerce-analytics'); ?></li>
            <li><?php _e('Performance metrics are calculated based on actual vs estimated delivery times', 'woocommerce-analytics'); ?></li>
        </ul>
    </div>
</div>
