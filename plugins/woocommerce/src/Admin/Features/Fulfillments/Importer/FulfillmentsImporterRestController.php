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
 * - `POST /wc/v3/fulfillments/import`         — single-shot orchestrator that runs the whole import in one call.
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
							'type'              => 'string',
							'required'          => true,
							'description'       => __( 'Import session token returned by /prepare.', 'woocommerce' ),
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => static function ( $value ) {
								// Accept the bin2hex(random_bytes(16)) form (32 hex chars) or the
								// UUID v4 fallback emitted by ImportSession::generate_token().
								return is_string( $value ) && preg_match( '/^[a-f0-9\-]{32,36}$/', $value ) === 1;
							},
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
	 * Single-shot orchestrator: stages the upload and runs the full import in one request.
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

		if ( ! $session->persisted() ) {
			$session->delete();
			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
			return new WP_Error(
				'woocommerce_fulfillments_import_session_failed',
				__( 'The import session could not be saved. Please try again.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

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

		// Serialize chunk processing per session. Two concurrent runs would read the same
		// byte offset and import the same rows twice, duplicating fulfillments and emails.
		$lock_key = 'wc_fulfillment_import_lock_' . $user_id . '_' . $token;
		if ( ! $this->acquire_run_lock( $lock_key ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_chunk_in_progress',
				__( 'Another chunk of this import is still being processed. Please retry in a moment.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		try {
			return $this->handle_run_locked( $request, $user_id, $token );
		} finally {
			delete_option( $lock_key );
		}
	}

	/**
	 * Acquire the per-session run lock.
	 *
	 * Uses add_option() as an atomic take; a stale lock older than the takeover
	 * threshold is claimed so a fatally interrupted chunk cannot wedge the import.
	 *
	 * @param string $lock_key Option name used as the lock.
	 * @return bool Whether the lock was acquired.
	 */
	private function acquire_run_lock( string $lock_key ): bool {
		if ( add_option( $lock_key, (string) time(), '', false ) ) {
			return true;
		}

		$held_since = (int) get_option( $lock_key );
		if ( $held_since > 0 && ( time() - $held_since ) < MINUTE_IN_SECONDS ) {
			return false;
		}

		update_option( $lock_key, (string) time(), false );
		return true;
	}

	/**
	 * Process one chunk while holding the per-session lock.
	 *
	 * @param WP_REST_Request $request The incoming JSON request.
	 * @param int             $user_id Current user ID.
	 * @param string          $token   Session token.
	 * @return array|WP_Error
	 */
	private function handle_run_locked( WP_REST_Request $request, int $user_id, string $token ) {
		$session = ImportSession::load( $user_id, $token );
		if ( ! $session instanceof ImportSession ) {
			return new WP_Error(
				'fulfillments_import_token_invalid',
				__( 'Import session is missing or has expired. Please re-upload the CSV.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		// Confirm the staged file is still the one we measured at prepare-time. Otherwise a
		// retained byte_offset would seek into the wrong bytes and silently import wrong rows.
		// Bypass the PHP stat cache so a just-modified file cannot present stale metadata.
		$session_file = $session->file();
		clearstatcache( true, $session_file );
		$expected_size  = $session->file_size();
		$expected_mtime = $session->file_mtime();
		$current_size   = file_exists( $session_file ) ? (int) filesize( $session_file ) : 0;
		$current_mtime  = file_exists( $session_file ) ? (int) filemtime( $session_file ) : 0;
		$size_changed   = $expected_size > 0 && $expected_size !== $current_size;
		$mtime_changed  = $expected_mtime > 0 && $expected_mtime !== $current_mtime;
		if ( ! file_exists( $session_file ) || $size_changed || $mtime_changed ) {
			$session->delete();
			return new WP_Error(
				'fulfillments_import_file_changed',
				__( 'The staged CSV was modified or removed. Please re-upload the file.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		$offset = (int) $request->get_param( 'offset' );
		$limit  = (int) $request->get_param( 'limit' );
		if ( $limit <= 0 ) {
			$limit = FulfillmentsCsvImporter::DEFAULT_CHUNK_SIZE;
		}
		$limit   = min( $limit, FulfillmentsCsvImporter::resolve_chunk_size() );
		$mapping = $this->normalize_mapping_input( $request->get_param( 'mapping' ) );

		$header_map = FulfillmentsCsvImporter::mapping_to_header_map( $mapping );
		$missing    = FulfillmentsCsvImporter::find_missing_required_columns( $header_map );
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

		// Idempotency guard: a client retry can arrive after the server already processed
		// and persisted this chunk (for example when only the response was lost). Rows at
		// this offset must not be imported twice, so return the recorded progress instead.
		if ( $offset < $session->processed() ) {
			return $this->build_run_response( $session, array(), array() );
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

		// A chunk-level abort (unreadable file, failed open) is not completion: keep the
		// session and staged file so the client can retry the same chunk.
		if ( ! empty( $chunk_result['aborted'] ) ) {
			$first_row  = is_array( $chunk_result['rows'] ?? null ) ? reset( $chunk_result['rows'] ) : false;
			$abort_code = is_array( $first_row ) ? (string) ( $first_row['code'] ?? 'chunk_failed' ) : 'chunk_failed';
			$message    = is_array( $first_row ) && '' !== (string) ( $first_row['message'] ?? '' )
				? (string) $first_row['message']
				: __( 'The import chunk could not be processed. Please try again.', 'woocommerce' );
			$retriable  = in_array( $abort_code, array( 'file_not_readable', 'file_open_failed' ), true );

			return new WP_Error(
				'woocommerce_fulfillments_import_chunk_failed',
				$message,
				array(
					'status' => $retriable ? WP_Http::INTERNAL_SERVER_ERROR : WP_Http::BAD_REQUEST,
					'code'   => $abort_code,
				)
			);
		}

		$counts          = (array) $chunk_result['counts'];
		$rows            = (array) $chunk_result['rows'];
		$seen            = (array) $chunk_result['seen_tracking_pairs'];
		$next_byte       = isset( $chunk_result['byte_offset'] ) ? (int) $chunk_result['byte_offset'] : 0;
		$consumed        = isset( $chunk_result['consumed'] ) ? max( 0, (int) $chunk_result['consumed'] ) : 0;
		$processed_after = min( $session->total(), $offset + $consumed );
		$rows_for_ui     = $this->prepare_rows_for_response( $rows );
		$errors_for_ui   = $this->extract_errors_for_response( $rows );

		// If progress cannot be stored the client must not advance: a later chunk would
		// resume from a stale byte offset and import the same rows again. The rows of
		// this chunk are already saved, so a retry updates them in place.
		if ( ! $session->record_chunk( $processed_after, $counts, $seen, $next_byte ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_progress_failed',
				__( 'Import progress could not be saved. Please retry.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		return $this->build_run_response( $session, $rows_for_ui, $errors_for_ui, ! empty( $chunk_result['eof'] ) );
	}

	/**
	 * Build the chunk response from the session's recorded progress, finalizing when done.
	 *
	 * @param ImportSession                    $session Session after the chunk was recorded.
	 * @param array<int, array<string, mixed>> $rows    Row results for this response.
	 * @param array<int, array<string, mixed>> $errors  Failed-row entries for this response.
	 * @param bool                             $eof     Whether the importer reached end of file.
	 * @return array
	 */
	private function build_run_response( ImportSession $session, array $rows, array $errors, bool $eof = false ): array {
		$processed = $session->processed();
		$total     = $session->total();
		$done      = $processed >= $total || $eof;

		$response = array(
			'processed' => $processed,
			'total'     => $total,
			'done'      => $done,
			'counts'    => $session->counts(),
			'rows'      => $rows,
			'errors'    => $errors,
		);

		if ( $done ) {
			$summary = $session->summary();
			$file    = $session->file();
			$session->delete();
			if ( '' !== $file && file_exists( $file ) ) {
				wp_delete_file( $file );
			}

			/**
			 * Fires after a bulk fulfillments CSV import completes.
			 *
			 * @since 11.1.0
			 *
			 * @param array $summary Import summary counts.
			 */
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
	 *
	 * @throws \Exception When staged-file validation fails; caught internally and returned as a WP_Error.
	 */
	protected function stage_uploaded_csv( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_no_file', 'csv_importer' );
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

		// CSVUploadHelper reads from $_FILES under a configurable key. Stage our REST file under
		// that key and restore the superglobal in finally so the assignment cannot leak.
		// The REST permission_callback handles authentication, hence the phpcs ignore below.
		$_FILES['fulfillment_import_file'] = $files['file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$file_path                         = '';
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
			} catch ( \Exception $e ) {
				if ( '' !== $file_path && file_exists( $file_path ) ) {
					wp_delete_file( $file_path );
				}
				FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_upload_failed', 'csv_importer' );
				return new WP_Error(
					'woocommerce_fulfillments_import_upload_failed',
					$e->getMessage(),
					array( 'status' => WP_Http::BAD_REQUEST )
				);
			} catch ( \Throwable $e ) {
				if ( '' !== $file_path && file_exists( $file_path ) ) {
					wp_delete_file( $file_path );
				}
				wc_get_logger()->error(
					'Fulfillments importer upload failed: ' . $e->getMessage(),
					array( 'source' => 'fulfillments-csv-importer' )
				);
				FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_upload_failed', 'csv_importer' );
				return new WP_Error(
					'woocommerce_fulfillments_import_upload_failed',
					__( 'The upload could not be processed. Please try again.', 'woocommerce' ),
					array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
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
	 * Stringify mapping keys so JSON-encoded CSV column indexes round-trip back to the client.
	 *
	 * @param array<int, string> $mapping CSV column index => canonical key.
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
	 * Shape per-row results for the chunk response.
	 *
	 * Per-row data is returned to the client every chunk and accumulated there, so the
	 * session transient never has to grow to hold every processed row.
	 *
	 * @param array<int, array<string, mixed>> $rows Per-row results from import_chunk().
	 * @return array<int, array<string, mixed>>
	 */
	private function prepare_rows_for_response( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$entry = array(
				'row'     => (int) ( $row['row'] ?? 0 ),
				'status'  => (string) ( $row['status'] ?? '' ),
				'message' => (string) ( $row['message'] ?? '' ),
			);
			if ( isset( $row['order_id'] ) ) {
				$entry['order_id'] = (int) $row['order_id'];
			}
			if ( isset( $row['fulfillment_id'] ) ) {
				$entry['fulfillment_id'] = (int) $row['fulfillment_id'];
			}
			if ( isset( $row['notified'] ) ) {
				$entry['notified'] = (bool) $row['notified'];
			}
			$out[] = $entry;
		}
		return $out;
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
