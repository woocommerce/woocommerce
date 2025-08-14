<?php

namespace Automattic\WooCommerce\RestApi\V4;

use Automattic\WooCommerce\RestApi\V4\Controllers\ProductsController;

/**
 * Main REST API v4 registration class.
 *
 * Handles registration of all v4 REST API endpoints and controllers.
 */
class RestApiV4 {

	/**
	 * Controllers to register.
	 *
	 * @var array
	 */
	private $controllers = array(
		ProductsController::class,
	);

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		foreach ( $this->controllers as $controller_class ) {
			if ( class_exists( $controller_class ) ) {
				$controller = new $controller_class();
				$controller->register_routes();
			}
		}
	}
}
