<?php
/**
 * FulfillmentsImporterRestController class file.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsTracker;
use Automattic\WooCommerce\Internal\Admin\ImportExport\CSVUploadHelper;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller backing the Fulfillments CSV importer.
 *
 * Exposes `POST /wc/v3/fulfillments/import` — accepts a CSV upload plus options and runs the
 * FulfillmentsCsvImporter service, returning the summary as JSON for the React UI.
 *
 * @since 10.9.0
 */
class FulfillmentsImporterRestController extends RestApiControllerBase {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * REST API base.
	 *
	 * @var string
	 */
	protected string $rest_base = '/fulfillments/import';

	/**
	 * Get the WooCommerce REST API namespace key for this controller.
	 *
	 * @since 10.9.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'fulfillments_importer';
	}

	/**
	 * Register the routes for the importer.
	 *
	 * @since 10.9.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'handle_import' ),
					'permission_callback' => fn( WP_REST_Request $request ) => $this->check_permission_for_fulfillments_import( $request ),
					'args'                => array(
						'notify_customer' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Whether to send shipment notification emails for imported fulfillments.', 'woocommerce' ),
						),
						'update_existing' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'When a fulfillment with the same tracking number already exists on the order, update it.', 'woocommerce' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Permission check for the import endpoint.
	 *
	 * Mirrors the pattern used by OrderFulfillmentsRestController::check_permission_for_fulfillments():
	 * importing fulfillments is an admin-only operation, so we require manage_woocommerce.
	 *
	 * @since 10.9.0
	 *
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True when allowed; WP_Error otherwise.
	 */
	protected function check_permission_for_fulfillments_import( WP_REST_Request $request ) {
		return $this->check_permission( $request, 'manage_woocommerce' );
	}

	/**
	 * Handle the import request — validate the file, run the importer, return the summary.
	 *
	 * @since 10.9.0
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array|WP_Error Summary on success; WP_Error on failure.
	 */
	protected function handle_import( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_no_file', 'csv_importer' );
			return new WP_Error(
				'woocommerce_fulfillments_import_no_file',
				__( 'No CSV file was uploaded.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		// CSVUploadHelper reads $_FILES under a configurable key. Stage our REST file under that key.
		$_FILES['fulfillment_import_file'] = $files['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		try {
			$csv_helper = wc_get_container()->get( CSVUploadHelper::class );
			$upload     = $csv_helper->handle_csv_upload(
				'fulfillment',
				'fulfillment_import_file',
				array(
					'csv' => 'text/csv',
					'txt' => 'text/plain',
				)
			);
			$file_path = (string) ( $upload['file'] ?? '' );

			FilesystemUtil::validate_upload_file_path( $file_path );

			if ( ! wc_is_file_valid_csv( $file_path ) ) {
				throw new \Exception( __( 'Invalid file type. The importer supports CSV and TXT file formats.', 'woocommerce' ) );
			}
		} catch ( \Throwable $e ) {
			unset( $_FILES['fulfillment_import_file'] );
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_upload_failed', 'csv_importer' );
			return new WP_Error(
				'woocommerce_fulfillments_import_upload_failed',
				$e->getMessage(),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}
		unset( $_FILES['fulfillment_import_file'] );

		$importer = new FulfillmentsCsvImporter(
			$file_path,
			array(
				'notify_customer' => (bool) $request->get_param( 'notify_customer' ),
				'update_existing' => (bool) $request->get_param( 'update_existing' ),
			)
		);

		$summary = $importer->run();

		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}

		/**
		 * Fires after a bulk fulfillments CSV import completes.
		 *
		 * @since 10.9.0
		 *
		 * @param array $summary Importer summary (created/updated/skipped/failed/notified/rows).
		 */
		do_action( 'woocommerce_fulfillments_csv_import_completed', $summary );

		return $summary;
	}
}
