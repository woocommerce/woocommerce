<?php
namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use WC_Gateway_Paypal;
use Automattic\WooCommerce\Blocks\Assets\Api;

/**
 * PayPal Standard payment method integration
 *
 * @since 2.6.0
 */
final class PayPal extends AbstractPaymentMethodType {
	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = WC_Gateway_Paypal::ID;

	/**
	 * An instance of the Asset Api
	 *
	 * @var Api
	 */
	private $asset_api;

	/**
	 * Constructor
	 *
	 * @param Api $asset_api An instance of Api.
	 */
	public function __construct( Api $asset_api ) {
		$this->asset_api = $asset_api;
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_paypal_settings', [] );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$this->asset_api->register_script(
			'wc-payment-method-paypal',
			'assets/client/blocks/wc-payment-method-paypal.js'
		);
		return [ 'wc-payment-method-paypal' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$gateway = WC_Gateway_Paypal::get_instance();

		if ( ! $gateway->is_available() ) {
			return [];
		}

		include_once WC_ABSPATH . 'includes/gateways/paypal/class-wc-gateway-paypal-buttons.php';
		$buttons = new \WC_Gateway_Paypal_Buttons( $gateway );
		$options = $buttons->get_options();

		return [
			'title'                  => $this->get_setting( 'title' ),
			'description'            => $this->get_description(),
			'supports'               => $this->get_supported_features(),
			'isButtonsEnabled'       => $buttons->is_enabled(),
			'isProductPage'          => is_product(),
			'appSwitchRequestOrigin' => $buttons->get_current_page_for_app_switch(),
			'buttonsOptions'         => $options,
			'wc_store_api_nonce'     => wp_create_nonce( 'wc_store_api' ),
			'create_order_nonce'     => wp_create_nonce( 'wc_gateway_paypal_standard_create_order' ),
			'cancel_payment_nonce'   => wp_create_nonce( 'wc_gateway_paypal_standard_cancel_payment' ),
		];
	}

	/**
	 * Get the description for the payment method. Add sandbox instructions if sandbox mode is enabled.
	 *
	 * @return string
	 */
	public function get_description() {
		$gateway     = WC_Gateway_Paypal::get_instance();
		$testmode    = $gateway->testmode;
		$description = $this->get_setting( 'description' ) ?? '';
		if ( $testmode ) {
			/* translators: %s: Link to PayPal sandbox testing guide page */
			$description .= '<br>' . sprintf( __( '<strong>Sandbox mode enabled</strong>. Only sandbox test accounts can be used. See the <a href="%s">PayPal Sandbox Testing Guide</a> for more details.', 'woocommerce' ), 'https://developer.paypal.com/tools/sandbox/' );
		}
		return trim( $description );
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$gateway  = WC_Gateway_Paypal::get_instance();
		$features = array_filter( $gateway->supports, array( $gateway, 'supports' ) );

		/**
		 * Filter to control what features are available for each payment gateway.
		 *
		 * @since 4.4.0
		 *
		 * @example See docs/examples/payment-gateways-features-list.md
		 *
		 * @param array $features List of supported features.
		 * @param string $name Gateway name.
		 * @return array Updated list of supported features.
		 */
		return apply_filters( '__experimental_woocommerce_blocks_payment_gateway_features_list', $features, $this->get_name() );
	}
}
