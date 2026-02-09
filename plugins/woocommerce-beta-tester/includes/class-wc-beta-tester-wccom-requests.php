<?php
/**
 * Modify wccom request responses.

 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Modify wccom request responses.
 */
class WC_Beta_Tester_WCCOM_Requests {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->wc_beta_tester_modify_wccom_responses();
	}

	/**
	 * Modify responses based on option value.
	 */
	public function wc_beta_tester_modify_wccom_responses() {
		$mode = get_option( 'wc_admin_test_helper_modify_wccom_request_responses', 'disabled' );

		if ( 'disabled' === $mode ) {
			return;
		}

        if ( 'timeout' === $mode ) {
            add_filter( 'pre_http_request', array( $this, 'override_http_request_timeout' ), 10, 3 );
        }

        if ( 'error' === $mode ) {
            add_filter( 'pre_http_request', array( $this, 'override_http_request_error' ), 10, 3 );
        }
	}

    /**
     * Override the http request with a timeout.
     */
    public function override_http_request_timeout( $response, $args, $url ) {
        if ( strpos( $url, 'https://woocommerce.com/wp-json/' ) !== false || strpos( $url, 'woocommerce.test/wp-json/' ) !== false ) {
            sleep( 6 ); // 6 seconds
            return new WP_Error( 'http_request_timeout', 'Mock timeout error' );
        }

        return false;
    }
   
    /**
     * Override the http request with an error.
     */
    public function override_http_request_error( $response, $args, $url ) {
        if ( strpos( $url, 'https://woocommerce.com/wp-json/' ) !== false ) {
            return array(
                'response' => array(
                    'code'    => 500,
                    'message' => 'Internal Server Error',
                ),
                'body'       => 'Server Error',
                'headers'    => array(),
                'cookies'    => array(),
                'filename'   => null,
            );
        }

        return false;
    }
}

new WC_Beta_Tester_WCCOM_Requests();
