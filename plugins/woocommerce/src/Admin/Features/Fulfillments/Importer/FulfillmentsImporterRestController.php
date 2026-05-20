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
 * Exposes three routes:
 *
 * - `POST /wc/v3/fulfillments/import`         — back-compat orchestrator that runs the whole import in one call.
 * - `POST /wc/v3/fulfillments/import/prepare` — uploads, parses headers, opens an ImportSession.
 * - `POST /wc/v3/fulfillments/import/run`     — processes one chunk against an existing session.
 *
 * @since 10.9.0
 */
class FulfillmentsImporterRestController extends RestApiControllerBase {

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
							'type'        => 'string',
							'default'     => ',',
							'description' => __( 'CSV delimiter. Defaults to comma.', 'woocommerce' ),
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
			)
		);

		register_rest_route(
			$this->route_namespace,
			$this->rest_base . '/run',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'handle_run' ),
					'permission_callback' => fn( WP_REST_Request $request ) => $this->check_permission_for_fulfillments_import( $request ),
					'args'                => array(
						'token'   => array(
							'type'        => 'string',
							'required'    => true,
							'description' => __( 'Import session token returned by /prepare.', 'woocommerce' ),
						),
						'offset'  => array(
							'type'        => 'integer',
							'default'     => 0,
							'minimum'     => 0,
							'description' => __( 'Zero-based row offset to start the chunk at.', 'woocommerce' ),
						),
						'limit'   => array(
							'type'        => 'integer',
							'default'     => FulfillmentsCsvImporter::DEFAULT_CHUNK_SIZE,
							'minimum'     => 1,
							'maximum'     => FulfillmentsCsvImporter::MAX_CHUNK_SIZE,
							'description' => __( 'Maximum number of rows to process in this chunk.', 'woocommerce' ),
						),
						'mapping' => array(
							'type'                 => 'object',
							'required'             => true,
							'description'          => __( 'CSV column index (stringified) → canonical column key.', 'woocommerce' ),
							'additionalProperties' => array(
								'type' => 'string',
								'enum' => array(
									'',
									FulfillmentsCsvImporter::COL_ORDER_NUMBER,
									FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
									FulfillmentsCsvImporter::COL_PROVIDER,
									FulfillmentsCsvImporter::COL_TRACKING_URL,
									FulfillmentsCsvImporter::COL_ITEMS,
								),
							),
						),
						'options' => array(
							'type'                 => 'object',
							'default'              => array(),
							'additionalProperties' => false,
							'properties'           => array(
								'notify_customer' => array( 'type' => 'boolean' ),
								'update_existing' => array( 'type' => 'boolean' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Permission check for the import endpoints.
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
	 * Back-compat orchestrator: runs the legacy single-shot endpoint by delegating to the chunked
	 * importer internals. Returns the same response shape callers depended on before the wizard.
	 *
	 * @since 10.9.0
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return array|WP_Error
	 */
	protected function handle_import( WP_REST_Request $request ) {
		$file_path = $this->stage_uploaded_csv( $request );
		if ( $file_path instanceof WP_Error ) {
			return $file_path;
		}

		// FulfillmentsCsvImporter is constructed per-request with a file path and runtime options,
		// so it is intentionally not container-managed.
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

	/**
	 * Prepare step: validate + stage the upload, parse headers, open a session.
	 *
	 * @since 10.9.0
	 *
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
			$prior_file = $prior->file();
			if ( '' !== $prior_file && file_exists( $prior_file ) ) {
				wp_delete_file( $prior_file );
			}
			$prior->delete();
		}

		$file_path = $this->stage_uploaded_csv( $request );
		if ( $file_path instanceof WP_Error ) {
			return $file_path;
		}

		$importer = new FulfillmentsCsvImporter(
			$file_path,
			array(
				'notify_customer' => $notify,
				'update_existing' => $update,
			)
		);

		$parsed = $importer->parse_headers( $delimiter_param );
		if ( isset( $parsed['error'] ) ) {
			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
			return new WP_Error(
				'fulfillments_csv_parse_error',
				(string) $parsed['error']['message'],
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$session = ImportSession::create(
			$user_id,
			$file_path,
			(string) $parsed['delimiter'],
			(array) $parsed['headers'],
			(int) $parsed['total'],
			$notify,
			$update
		);

		return array(
			'token'            => $session->token(),
			'headers'          => $parsed['headers'],
			'sample'           => $parsed['sample'],
			'total'            => $parsed['total'],
			'detected_mapping' => $this->mapping_for_response( (array) $parsed['detected_mapping'] ),
			'delimiter'        => $parsed['delimiter'],
		);
	}

	/**
	 * Run step: process one chunk against an existing session.
	 *
	 * @since 10.9.0
	 *
	 * @param WP_REST_Request $request The incoming JSON request.
	 * @return array|WP_Error
	 */
	protected function handle_run( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$token   = (string) $request->get_param( 'token' );

		$session = ImportSession::load( $user_id, $token );
		if ( ! $session instanceof ImportSession ) {
			return new WP_Error(
				'fulfillments_import_token_invalid',
				__( 'Import session is missing or has expired. Please re-upload the CSV.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$offset = (int) $request->get_param( 'offset' );
		$limit  = (int) $request->get_param( 'limit' );
		if ( $limit <= 0 ) {
			$limit = FulfillmentsCsvImporter::DEFAULT_CHUNK_SIZE;
		}
		$limit   = min( $limit, FulfillmentsCsvImporter::resolve_chunk_size() );
		$mapping = $this->normalize_mapping_input( $request->get_param( 'mapping' ) );

		$header_map = $this->mapping_to_header_map( $mapping );
		$missing    = $this->find_missing_required_columns( $header_map );
		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'fulfillments_import_mapping_invalid',
				sprintf(
					/* translators: %s: comma-separated list of missing column names. */
					__( 'Mapping is missing required column(s): %s.', 'woocommerce' ),
					implode( ', ', $missing )
				),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$options_param   = (array) $request->get_param( 'options' );
		$notify_customer = array_key_exists( 'notify_customer', $options_param ) ? (bool) $options_param['notify_customer'] : $session->notify_customer();
		$update_existing = array_key_exists( 'update_existing', $options_param ) ? (bool) $options_param['update_existing'] : $session->update_existing();

		$importer = new FulfillmentsCsvImporter(
			$session->file(),
			array(
				'notify_customer' => $notify_customer,
				'update_existing' => $update_existing,
				'delimiter'       => $session->delimiter(),
			)
		);

		$chunk_result = $importer->import_chunk(
			$offset,
			$limit,
			$mapping,
			array(
				'notify_customer'     => $notify_customer,
				'update_existing'     => $update_existing,
				'delimiter'           => $session->delimiter(),
				'seen_tracking_pairs' => $session->seen_tracking_pairs(),
				'byte_offset'         => $session->byte_offset(),
			)
		);

		$counts          = (array) $chunk_result['counts'];
		$rows            = (array) $chunk_result['rows'];
		$seen            = (array) $chunk_result['seen_tracking_pairs'];
		$next_byte       = isset( $chunk_result['byte_offset'] ) ? (int) $chunk_result['byte_offset'] : 0;
		$consumed        = isset( $chunk_result['consumed'] ) ? max( 0, (int) $chunk_result['consumed'] ) : 0;
		$processed_after = min( $session->total(), $offset + $consumed );
		$errors_for_ui   = $this->extract_errors_for_response( $rows );

		$session->record_chunk( $processed_after, $counts, $rows, $seen, $next_byte );

		$processed = $session->processed();
		$total     = $session->total();
		// EOF reached when the importer returns fewer rows than the requested limit.
		$done = $processed >= $total || $consumed < $limit;

		$response = array(
			'processed' => $processed,
			'total'     => $total,
			'done'      => $done,
			'counts'    => $session->counts(),
			'errors'    => $errors_for_ui,
		);

		if ( $done ) {
			$summary = $session->summary();
			$file    = $session->file();
			$session->delete();
			if ( '' !== $file && file_exists( $file ) ) {
				wp_delete_file( $file );
			}

			/** This filter is documented above. */
			do_action( 'woocommerce_fulfillments_csv_import_completed', $summary );

			$response['summary'] = $summary;
		}

		return $response;
	}

	/**
	 * Validate the multipart file, hand it to CSVUploadHelper, and return the staged absolute path.
	 *
	 * @param WP_REST_Request $request Incoming request carrying the multipart upload.
	 * @return string|WP_Error
	 */
	private function stage_uploaded_csv( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_no_file', 'csv_importer' );
			return new WP_Error(
				'woocommerce_fulfillments_import_no_file',
				__( 'No CSV file was uploaded.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$upload_limit = (int) apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$file_size    = isset( $files['file']['size'] ) ? (int) $files['file']['size'] : 0;
		if ( $upload_limit > 0 && $file_size > $upload_limit ) {
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_file_too_large', 'csv_importer' );
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

		// CSVUploadHelper reads $_FILES under a configurable key. Stage our REST file under that key,
		// then unconditionally restore the superglobal in finally so the assignment cannot leak.
		// Nonce verification is handled by the REST permission_callback, not nonces.
		$_FILES['fulfillment_import_file'] = $files['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$file_path = '';
		try {
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
				$file_path  = (string) ( $upload['file'] ?? '' );

				FilesystemUtil::validate_upload_file_path( $file_path );

				if ( ! wc_is_file_valid_csv( $file_path ) ) {
					throw new \Exception( __( 'Invalid file type. The importer supports CSV and TXT file formats.', 'woocommerce' ) );
				}
			} catch ( \Throwable $e ) {
				if ( '' !== $file_path && file_exists( $file_path ) ) {
					wp_delete_file( $file_path );
				}
				FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_upload_failed', 'csv_importer' );
				return new WP_Error(
					'woocommerce_fulfillments_import_upload_failed',
					$e->getMessage(),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			}
		} finally {
			unset( $_FILES['fulfillment_import_file'] );
		}

		return $file_path;
	}

	/**
	 * Normalize a mapping object from the request body into an int-keyed array.
	 *
	 * @param mixed $raw Raw mapping (object or array, string-keyed by CSV column index).
	 * @return array<int, string>
	 */
	private function normalize_mapping_input( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $col => $canonical ) {
			if ( ! is_numeric( $col ) ) {
				continue;
			}
			$canonical = is_string( $canonical ) ? trim( $canonical ) : '';
			if ( '' === $canonical ) {
				continue;
			}
			$out[ (int) $col ] = $canonical;
		}
		return $out;
	}

	/**
	 * Stringify mapping keys for the JSON response (CSV column indexes survive a round-trip).
	 *
	 * @param array<int, string> $mapping CSV column index → canonical key.
	 * @return array<string, string>
	 */
	private function mapping_for_response( array $mapping ): array {
		$out = array();
		foreach ( $mapping as $col => $canonical ) {
			$out[ (string) $col ] = (string) $canonical;
		}
		return $out;
	}

	/**
	 * Invert a column-index mapping into the canonical-keyed shape used to detect missing required columns.
	 *
	 * @param array<int, string> $mapping CSV column index → canonical key.
	 * @return array<string, int>
	 */
	private function mapping_to_header_map( array $mapping ): array {
		$out = array();
		foreach ( $mapping as $col => $canonical ) {
			$canonical = is_string( $canonical ) ? trim( $canonical ) : '';
			if ( '' === $canonical || isset( $out[ $canonical ] ) ) {
				continue;
			}
			$out[ $canonical ] = (int) $col;
		}
		return $out;
	}

	/**
	 * Return the canonical keys that are required but not present in the supplied header map.
	 *
	 * @param array<string, int> $header_map Canonical column key → CSV column index.
	 * @return array<int, string>
	 */
	private function find_missing_required_columns( array $header_map ): array {
		$required = array(
			FulfillmentsCsvImporter::COL_ORDER_NUMBER,
			FulfillmentsCsvImporter::COL_TRACKING_NUMBER,
			FulfillmentsCsvImporter::COL_PROVIDER,
		);
		$missing  = array();
		foreach ( $required as $key ) {
			if ( ! isset( $header_map[ $key ] ) ) {
				$missing[] = $key;
			}
		}
		return $missing;
	}

	/**
	 * Reduce per-row results to the failed-row shape returned in the `errors` array on each chunk response.
	 *
	 * @param array<int, array<string, mixed>> $rows Per-row results from import_chunk().
	 * @return array<int, array{row:int, code:string, message:string}>
	 */
	private function extract_errors_for_response( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || ( $row['status'] ?? '' ) !== 'failed' ) {
				continue;
			}
			$out[] = array(
				'row'     => (int) ( $row['row'] ?? 0 ),
				'code'    => (string) ( $row['code'] ?? 'failed' ),
				'message' => (string) ( $row['message'] ?? '' ),
			);
		}
		return $out;
	}
}
