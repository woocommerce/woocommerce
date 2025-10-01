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

if ( ! class_exists( 'WC_Gateway_Paypal_Request' ) ) {
	require_once __DIR__ . '/includes/class-wc-gateway-paypal-request.php';
}

/**
 * Handles PayPal Buttons.
 */
class WC_Gateway_Paypal_Buttons {

	/**
	 * The option for the client-id.
	 *
	 * @var string
	 */
	private const CLIENT_ID_OPTION = 'woocommerce_paypal_client_id';


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
	 * The request instance.
	 *
	 * @var WC_Gateway_Paypal_Request
	 */
	private $request;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway The gateway instance.
	 */
	public function __construct( WC_Gateway_Paypal $gateway ) {
		$this->gateway = $gateway;
		$this->request = new WC_Gateway_Paypal_Request( $this->gateway );

		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		$this->enabled = $this->gateway->should_use_orders_v2() && 'yes' === $this->gateway->get_option( 'paypal_buttons', 'yes' );
	}

	/**
	 * Get the options for the PayPal buttons.
	 *
	 * @return array
	 */
	public function get_options() {
		$common_options = $this->get_common_options();
		$options        = array(
			'partner-attribution-id' => 'Woo_Cart_CoreUpgrade',
			'page-type'              => $this->get_page_type(),
		);

		return array_merge( $common_options, $options );
	}

	/**
	 * Get the common attributes for the PayPal JS SDK script and modules.
	 *
	 * @return array
	 */
	public function get_common_options() {
		$intent = $this->gateway->get_option( 'paymentaction' ) === 'authorization' ? 'authorize' : 'capture';

		return array(
			'client-id'       => $this->get_client_id(),
			'components'      => 'buttons,funding-eligibility,messages',
			'disable-funding' => 'card,applepay',
			'enable-funding'  => 'venmo,paylater',
			'currency'        => get_woocommerce_currency(),
			'intent'          => $intent,
			'merchant-id'     => $this->gateway->email,
		);
	}

	/**
	 * Get the client-id for the PayPal buttons.
	 *
	 * @return string|null The PayPal client-id, or null if the request fails.
	 */
	public function get_client_id() {
		if ( ! $this->gateway->should_use_orders_v2() ) {
			return null;
		}

		$option_key = self::CLIENT_ID_OPTION . ( $this->gateway->testmode ? '_sandbox' : '_live' );
		$client_id  = get_option( $option_key, null );

		if ( empty( $client_id ) ) {
			$client_id = $this->request->fetch_paypal_client_id();
			if ( empty( $client_id ) ) {
				return null;
			}
			update_option( $option_key, $client_id );
		}

		return $client_id;
	}

	/**
	 * Get the page type for the PayPal buttons.
	 *
	 * @return string
	 */
	public function get_page_type() {
		$page_type = 'checkout';
		if ( is_cart() || has_block( 'woocommerce/cart' ) ) {
			$page_type = 'cart';
		} elseif ( is_product() ) {
			$page_type = 'product-details';
		}

		return $page_type;
	}

	/**
	 * Whether PayPal Buttons is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Get the current page URL, to be used for App Switch.
	 * Limited to checkout, cart, and product pages for security.
	 *
	 * @return string
	 */
	public function get_current_page_for_app_switch() {
		// If checkout, cart or product page, return the current page URL.
		if ( is_checkout() || is_cart() || is_product() ) {
			return get_permalink( get_the_ID() );
		}

		return '';
	}
}
