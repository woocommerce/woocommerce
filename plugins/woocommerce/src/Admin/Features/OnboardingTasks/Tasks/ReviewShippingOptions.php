<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use \Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;
use \Automattic\WooCommerce\Admin\PluginsHelper;
use WC_Data_Store;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Shipping;

/**
 * Review Shipping Options Task
 */
class ReviewShippingOptions extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'shipping';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Review Shipping Options', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		// TODO: Implement is_complete() method.
		return false;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		// TODO: Just check automated shipping options have been set up when #33366 is completed.
		if ( Shipping::sell_only_digital_type() || Shipping::sell_unknown_product_type() ) {
			return false;
		}

		if ( ! Shipping::has_shipping_zones() ) {
			return false;
		}

		$store_country = wc_format_country_state_string( get_option( 'woocommerce_default_country', '' ) )['country'];

		if ( ! $store_country ) {
			return false;
		}

		return 'US' === $store_country || ! in_array( $store_country, array( 'US', 'CA', 'AU', 'UK' ), true );
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=shipping' );
	}
}
