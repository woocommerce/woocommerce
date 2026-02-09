<?php
/**
 * WCCOM Site Connection REST API Controller
 *
 * Handle requests to /connection.
 *
 * @package WooCommerce\WCCom\API
 * @since   9.6.0
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * REST API WC_REST_WCCOM_Site_Connection_Controller Class.
 *
 * @extends WC_REST_WCCOM_Site_Status_Controller
 */
class WC_REST_WCCOM_Site_Connection_Controller extends WC_REST_WCCOM_Site_Controller {
	const CONNECTION_DATA_FOUND = 'connection_data_found';
	const CONNECTION_VALID      = 'connection_valid';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'connection';

	/**
	 * Register the routes for Site Connection Controller.
	 *
	 * @since 9.6.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/disconnect',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'handle_disconnect_request' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'handle_status_request' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			),
		);
	}

	/**
	 * Check whether user has permission to access controller's endpoints.
	 *
	 * @since 9.6.0
	 * @param WP_USER $user User object.
	 * @return bool
	 */
	public function user_has_permission( $user ): bool {
		return user_can( $user, 'install_plugins' ) && user_can( $user, 'activate_plugins' );
	}

	/**
	 * Disconnect the site from WooCommerce.com.
	 *
	 * @since  9.6.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response
	 */
	public function handle_disconnect_request( $request ) {
		$request_hash = $request['hash'];
		if ( empty( $request_hash ) || ! WC_Helper::verify_request_hash( $request_hash ) ) {
			return $this->get_response(
				array(),
				403
			);
		}

		if ( WC_Helper::is_site_connected() ) {
			WC_Helper::disconnect();
		}

		return $this->get_response(
			array(
				'status' => true,
			)
		);
	}

	/**
	 * Get the status of the WooCommerce.com connection.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @since  9.9.0
	 */
	public function handle_status_request() {
		$auth = WC_Helper_Options::get( 'auth' );
		if ( empty( $auth['access_token'] ) || empty( $auth['access_token_secret'] ) ) {
			return $this->get_response(
				array(
					self::CONNECTION_DATA_FOUND => false,
					self::CONNECTION_VALID      => false,
				)
			);
		}

		$connection_data = WC_Helper::fetch_helper_connection_info();
		if ( $connection_data instanceof WP_Error ) {
			return $connection_data;
		}

		if ( null === $connection_data ) {
			return $this->get_response(
				array(
					self::CONNECTION_DATA_FOUND => true,
					self::CONNECTION_VALID      => false,
				)
			);
		}

		return $this->get_response(
			array_merge(
				array(
					self::CONNECTION_DATA_FOUND => true,
					self::CONNECTION_VALID      => true,
				),
				$connection_data
			)
		);
	}
}
