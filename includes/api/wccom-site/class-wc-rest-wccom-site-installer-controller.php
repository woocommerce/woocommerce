<?php
/**
 * WCCOM Site Installer REST API Controller
 *
 * Handles requests to /installer.
 *
 * @package WooCommerce/API
 * @since   3.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM Site Installer Controller Class.
 *
 * @package WooCommerce/API
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

	/**
	 * Check permissions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
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
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_install_state( $request ) {
		require_once( WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php' );
		return rest_ensure_response( WC_Helper_Product_Install::get_state() );
	}

	/**
	 * Install WooCommerce.com products.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function install( $request ) {
		require_once( WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php' );

		$body = WP_REST_Server::get_raw_data();
		if ( empty( $body ) ) {
			return new WP_Error( 'empty_body', __( 'Request body is empty.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$data = json_decode( $body, true );
		if ( empty( $data['products'] ) || ! is_array( $data['products'] ) ) {
			return new WP_Error( 'missing_products', __( 'Missing products in request body.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( WC_Helper_Product_Install::install( $data['products'] ) );
	}

	/**
	 * Reset installation state.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function reset_install( $request ) {
		require_once( WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php' );
		$resp = rest_ensure_response( WC_Helper_Product_Install::reset_state() );
		$resp->set_status( 204 );

		return $resp;
	}
}
