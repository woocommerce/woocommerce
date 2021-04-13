<?php
namespace Automattic\WooCommerce\Blocks\Payments;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\NoticeHandler;
use Automattic\WooCommerce\Blocks\Payments\Integrations\Stripe;
use Automattic\WooCommerce\Blocks\Payments\Integrations\Cheque;
use Automattic\WooCommerce\Blocks\Payments\Integrations\PayPal;
use Automattic\WooCommerce\Blocks\Payments\Integrations\BankTransfer;
use Automattic\WooCommerce\Blocks\Payments\Integrations\CashOnDelivery;

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
		add_action( 'wp_print_scripts', array( $this, 'verify_payment_methods_dependencies' ), 1 );
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
		return array_merge( $dependencies, $this->payment_method_registry->get_all_active_payment_method_script_dependencies() );
	}

	/**
	 * Returns true if the payment gateway is enabled.
	 *
	 * @param object $gateway Payment gateway.
	 * @return boolean
	 */
	private function is_payment_gateway_enabled( $gateway ) {
		return filter_var( $gateway->enabled, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Add payment method data to Asset Registry.
	 */
	public function add_payment_method_script_data() {
		// Enqueue the order of enabled gateways as `paymentGatewaySortOrder`.
		if ( ! $this->asset_registry->exists( 'paymentGatewaySortOrder' ) ) {
			$payment_gateways = WC()->payment_gateways->payment_gateways();
			$enabled_gateways = array_filter( $payment_gateways, array( $this, 'is_payment_gateway_enabled' ) );
			$this->asset_registry->add( 'paymentGatewaySortOrder', array_keys( $enabled_gateways ) );
		}

		// Enqueue all registered gateway data (settings/config etc).
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
		$payment_method_registry->register(
			Package::container()->get( BankTransfer::class )
		);
		$payment_method_registry->register(
			Package::container()->get( CashOnDelivery::class )
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

	/**
	 * Verify all dependencies of registered payment methods have been registered.
	 * If not, remove that payment method script from the list of dependencies
	 * of Cart and Checkout block scripts so it doesn't break the blocks and show
	 * an error in the admin.
	 */
	public function verify_payment_methods_dependencies() {
		$wp_scripts             = wp_scripts();
		$payment_method_scripts = $this->payment_method_registry->get_all_active_payment_method_script_dependencies();

		foreach ( $payment_method_scripts as $payment_method_script ) {
			if (
				! array_key_exists( $payment_method_script, $wp_scripts->registered ) ||
				! property_exists( $wp_scripts->registered[ $payment_method_script ], 'deps' )
			) {
				continue;
			}
			$deps = $wp_scripts->registered[ $payment_method_script ]->deps;
			foreach ( $deps as $dep ) {
				if ( ! wp_script_is( $dep, 'registered' ) ) {
					$error_handle  = $dep . '-dependency-error';
					$error_message = sprintf(
						'Payment gateway with handle \'%1$s\' has been deactivated because its dependency \'%2$s\' is not registered. Read the docs about registering assets for payment methods: https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/extensibility/payment-method-integration.md#registering-assets',
						esc_html( $payment_method_script ),
						esc_html( $dep )
					);

					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $error_message );

					// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter,WordPress.WP.EnqueuedResourceParameters.MissingVersion
					wp_register_script( $error_handle, '' );
					wp_enqueue_script( $error_handle );
					wp_add_inline_script(
						$error_handle,
						sprintf( 'console.error( "%s" );', $error_message )
					);

					$cart_checkout_scripts = [ 'wc-cart-block', 'wc-cart-block-frontend', 'wc-checkout-block', 'wc-checkout-block-frontend' ];
					foreach ( $cart_checkout_scripts as $script_handle ) {
						if (
							! array_key_exists( $script_handle, $wp_scripts->registered ) ||
							! property_exists( $wp_scripts->registered[ $script_handle ], 'deps' )
						) {
							continue;
						}
						// Remove payment method script from dependencies.
						$wp_scripts->registered[ $script_handle ]->deps = array_diff(
							$wp_scripts->registered[ $script_handle ]->deps,
							[ $payment_method_script ]
						);
					}
				}
			}
		}
	}
}
