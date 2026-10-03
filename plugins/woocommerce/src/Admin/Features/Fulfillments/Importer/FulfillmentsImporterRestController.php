<?php
/**
 * FulfillmentsImporterRestController class file.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

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
 * Exposes `POST /wc/v3/fulfillments/import/prepare`, which uploads the CSV, parses the
 * headers and opens an ImportSession for the wizard's column-mapping step.
 *
 * @since 11.3.0
 */
class FulfillmentsImporterRestController extends RestApiControllerBase {

	/**
	 * REST API base.
	 *
	 * @var string
	 */
	protected string $rest_base = '/fulfillments/import';

	/**
	 * Register the REST routes through the base class, plus the Action Scheduler
	 * callback that removes the staged CSV of an abandoned import session.
	 *
	 * @since 11.3.0
	 */
	public function register(): void {
		parent::register();
		add_action( ImportSession::CLEANUP_HOOK, array( ImportSession::class, 'handle_cleanup_hook' ), 10, 4 );
	}

	/**
	 * Get the WooCommerce REST API namespace key for this controller.
	 *
	 * @since 11.3.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'fulfillments_importer';
	}

	/**
	 * Register the routes for the importer.
	 *
	 * @since 11.3.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->rest_base . '/prepare',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'handle_prepare' ),
					'permission_callback' => fn( WP_REST_Request $request ) => $this->check_permission_for_fulfillments_import( $request ),
					'args'                => array(
						'delimiter'       => array(
							'type'              => 'string',
							'default'           => ',',
							'description'       => __( 'Single-character CSV delimiter. Defaults to comma.', 'woocommerce' ),
							'sanitize_callback' => array( FulfillmentsCsvImporter::class, 'normalize_delimiter' ),
						),
						'notify_customer' => array(
							'type'    => 'boolean',
							'default' => false,
						),
						'update_existing' => array(
							'type'    => 'boolean',
							'default' => true,
						),
					),
				),
				'schema' => fn() => $this->get_schema_for_prepare(),
			)
		);
	}

	/**
	 * Get the response schema for the prepare endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_prepare(): array {
		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Prepare fulfillments import response.', 'woocommerce' );
		$schema['properties'] = array(
			'token'            => array(
				'type'        => 'string',
				'description' => __( 'Import session token to pass to the run endpoint.', 'woocommerce' ),
			),
			'headers'          => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'string' ),
				'description' => __( 'Header row of the staged CSV.', 'woocommerce' ),
			),
			'sample'           => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'string' ),
				'description' => __( 'First non-blank data row, for the mapping preview.', 'woocommerce' ),
			),
			'total'            => array(
				'type'        => 'integer',
				'description' => __( 'Number of CSV records after the header.', 'woocommerce' ),
			),
			'detected_mapping' => array(
				'type'                 => 'object',
				'additionalProperties' => array( 'type' => 'string' ),
				'description'          => __( 'Auto-detected column mapping, keyed by CSV column index.', 'woocommerce' ),
			),
			'delimiter'        => array(
				'type'        => 'string',
				'description' => __( 'Effective CSV delimiter.', 'woocommerce' ),
			),
		);
		return $schema;
	}

	/**
	 * Permission check for the import endpoint.
	 *
	 * @since 11.3.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True when allowed; WP_Error otherwise.
	 */
	protected function check_permission_for_fulfillments_import( WP_REST_Request $request ) {
		return $this->check_permission( $request, 'manage_woocommerce' );
	}

