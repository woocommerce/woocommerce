<?php


namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Payments;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;

/**
 * Payments Task
 */
class AdditionalPayments extends Payments {

	/**
	 * Parent ID.
	 *
	 * @return string
	 */
	public function get_parent_id() {
		return 'extended';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Set up additional payment providers', 'woocommerce-admin' );
	}


	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		if ( ! Features::is_enabled( 'payment-gateway-suggestions' ) ) {
			// Hide task if feature not enabled.
			return false;
		}

		$woocommerce_payments = new WooCommercePayments();

		if ( $woocommerce_payments->is_requested() && $woocommerce_payments->is_supported() && ! $woocommerce_payments->is_connected() ) {
			// Hide task if WC Pay is installed via OBW, in supported country, but not connected.
			return false;
		}

		return true;
	}
}

