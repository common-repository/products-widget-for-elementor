<?php
/*
Plugin Name: Products Widget For Elementor
Plugin URI: https://github.com/IbrahimRumani/product-collection-widget
Description: A simple Elementor widget to display WooCommerce products in Elementor Page Builder
Version: 1.0.1
Requires at least: 5.5
Tested up to: 6.4.3
Requires PHP: 7.1
Author: InfoBahn
Author URI: https://infobahn.io?utm_source=wordpress&utm_medium=plugin_uri&utm_campaign=wc_elementor_plugin
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly to maintain security
}

/**
 * Check for WooCommerce and Elementor's activation before initializing the plugin.
 */
function ibtg_check_required_plugins() {
    // Checking if WooCommerce is active
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'ibtg_woocommerce_missing_notice');
        return;
    }

    // Checking if Elementor is active
    if (!in_array('elementor/elementor.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'ibtg_elementor_missing_notice');
        return;
    }

    // Require the main plugin file if all checks pass
    require_once plugin_dir_path(__FILE__) . 'widgets/product-collections.php';
}

/**
 * Admin notice for missing WooCommerce plugin
 */
function ibtg_woocommerce_missing_notice() {
    echo '<div class="notice notice-warning is-dismissible">
             <p>' . esc_html__('Product Collection Widget requires WooCommerce to be installed and active.', 'products-widget-for-elementor') . '</p>
         </div>';
}

/**
 * Admin notice for missing Elementor plugin
 */
function ibtg_elementor_missing_notice() {
    echo '<div class="notice notice-warning is-dismissible">
             <p>' . esc_html__('Product Collection Widget requires Elementor to be installed and active.', 'products-widget-for-elementor') . '</p>
         </div>';
}

// Hook into plugins_loaded to check for required plugins
add_action('plugins_loaded', 'ibtg_check_required_plugins');
