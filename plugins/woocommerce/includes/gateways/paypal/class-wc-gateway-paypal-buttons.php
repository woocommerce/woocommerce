<?php
/**
 * Class WC_Gateway_Paypal_Buttons file.
 *
 * @package WooCommerce\Gateways
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons instead. This class will be removed in 11.0.0.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Gateways\PayPal\Buttons as PayPalButtons;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_Paypal_Request' ) ) {
	require_once __DIR__ . '/includes/class-wc-gateway-paypal-request.php';
}

/**
 * Handles PayPal Buttons.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons instead. This class will be removed in 11.0.0.
 */
class WC_Gateway_Paypal_Buttons {

	/**
	 * The delegated buttons instance.
	 *
	 * @var PayPalButtons
	 */
	private $buttons;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway The gateway instance.
	 */
	public function __construct( WC_Gateway_Paypal $gateway ) {
		$this->buttons = new PayPalButtons( $gateway );
	}

	/**
	 * Get the options for the PayPal buttons.
	 *
	 * @return array
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_options() instead. This method will be removed in 11.0.0.
	 */
	public function get_options() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			'Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_options() instead.'
		);

		return $this->buttons->get_options();
	}

	/**
	 * Get the common attributes for the PayPal JS SDK script and modules.
	 *
	 * @return array
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_common_options() instead. This method will be removed in 11.0.0.
	 */
	public function get_common_options() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			'Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_common_options() instead.'
		);

		return $this->buttons->get_common_options();
	}

	/**
	 * Get the client-id for the PayPal buttons.
	 *
	 * @return string|null The PayPal client-id, or null if the request fails.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_client_id() instead. This method will be removed in 11.0.0.
	 */
	public function get_client_id() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			'Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_client_id() instead.'
		);

		return $this->buttons->get_client_id();
	}

	/**
	 * Get the page type for the PayPal buttons.
	 *
	 * @return string
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_page_type() instead. This method will be removed in 11.0.0.
	 */
	public function get_page_type() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			'Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_page_type() instead.'
		);

		return $this->buttons->get_page_type();
	}

	/**
	 * Whether PayPal Buttons is enabled.
	 *
	 * @return bool
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons::is_enabled() instead. This method will be removed in 11.0.0.
	 */
	public function is_enabled() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			'Use Automattic\WooCommerce\Gateways\PayPal\Buttons::is_enabled() instead.'
		);

		return $this->buttons->is_enabled();
	}

	/**
	 * Get the current page URL, to be used for App Switch.
	 * Limited to checkout, cart, and product pages for security.
	 *
	 * @return string
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_current_page_for_app_switch() instead. This method will be removed in 11.0.0.
	 */
	public function get_current_page_for_app_switch() {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			'Use Automattic\WooCommerce\Gateways\PayPal\Buttons::get_current_page_for_app_switch() instead.'
		);

		return $this->buttons->get_current_page_for_app_switch();
	}
}
