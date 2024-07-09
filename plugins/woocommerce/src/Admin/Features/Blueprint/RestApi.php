<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\ImportSchema;
use Automattic\WooCommerce\Blueprint\JsonResultFormatter;
use Automattic\WooCommerce\Blueprint\ZipExportedSchema;

class RestApi {
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
					'callback'            => array( $this, 'import' ),
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

		$data = $exporter->export($steps, $export_as_zip);

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

	public function import() {
		if ( !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK ) {
			$uploaded_file = $_FILES['file']['tmp_name'];

			try {
				if ( $_FILES['file']['type'] === 'application/zip' ) {

					$newpath = \wp_upload_dir()['path'].'/'.$_FILES['file']['name'];
					move_uploaded_file($uploaded_file, $newpath);

					$blueprint = ImportSchema::crate_from_zip( $newpath );
				} else {
					$blueprint = ImportSchema::create_from_json( $uploaded_file );
				}
			} catch(\InvalidArgumentException $e) {
				return new \WP_REST_Response(array(
					'status' => 'error',
					'message' => $e->getMessage(),
				), 400);
			}

			$results = $blueprint->import();
			$result_formatter = new JsonResultFormatter($results);
			$redirect = $blueprint->get_schema()->landingPage ?? null;
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
