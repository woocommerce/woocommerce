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

/**
 * RestApi class.
 */
class RestApi {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
		add_filter( 'rest_authentication_errors', array( $this, 'store_api_authentication' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
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
	public function get_routes_from_namespace( $namespace ) {
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
	 * The Store API does not require authentication.
	 *
	 * @param \WP_Error|mixed $result Error from another authentication handler, null if we should handle it, or another value if not.
	 * @return \WP_Error|null|bool
	 */
	public function store_api_authentication( $result ) {
		// Pass through errors from other authentication methods used before this one.
		if ( ! empty( $result ) || ! self::is_request_to_store_api() ) {
			return $result;
		}
		return true;
	}

	/**
	 * Check if is request to the Store API.
	 *
	 * @return bool
	 */
	protected function is_request_to_store_api() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		return false !== strpos( $request_uri, $rest_prefix . 'wc/store' );
	}
}
