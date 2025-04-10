<?php
/**
 * Override the coming soon options.

 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Override Coming Soon Options Class.
 */
class WC_Beta_Tester_Override_Coming_Soon_Options {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->wc_beta_tester_override_coming_soon_options();
	}

	/**
	 * Override the coming soon options.
	 */
	public function wc_beta_tester_override_coming_soon_options() {
		$mode = get_option( 'wc_admin_test_helper_force_coming_soon_mode', 'disabled' );

		if ( 'disabled' === $mode ) {
			return;
		}

		$is_request_frontend = ( ! is_admin() || defined( 'DOING_AJAX' ) )
			&& ! defined( 'DOING_CRON' ) && ! WC()->is_rest_api_request();
		if ( ! $is_request_frontend ) {
			return;
		}

		$this->override_woocommerce_coming_soon_option( $mode );
		$this->override_woocommerce_store_pages_only_option( $mode );
	}

	/**
	 * Override the woocommerce_coming_soon option.
	 *
	 * @param string $mode The coming soon mode.
	 */
	private function override_woocommerce_coming_soon_option( $mode ) {
		add_filter(
			'option_woocommerce_coming_soon',
			function ( $value ) use ( $mode ) {
				if ( 'site' === $mode || 'store' === $mode ) {
					return 'yes';
				}
				return $value;
			}
		);
	}

	/**
	 * Override the woocommerce_store_pages_only option.
	 *
	 * @param string $mode The coming soon mode.
	 */
	private function override_woocommerce_store_pages_only_option( $mode ) {
		add_filter(
			'option_woocommerce_store_pages_only',
			function ( $value ) use ( $mode ) {
				if ( 'store' === $mode ) {
					return 'yes';
				}
				return $value;
			}
		);
	}
}

new WC_Beta_Tester_Override_Coming_Soon_Options();
