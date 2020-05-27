<?php
/**
 * Payment Api class.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Payments;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\NoticeHandler;
use Automattic\WooCommerce\Blocks\Payments\Integrations\Stripe;
use Automattic\WooCommerce\Blocks\Payments\Integrations\Cheque;
use Automattic\WooCommerce\Blocks\Payments\Integrations\PayPal;

/**
 *  The Api class provides an interface to payment method registration.
 *
 * @since 2.6.0
 */
class Api {
	/**
	 * Reference to the PaymentMethodRegistry instance.
	 *
	 * @var PaymentMethodRegistry
	 */
	private $payment_method_registry;

	/**
	 * Reference to the AssetDataRegistry instance.
	 *
	 * @var AssetDataRegistry
	 */
	private $asset_registry;

	/**
	 * Constructor
	 *
	 * @param PaymentMethodRegistry $payment_method_registry An instance of Payment Method Registry.
	 * @param AssetDataRegistry     $asset_registry  Used for registering data to pass along to the request.
	 */
	public function __construct( PaymentMethodRegistry $payment_method_registry, AssetDataRegistry $asset_registry ) {
		$this->payment_method_registry = $payment_method_registry;
		$this->asset_registry          = $asset_registry;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'init', array( $this->payment_method_registry, 'initialize' ), 5 );
		add_filter( 'woocommerce_blocks_register_script_dependencies', array( $this, 'add_payment_method_script_dependencies' ), 10, 2 );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'add_payment_method_script_data' ) );
		add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'add_payment_method_script_data' ) );
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_method_integrations' ) );
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'process_legacy_payment' ), 999, 2 );
	}

	/**
	 * Add payment method script handles as script dependencies.
	 *
	 * @param array  $dependencies Array of script dependencies.
	 * @param string $handle Script handle.
	 * @return array
	 */
	public function add_payment_method_script_dependencies( $dependencies, $handle ) {
		if ( ! in_array( $handle, [ 'wc-checkout-block', 'wc-checkout-block-frontend', 'wc-cart-block', 'wc-cart-block-frontend' ], true ) ) {
			return $dependencies;
		}
		return array_merge( $dependencies, $this->payment_method_registry->get_all_registered_script_handles() );
	}

	/**
	 * Add payment method data to Asset Registry.
	 */
	public function add_payment_method_script_data() {
		$script_data = $this->payment_method_registry->get_all_registered_script_data();

		foreach ( $script_data as $asset_data_key => $asset_data_value ) {
			if ( ! $this->asset_registry->exists( $asset_data_key ) ) {
				$this->asset_registry->add( $asset_data_key, $asset_data_value );
			}
		}
	}

	/**
	 * Register payment method integrations bundled with blocks.
	 *
	 * @param PaymentMethodRegistry $payment_method_registry Payment method registry instance.
	 */
	public function register_payment_method_integrations( PaymentMethodRegistry $payment_method_registry ) {
		// This is temporarily registering Stripe until it's moved to the extension.
		if ( class_exists( '\WC_Stripe' ) && ! $payment_method_registry->is_registered( 'stripe' ) ) {
			$payment_method_registry->register(
				Package::container()->get( Stripe::class )
			);
		}
		$payment_method_registry->register(
			Package::container()->get( Cheque::class )
		);
		$payment_method_registry->register(
			Package::container()->get( PayPal::class )
		);
	}

	/**
	 * Attempt to process a payment for the checkout API if no payment methods support the
	 * woocommerce_rest_checkout_process_payment_with_context action.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result of the payment.
	 */
	public function process_legacy_payment( PaymentContext $context, PaymentResult &$result ) {
		if ( $result->status ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$post_data = $_POST;

		// Set constants.
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Add the payment data from the API to the POST global.
		$_POST = $context->payment_data;

		// Call the process payment method of the chosen gateway.
		$payment_method_object = $context->get_payment_method_instance();

		if ( ! $payment_method_object instanceof \WC_Payment_Gateway ) {
			return;
		}

		$payment_method_object->validate_fields();

		// If errors were thrown, we need to abort.
		NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

		// Process Payment.
		$gateway_result = $payment_method_object->process_payment( $context->order->get_id() );

		// Restore $_POST data.
		$_POST = $post_data;

		// If `process_payment` added notices, clear them. Notices are not displayed from the API -- payment should fail,
		// and a generic notice will be shown instead if payment failed.
		wc_clear_notices();

		// Handle result.
		$result->set_status( isset( $gateway_result['result'] ) && 'success' === $gateway_result['result'] ? 'success' : 'failure' );

		// set payment_details from result.
		$result->set_payment_details( array_merge( $result->payment_details, $gateway_result ) );
		$result->set_redirect_url( $gateway_result['redirect'] );
	}
}
