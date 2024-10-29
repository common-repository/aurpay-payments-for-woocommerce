<?php

use Automattic\WooCommerce\Admin\Overrides\Order;

defined( 'ABSPATH' ) || exit;

class AurpayWCPaymentGateway extends WC_Payment_Gateway
{

    // Create aurpay order API
    private $pay_url      = 'https://dashboard.aurpay.net/api/order/plugin';

    /**
     * AurpayWCPaymentGateway constructor.
     * @since 1.0
     * @version 1.0.0
     */
    public function __construct()
    {

        $this->id         = WC_Aurpay_ID;
        $this->icon       = WC_Aurpay_URL . '/assets/images/pay-logo.png';
        $this->has_fields = true;

        $this->method_title       = 'Aurpay Crypto Payments'; // checkout option title
        $this->method_description = 'Aurpay Redirects to Aurpay Off-Site Checkout';

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = 'Aurpay';
        $this->apikey      = $this->get_option( 'publickey' );
    }


    /**
     * Fields
     * @since 1.0
     * @version 1.0.0
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'            => array(
                'title'   => __('Enable/Disable', 'aurpay'),
                'type'    => 'checkbox',
                'label'   => __('Enable Aurpay Payment', 'aurpay'),
                'default' => 'no',
            ),
            'publickey' => array(
                'title'       => __('Publickey', 'aurpay'),
                'description' => __('Please enter your Aurpay Publickey; this is needed in order to take payment.', 'aurpay'),
                'type'        => 'password'
            )
        );

    }


    /**
     * Add payment fields for woocommerce
     * @since 1.0
     * @version 1.0.1
     */
    public function payment_fields()
    {
        $options = [
            'ETH'        => 'ETH',
            'USDC-ERC20' => 'USDC-ERC20',
            'USDT-ERC20' => 'USDT-ERC20',
            'DAI-ERC20'  => 'DAI-ERC20',
            // 'BTC'        => 'BTC',
            // 'USDT-OMNI'  => 'USDT-OMNI',
            'TRX'        => 'TRX',
            'USDT-TRC20' => 'USDT-TRC20',
            'USDC-TRC20' => 'USDC-TRC20',
        ];
        ksort($options);


        woocommerce_form_field('aurpay_coin',
            [
                'type'      => 'select',
                'class'     => ['aurpay_coin'],
                'label'     => esc_html__($this->get_option('currency_selection_text')),
                'options'   => $options,
                'required'  => true,
                'autofocus' => true,
            ]
        );
    }


    /**
     * Get currency from chain
     * @since 1.0
     * @version 1.0.1
     */
    public function get_chain($currency)
    {
        $ETH = array('ETH', 'USDC-ERC20', 'USDT-ERC20', 'DAI-ERC20');
        $BTC = array('BTC', 'USDT-OMNI');
        $TRX = array('TRX', 'USDT-TRC20', 'USDC-TRC20');

        if(in_array($currency, $ETH)){
            return 'ETH';
        }
        if(in_array($currency, $BTC)){
            return 'BTC';
        }
        if(in_array($currency, $TRX)){
            return 'TRX';
        }

    }


    /**
     * payment requests
     * @since 1.0
     * @version 1.0.0
     */
    public function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        if (!$_POST['aurpay_coin']) {
            wc_add_notice(__('Please select a currency'), 'error');
            exit;
        }


        // No payment is required to exit
        if (!$order || !$order->needs_payment()) {
            wp_redirect($this->get_return_url($order));
            exit;
        }

        $pay_url = $this->pay_url;

        $headers = array(
            'Content-Type'  =>  'application/json; charset=utf-8',
            'API-Key'        =>  $this->apikey,
        );

        $currency = sanitize_text_field($_POST['aurpay_coin']);
        $chain = $this->get_chain($currency);

        $body = [

            'chain'     => $chain,
            'currency'  => $currency,
            'callback'  => $this->get_return_url($order),
            'platform'  => 'WOOCOMMERCE',
            'origin'    => [
                'id'        => $order_id,
                'currency'  => $order->get_currency(),
                'price'     => $order->get_total(),
                'url'       => site_url(),
                'callback_url'  =>  site_url() . '/wp-json/wcaurpay/v1/complete-payment?order_id=' . $order_id,
            ]

        ];

        $options = array(
            'timeout'       =>  30,
            'redirection'   =>  5,
            'headers'       =>  $headers,
            'body'          =>  wp_json_encode( $body ),
            'sslverify'     =>  false,
        );

        $response = wp_remote_post(
            $this->pay_url,
            $options
        );

        $resp_data = json_decode(wp_remote_retrieve_body($response)) ?: [];
        $response_code = wp_remote_retrieve_response_code( $response );

        if ($response_code!=200){
            wc_add_notice( sprintf( 'Server Error[%d]: %s',  $response_code, $resp_data->msg), 'error' );
            return;
        }

        if ($response_code==200 && $resp_data->code == 0) {

            $order->update_status( 'Processing', __( 'Awaiting cheque payment', 'woocommerce' ));

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'   => 'success',
                'redirect' => $resp_data->data->pay_url,
            );
        } else {
            if ($resp_data->code = 401) {
                wc_add_notice( sprintf( 'Aurpay payment notify Error: %s', 'Please fill in the public key in admin'), 'error' );
            }
            else {
                wc_add_notice( sprintf( 'Aurpay payment notify Error[%d]: %s',  $resp_data->code, $resp_data->message ), 'error' );
            }
        }
    }


    /**
     * @return mixed
     */
    public function woocommerce_aurpay_add_gateway($methods)
    {
        $methods[] = $this;
        return $methods;
    }

}