<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;
use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\ImportSchema;
use Automattic\WooCommerce\Blueprint\ResultFormatters\JsonResultFormatter;
use Automattic\WooCommerce\Blueprint\ImportStep;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\ZipExportedSchema;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

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
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'steps'         => array(
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
						'export_as_zip' => array(
							'description' => __( 'Export as a zip file', 'woocommerce' ),
							'type'        => 'boolean',
							'default'     => false,
							'required'    => false,
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
					'permission_callback' => array( $this, 'check_permission' ),
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
	}

	/**
	 * Check if the current user has permission to perform the request.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
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

		$export_as_zip = $request->get_param( 'export_as_zip' );
		$exporter      = new ExportSchema();

		if ( isset( $payload['plugins'] ) ) {
			$exporter->onBeforeExport(
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
			$exporter->onBeforeExport(
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

		$data = $exporter->export( $steps, $export_as_zip );

		if ( $export_as_zip ) {
			$zip  = new ZipExportedSchema( $data );
			$data = $zip->zip();
			$data = site_url( str_replace( ABSPATH, '', $data ) );
		}

		return new \WP_HTTP_Response(
			array(
				'data' => $data,
				'type' => $export_as_zip ? 'zip' : 'json',
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

		if ( isset( $steps['settings'] ) ) {
			$blueprint_steps = array_merge( $blueprint_steps, $steps['settings'] );
		}

		if ( isset( $steps['plugins'] ) ) {
			$blueprint_steps[] = 'installPlugin';
		}

		if ( isset( $steps['themes'] ) ) {
			$blueprint_steps[] = 'installTheme';
		}

		return $blueprint_steps;
	}


	/**
	 * Get list of settings that will be overridden by the import.
	 *
	 * @param array $requested_steps List of steps from the import schema.
	 * @return array List of settings that will be overridden.
	 */
	private function get_settings_to_overwrite( array $requested_steps ): array {
		$settings_map = array(
			'setWCSettings'            => __( 'Settings', 'woocommerce' ),
			'setWCCoreProfilerOptions' => __( 'Core Profiler Options', 'woocommerce' ),
			'setWCPaymentGateways'     => __( 'Payment Gateways', 'woocommerce' ),
			'setWCShipping'            => __( 'Shipping', 'woocommerce' ),
			'setWCTaskOptions'         => __( 'Task Options', 'woocommerce' ),
			'setWCTaxRates'            => __( 'Tax Rates', 'woocommerce' ),
			'installPlugin'            => __( 'Plugins', 'woocommerce' ),
			'installTheme'             => __( 'Themes', 'woocommerce' ),
		);

		$settings = array();
		foreach ( $requested_steps as $step ) {
			$step_name = $step->meta->alias ?? $step->step;
			if ( isset( $settings_map[ $step_name ] )
			&& ! in_array( $settings_map[ $step_name ], $settings, true ) ) {
				$settings[] = $settings_map[ $step_name ];
			}
		}

		return $settings;
	}

	/**
	 * Import a single step.
	 *
	 * @param \WP_REST_Request $request The request object.
	 *
	 * @return array
	 */
	public function import_step( \WP_REST_Request $request ) {
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

		return array(
			'success'  => $result->is_success(),
			'messages' => $result->get_messages(),
		);
	}



	/**
	 * Get the schema for the queue endpoint.
	 *
	 * @return array
	 */
	public function get_queue_response_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'queue',
			'type'       => 'object',
			'properties' => array(
				'reference'             => array(
					'type' => 'string',
				),
				'process_nonce'         => array(
					'type' => 'string',
				),
				'settings_to_overwrite' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'error_type'            => array(
					'type'    => 'string',
					'default' => null,
					'enum'    => array( 'upload', 'schema_validation', 'conflict' ),
				),
				'errors'                => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the schema for the process endpoint.
	 *
	 * @return array
	 */
	public function get_process_response_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'process',
			'type'       => 'object',
			'properties' => array(
				'processed' => array(
					'type' => 'boolean',
				),
				'message'   => array(
					'type' => 'string',
				),
				'data'      => array(
					'type'       => 'object',
					'properties' => array(
						'redirect' => array(
							'type' => 'string',
						),
						'result'   => array(
							'type' => 'array',
						),
					),
				),
			),
		);
		return $schema;
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
