<?php
/**
 * REST API Onboarding Themes Controller
 *
 * Handles requests to install and activate themes.
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Blocks\AIContent\UpdateProducts;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Themes Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
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
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_products( $request ) {
		$update_products = new UpdateProducts();

		$products = $update_products->fetch_dummy_products_to_update();

		if ( is_wp_error( $products ) ) {
			return rest_ensure_response( array( 'success' => false ) );
		}

		return rest_ensure_response( array( 'success' => true ) );

	}

	/**
	 * Check if a given request has access to manage themes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot create dummy products.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

}
