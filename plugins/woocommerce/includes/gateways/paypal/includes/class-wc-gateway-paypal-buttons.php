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
 * Handles PayPal JSSDK buttons.
 */
class WC_Gateway_Paypal_Buttons {
	/**
	 * The client ID.
	 *
	 * @var string
	 */
	private $client_id;

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_Paypal
	 */
	private $gateway;

	/**
	 * Constructor.
	 *
	 * @param WC_Gateway_Paypal $gateway The gateway instance.
	 */
	public function __construct( WC_Gateway_Paypal $gateway ) {
		$this->gateway = $gateway;

		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_buttons_container' ) );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_buttons_container' ) );
		add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'render_buttons_container' ) );
		add_action( 'woocommerce_pay_order_before_payment', array( $this, 'render_buttons_container' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_data_attributes_to_jssdk_script' ), 10, 3 );
	}

	/**
	 * Renders the button wrapper.
	 */
	public function render_buttons_container() {
		echo '<div id="woocommerce-paypal-standard-buttons-container"></div>';
	}

	/**
	 * Enqueues the scripts.
	 */
	public function scripts() {
		// TODO: Get client ID from the proxy?
		$client_id = 'sb';

		$intent = $this->gateway->get_option( 'paymentaction' ) === 'authorization' ? 'authorize' : 'capture';
		$params = array(
			'components'     => 'buttons,funding-eligibility',
			'enable-funding' => 'venmo,paylater',
			'currency'       => get_woocommerce_currency(),
			'intent'         => $intent,
			'merchant-id'    => $this->gateway->email,
		);

		wp_enqueue_script(
			'paypal-jssdk',
			'https://www.paypal.com/sdk/js?client-id=' . $client_id . '&' . http_build_query( $params ),
			array(),
			null, // PayPal does not like version numbers in the URL.
			true
		);

		wp_enqueue_script(
			'paypal-standard-buttons',
			WC()->plugin_url() . '/includes/gateways/paypal/assets/js/paypal-buttons.js',
			array( 'paypal-jssdk' ),
			WC_VERSION,
			true
		);

		wp_localize_script(
			'paypal-standard-buttons',
			'PayPalStandardButtons',
			array(
				'endpoints' => array(
					'storeAPICart'          => get_site_url( null, '/wp-json/wc/store/v1/cart' ),
					'storeAPICheckout'      => get_site_url( null, '/wp-json/wc/store/v1/checkout' ),
					'createPayPalOrder'     => get_site_url( null, '/wp-json/wc/v3/paypal-buttons/create-paypal-order' ),
					'updateShippingAddress' => get_site_url( null, '/wp-json/wc/v3/paypal-buttons/update-shipping-address' ),
				),
				'nonce'     => wp_create_nonce( 'wc_store_api' ),
			),
		);
	}

	/**
	 * Add data attributes to the script tag.
	 *
	 * @param string $tag The script tag.
	 * @param string $handle The script handle.
	 * @param string $src The script source.
	 * @return string The modified script tag.
	 */
	public function add_data_attributes_to_jssdk_script( $tag, $handle, $src ) {
		// Check if this is the script you want to target.
		if ( 'paypal-jssdk' === $handle ) {

			// TODO: product-listing, search-results, product-details, mini-cart, cart or checkout.
			$page_type              = 'checkout';
			$partner_attribution_id = 'Woo_Cart_CoreUpgrade';

			// Find the <script> tag and add the data attributes.
			// We'll use a placeholder and replace it.
			$new_tag = str_replace( '<script', '<script data-page-type="' . $page_type . '" data-partner-attribution-id="' . $partner_attribution_id . '" ', $tag );
			return $new_tag;
		}

		return $tag;
	}
}
