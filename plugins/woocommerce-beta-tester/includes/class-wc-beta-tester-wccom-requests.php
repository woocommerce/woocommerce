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
            add_filter('pre_http_request', array( $this, 'override_http_request_timeout' ), 10, 3);
        }

        if ( 'error' === $mode ) {
            // For now.
            return;
        }
	}

    /**
     * Override the http request timeout.
     */
    public function override_http_request_timeout( $response, $args, $url ) {
        if ( strpos( $url, 'https://woocommerce.com/wp-json/wccom-extensions/3.0/promotions?country=AU' ) !== false ) {
            error_log( $url );
            // sleep( 6000 );
        }

        // Otherwise, return the original value to allow the request to proceed
        return $response;
    }
}

new WC_Beta_Tester_WCCOM_Requests();
