<?php
/**
 * Plugin Name: WooCommerce--Innovative crypto payment plugin, accept Bitcoin, ETH, USDT, &50 more tokens. Incentive& Growth.
 * Plugin URI: https://dashboard.aurpay.net
 * Description: Expand customer base with the most secure&fast crypto payment plugin, non-custodail & no fraud/chargeback, less than 1% fees, 50+ cryptos. Invoice, payment link.
 * Version: 1.0.1
 * Author: Aurpay
 * Author URI: https://dashboard.aurpay.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: Expand customer base with the most secure&fast crypto payment plugin, non-custodail & no fraud/chargeback, less than 1% fees, 50+ cryptos. Invoice, payment link.
 * Tags: crypto payment, erc20, cryptocurrency, e-commerce, bitcoin, bitcoin lighting network, ethereum, crypto pay, smooth withdrawals, cryptocurrency payments, low commission, safest account, pay with meta mask, payment button, crypto invoice
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

defined( 'ABSPATH' ) || exit;

if (!defined('WC_AURPAY')) {
    define('WC_AURPAY', 'WC_AURPAY');
} else {
    return;
}

define('WC_Aurpay_VERSION', '1.0.1');
define('WC_Aurpay_ID', 'aurpay_payment_gateway_wc' /*'aurpay'*/);
define('WC_Aurpay_DIR', rtrim(plugin_dir_path(__FILE__), '/'));
define('WC_Aurpay_URL', rtrim(plugin_dir_url(__FILE__), '/'));

add_action('plugins_loaded', 'aurpay_payment_gateway_wc_init');
add_action('wp_enqueue_scripts','aurpay_add_styles');

add_filter( 'allowed_redirect_hosts', 'aurpay_allowed_redirect_hosts' );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'aurpay_payment_gateway_wc_plugin_edit_link' );


if (!function_exists( 'register_activation_hook' )) {
    register_activation_hook(__FILE__, function () {
        global $wpdb;
        $wpdb->query(
            "update {$wpdb->prefix} postmeta
            set meta_value='aurpay'
            where meta_key='_payment_method'
            and meta_value='aurpay_payment_gateway_wc';");
    });
}

if (!function_exists( 'aurpay_allowed_redirect_hosts' )) {
    function aurpay_allowed_redirect_hosts($allowed_host)
    {
        $allowed_host[] = 'dashboard.aurpay.net';
        return $allowed_host;
    }
}

/**
 * Runs on Plugin's activation
 * @since 1.0
 * @version 1.0
 */
if (!function_exists('aurpay_payment_gateway_wc_init')) {
    function aurpay_payment_gateway_wc_init()
    {
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }
        require_once WC_Aurpay_DIR . '/includes/class-checkout.php';
        require_once WC_Aurpay_DIR . '/includes/class-rest-api.php';

        $appgwc = new AurpayWCPaymentGateway();

        $apapi = new AurpayAPI($appgwc->apikey);

        add_filter('woocommerce_payment_gateways', array($appgwc, 'woocommerce_aurpay_add_gateway'), 10, 1);
        add_action('woocommerce_receipt_' . $appgwc->id, array($appgwc, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $appgwc->id, array($appgwc, 'process_admin_options')); // WC >= 2.0
        add_action('woocommerce_update_options_payment_gateways', array($appgwc, 'process_admin_options'));
    }
}


/**
 * Add validate for coin select.
 * @since 1.0
 * @version 1.0
 */
add_action('woocommerce_checkout_process', 'my_custom_checkout_field_process');
if (!function_exists( 'my_custom_checkout_field_process' )) {
    function my_custom_checkout_field_process()
    {
        // Check if set, if its not set add an error.
        if (!$_POST['aurpay_coin']) {
            wc_add_notice(__('Please select a currency'), 'error');
        }
    }
}


/**
 * Runs on Plugin's activation
 * @since 1.0
 * @version 1.0
 */
if ( !function_exists( 'om_woocommerce_requirements' ) ) {
    function om_woocommerce_requirements() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_attr_e( 'Please activate', 'woocommerce' );?> <a href='https://wordpress.org/plugins/woocommerce/'><?php esc_attr_e( 'Woocommerce', 'woocommerce' ); ?></a> <?php esc_attr_e( 'to use this plugin.', 'woocommerce' ); ?></p>
        </div>
        <?php
    }
}


/**
 * Plug-in edit link
 * @since 1.0
 * @version 1.0
 */
if (!function_exists( 'aurpay_payment_gateway_wc_plugin_edit_link' )) {
    function aurpay_payment_gateway_wc_plugin_edit_link($links)
    {
        return array_merge(
            array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . WC_Aurpay_ID ) . '">' . __( 'Settings', 'aurpay' ) . '</a>',
            ),
            $links
        );
    }
}


/**
 * Add style to aurpay payment
 * @since 1.0
 * @version 1.0
 */
if (!function_exists( 'aurpay_add_styles' )) {
    function aurpay_add_styles()
    {
        wp_enqueue_style('style_file' , WC_Aurpay_URL . '/assets/css/aurpay.css');
    }
}