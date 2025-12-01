# WooCommerce Performance Analytics

A comprehensive analytics plugin for WooCommerce that tracks profit per SKU, customer lifetime value (LTV), marketing channel ROI, and courier performance.

## Features

### 1. **Profit per SKU**

- Track cost of goods sold (COGS) per product
- Calculate profit margins automatically
- View profit analysis by product, date range, and category
- Export detailed profit reports to CSV

### 2. **Customer Lifetime Value (LTV)**

- Automatic LTV calculation on order completion
- Customer segmentation (Platinum, Gold, Silver, Bronze)
- Track total orders, average order value, and purchase frequency
- Identify active vs inactive customers
- Predict future customer value

### 3. **Marketing Channel ROI**

- Automatic UTM parameter tracking
- Attribution tracking for all orders
- ROI calculation per marketing channel
- Cost per conversion metrics
- Support for manual marketing spend input

### 4. **Courier Performance**

- Track delivery times by courier
- Monitor on-time delivery rates
- Identify delay sources and patterns
- Compare courier performance metrics
- Visual performance charts

## Installation

1. Download or clone this repository to your WordPress plugins directory:

   ```
   /wp-content/plugins/woocommerce-analytics/
   ```

2. Go to WordPress Admin → Plugins

3. Find "WooCommerce Performance Analytics" and click Activate

4. The plugin will automatically create necessary database tables

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Usage

### Setting Up Product Costs

1. Go to Products → Edit any product
2. Find the "Product Costs & Profit Settings" metabox in the sidebar
3. Enter:
   - Cost Price (COGS)
   - Additional Costs (packaging, processing)
   - Shipping Cost (per unit)
4. Profit margins will be calculated automatically

### Tracking Courier Performance

1. Go to WooCommerce → Orders
2. Open any order
3. Find the "Courier Tracking & Performance" metabox
4. Enter:
   - Courier Name (select from dropdown)
   - Tracking Number
   - Dispatch Date
   - Estimated Delivery Date
5. When order is completed, actual delivery date is recorded automatically
6. View courier performance reports in WC Analytics → Courier Performance

### UTM Tracking for Marketing Attribution

Marketing attribution is tracked automatically when customers visit your site with UTM parameters:

**Example URL:**

```
https://yoursite.com/?utm_source=facebook&utm_medium=cpc&utm_campaign=spring_sale
```

UTM parameters are stored in cookies and attributed to orders when customers complete checkout.

### Viewing Reports

Navigate to **WC Analytics** in the WordPress admin menu:

- **Dashboard** - Overview of all metrics with charts
- **SKU Profits** - Detailed profit analysis by product
- **Customer LTV** - Customer value rankings and segments
- **Marketing ROI** - Channel performance and ROI metrics
- **Courier Performance** - Delivery time and reliability statistics

### Exporting Data

All reports include "Export to CSV" buttons for downloading data in spreadsheet format.

## Database Tables

The plugin creates 4 custom tables:

1. `wp_wc_analytics_sku_costs` - Product costs and profit calculations
2. `wp_wc_analytics_customer_ltv` - Customer lifetime value data
3. `wp_wc_analytics_attribution` - Marketing attribution tracking
4. `wp_wc_analytics_courier_performance` - Courier delivery metrics

## Permissions

By default, the following user roles can access analytics:

- **Administrator** - Full access to all features
- **Shop Manager** - Full access to all features

You can customize permissions by modifying the capability checks in the code.

## Customization

### Customer Segments

Customer segments are based on spending thresholds. Edit `class-ltv-calculator.php`:

```php
private function get_customer_segment($total_spent, $total_orders) {
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
```

### Courier List

To add more couriers, edit `class-courier-manager.php` and update the `$couriers` array:

```php
$couriers = array(
    'Pathao' => 'Pathao',
    'Redx' => 'Redx',
    'Your Courier' => 'Your Courier',
    // Add more here
);
```

## Troubleshooting

### Data Not Showing

1. Ensure products have cost prices set
2. Complete at least one order after plugin activation
3. Check that WooCommerce orders are marked as "Completed" or "Processing"

### UTM Parameters Not Tracking

1. Clear browser cookies
2. Test with a new incognito/private window
3. Verify UTM parameters are in the URL when visiting the site

### Database Tables Not Created

1. Deactivate and reactivate the plugin
2. Check database user has CREATE TABLE permissions
3. Check WordPress debug log for errors

## Uninstallation

When you uninstall the plugin:

1. All custom database tables are dropped
2. All plugin options are deleted
3. Product cost meta data is preserved (can be manually deleted if needed)

## Security

The plugin follows WordPress security best practices:

- Nonce verification on all forms
- Capability checks before sensitive operations
- SQL injection prevention with prepared statements
- Input sanitization and output escaping
- CSRF protection

## Support

For issues, questions, or feature requests, please contact the plugin author or create an issue in the repository.

## Changelog

### Version 1.0.0 (December 2025)

- Initial release
- SKU profit tracking
- Customer LTV calculations
- Marketing attribution
- Courier performance monitoring
- Interactive dashboards with Chart.js
- CSV export functionality

## Credits

Developed by Mushfikur Rahman

## License

GPL v2 or later
