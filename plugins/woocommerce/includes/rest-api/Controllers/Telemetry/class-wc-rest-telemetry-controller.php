<?php
/**
 * REST API WC Telemetry controller
 *
 * Handles requests to the /wc-telemetry endpoint.
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
class WC_REST_Telemetry_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-telemetry';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'tracker';

	/**
	 * Register the route for /tracker
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
	 * Check whether a given request has permission to post telemetry data
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function telemetry_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you post telemetry data.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
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

		if ( isset( $data[ $platform ] ) ) {
			$existing_usage = $data[ $platform ];

			// Sets the installation date only if it has not been set before.
			if ( isset( $new['installation_date'] ) && ! isset( $existing_usage['installation_date'] ) ) {
				$data[ $platform ]['installation_date'] = $new['installation_date'];
			}

			if ( version_compare( $new['version'], $existing_usage['version'], '>=' ) ) {
				$data[ $platform ]['version']   = $new['version'];
				$data[ $platform ]['last_used'] = $new['last_used'];
			}
		} else {
			// Only sets `first_used` when the platform usage data hasn't been set before.
			$new['first_used'] = $new['last_used'];
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

		// The installation date could be null from earlier mobile client versions.
		$installation_date = $request->get_param( 'installation_date' );

		return array_filter(
			array(
				'platform'          => sanitize_text_field( $platform ),
				'version'           => sanitize_text_field( $version ),
				'last_used'         => gmdate( 'c' ),
				'installation_date' => isset( $installation_date ) ? get_gmt_from_date( $installation_date, 'c' ) : null,
			),
			function( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'platform'          => array(
				'description'       => __( 'Platform to track.', 'woocommerce' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'version'           => array(
				'description'       => __( 'Platform version to track.', 'woocommerce' ),
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'installation_date' => array(
				'description'       => __( 'Installation date of the WooCommerce mobile app.', 'woocommerce' ),
				'required'          => false, // For backward compatibility.
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
