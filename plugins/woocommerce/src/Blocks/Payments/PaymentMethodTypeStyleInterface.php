<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Payments;

/**
 * Defines style handles for payment method integrations.
 *
 * @since 11.1.0
 */
interface PaymentMethodTypeStyleInterface extends PaymentMethodTypeInterface {
	/**
	 * Returns an array of style handles to enqueue for this payment method in the frontend context.
	 *
	 * @return string[]
	 *
	 * @since 11.1.0
	 */
	public function get_payment_method_style_handles();

	/**
	 * Returns an array of style handles to enqueue for this payment method in the admin context.
	 *
	 * @return string[]
	 *
	 * @since 11.1.0
	 */
	public function get_payment_method_style_handles_for_admin();
}
