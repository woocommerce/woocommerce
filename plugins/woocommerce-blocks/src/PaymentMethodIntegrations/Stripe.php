<?php
/**
 * Temporary integration of the stripe payment method for the new cart and
 * checkout blocks. Once the api is demonstrated to be stable, this integration
 * will be moved to the Stripe extension
 *
 * @package WooCommerce/Blocks
 * @since $VID:$
 */

namespace Automattic\WooCommerce\Blocks\PaymentMethodIntegrations;

use Exception;
use WC_Stripe_Payment_Request;
use WC_Stripe_Helper;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

/**
 * Stripe payment method integration
 *
 * @since $VID:$
 */
class Stripe {

	/**
	 * An instance of the AssetDataRegistry
	 *
	 * @var AssetDataRegistry
	 */
	private $asset_registry;

	/**
	 * An instance of the Asset Api
	 *
	 * @var Api
	 */
	private $asset_api;

	/**
	 * Stripe settings from the WP options table
	 *
	 * @var array
	 */
	private $stripe_settings;

	/**
	 * Constructor for the class
	 *
	 * @param AssetDataRegistry $asset_registry  Used for registering data to pass along to the request.
	 * @param Api               $asset_api       Used for registering scripts and styles.
	 */
	public function __construct( AssetDataRegistry $asset_registry, Api $asset_api ) {
		$this->asset_registry  = $asset_registry;
		$this->asset_api       = $asset_api;
		$this->stripe_settings = get_option( 'woocommerce_stripe_settings', [] );
	}

	/**
	 * When called registers the stripe handle for enqueueing with cart and
	 * checkout blocks.
	 * Note: this assumes the stripe extension has registered this script.
	 * This will also ensure stripe data is loaded with the blocks.
	 */
	public function register_assets() {
		// only do when not in admin.
		if ( ! is_admin() ) {
			add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'enqueue_data' ] );
			add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_before', [ $this, 'enqueue_data' ] );
		}
		add_action( 'init', [ $this, 'register_scripts_and_styles' ] );
	}

	/**
	 * When called, registers a stripe data object (with 'stripe_data' as the
	 * key) for including everywhere stripe integration happens.
	 */
	public function enqueue_data() {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$stripe_gateway     = isset( $available_gateways['stripe'] ) && is_a( $available_gateways['stripe'], '\WC_Gateway_Stripe' ) ? $available_gateways['stripe'] : null;
		$data               = [
			'stripeTotalLabel' => $this->get_total_label(),
			'publicKey'        => $this->get_publishable_key(),
			'allowPrepaidCard' => $this->get_allow_prepaid_card(),
			'button'           => [
				'type'   => $this->get_button_type(),
				'theme'  => $this->get_button_theme(),
				'height' => $this->get_button_height(),
				'locale' => $this->get_button_locale(),
			],
			'inline_cc_form'   => $stripe_gateway && $stripe_gateway->inline_cc_form,
		];
		if ( ! $this->asset_registry->exists( 'stripe_data' ) ) {
			$this->asset_registry->add( 'stripe_data', $data );
		}
		wp_enqueue_script( 'wc-payment-method-extensions' );
	}

	/**
	 * Register scripts and styles for extension.
	 */
	public function register_scripts_and_styles() {
		wp_register_script( 'stripe', 'https://js.stripe.com/v3/', '', '3.0', true );
		$this->asset_api->register_script(
			'wc-payment-method-extensions',
			'build/wc-payment-method-extensions.js',
			[ 'stripe' ]
		);
	}

	/**
	 * Returns the label to use accompanying the total in the stripe statement.
	 *
	 * @return  string  Statement descriptor
	 */
	private function get_total_label() {
		return ! empty( $this->stripe_settings['statement_descriptor'] ) ? WC_Stripe_Helper::clean_statement_descriptor( $this->stripe_settings['statement_descriptor'] ) : '';
	}

	/**
	 * Returns the publishable api key for the Stripe service.
	 *
	 * @return  string  Public api key.
	 */
	private function get_publishable_key() {
		$test_mode = ( ! empty( $this->stripe_settings['testmode'] ) && 'yes' === $this->stripe_settings['testmode'] );
		if ( $test_mode ) {
			return ! empty( $this->stripe_settings['test_publishable_key'] ) ? $this->stripe_settings['test_publishable_key'] : '';
		}
		return ! empty( $this->stripe_settings['publishable_key'] ) ? $this->stripe_settings['publishable_key'] : '';
	}

	/**
	 * Returns whether to allow prepaid cards for payments.
	 *
	 * @return  bool  True means to allow prepaid card (default)
	 */
	private function get_allow_prepaid_card() {
		return apply_filters( 'wc_stripe_allow_prepaid_card', true );
	}

	/**
	 * Return the button type for the payment button.
	 *
	 * @return  string  Defaults to 'default'
	 */
	private function get_button_type() {
		return isset( $this->stripe_settings['payment_request_button_type'] ) ? $this->stripe_settings['payment_request_button_type'] : 'default';
	}

	/**
	 * Return the theme to use for the payment button.
	 *
	 * @return  string  Defaults to 'dark'.
	 */
	private function get_button_theme() {
		return isset( $this->stripe_settings['payment_request_button_theme'] ) ? $this->stripe_settings['payment_request_button_theme'] : 'dark';
	}

	/**
	 * Return the height for the payment button.
	 *
	 * @return  string  A pixel value for the hight (defaults to '64')
	 */
	private function get_button_height() {
		return isset( $this->stripe_settings['payment_request_button_height'] ) ? str_replace( 'px', '', $this->stripe_settings['payment_request_button_height'] ) : '64';
	}

	/**
	 * Return the locale for the payment button.
	 *
	 * @return  string  Defaults to en_US.
	 */
	private function get_button_locale() {
		return apply_filters( 'wc_stripe_payment_request_button_locale', substr( get_locale(), 0, 2 ) );
	}
}
