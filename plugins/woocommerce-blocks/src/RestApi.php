<?php
/**
 * Registers controllers in the blocks REST API namespace.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\StoreApi\RoutesController;
use Automattic\WooCommerce\Blocks\StoreApi\SchemaController;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\NoticeHandler;

/**
 * RestApi class.
 */
class RestApi {

	/**
	 * Initialize class features.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 10 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'maybe_init_cart_session' ), 1 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'store_api_authentication' ) );
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( __CLASS__, 'process_legacy_payment' ), 999, 2 );
	}

	/**
	 * Register REST API routes.
	 */
	public static function register_rest_routes() {
		// Init the REST API.
		$controllers = self::get_controllers();

		foreach ( $controllers as $name => $class ) {
			$instance = new $class();
			$instance->register_routes();
		}

		// Init the Store API.
		$schemas = new SchemaController();
		$routes  = new RoutesController( $schemas );
		$routes->register_routes();
	}

	/**
	 * Get routes for a namespace.
	 *
	 * @param string $namespace Namespace to retrieve.
	 * @return array|null
	 */
	public static function get_routes_from_namespace( $namespace ) {
		$rest_server     = rest_get_server();
		$namespace_index = $rest_server->get_namespace_index(
			[
				'namespace' => $namespace,
				'context'   => 'view',
			]
		);

		$response_data = $namespace_index->get_data();

		return isset( $response_data['routes'] ) ? $response_data['routes'] : null;
	}

	/**
	 * Check if is request to the Store API.
	 *
	 * @return bool
	 */
	protected static function is_request_to_store_api() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		return false !== strpos( $request_uri, $rest_prefix . 'wc/store' );
	}

	/**
	 * The Store API does not require authentication.
	 *
	 * @param \WP_Error|mixed $result Error from another authentication handler, null if we should handle it, or another value if not.
	 * @return \WP_Error|null|bool
	 */
	public static function store_api_authentication( $result ) {
		// Pass through errors from other authentication methods used before this one.
		if ( ! empty( $result ) || ! self::is_request_to_store_api() ) {
			return $result;
		}

		return true;
	}

	/**
	 * If we're making a cart request, we may need to load some additional classes from WC Core so we're ready to deal with requests.
	 *
	 * Note: We load the session here early so guest nonces are in place.
	 *
	 * @param mixed $return Value being filtered.
	 * @return mixed
	 */
	public static function maybe_init_cart_session( $return ) {
		if ( function_exists( 'wc_load_cart' ) && ! \is_wp_error( $return ) && self::is_request_to_store_api() ) {
			// @todo Load Dependencies for wc_load_cart(). See https://github.com/woocommerce/woocommerce/pull/26219
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
			wc_load_cart();
		}
		return $return;
	}

	/**
	 * Return a list of controller classes for this REST API namespace.
	 *
	 * @return array
	 */
	protected static function get_controllers() {
		return [
			'product-attributes'      => __NAMESPACE__ . '\RestApi\Controllers\ProductAttributes',
			'product-attribute-terms' => __NAMESPACE__ . '\RestApi\Controllers\ProductAttributeTerms',
			'product-categories'      => __NAMESPACE__ . '\RestApi\Controllers\ProductCategories',
			'product-tags'            => __NAMESPACE__ . '\RestApi\Controllers\ProductTags',
			'products'                => __NAMESPACE__ . '\RestApi\Controllers\Products',
			'variations'              => __NAMESPACE__ . '\RestApi\Controllers\Variations',
			'product-reviews'         => __NAMESPACE__ . '\RestApi\Controllers\ProductReviews',
		];
	}

	/**
	 * Attempt to process a payment for the checkout API if no payment methods support the
	 * woocommerce_rest_checkout_process_payment_with_context action.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result of the payment.
	 */
	public static function process_legacy_payment( PaymentContext $context, PaymentResult &$result ) {
		if ( $result->status ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$post_data = $_POST;

		// Set constants.
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Add the payment data from the API to the POST global.
		$_POST = $context->payment_data;

		// Call the process payment method of the chosen gatway.
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
