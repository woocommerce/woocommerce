<?php
/**
 * REST API WC Telemetry controller
 *
 * Handles requests to the /telemetry endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Telemetry controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Telemetry_V2_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'telemetry';

	/**
	 * Register the route for /system_status
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'record_usage_data' ),
					'permission_callback' => array( $this, 'telemetry_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function telemetry_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Record WCTracker Data
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 */
	public function record_usage_data( $request ) {
		$new = $this->get_usage_data( $request );
		if ( ! $new || ! $new['platform'] ) {
			return;
		}

		$data = get_option( 'woocommerce_mobile_app_usage' );
		if ( ! $data ) {
			$data = array();
		}

		$platform = $new['platform'];
		if ( ! $data[ $platform ] || version_compare( $new['version'], $data[ $platform ]['version'], '>=' ) ) {
			$data[ $platform ] = $new;
		}

		update_option( 'woocommerce_mobile_app_usage', $data );
	}

	/**
	 * Get usage data from current request
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Array
	 */
	public function get_usage_data( $request ) {
		$platform = strtolower( $request->get_param( 'platform' ) );
		switch ( $platform ) {
			case 'ios':
			case 'android':
				break;
			default:
				return;
		}

		$version = $request->get_param( 'version' );
		if ( ! $version ) {
			return;
		}

		return array(
			'platform'  => sanitize_text_field( $platform ),
			'version'   => sanitize_text_field( $version ),
			'last_used' => date( 'c' ),
		);
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}
