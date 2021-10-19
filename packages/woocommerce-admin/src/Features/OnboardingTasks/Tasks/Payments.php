<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\WooCommercePayments;

/**
 * Payments Task
 */
class Payments {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'          => 'payments',
			'title'       => __( 'Set up payments', 'woocommerce' ),
			'content'     => __(
				'Choose payment providers and enable payment methods at checkout.',
				'woocommerce'
			),
			'is_complete' => self::has_gateways(),
			'can_view'    => Features::is_enabled( 'payment-gateway-suggestions' ) &&
				(
					! WooCommercePayments::is_requested() ||
					! WooCommercePayments::is_installed() ||
					! WooCommercePayments::is_supported()
				),
			'time'        => __( '2 minutes', 'woocommerce' ),
		);
	}

	/**
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function has_gateways() {
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);

		return ! empty( $enabled_gateways );
	}
}
