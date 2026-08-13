<?php
/**
 * WCCOM Site Installer REST API Controller Version
 *
 * Handles requests to /installer.
 *
 * @package WooCommerce\WCCom\API
 * @since 7.7.0
 */

use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM Site Installer Controller Class.
 *
 * @extends WC_REST_WCCOM_Site_Controller
 */
class WC_REST_WCCOM_Site_Installer_Controller extends WC_REST_WCCOM_Site_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'installer';

	/**
	 * Register the routes for plugin auto-installer.
	 *
	 * @since 7.7.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<product_id>\d+)/state',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_product_install_state' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'product_id' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'install' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'product-id'      => array(
							'required' => true,
							'type'     => 'integer',
						),
						'run-until-step'  => array(
							'required' => true,
							'type'     => 'string',
							'enum'     => WC_WCCOM_Site_Installation_Manager::STEPS,
						),
						'idempotency-key' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reset',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reset_install' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'product-id'      => array(
							'required' => true,
							'type'     => 'integer',
						),
						'idempotency-key' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Check whether user has permission to access controller's endpoints.
	 *
	 * @since 8.6.0
	 * @param WP_USER $user User object.
	 * @return bool
	 */
	public function user_has_permission( $user ): bool {
		return user_can( $user, 'install_plugins' ) && user_can( $user, 'install_themes' );
	}

	/**
	 * Get installation state.
	 *
	 * @since 3.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function get_product_install_state( $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 ) {
			return rest_ensure_response(
				new WP_Error(
					'missing_param',
					'The product_id parameter is required.',
					array( 'status' => 400 )
				)
			);
		}

		$installation_manager = new WC_WCCOM_Site_Installation_Manager( $product_id );

		try {
			$state = $installation_manager->get_installation_status();
			return $this->success_response( $product_id, $state );
		} catch ( Installer_Error $exception ) {
			return $this->failure_response( $product_id, $exception );
		}
	}

	/**
	 * Install WooCommerce.com products.
	 *
	 * @since 7.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function install( $request ) {
		try {
			$product_id      = $request['product-id'];
			$run_until_step  = $request['run-until-step'];
			$idempotency_key = $request['idempotency-key'];

			$installation_manager = new WC_WCCOM_Site_Installation_Manager( $product_id, $idempotency_key );
			$installation_manager->run_installation( $run_until_step );

			$response = $this->success_response( $product_id );

		} catch ( Installer_Error $exception ) {
			$response = $this->failure_response( $product_id, $exception );
		}

		return $response;
	}

	/**
	 * Reset installation state.
	 *
	 * @since 7.7.0
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reset_install( $request ) {
		try {
			$product_id      = $request['product-id'];
			$idempotency_key = $request['idempotency-key'];

			$installation_manager = new WC_WCCOM_Site_Installation_Manager( $product_id, $idempotency_key );
			$installation_manager->reset_installation();

			$response = $this->success_response( $product_id );

		} catch ( Installer_Error $exception ) {
			$response = $this->failure_response( $product_id, $exception );
		}

		return $response;
	}

	/**
	 * Generate a standardized response for a successful request.
	 *
	 * @param int                                   $product_id Product ID.
	 * @param WC_WCCOM_Site_Installation_State|null $state Installation state object.
	 * @return WP_REST_Response|WP_Error
	 */
	protected function success_response( $product_id, ?WC_WCCOM_Site_Installation_State $state = null ) {
		$state    = $state ?? WC_WCCOM_Site_Installation_State_Storage::get_state( $product_id );
		$response = rest_ensure_response(
			array(
				'success' => true,
				'state'   => $state ? $this->map_state_to_response( $state ) : null,
			)
		);
		$response->set_status( 200 );
		return $response;
	}

	/**
	 * Generate a standardized response for a failed request.
	 *
	 * @param int             $product_id Product ID.
	 * @param Installer_Error $exception The exception.
	 * @return WP_REST_Response|WP_Error
	 */
	protected function failure_response( $product_id, $exception ) {
		$state    = WC_WCCOM_Site_Installation_State_Storage::get_state( $product_id );
		$response = rest_ensure_response(
			array(
				'success'       => false,
				'error_code'    => $exception->get_error_code(),
				'error_message' => $exception->get_error_message(),
				'state'         => $state ? $this->map_state_to_response( $state ) : null,
			)
		);
		$response->set_status( $exception->get_http_code() );
		return $response;
	}

	/**
	 * Map the installation state to a response.
	 *
	 * @param WC_WCCOM_Site_Installation_State $state The installation state.
	 * @return array
	 */
	protected function map_state_to_response( $state ) {
		return array(
			'product_id'                    => $state->get_product_id(),
			'idempotency_key'               => $state->get_idempotency_key(),
			'last_step_name'                => $state->get_last_step_name(),
			'last_step_status'              => $state->get_last_step_status(),
			'last_step_error'               => $state->get_last_step_error(),
			'product_type'                  => $state->get_product_type(),
			'product_name'                  => $state->get_product_name(),
			'already_installed_plugin_info' => $state->get_already_installed_plugin_info(),
			'started_seconds_ago'           => time() - $state->get_started_date(),
		);
	}
}
