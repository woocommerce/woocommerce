<?php
/**
 * WCCOM Site Installer REST API Controller
 *
 * Handles requests to /installer.
 *
 * @package WooCommerce\WooCommerce_Site\Rest_Api
 * @since   3.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM Site Installer Controller Class.
 *
 * @package WooCommerce/WCCOM_Site/REST_API
 * @extends WC_REST_Controller
 */
class WC_REST_WCCOM_Site_Installer_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wccom-site/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'installer';

	/**
	 * Register the routes for product reviews.
	 *
	 * @since 3.7.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
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

	/**
	 * Check permissions.
	 *
	 * @since 3.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'install_themes' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_install_product', __( 'You do not have permission to install plugin or theme', 'woocommerce' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Get installation state.
	 *
	 * @since 3.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_install_state( $request ) {
		return rest_ensure_response( WC_WCCOM_Site_Installer::get_state() );
	}

	/**
	 * Install WooCommerce.com products.
	 *
	 * @since 3.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function install( $request ) {
		if ( empty( $request['products'] ) ) {
			return new WP_Error( 'missing_products', __( 'Missing products in request body.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$validation_result = $this->validate_products( $request['products'] );
		if ( is_wp_error( $validation_result ) ) {
			return $validation_result;
		}

		return rest_ensure_response( WC_WCCOM_Site_Installer::schedule_install( $request['products'] ) );
	}

	/**
	 * Reset installation state.
	 *
	 * @since 3.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function reset_install( $request ) {
		$resp = rest_ensure_response( WC_WCCOM_Site_Installer::reset_state() );
		$resp->set_status( 204 );

		return $resp;
	}

	/**
	 * Validate products from request body.
	 *
	 * @since 3.7.0
	 * @param array $products Array of products where key is product ID and
	 *                        element is install args.
	 * @return bool|WP_Error
	 */
	protected function validate_products( $products ) {
		$err = new WP_Error( 'invalid_products', __( 'Invalid products in request body.', 'woocommerce' ), array( 'status' => 400 ) );

		if ( ! is_array( $products ) ) {
			return $err;
		}

		foreach ( $products as $product_id => $install_args ) {
			if ( ! absint( $product_id ) ) {
				return $err;
			}

			if ( empty( $install_args ) || ! is_array( $install_args ) ) {
				return $err;
			}
		}

		return true;
	}
}
