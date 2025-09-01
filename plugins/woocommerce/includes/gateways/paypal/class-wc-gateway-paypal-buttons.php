<?php
/**
 * Class WC_Gateway_Paypal_Buttons file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles PayPal Buttons.
 */
class WC_Gateway_Paypal_Buttons {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Paypal
	 */
	private $gateway;

	/**
	 * Whether the gateway should use Orders v2 API.
	 *
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway The gateway instance.
	 */
	public function __construct( WC_Gateway_Paypal $gateway ) {
		$this->gateway = $gateway;

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// TODO: We also want to check the settings.
		$this->enabled = $this->gateway->should_use_orders_v2();
	}

	/**
	 * Get the options for the PayPal buttons.
	 *
	 * @return array
	 */
	public function get_options() {
		$intent = $this->gateway->get_option( 'paymentaction' ) === 'authorization' ? 'authorize' : 'capture';

		$page_type = 'checkout';
		if ( is_cart() || has_block( 'woocommerce/cart' ) ) {
			$page_type = 'cart';
		} elseif ( is_product() ) {
			$page_type = 'product-details';
		}

		return array(
			// phpcs:ignore Generic.Commenting.Todo.TaskFound
			'client-id'              => 'sb', // TODO: Get the client ID.
			'components'             => 'buttons,funding-eligibility,messages',
			'disable-funding'        => 'card,applepay',
			'enable-funding'         => 'venmo,paylater',
			'currency'               => get_woocommerce_currency(),
			'intent'                 => $intent,
			'merchant-id'            => $this->gateway->email,
			'partner-attribution-id' => 'Woo_Cart_CoreUpgrade',
			'page-type'              => $page_type,
		);
	}

	/**
	 * Whether PayPal Buttons is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}
}
