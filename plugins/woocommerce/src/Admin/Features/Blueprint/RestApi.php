<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;
use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\ImportStep;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;
use WP_Error;

/**
 * Class RestApi
 *
 * This class handles the REST API endpoints for importing and exporting WooCommerce Blueprints.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint
 */
class RestApi {
	/**
	 * Maximum allowed file size in bytes (50MB)
	 */
	const MAX_FILE_SIZE = 52428800; // 50 * 1024 * 1024

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * ComingSoonHelper instance.
	 *
	 * @var ComingSoonHelper
	 */
	protected $coming_soon_helper;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->coming_soon_helper = new ComingSoonHelper();
	}

	/**
	 * Get maximum allowed file size for blueprint uploads.
	 *
	 * @return int Maximum file size in bytes
	 */
	protected function get_max_file_size() {
		/**
		 * Filters the maximum allowed file size for blueprint uploads.
		 *
		 * @since 9.3.0
		 * @param int $max_size Maximum file size in bytes.
		 */
		return apply_filters( 'woocommerce_blueprint_upload_max_file_size', self::MAX_FILE_SIZE );
	}

	/**
	 * Register routes.
	 *
	 * @since 9.3.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/blueprint/export',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'export' ),
					'permission_callback' => array( $this, 'check_export_permission' ),
					'args'                => array(
						'steps' => array(
							'description' => __( 'A list of plugins to install', 'woocommerce' ),
							'type'        => 'object',
							'properties'  => array(
								'settings' => array(
									'type'  => 'array',
									'items' => array(
										'type' => 'string',
									),
								),
								'plugins'  => array(
									'type'  => 'array',
									'items' => array(
										'type' => 'string',
									),
								),
								'themes'   => array(
									'type'  => 'array',
									'items' => array(
										'type' => 'string',
									),
								),
							),
							'default'     => array(),
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/blueprint/import-step',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_step' ),
					'permission_callback' => array( $this, 'check_import_permission' ),
					'args'                => array(
						'step_definition' => array(
							'description' => __( 'The step definition to import', 'woocommerce' ),
							'type'        => 'object',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_import_step_response_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/blueprint/import-allowed',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_import_allowed' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_woocommerce' );
					},
				),
				'schema' => array( $this, 'get_import_allowed_schema' ),
			)
		);
	}

	/**
	 * General permission check for export requests.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_export_permission() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot export WooCommerce Blueprints.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * General permission check for import requests.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_import_permission() {
		if (
			! current_user_can( 'manage_woocommerce' ) ||
			! current_user_can( 'manage_options' )
		) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot import WooCommerce Blueprints.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Handle the export request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_HTTP_Response The response object.
	 */
	public function export( $request ) {
		$payload = $request->get_param( 'steps' );
		$steps   = $this->steps_payload_to_blueprint_steps( $payload );

		$exporter = new ExportSchema();

		if ( isset( $payload['plugins'] ) ) {
			$exporter->on_before_export(
				'installPlugin',
				function ( ExportInstallPluginSteps $exporter ) use ( $payload ) {
					$exporter->filter(
						function ( array $plugins ) use ( $payload ) {
							return array_intersect_key( $plugins, array_flip( $payload['plugins'] ) );
						}
					);
				}
			);
		}

		if ( isset( $payload['themes'] ) ) {
			$exporter->on_before_export(
				'installTheme',
				function ( ExportInstallThemeSteps $exporter ) use ( $payload ) {
					$exporter->filter(
						function ( array $plugins ) use ( $payload ) {
							return array_intersect_key( $plugins, array_flip( $payload['themes'] ) );
						}
					);
				}
			);
		}

		$data = $exporter->export( $steps );

		if ( is_wp_error( $data ) ) {
			return new \WP_REST_Response( $data, 400 );
		}

		return new \WP_HTTP_Response(
			array(
				'data' => $data,
				'type' => 'json',
			)
		);
	}

	/**
	 * Convert step list from the frontend to the backend format.
	 *
	 * From:
	 * {
	 *  "settings": ["setWCSettings", "setWCShippingZones", "setWCShippingMethods", "setWCShippingRates"],
	 *  "plugins": ["akismet/akismet.php],
	 *  "themes": ["approach],
	 * }
	 *
	 * To:
	 *
	 * ["setWCSettings", "setWCShippingZones", "setWCShippingMethods", "setWCShippingRates", "installPlugin", "installTheme"]
	 *
	 * @param array $steps steps payload from the frontend.
	 *
	 * @return array
	 */
	private function steps_payload_to_blueprint_steps( $steps ) {
		$blueprint_steps = array();

		if ( isset( $steps['settings'] ) && count( $steps['settings'] ) > 0 ) {
			$blueprint_steps = array_merge( $blueprint_steps, $steps['settings'] );
		}

		if ( isset( $steps['plugins'] ) && count( $steps['plugins'] ) > 0 ) {
			$blueprint_steps[] = 'installPlugin';
		}

		if ( isset( $steps['themes'] ) && count( $steps['themes'] ) > 0 ) {
			$blueprint_steps[] = 'installTheme';
		}

		return $blueprint_steps;
	}

	/**
	 * Import a single step.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return \WP_REST_Response|array
	 */
	public function import_step( \WP_REST_Request $request ) {
		$session_token = $request->get_header( 'X-Blueprint-Import-Session' );

		// If no session token, this is the first step: generate and store a new token.
		if ( ! $session_token ) {
			$session_token = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'bp_', true );
		}

		if ( ! $this->can_import_blueprint( $session_token ) ) {
			return array(
				'success'  => false,
				'messages' => array(
					array(
						'message' => __( 'Blueprint imports are disabled', 'woocommerce' ),
						'type'    => 'error',
					),
				),
			);
		}

		if ( false === get_transient( 'blueprint_import_session_' . $session_token ) ) {
			set_transient( 'blueprint_import_session_' . $session_token, true, 10 * MINUTE_IN_SECONDS );
		}

		// Get the raw body size.
		$body_size = strlen( $request->get_body() );
		if ( $body_size > $this->get_max_file_size() ) {
			return array(
				'success'  => false,
				'messages' => array(
					array(
						'message' => sprintf(
							// Translators: %s is the maximum file size in megabytes.
							__( 'Blueprint step definition size exceeds maximum limit of %s MB', 'woocommerce' ),
							( $this->get_max_file_size() / ( 1024 * 1024 ) )
						),
						'type'    => 'error',
					),
				),
			);
		}

		// Make sure we're dealing with object.
		$step_definition = json_decode( wp_json_encode( $request->get_param( 'step_definition' ) ) );
		$step_importer   = new ImportStep( $step_definition );
		$result          = $step_importer->import();

		$response = new \WP_REST_Response(
			array(
				'success'  => $result->is_success(),
				'messages' => $result->get_messages(),
			)
		);
		$response->header( 'X-Blueprint-Import-Session', $session_token );
		return $response;
	}

	/**
	 * Check if blueprint imports are allowed based on site status, configuration, and session token.
	 *
	 * @param string|null $session_token Optional session token for import session.
	 * @return bool Returns true if imports are allowed, false otherwise.
	 */
	private function can_import_blueprint( $session_token = null ) {
		// Allow import if a valid session token is present so when a site is turned into live during the import process, the import can continue.
		if ( $session_token && get_transient( 'blueprint_import_session_' . $session_token ) ) {
			return true;
		}

		// Check if override constant is defined and true.
		if ( defined( 'ALLOW_BLUEPRINT_IMPORT_IN_LIVE_MODE' ) && ALLOW_BLUEPRINT_IMPORT_IN_LIVE_MODE ) {
			return true;
		}

		// Only allow imports in coming soon mode.
		if ( $this->coming_soon_helper->is_site_live() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get whether blueprint imports are allowed.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_import_allowed() {
		$can_import = $this->can_import_blueprint();

		return rest_ensure_response(
			array(
				'import_allowed' => $can_import,
			)
		);
	}

	/**
	 * Get the schema for the import-allowed endpoint.
	 *
	 * @return array
	 */
	public function get_import_allowed_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'blueprint-import-allowed',
			'type'       => 'object',
			'properties' => array(
				'import_allowed' => array(
					'description' => __( 'Whether blueprint imports are currently allowed', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}


	/**
	 * Get the schema for the import-step endpoint.
	 *
	 * @return array
	 */
	public function get_import_step_response_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'import-step',
			'type'       => 'object',
			'properties' => array(
				'success'  => array(
					'type' => 'boolean',
				),
				'messages' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'message' => array(
								'type' => 'string',
							),
							'type'    => array(
								'type' => 'string',
							),
						),
						'required'   => array( 'message', 'type' ),
					),
				),
			),
			'required'   => array( 'success', 'messages' ),
		);
		return $schema;
	}
}