	/**
	 * Prepare step: validate + stage the upload, parse headers, open a session.
	 *
	 * @since 11.3.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The incoming multipart request.
	 * @return array|WP_Error
	 */
	protected function handle_prepare( WP_REST_Request $request ) {
		$delimiter_param = FulfillmentsCsvImporter::normalize_delimiter( $request->get_param( 'delimiter' ) );
		$notify          = (bool) $request->get_param( 'notify_customer' );
		$update          = (bool) $request->get_param( 'update_existing' );
		$user_id         = get_current_user_id();

		// Replace any prior session (and its staged file) for this user before staging the new upload.
		$prior = ImportSession::active_for_user( $user_id );
		if ( $prior instanceof ImportSession ) {
			$this->delete_staged_file( $prior->file(), $prior->attachment_id() );
			$prior->delete();
		}

		$staged = $this->stage_uploaded_csv( $request );
		if ( $staged instanceof WP_Error ) {
			return $staged;
		}
		$file_path     = (string) $staged['file'];
		$attachment_id = (int) $staged['id'];

		$importer = new FulfillmentsCsvImporter(
			$file_path,
			array(
				'notify_customer' => $notify,
				'update_existing' => $update,
			)
		);

		$parsed = $importer->parse_headers( $delimiter_param );
		if ( isset( $parsed['error'] ) ) {
			$this->delete_staged_file( $file_path, $attachment_id );
			return new WP_Error(
				'woocommerce_fulfillments_csv_parse_error',
				(string) $parsed['error']['message'],
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$total = (int) ( $parsed['total'] ?? 0 );
		if ( $total > FulfillmentsCsvImporter::MAX_IMPORT_ROWS ) {
			$this->delete_staged_file( $file_path, $attachment_id );
			return new WP_Error(
				'woocommerce_fulfillments_import_too_many_rows',
				sprintf(
					/* translators: %s: maximum supported rows. */
					__( 'The importer supports up to %s rows per file. Please split the file and import it in parts.', 'woocommerce' ),
					number_format_i18n( FulfillmentsCsvImporter::MAX_IMPORT_ROWS )
				),
				array( 'status' => WP_Http::REQUEST_ENTITY_TOO_LARGE )
			);
		}

		$session = ImportSession::create(
			$user_id,
			$file_path,
			(string) ( $parsed['delimiter'] ?? ',' ),
			(array) ( $parsed['headers'] ?? array() ),
			$total,
			$notify,
			$update,
			$attachment_id
		);

		if ( ! $session->persisted() ) {
			$session->delete();
			$this->delete_staged_file( $file_path, $attachment_id );
			return new WP_Error(
				'woocommerce_fulfillments_import_session_failed',
				__( 'The import session could not be saved. Please try again.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		return array(
			'token'            => $session->token(),
			'headers'          => $parsed['headers'] ?? array(),
			'sample'           => $parsed['sample'] ?? array(),
			'total'            => $parsed['total'] ?? 0,
			'detected_mapping' => $this->mapping_for_response( (array) ( $parsed['detected_mapping'] ?? array() ) ),
			'delimiter'        => $parsed['delimiter'] ?? ',',
		);
	}

	/**
	 * Validate the multipart file, hand it to CSVUploadHelper, and return the staged file details.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request Incoming request carrying the multipart upload.
	 * @return array{file:string, id:int}|WP_Error Staged absolute path and the attachment post ID created for it.
	 *
	 * @throws \Exception When staged-file validation fails; caught internally and returned as a WP_Error.
	 */
	protected function stage_uploaded_csv( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_no_file',
				__( 'No CSV file was uploaded.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		/**
		 * This filter is documented in wp-admin/includes/import.php.
		 *
		 * @since 2.3.0
		 */
		$upload_limit = (int) apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$file_size    = isset( $files['file']['size'] ) ? (int) $files['file']['size'] : 0;
		if ( $upload_limit > 0 && $file_size > $upload_limit ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_file_too_large',
				sprintf(
					/* translators: %s: human-readable maximum upload size, e.g. "8 MB". */
					__( 'The uploaded file is larger than the allowed maximum of %s.', 'woocommerce' ),
					size_format( $upload_limit )
				),
				array( 'status' => WP_Http::REQUEST_ENTITY_TOO_LARGE )
			);
		}

		// CSVUploadHelper ultimately calls wp_handle_upload(), which is only loaded
		// on wp-admin page loads; REST requests must pull it in explicitly.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// CSVUploadHelper reads from $_FILES under a configurable key. Stage our REST file under
		// that key and restore the superglobal in finally so the assignment cannot leak.
		// The REST permission_callback handles authentication, hence the phpcs ignore below.
		$_FILES['fulfillment_import_file'] = $files['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$file_path                         = '';
		$attachment_id                     = 0;
		try {
			try {
				$csv_helper    = wc_get_container()->get( CSVUploadHelper::class );
				$upload        = $csv_helper->handle_csv_upload(
					'fulfillment',
					'fulfillment_import_file',
					array(
						'csv' => 'text/csv',
						'txt' => 'text/plain',
					)
				);
				$file_path     = (string) ( $upload['file'] ?? '' );
				$attachment_id = (int) ( $upload['id'] ?? 0 );

				FilesystemUtil::validate_upload_file_path( $file_path );

				if ( ! wc_is_file_valid_csv( $file_path ) ) {
					throw new \Exception( __( 'Invalid file type. The importer supports CSV and TXT file formats.', 'woocommerce' ) );
				}
			} catch ( \Exception $e ) {
				$this->discard_failed_upload( $file_path, $attachment_id );
				return new WP_Error(
					'woocommerce_fulfillments_import_upload_failed',
					$e->getMessage(),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			} catch ( \Throwable $e ) {
				$this->discard_failed_upload( $file_path, $attachment_id );
				wc_get_logger()->error(
					'Fulfillments importer upload failed: ' . $e->getMessage(),
					array( 'source' => 'fulfillments-csv-importer' )
				);
				return new WP_Error(
					'woocommerce_fulfillments_import_upload_failed',
					__( 'The upload could not be processed. Please try again.', 'woocommerce' ),
					array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
				);
			}
		} finally {
			unset( $_FILES['fulfillment_import_file'] );
		}

		return array(
			'file' => $file_path,
			'id'   => $attachment_id,
		);
	}

	/**
	 * Remove a just-staged upload that failed validation, including its attachment post.
	 *
	 * The path came straight from the upload handler, so no containment check applies here.
	 *
	 * @param string $file          Staged absolute path; may be empty when staging never completed.
	 * @param int    $attachment_id Attachment post created by the upload handler; 0 when none.
	 */
	private function discard_failed_upload( string $file, int $attachment_id ): void {
		if ( $attachment_id > 0 ) {
			wp_delete_attachment( $attachment_id, true );
		}
		if ( '' !== $file && file_exists( $file ) ) {
			wp_delete_file( $file );
		}
	}

	/**
	 * Delete a session's staged CSV and the attachment post created for it.
	 *
	 * The path comes from persisted session state, so it must resolve inside an allowed
	 * upload location, and the attachment must still point at that same path.
	 *
	 * @param string $file          Absolute staged path from session state.
	 * @param int    $attachment_id Attachment post created by the upload handler; 0 when none.
	 */
	private function delete_staged_file( string $file, int $attachment_id ): void {
		if ( '' === $file ) {
			return;
		}
		if ( file_exists( $file ) && ! $this->is_valid_staged_path( $file ) ) {
			return;
		}
		if ( $attachment_id > 0 && get_attached_file( $attachment_id ) === $file ) {
			wp_delete_attachment( $attachment_id, true );
		}
		if ( file_exists( $file ) ) {
			wp_delete_file( $file );
		}
	}

	/**
	 * Whether a persisted staged-file path resolves inside the uploads directory.
	 *
	 * @param string $path Absolute path from session state.
	 * @return bool
	 */
	private function is_valid_staged_path( string $path ): bool {
		return ImportSession::is_staged_path( $path );
	}

	/**
	 * Shape the detected mapping so it always serializes as a JSON object.
	 *
	 * PHP canonicalizes numeric string keys back to int, so a mapping over contiguous
	 * columns starting at 0 would otherwise encode as a JSON array and break the object
	 * shape declared in the response schema.
	 *
	 * @param array<int, string> $mapping CSV column index => canonical key.
	 * @return \stdClass Column index => canonical key.
	 */
	private function mapping_for_response( array $mapping ): \stdClass {
		$out = array();
		foreach ( $mapping as $col => $canonical ) {
			$out[ (string) $col ] = (string) $canonical;
		}
		return (object) $out;
	}
}
