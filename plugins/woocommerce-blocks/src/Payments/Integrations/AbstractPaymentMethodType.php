<?php
/**
 * Abstract payment method type class.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodTypeInterface;

/**
 * AbstractPaymentMethodType class.
 *
 * @since 2.6.0
 */
abstract class AbstractPaymentMethodType implements PaymentMethodTypeInterface {
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Settings from the WP options table
	 *
	 * @var array
	 */
	protected $settings = [];

	/**
	 * Get a setting from the settings array if set.
	 *
	 * @param string $name Setting name.
	 * @param mixed  $default Value that is returned if the setting does not exist.
	 * @return mixed
	 */
	protected function get_setting( $name, $default = '' ) {
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : $default;
	}

	/**
	 * Returns the name of the payment method.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Returns an array of script handles to enqueue for this payment method in
	 * the frontend context
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		return [];
	}

	/**
	 * Returns an array of script handles to enqueue for this payment method in
	 * the admin context
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles_for_admin() {
		return $this->get_payment_method_script_handles();
	}

	/**
	 * An array of key, value pairs of data made available to payment methods
	 * client side.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [];
	}
}
