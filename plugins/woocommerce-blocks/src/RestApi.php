<?php
/**
 * Registers controllers in the blocks REST API namespace.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Blocks\RestApi\StoreApi\RoutesController;
use \Automattic\WooCommerce\Blocks\RestApi\StoreApi\SchemaController;

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
	 * @todo check compat < WC 3.6. Make specific to cart endpoint.
	 * @param mixed $return Value being filtered.
	 * @return mixed
	 */
	public static function maybe_init_cart_session( $return ) {
		$wc_instance = wc();
		// if WooCommerce instance isn't available or already have an
		// authentication error, just return.
		if ( ! method_exists( $wc_instance, 'initialize_session' ) || \is_wp_error( $return ) ) {
			return $return;
		}
		$wc_instance->frontend_includes();
		$wc_instance->initialize_session();
		$wc_instance->initialize_cart();
		$wc_instance->cart->get_cart();

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
}
