<?php
namespace Automattic\WooCommerce\Blocks\Payments;

interface PaymentMethodTypeInterface {
	/**
	 * The name of the payment method
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active();

	/**
	 * When called invokes any initialization/setup for the payment method type
	 * instance.
	 */
	public function initialize();

	/**
	 * Returns an array of script handles to enqueue for this payment method in
	 * the frontend context
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles();

	/**
	 * Returns an array of script handles to enqueue for this payment method in
	 * the admin context
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles_for_admin();

	/**
	 * An array of key, value pairs of data made available to payment methods
	 * client side.
	 *
	 * @return array
	 */
	public function get_payment_method_data();

	/**
	 * Get array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features();
}
