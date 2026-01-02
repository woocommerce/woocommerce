<?php
/**
 * REST API Onboarding Profile Controller
 *
 * Handles requests to /onboarding/profile
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use WC_REST_Data_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Onboarding Plugins controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class OnboardingPlugins extends WC_REST_Data_Controller {
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
	protected $rest_base = 'onboarding/plugins';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install-and-activate-async',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_and_activate_async' ),
					'permission_callback' => array( $this, 'can_install_and_activate_plugins' ),
					'args'                => array(
						'plugins' => array(
							'description'       => 'A list of plugins to install',
							'type'              => 'array',
							'items'             => 'string',
							'sanitize_callback' => function ( $value ) {
								return array_map(
									function ( $value ) {
										return sanitize_text_field( $value );
									},
									$value
								);
							},
							'required'          => true,
						),
						'source'  => array(
							'description'       => 'The source of the request',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => false,
						),
					),
				),
				'schema' => array( $this, 'get_install_async_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/install-and-activate',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_and_activate' ),
					'permission_callback' => array( $this, 'can_install_and_activate_plugins' ),

				),
				'schema' => array( $this, 'get_install_activate_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scheduled-installs/(?P<job_id>\w+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_scheduled_installs' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
				),
				'schema' => array( $this, 'get_install_async_schema' ),
			)
		);

		// This is an experimental endpoint and is subject to change in the future.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/jetpack-authorization-url',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_jetpack_authorization_url' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
					'args'                => array(
						'redirect_url' => array(
							'description'       => 'The URL to redirect to after authorization',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => true,
						),
						'from'         => array(
							'description'       => 'from value for the jetpack authorization page',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => false,
							'default'           => 'woocommerce-onboarding',
						),
					),
				),
			)
		);

		// Endpoint to connect to WooCommerce.com via Jetpack.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/wccom-connect-via-jetpack',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'wccom_connect_via_jetpack' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
				),
			)
		);
		add_action( 'woocommerce_plugins_install_error', array( $this, 'log_plugins_install_error' ), 10, 4 );
		add_action( 'woocommerce_plugins_install_api_error', array( $this, 'log_plugins_install_api_error' ), 10, 2 );
	}

	/**
	 * Install and activate a plugin.
	 *
	 * @param WP_REST_Request $request WP Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function install_and_activate( WP_REST_Request $request ) {
		$response             = array();
		$response['install']  = PluginsHelper::install_plugins( $request->get_param( 'plugins' ) );
		$response['activate'] = PluginsHelper::activate_plugins( $response['install']['installed'] );

		return new WP_REST_Response( $response );
	}

	/**
	 * Queue plugin install request.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array
	 */
	public function install_and_activate_async( WP_REST_Request $request ) {
		$plugins = $request->get_param( 'plugins' );
		$source  = $request->get_param( 'source' );
		$job_id  = uniqid();

		WC()->queue()->add( 'woocommerce_plugins_install_and_activate_async_callback', array( $plugins, $job_id, $source ) );

		$plugin_status = array();
		foreach ( $plugins as $plugin ) {
			$plugin_status[ $plugin ] = array(
				'status' => 'pending',
				'errors' => array(),
			);
		}

		return array(
			'job_id'  => $job_id,
			'status'  => 'pending',
			'plugins' => $plugin_status,
		);
	}

	/**
	 * Returns current status of given job.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array|WP_REST_Response
	 */
	public function get_scheduled_installs( WP_REST_Request $request ) {
		$job_id = $request->get_param( 'job_id' );

		$actions = WC()->queue()->search(
			array(
				'hook'    => 'woocommerce_plugins_install_and_activate_async_callback',
				'search'  => $job_id,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$actions = array_filter(
			PluginsHelper::get_action_data( $actions ),
			function ( $action ) use ( $job_id ) {
				return $action['job_id'] === $job_id;
			}
		);

		if ( empty( $actions ) ) {
			return new WP_REST_Response( null, 404 );
		}

		$response = array(
			'job_id' => $actions[0]['job_id'],
			'status' => $actions[0]['status'],
		);

		$option = get_option( 'woocommerce_onboarding_plugins_install_and_activate_async_' . $job_id );
		if ( isset( $option['plugins'] ) ) {
			$response['plugins'] = $option['plugins'];
		}

		return $response;
	}

	/**
	 * Return Jetpack authorization URL.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array
	 */
	public function get_jetpack_authorization_url( WP_REST_Request $request ) {
		return JetpackConnection::get_authorization_url(
			$request->get_param( 'redirect_url' ),
			$request->get_param( 'from' )
		);
	}

	/**
	 * Connect to WooCommerce.com via Jetpack.
	 *
	 * Makes a POST request to the WordPress.com WCCOM connect endpoint via Jetpack
	 * and stores the returned credentials.
	 *
	 * @since 10.5.0
	 *
	 * @return array|WP_Error Connection result or error.
	 */
	public function wccom_connect_via_jetpack() {
		if ( ! class_exists( 'WC_Jetpack' ) ) {
			return new WP_Error(
				'woocommerce_jetpack_not_available',
				__( 'Jetpack connection is not available.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$jetpack = \WC_Jetpack::instance();

		if ( ! $jetpack->is_connected() ) {
			return new WP_Error(
				'woocommerce_jetpack_not_connected',
				__( 'Site is not connected to WordPress.com via Jetpack.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		if ( ! $jetpack->is_user_connected() ) {
			return new WP_Error(
				'woocommerce_jetpack_user_not_connected',
				__( 'Current user is not connected to WordPress.com via Jetpack.', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$response = Jetpack_Connection_Client::wpcom_json_api_request_as_user(
			'wccom/connect',
			'2',
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 30,
			),
			null,
			'wpcom'
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'woocommerce_wccom_connect_request_failed',
				$response->get_error_message(),
				array( 'status' => 500 )
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		if ( $response_code >= 400 ) {
			$error_message = $data['message'] ?? __( 'Failed to connect to WooCommerce.com via Jetpack.', 'woocommerce' );
			return new WP_Error(
				$data['code'] ?? 'woocommerce_wccom_connect_failed',
				$error_message,
				array( 'status' => $response_code )
			);
		}

		if ( empty( $data['access_token'] ) || empty( $data['access_token_secret'] ) || empty( $data['site_id'] ) ) {
			return new WP_Error(
				'woocommerce_wccom_connect_invalid_response',
				__( 'Invalid response from WooCommerce.com connection endpoint.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		\WC_Helper::update_auth_option(
			$data['access_token'],
			$data['access_token_secret'],
			(int) $data['site_id'],
			home_url()
		);

		return array(
			'success' => true,
			'message' => __( 'Successfully connected to WooCommerce.com via Jetpack.', 'woocommerce' ),
		);
	}

	/**
	 * Check whether the current user has permission to install plugins
	 *
	 * @return WP_Error|boolean
	 */
	public function can_install_plugins() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_update',
				__( 'Sorry, you cannot manage plugins.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Check whether the current user has permission to install and activate plugins
	 *
	 * @return WP_Error|boolean
	 */
	public function can_install_and_activate_plugins() {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error(
				'woocommerce_rest_cannot_update',
				__( 'Sorry, you cannot manage plugins.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * JSON Schema for both install-async and scheduled-installs endpoints.
	 *
	 * @return array
	 */
	public function get_install_async_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Install Async Schema',
			'type'       => 'object',
			'properties' => array(
				'type'       => 'object',
				'properties' => array(
					'job_id' => 'integer',
					'status' => array(
						'type' => 'string',
						'enum' => array( 'pending', 'complete', 'failed' ),
					),
				),
			),
		);
	}

	/**
	 * JSON Schema for install-and-activate endpoint.
	 *
	 * @return array
	 */
	public function get_install_activate_schema() {
		$error_schema = array(
			'type'              => 'object',
			'patternProperties' => array(
				'^.*$' => array(
					'type' => 'string',
				),
			),
			'items'             => array(
				'type' => 'string',
			),
		);

		$install_schema = array(
			'type'       => 'object',
			'properties' => array(
				'installed' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'results'   => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'errors'    => array(
					'type'       => 'object',
					'properties' => array(
						'errors'     => $error_schema,
						'error_data' => $error_schema,
					),
				),
			),
		);

		$activate_schema = array(
			'type'       => 'object',
			'properties' => array(
				'activated' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'active'    => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'errors'    => array(
					'type'       => 'object',
					'properties' => array(
						'errors'     => $error_schema,
						'error_data' => $error_schema,
					),
				),
			),
		);

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Install and Activate Schema',
			'type'       => 'object',
			'properties' => array(
				'type'       => 'object',
				'properties' => array(
					'install'  => $install_schema,
					'activate' => $activate_schema,
				),
			),
		);
	}

	public function log_plugins_install_error( $slug, $api, $result, $upgrader ) {
		$properties = array(
			'error_message'         => sprintf(
			/* translators: %s: plugin slug (example: woocommerce-services) */
				__(
					'The requested plugin `%s` could not be installed.',
					'woocommerce'
				),
				$slug
			),
			'type'                  => 'plugin_info_api_error',
			'slug'                  => $slug,
			'api_version'           => $api->version,
			'api_download_link'     => $api->download_link,
			'upgrader_skin_message' => implode( ',', $upgrader->skin->get_upgrade_messages() ),
			'result'                => is_wp_error( $result ) ? $result->get_error_message() : 'null',
		);
		wc_admin_record_tracks_event( 'coreprofiler_install_plugin_error', $properties );
	}

	public function log_plugins_install_api_error( $slug, $api ) {
		$properties = array(
			'error_message'     => sprintf(
			// translators: %s: plugin slug (example: woocommerce-services).
				__(
					'The requested plugin `%s` could not be installed. Plugin API call failed.',
					'woocommerce'
				),
				$slug
			),
			'type'              => 'plugin_install_error',
			'api_error_message' => $api->get_error_message(),
			'slug'              => $slug,
		);
		wc_admin_record_tracks_event( 'coreprofiler_install_plugin_error', $properties );
	}
}
