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
 * Exposes two routes:
 *
 * - `POST /wc/v3/fulfillments/import/prepare` uploads the CSV, parses headers, opens an ImportSession.
 * - `POST /wc/v3/fulfillments/import/run` processes one chunk against an existing session.
 *
 * @since 11.2.0
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
	 * @since 11.2.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'fulfillments_importer';
	}

	/**
	 * Register the routes for the importer.
	 *
	 * @since 11.2.0
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
								// Accept only the two forms emitted by ImportSession::generate_token():
								// bin2hex(random_bytes(16)) (32 hex chars) or the UUID v4 fallback.
								return is_string( $value )
									&& preg_match( '/^(?:[a-f0-9]{32}|[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})$/', $value ) === 1;
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
							'description'          => __( 'Map of CSV column index (stringified) to canonical column key.', 'woocommerce' ),
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
				'schema' => fn() => $this->get_schema_for_run(),
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
	 * Get the response schema for the run endpoint.
	 *
	 * @return array
	 */
	private function get_schema_for_run(): array {
		$counts_schema = array(
			'type'       => 'object',
			'properties' => array(
				'created'  => array( 'type' => 'integer' ),
				'updated'  => array( 'type' => 'integer' ),
				'skipped'  => array( 'type' => 'integer' ),
				'failed'   => array( 'type' => 'integer' ),
				'notified' => array( 'type' => 'integer' ),
			),
		);

		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Run fulfillments import chunk response.', 'woocommerce' );
		$schema['properties'] = array(
			'processed' => array(
				'type'        => 'integer',
				'description' => __( 'Cumulative number of processed rows.', 'woocommerce' ),
			),
			'total'     => array(
				'type'        => 'integer',
				'description' => __( 'Total number of CSV records.', 'woocommerce' ),
			),
			'done'      => array(
				'type'        => 'boolean',
				'description' => __( 'Whether the import is complete.', 'woocommerce' ),
			),
			'counts'    => $counts_schema,
			'rows'      => array(
				'type'        => 'array',
				'description' => __( 'Per-row results for this chunk.', 'woocommerce' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'row'            => array( 'type' => 'integer' ),
						'status'         => array( 'type' => 'string' ),
						'message'        => array( 'type' => 'string' ),
						'order_number'   => array( 'type' => 'string' ),
						'code'           => array( 'type' => 'string' ),
						'order_id'       => array( 'type' => 'integer' ),
						'fulfillment_id' => array( 'type' => 'integer' ),
						'notified'       => array( 'type' => 'boolean' ),
					),
				),
			),
			'errors'    => array(
				'type'        => 'array',
				'description' => __( 'Failed rows for this chunk.', 'woocommerce' ),
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'row'     => array( 'type' => 'integer' ),
						'code'    => array( 'type' => 'string' ),
						'message' => array( 'type' => 'string' ),
					),
				),
			),
			'summary'   => array_merge(
				$counts_schema,
				array( 'description' => __( 'Final summary, present on the last chunk only.', 'woocommerce' ) )
			),
		);
		return $schema;
	}

	/**
	 * Permission check for the import endpoints.
	 *
	 * @since 11.2.0
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
	 * @since 11.2.0
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
			FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_too_many_rows', 'csv_importer' );
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
	 * Run step: process one chunk against an existing session.
	 *
	 * @since 11.2.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
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
	 * The add_option() function checks for an existing row in PHP before inserting, so two
	 * concurrent requests can both pass that check. The lock is taken with INSERT IGNORE instead and
	 * let the unique index decide, the same way WP_Upgrader::create_lock() does. A lock
	 * older than the takeover threshold is claimed so a fatally interrupted chunk cannot
	 * wedge the import.
	 *
	 * @param string $lock_key Option name used as the lock.
	 * @return bool Whether the lock was acquired.
	 */
	private function acquire_run_lock( string $lock_key ): bool {
		global $wpdb;

		$now = (string) time();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The option API cannot express an atomic take.
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'no' )",
				$lock_key,
				$now
			)
		);
		$this->flush_run_lock_cache( $lock_key );

		if ( 1 === (int) $inserted ) {
			return true;
		}

		$held_since = (string) get_option( $lock_key, '' );
		if ( '' === $held_since || ( time() - (int) $held_since ) < MINUTE_IN_SECONDS ) {
			return false;
		}

		// Stale lock. Matching on the value we just read means only one of several racing
		// takeovers updates a row, so only one of them proceeds.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- See above.
		$claimed = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				$now,
				$lock_key,
				$held_since
			)
		);
		$this->flush_run_lock_cache( $lock_key );

		return 1 === (int) $claimed;
	}

	/**
	 * Drop the cached option value after writing the lock row directly.
	 *
	 * @param string $lock_key Option name used as the lock.
	 */
	private function flush_run_lock_cache( string $lock_key ): void {
		wp_cache_delete( $lock_key, 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/**
	 * Process one chunk while holding the per-session lock.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The incoming JSON request.
	 * @param int             $user_id Current user ID.
	 * @param string          $token   Session token.
	 * @return array|WP_Error
	 */
	private function handle_run_locked( WP_REST_Request $request, int $user_id, string $token ) {
		$session = ImportSession::load( $user_id, $token );
		if ( ! $session instanceof ImportSession ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_token_invalid',
				__( 'Import session is missing or has expired. Please re-upload the CSV.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		// Confirm the staged file is still the one we measured at prepare-time. Otherwise a
		// retained byte_offset would seek into the wrong bytes and silently import wrong rows.
		// Bypass the PHP stat cache so a just-modified file cannot present stale metadata.
		$session_file = $session->file();
		clearstatcache( true, $session_file );
		$file_missing = '' === $session_file || ! file_exists( $session_file );

		// The path comes from persisted transient state, so refuse anything outside the
		// uploads directory, mirroring the abandoned-file cleanup. Unreadable files
		// are left for the chunk abort path, which is retriable and keeps the session.
		$file_invalid = ! $file_missing
			&& is_readable( $session_file )
			&& ! $this->is_valid_staged_path( $session_file );

		$expected_size  = $session->file_size();
		$expected_mtime = $session->file_mtime();
		$expected_hash  = $session->file_head_hash();
		$current_size   = $file_missing ? 0 : (int) filesize( $session_file );
		$current_mtime  = $file_missing ? 0 : (int) filemtime( $session_file );
		$current_hash   = $file_missing ? '' : ImportSession::hash_file_head( $session_file );
		$size_changed   = $expected_size > 0 && $expected_size !== $current_size;
		$mtime_changed  = $expected_mtime > 0 && $expected_mtime !== $current_mtime;
		$hash_changed   = '' !== $expected_hash && '' !== $current_hash && $expected_hash !== $current_hash;

		if ( $file_missing || $file_invalid || $size_changed || $mtime_changed || $hash_changed ) {
			// The session's scheduled cleanup is unscheduled on delete, so remove the
			// stale staged file now or nothing ever will.
			if ( ! $file_invalid ) {
				$this->delete_staged_file( $session_file, $session->attachment_id() );
			}
			$session->delete();
			return new WP_Error(
				'woocommerce_fulfillments_import_file_changed',
				__( 'The staged CSV was modified or removed. Please re-upload the file.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		$offset = (int) $request->get_param( 'offset' );
		$limit  = (int) $request->get_param( 'limit' );
		if ( $limit <= 0 ) {
			$limit = FulfillmentsCsvImporter::DEFAULT_CHUNK_SIZE;
		}
		$limit = min( $limit, FulfillmentsCsvImporter::resolve_chunk_size() );

		// Mapping and options are frozen into the session by the first chunk and
		// ignored afterwards; otherwise an API caller could import different row
		// ranges of the same session under different rules.
		$mapping = $session->frozen_mapping();
		if ( null === $mapping ) {
			$mapping = $this->normalize_mapping_input( $request->get_param( 'mapping' ) );

			$header_map = FulfillmentsCsvImporter::mapping_to_header_map( $mapping );
			$missing    = FulfillmentsCsvImporter::find_missing_required_columns( $header_map );
			if ( ! empty( $missing ) ) {
				return new WP_Error(
					'woocommerce_fulfillments_import_mapping_invalid',
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

			$session->freeze_run_settings( $mapping, $notify_customer, $update_existing );
		} else {
			$notify_customer = $session->notify_customer();
			$update_existing = $session->update_existing();
		}

		// Idempotency guard: a client retry can arrive after the server already processed
		// and persisted this chunk (for example when only the response was lost). Rows at
		// this offset must not be imported twice, so return the recorded progress instead.
		if ( $offset < $session->processed() ) {
			return $this->build_run_response( $session, array(), array() );
		}

		// An offset ahead of the recorded progress would import from the stored byte
		// position anyway but record offset + consumed, inflating progress and ending
		// the import with tail rows unprocessed. The shipped client always resumes
		// from the server-reported processed count, so reject the mismatch.
		if ( $offset > $session->processed() ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_offset_mismatch',
				__( 'The requested offset is ahead of the recorded progress. Please resume from the last confirmed position.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		// Notified rows send mail synchronously, so cap the per-request row count to
		// keep a chunk within typical execution time limits.
		if ( $notify_customer ) {
			$limit = min( $limit, FulfillmentsCsvImporter::NOTIFY_CHUNK_SIZE );
		}

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
			$summary       = $session->summary();
			$file          = $session->file();
			$attachment_id = $session->attachment_id();
			$session->delete();
			$this->delete_staged_file( $file, $attachment_id );

			/**
			 * Fires after a bulk fulfillments CSV import completes.
			 *
			 * @since 11.2.0
			 *
			 * @param array $summary Import summary counts. Carries created, updated, skipped,
			 *                       failed and notified totals, plus an always-empty rows key;
			 *                       per-row results are streamed to the client, not persisted.
			 */
			do_action( 'woocommerce_fulfillments_csv_import_completed', $summary );

			$response['summary'] = $summary;
		}

		return $response;
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
				FulfillmentsTracker::track_fulfillment_validation_error( 'import', 'woocommerce_fulfillments_import_upload_failed', 'csv_importer' );
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
				'row'          => (int) ( $row['row'] ?? 0 ),
				'status'       => (string) ( $row['status'] ?? '' ),
				'message'      => (string) ( $row['message'] ?? '' ),
				'order_number' => (string) ( $row['order_number'] ?? '' ),
			);
			if ( isset( $row['code'] ) ) {
				$entry['code'] = (string) $row['code'];
			}
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
