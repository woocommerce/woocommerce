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
		$woocommerce_payments = new WooCommercePayments();
		return Features::is_enabled( 'payment-gateway-suggestions' ) && $woocommerce_payments->can_view();
	}
}

