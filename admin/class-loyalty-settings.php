<?php
/**
 * Loyalty Settings Class
 * 
 * Handles loyalty tier settings and configuration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WC_Analytics_Loyalty_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'), 60);
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add settings submenu page
     */
    public function add_settings_page() {
        add_submenu_page(
            'wc-analytics-dashboard',
            __('Loyalty Settings', 'wc-analytics'),
            __('Loyalty Settings', 'wc-analytics'),
            'manage_woocommerce',
            'wc-analytics-loyalty-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wc_analytics_loyalty_settings', 'wc_analytics_loyalty_tiers');
        
        add_settings_section(
            'wc_analytics_loyalty_section',
            __('Loyalty Tier Configuration', 'wc-analytics'),
            array($this, 'section_callback'),
            'wc-analytics-loyalty-settings'
        );
        
        add_settings_field(
            'loyalty_tiers',
            __('Loyalty Tiers', 'wc-analytics'),
            array($this, 'loyalty_tiers_callback'),
            'wc-analytics-loyalty-settings',
            'wc_analytics_loyalty_section'
        );
    }
    
    /**
     * Section callback
     */
    public function section_callback() {
        echo '<p>' . __('Configure your customer loyalty tiers based on total number of orders.', 'wc-analytics') . '</p>';
    }
    
    /**
     * Get default loyalty tiers
     */
    public static function get_default_tiers() {
        return array(
            array(
                'name' => 'Bronze',
                'min_value' => 0,
                'max_value' => 5,
                'color' => '#CD7F32'
            ),
            array(
                'name' => 'Silver',
                'min_value' => 6,
                'max_value' => 10,
                'color' => '#C0C0C0'
            ),
            array(
                'name' => 'Gold',
                'min_value' => 11,
                'max_value' => 20,
                'color' => '#FFD700'
            ),
            array(
                'name' => 'Platinum',
                'min_value' => 21,
                'max_value' => 999999999,
                'color' => '#E5E4E2'
            )
        );
    }
    
    /**
     * Get loyalty tiers
     */
    public static function get_loyalty_tiers() {
        $tiers = get_option('wc_analytics_loyalty_tiers');
        
        if (empty($tiers)) {
            $tiers = self::get_default_tiers();
            update_option('wc_analytics_loyalty_tiers', $tiers);
        }
        
        return $tiers;
    }
    
    /**
     * Get customer tier by total orders
     */
    public static function get_customer_tier($total_orders) {
        $tiers = self::get_loyalty_tiers();
        
        foreach ($tiers as $tier) {
            if ($total_orders >= $tier['min_value'] && $total_orders <= $tier['max_value']) {
                return $tier['name'];
            }
        }
        
        return 'Bronze'; // Default tier
    }
    
    /**
     * Loyalty tiers callback
     */
    public function loyalty_tiers_callback() {
        $tiers = self::get_loyalty_tiers();
        ?>
        <div id="loyalty-tiers-container">
            <table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
                <thead>
                    <tr>
                        <th><?php _e('Tier Name', 'wc-analytics'); ?></th>
                        <th><?php _e('Min Orders', 'wc-analytics'); ?></th>
                        <th><?php _e('Max Orders', 'wc-analytics'); ?></th>
                        <th><?php _e('Color', 'wc-analytics'); ?></th>
                        <th><?php _e('Actions', 'wc-analytics'); ?></th>
                    </tr>
                </thead>
                <tbody id="loyalty-tiers-list">
                    <?php foreach ($tiers as $index => $tier): ?>
                    <tr class="tier-row">
                        <td>
                            <input type="text" 
                                   name="wc_analytics_loyalty_tiers[<?php echo $index; ?>][name]" 
                                   value="<?php echo esc_attr($tier['name']); ?>" 
                                   class="regular-text" 
                                   required />
                        </td>
                        <td>
                            <input type="number" 
                                   name="wc_analytics_loyalty_tiers[<?php echo $index; ?>][min_value]" 
                                   value="<?php echo esc_attr($tier['min_value']); ?>" 
                                   class="small-text" 
                                   min="0" 
                                   required />
                        </td>
                        <td>
                            <input type="number" 
                                   name="wc_analytics_loyalty_tiers[<?php echo $index; ?>][max_value]" 
                                   value="<?php echo esc_attr($tier['max_value']); ?>" 
                                   class="small-text" 
                                   min="0" 
                                   required />
                        </td>
                        <td>
                            <input type="color" 
                                   name="wc_analytics_loyalty_tiers[<?php echo $index; ?>][color]" 
                                   value="<?php echo esc_attr($tier['color']); ?>" />
                        </td>
                        <td>
                            <button type="button" class="button remove-tier"><?php _e('Remove', 'wc-analytics'); ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" id="add-tier" class="button button-secondary">
                    <?php _e('Add Tier', 'wc-analytics'); ?>
                </button>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let tierIndex = <?php echo count($tiers); ?>;
            
            $('#add-tier').on('click', function() {
                const newRow = `
                    <tr class="tier-row">
                        <td>
                            <input type="text" 
                                   name="wc_analytics_loyalty_tiers[${tierIndex}][name]" 
                                   value="" 
                                   class="regular-text" 
                                   required />
                        </td>
                        <td>
                            <input type="number" 
                                   name="wc_analytics_loyalty_tiers[${tierIndex}][min_value]" 
                                   value="0" 
                                   class="small-text" 
                                   min="0" 
                                   required />
                        </td>
                        <td>
                            <input type="number" 
                                   name="wc_analytics_loyalty_tiers[${tierIndex}][max_value]" 
                                   value="0" 
                                   class="small-text" 
                                   min="0" 
                                   required />
                        </td>
                        <td>
                            <input type="color" 
                                   name="wc_analytics_loyalty_tiers[${tierIndex}][color]" 
                                   value="#000000" />
                        </td>
                        <td>
                            <button type="button" class="button remove-tier"><?php _e('Remove', 'wc-analytics'); ?></button>
                        </td>
                    </tr>
                `;
                $('#loyalty-tiers-list').append(newRow);
                tierIndex++;
            });
            
            $(document).on('click', '.remove-tier', function() {
                if ($('.tier-row').length > 1) {
                    $(this).closest('tr').remove();
                } else {
                    alert('<?php _e('You must have at least one tier.', 'wc-analytics'); ?>');
                }
            });
        });
        </script>
        
        <style>
        .tier-row input[type="text"],
        .tier-row input[type="number"] {
            width: 100%;
        }
        .tier-row input[type="color"] {
            width: 50px;
            height: 30px;
            border: none;
            cursor: pointer;
        }
        </style>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_analytics_loyalty_settings');
                do_settings_sections('wc-analytics-loyalty-settings');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2><?php _e('How it Works', 'wc-analytics'); ?></h2>
                <p><?php _e('Loyalty tiers are automatically assigned to customers based on their total number of orders.', 'wc-analytics'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Set minimum and maximum order counts for each tier.', 'wc-analytics'); ?></li>
                    <li><?php _e('Customers are tracked by phone number (for guests) or user ID (for registered users).', 'wc-analytics'); ?></li>
                    <li><?php _e('Tiers are displayed on the order edit page.', 'wc-analytics'); ?></li>
                    <li><?php _e('Phone numbers are stored in international format (+880xxxxxxxxxx).', 'wc-analytics'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
