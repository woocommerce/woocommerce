<?php
/**
 * Helper REST API Product Reviews Controller
 *
 * Handles requests to /product-installation.
 *
 * @package WooCommerce/API
 * @since   3.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Reviews Controller Class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Helper_Product_Installation_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-helper/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'product-installation';

	/**
	 * Register the routes for product reviews.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_install_state' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'install' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'reset_install' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	public function check_permission( $request ) {
	}

	public function get_install_state( $request ) {
		return rest_ensure_response( WC_Helper_Product_Install::get_state() );
	}

	public function install( $request ) {

	}

	public function reset_install( $request ) {

	}
}
