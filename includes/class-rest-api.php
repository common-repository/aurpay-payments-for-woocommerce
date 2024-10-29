<?php

defined( 'ABSPATH' ) || exit;

if( !class_exists( 'AurpayAPI' ) ):
    class AurpayAPI {

        var $apikey;

        /**
         * Register Rest route call-back
         * @since 1.0
         * @version 1.0
         */
        public function __construct($apikey)
        {
            $this->apikey = $apikey;
            add_action( 'rest_api_init', array( $this, 'register_rest_route' ), );
        }


        /**
        * Add order complete payment api
        * @since 1.0
        * @version 1.0
        */
        public function register_rest_route()
        {
            // api: /wp-json/wcaurpay/v1/complete-payment
            register_rest_route( 'wcaurpay/v1', '/complete-payment', array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'complete_payment_api' ),
                'permission_callback' => function () { return true; }
            ));
        }


        /**
        * Completes payment API
        * @since 1.0
        * @version 1.0
        */
        public function complete_payment_api(WP_REST_Request $req)
        {
            $req_apikey = $req -> get_header('Api-Key');
            if($req_apikey != $this->apikey)  wp_send_json( array( 'message'	=>	'Apikey Error: '.$req_apikey ), 400 );

            $order_id = $req->get_param('order_id');

            try
            {
                $order = new WC_Order( $order_id );

                $order->payment_complete();

			    delete_option( $order_id );

                wp_send_json_success( array(
                    'order_id' => $req->get_param('order_id'),
                ), 200 );
            } catch (Exception $e) {
                wp_send_json( array( 'message'	=>	'No order associated with this ID.' . $order_id . $e ), 400 );
            }
        }
    }
endif;