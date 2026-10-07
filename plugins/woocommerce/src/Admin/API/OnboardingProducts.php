<?php
/**
 * REST API Onboarding Products Controller
 *
 * Handles requests to create dummy products for the Customize Your Store flow.
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Products Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 * @deprecated 11.1.0
 */
class OnboardingProducts extends \WC_REST_Data_Controller {
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
	protected $rest_base = 'onboarding';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/products',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_products' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Create products.
	 *
	 * @deprecated 11.1.0 Sample product creation for the Customize Your Store flow has been removed.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function create_products( $request ) {
		wc_deprecated_function( __METHOD__, '11.1.0' );

		return rest_ensure_response( array( 'success' => false ) );
	}

	/**
	 * Check if a given request has access to create dummy products.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot create dummy products.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
}
