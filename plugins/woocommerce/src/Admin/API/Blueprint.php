<?php

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\Blueprint\ExportSchema;
use Automattic\WooCommerce\Admin\Features\Blueprint\ImportSchema;
use Automattic\WooCommerce\Admin\Features\Blueprint\JsonResultFormatter;
use Automattic\WooCommerce\Admin\Features\Blueprint\SettingsExporter;
use Automattic\WooCommerce\Admin\Features\Blueprint\ZipExportedSchema;

class Blueprint {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'blueprint';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'process';

	/**
	 * Register routes.
	 *
	 * @since 3.5.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process' ),
					'permission_callback' => function () {
						return true;
					},
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/export',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'export' ),
					'permission_callback' => function () {
						return true;
					},
					'args'                => array(
						'steps' => array(
							'description'       => 'A list of plugins to install',
							'type'              => 'array',
							'items'             => 'string',
							'default'           => array(),
							'sanitize_callback' => function ( $value ) {
								return array_map(
									function ( $value ) {
										return sanitize_text_field( $value );
									},
									$value
								);
							},
							'required'          => false,
						),
						'export_as_zip' => array(
							'description'       => 'Export as a zip file',
							'type'              => 'boolean',
							'default'           => false,
							'required'          => false,
						),
					),
				),
			)
		);
	}

	public function export($request) {
		$steps = $request->get_param('steps', array());
		$export_as_zip = $request->get_param('export_as_zip', false);
		$exporter = new ExportSchema();

		if ($export_as_zip) {
			$exporter->get_exporter('installPlugins')->include_private_plugins(true);
		}


		$data = $exporter->export($steps);

		if ($export_as_zip) {
			$zip = new ZipExportedSchema($data);
			$data = $zip->zip();
			$data = site_url(str_replace(ABSPATH, '', $data));
		}

		return new \WP_HTTP_Response(array(
			'data' => $data,
			'type' => $export_as_zip ? 'zip' : 'json',
		));
	}

	public function process() {
		if ( !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK ) {
			$uploaded_file = $_FILES['file']['tmp_name'];

			try {
				if ( $_FILES['file']['type'] === 'application/zip' ) {
					$blueprint = ImportSchema::crate_from_zip( $uploaded_file );
				} else {
					$blueprint = ImportSchema::create_from_json( $uploaded_file );
				}
			} catch(\InvalidArgumentException $e) {
				return new \WP_REST_Response(array(
					'status' => 'error',
					'message' => $e->getMessage(),
				), 400);
			}

			$results = $blueprint->process();
			$result_formatter = new JsonResultFormatter($results);
			$redirect = $blueprint->get_schema()->get_step('redirectToAfter');
			$redirect_url = $redirect->url ?? 'admin.php?page=wc-admin';

			$is_success = $result_formatter->is_success() ? 'success' : 'error';

			return new \WP_HTTP_Response( array(
				'status' => $is_success,
				'message' => $is_success === 'error' ? __('There was an error while processing your schema', 'woocommerce') : 'success',
				'data' => array(
					'redirect' => admin_url($redirect_url),
					'result' => $result_formatter->format(),
				),
			), 200 );
		}

		return new \WP_REST_Response(array(
			'status' => 'error',
			'message' => 'No file uploaded',
		), 400);
	}
}
