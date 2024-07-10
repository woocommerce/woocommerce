<?php
/**
 * Override the coming soon options.

 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

class WC_Beta_Tester_Override_Coming_Soon_Options {
	public function __construct() {
		add_action('init', array($this, 'wc_beta_tester_override_coming_soon_options'));
	}

	public function wc_beta_tester_override_coming_soon_options() {
		$mode = get_option('wc_admin_test_helper_force_coming_soon_mode', 'disabled');

		if ('disabled' === $mode) {
			return;
		}

		$is_request_frontend = (!is_admin() || defined('DOING_AJAX'))
			&& !defined('DOING_CRON') && !WC()->is_rest_api_request();
		if (!$is_request_frontend) {
			return;
		}

		$this->override_woocommerce_coming_soon_option($mode);
		$this->override_woocommerce_store_pages_only_option($mode);
	}

	private function override_woocommerce_coming_soon_option($mode) {
		add_filter('option_woocommerce_coming_soon', function ($value) use ($mode) {
			if ('site' === $mode || 'store' === $mode) {
				return 'yes';
			}
			return $value;
		});
	}

	private function override_woocommerce_store_pages_only_option($mode) {
		add_filter('option_woocommerce_store_pages_only', function ($value) use ($mode) {
			if (is_admin() || wp_doing_ajax()) {
				return;
			}

			if ('store' === $mode) {
				return 'yes';
			}
			return $value;
		});
	}
}

new WC_Beta_Tester_Override_Coming_Soon_Options();
