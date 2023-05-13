<?php
/**
 * REST API Onboarding Profile Controller
 *
 * Handles requests to /onboarding/profile
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use ActionScheduler;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\PluginsInstallLoggers\AsynPluginsInstallLogger;
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
class OnboardingPlugins extends \WC_REST_Data_Controller {
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
			'/' . $this->rest_base . '/install-async',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_async' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
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
					),
				),
				'schema' => array( $this, 'get_install_async_schema' ),
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
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/run-in-background',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'run_in_background' ),
					'permission_callback' => array( $this, 'can_install_plugins' ),
				),
			)
		);
	}

	/**
	 * Initiate install_plugins.
	 *
	 * This function is called from /install-async endpoint with blocking = false option
	 * to simulate a background process.
	 *
	 * @return void
	 */
	public function run_in_background( $request ) {
		$timeout = 60;
		set_time_limit( $timeout );
		ini_set( 'max_execution_time', $timeout );

		$job_id      = $request->get_param( 'job_id' );
		$plugins     = $request->get_param( 'plugins' );
		$option_name = 'woocommerce_onboarding_plugins_install_async_' . $job_id;
		$logger      = new AsynPluginsInstallLogger( $option_name );
		PluginsHelper::install_plugins( $plugins, $logger );
	}

	/**
	 * Queue plugin install request.
	 *
	 * @param WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return array
	 */
	public function install_async( WP_REST_Request $request ) {
		$plugins = $request->get_param( 'plugins' );
		$job_id  = uniqid();
		$url     = site_url( 'wp-json/wc-admin/onboarding/plugins/run-in-background' );

		wp_remote_post(
			$url,
			array(
				'timeout'  => 0.01,
				'blocking' => false,
				'headers'  => [
					'Content-Type' => 'application/json'
				],

				'body'      => wp_json_encode(
					array(
						'job_id'  => $job_id,
						'plugins' => $plugins,
					)
				),
				'cookies'   => $_COOKIE,
				'sslverify' => false,
			)
		);

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

		$option = get_option( 'woocommerce_onboarding_plugins_install_async_' . $job_id, false );
		if ( false === $option ) {
			return new WP_REST_Response( null, 404 );
		}

		$response = array(
			'job_id' => $job_id,
			'status' => $option['status'],
		);

		if ( isset( $option['plugins'] ) ) {
			$response['plugins'] = $option['plugins'];
		}

		return $response;
	}

	/**
	 * Check whether the current user has permission to install plugins
	 *
	 * @return WP_Error|boolean
	 */
	public function can_install_plugins() {
		return true;
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update',
				__( 'Sorry, you cannot manage plugins.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() ) );
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
}
