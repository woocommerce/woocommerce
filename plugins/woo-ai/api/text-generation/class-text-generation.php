<?php
/**
 * REST API Data Controller
 *
 * Handles requests to /data
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Data controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Text_Generation extends \WC_REST_Data_Controller {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_routes();
	}

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'wooai/text-generation';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_response' ),
					'permission_callback' => array( $this, 'get_response_permission_check' ),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_response_permission_check( $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the onboarding tasks.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_response( $request ) {
		return rest_ensure_response( 'hello' );
	}

}

new Text_Generation();
