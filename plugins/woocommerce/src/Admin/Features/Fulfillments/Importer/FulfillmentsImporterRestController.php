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
use Automattic\WooCommerce\Utilities\OrderUtil;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller backing the Fulfillments CSV importer.
 *
 * Exposes `POST /wc/v3/fulfillments/import/prepare`, which uploads the CSV, parses the
 * headers and opens an ImportSession for the wizard's column-mapping step, and
 * `POST /wc/v3/fulfillments/import/run`, which imports the staged file one chunk per request.
 *
 * @internal
 * @since 11.4.0
 */
class FulfillmentsImporterRestController extends RestApiControllerBase {

	/**
	 * REST API base.
	 *
	 * @var string
	 */
	protected string $rest_base = '/fulfillments/import';

	/**
	 * Prefix of the option row that locks a user's import while one of its chunks runs.
	 */
	private const RUN_LOCK_PREFIX = 'woocommerce_fulfillments_import_lock_';

	/**
	 * Seconds after which a run lock that was never released can be taken over.
	 *
	 * A lock is only left behind when PHP is killed mid-chunk, so this is set well above
	 * the time a chunk takes: taking over a chunk that is still running imports it twice.
	 */
	private const RUN_LOCK_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Upload helper that stages the CSV under the wc-imports directory.
	 *
	 * @var CSVUploadHelper
	 */
	private CSVUploadHelper $csv_upload_helper;

	/**
	 * Receive the upload helper from the container.
	 *
	 * @internal
	 * @since 11.4.0
	 *
	 * @param CSVUploadHelper $csv_upload_helper Upload helper.
	 */
	final public function init( CSVUploadHelper $csv_upload_helper ): void {
		$this->csv_upload_helper = $csv_upload_helper;
	}

	/**
	 * Get the WooCommerce REST API namespace key for this controller.
	 *
	 * @since 11.4.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'fulfillments_importer';
	}

	/**
	 * Register the routes for the importer.
	 *
	 * @since 11.4.0
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
							'description'       => __( 'Single ASCII character used as the CSV delimiter, or "tab". Defaults to comma.', 'woocommerce' ),
							'validate_callback' => fn( $value ) => $this->validate_delimiter( $value ),
							'sanitize_callback' => array( FulfillmentsCsvImporter::class, 'normalize_delimiter' ),
						),
						'notify_customer' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Initial value for the import run: whether customers are notified about imported fulfillments. Stored on the session.', 'woocommerce' ),
						),
						'update_existing' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Initial value for the import run: whether a fulfillment with the same tracking number is updated instead of skipped. Stored on the session.', 'woocommerce' ),
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
							'type'        => 'string',
							'required'    => true,
							'pattern'     => '^([a-f0-9]{32}|[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})$',
							'description' => __( 'Import session token returned by the prepare endpoint.', 'woocommerce' ),
						),
						'offset'  => array(
							'type'        => 'integer',
							'default'     => 0,
							'minimum'     => 0,
							'description' => __( 'Number of data rows processed so far, as reported by the previous response.', 'woocommerce' ),
						),
						'limit'   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => FulfillmentsCsvImporter::MAX_CHUNK_SIZE,
							'description' => __( 'Most data rows to import in this request. Defaults to, and is capped by, the chunk size of the store.', 'woocommerce' ),
						),
						'mapping' => array(
							'type'                 => 'object',
							'required'             => true,
							'description'          => __( 'Canonical column for each CSV column index. Stored by the first chunk and ignored afterwards.', 'woocommerce' ),
							'patternProperties'    => array(
								'^[0-9]+$' => array(
									'type' => 'string',
									'enum' => FulfillmentsCsvImporter::canonical_columns(),
								),
							),
							'additionalProperties' => false,
						),
						'options' => array(
							'type'                 => 'object',
							'default'              => array(),
							'description'          => __( 'Run options. Stored by the first chunk and ignored afterwards; an omitted option keeps the value sent to the prepare endpoint.', 'woocommerce' ),
							'properties'           => array(
								'notify_customer' => array(
									'type'        => 'boolean',
									'description' => __( 'Whether customers are notified about imported fulfillments.', 'woocommerce' ),
								),
								'update_existing' => array(
									'type'        => 'boolean',
									'description' => __( 'Whether a fulfillment with the same tracking number is updated instead of skipped.', 'woocommerce' ),
								),
							),
							'additionalProperties' => false,
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
				'description' => __( 'Import session token to pass to the next step of the import.', 'woocommerce' ),
			),
			'headers'          => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'string' ),
				'description' => __( 'Header row of the staged CSV.', 'woocommerce' ),
			),
			'sample'           => array(
				'type'        => 'array',
				'items'       => array( 'type' => 'string' ),
				'description' => __( 'First non-blank data row, for the mapping preview. Cells are trimmed and padded to the header length.', 'woocommerce' ),
			),
			'total'            => array(
				'type'        => 'integer',
				'description' => __( 'Number of non-blank data rows after the header.', 'woocommerce' ),
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
		$counts = array(
			'created'  => array(
				'type'        => 'integer',
				'description' => __( 'Rows that created a fulfillment.', 'woocommerce' ),
			),
			'updated'  => array(
				'type'        => 'integer',
				'description' => __( 'Rows that updated a fulfillment.', 'woocommerce' ),
			),
			'skipped'  => array(
				'type'        => 'integer',
				'description' => __( 'Rows that changed nothing.', 'woocommerce' ),
			),
			'failed'   => array(
				'type'        => 'integer',
				'description' => __( 'Rows that could not be imported.', 'woocommerce' ),
			),
			'notified' => array(
				'type'        => 'integer',
				'description' => __( 'Rows whose customer notification action ran.', 'woocommerce' ),
			),
		);
		$rows   = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'row'            => array(
						'type'        => 'integer',
						'description' => __( 'Record number of the row in the file, counting the header and blank lines.', 'woocommerce' ),
					),
					'order_number'   => array(
						'type'        => 'string',
						'description' => __( 'Order number cell of the row.', 'woocommerce' ),
					),
					'status'         => array(
						'type'        => 'string',
						'description' => __( 'Result of the row.', 'woocommerce' ),
						'enum'        => array(
							FulfillmentsCsvImporter::STATUS_CREATED,
							FulfillmentsCsvImporter::STATUS_UPDATED,
							FulfillmentsCsvImporter::STATUS_SKIPPED,
							FulfillmentsCsvImporter::STATUS_FAILED,
						),
					),
					'message'        => array(
						'type'        => 'string',
						'description' => __( 'Plain text that can quote values from the file. Escape it before output.', 'woocommerce' ),
					),
					'code'           => array(
						'type'        => 'string',
						'description' => __( 'Failure code, on failed rows only.', 'woocommerce' ),
					),
					'order_id'       => array(
						'type'        => 'integer',
						'description' => __( 'ID of the order, once it is found.', 'woocommerce' ),
					),
					'order_edit_url' => array(
						'type'        => 'string',
						'format'      => 'uri',
						'description' => __( 'Admin URL of the order.', 'woocommerce' ),
					),
					'fulfillment_id' => array(
						'type'        => 'integer',
						'description' => __( 'ID of the fulfillment the row matched or saved.', 'woocommerce' ),
					),
					'notified'       => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the customer notification action ran for the row.', 'woocommerce' ),
					),
				),
			),
		);

		$schema               = $this->get_base_schema();
		$schema['title']      = __( 'Run fulfillments import chunk response.', 'woocommerce' );
		$schema['properties'] = array(
			'processed' => array(
				'type'        => 'integer',
				'description' => __( 'Data rows processed so far. Send it as the offset of the next request.', 'woocommerce' ),
			),
			'total'     => array(
				'type'        => 'integer',
				'description' => __( 'Number of non-blank data rows in the file.', 'woocommerce' ),
			),
			'done'      => array(
				'type'        => 'boolean',
				'description' => __( 'Whether every data row has been processed.', 'woocommerce' ),
			),
			'counts'    => array(
				'type'        => 'object',
				'description' => __( 'Results of all chunks so far.', 'woocommerce' ),
				'properties'  => $counts,
			),
			'rows'      => array_merge(
				$rows,
				array( 'description' => __( 'Result of each row in this chunk.', 'woocommerce' ) )
			),
			'summary'   => array(
				'type'        => 'object',
				'description' => __( 'Final counts, present once the import is done. Its rows list is always empty.', 'woocommerce' ),
				'properties'  => array_merge( $counts, array( 'rows' => $rows ) ),
			),
		);
		return $schema;
	}

	/**
	 * Permission check for the import endpoint.
	 *
	 * @since 11.4.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The request for which the permission is checked.
	 * @return bool|WP_Error True when allowed; WP_Error otherwise.
	 */
	protected function check_permission_for_fulfillments_import( WP_REST_Request $request ) {
		return $this->check_permission( $request, 'manage_woocommerce' );
	}

	/**
	 * Validate the delimiter argument: one ASCII byte, a tab spelling, or empty for the default.
	 *
	 * The check mirrors FulfillmentsCsvImporter::normalize_delimiter(): a value the
	 * normalizer would replace (a quote, a line break, a non-ASCII byte, more than one
	 * byte) is rejected here instead of being read as a comma without telling the caller.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $value Raw request value.
	 * @return true|WP_Error
	 */
	protected function validate_delimiter( $value ) {
		if ( ! is_string( $value ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_invalid_delimiter',
				__( 'The delimiter must be a string.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}
		if ( '' === $value || FulfillmentsCsvImporter::is_tab_spelling( $value ) ) {
			return true;
		}
		if ( strlen( $value ) > 1 || FulfillmentsCsvImporter::normalize_delimiter( $value ) !== $value ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_invalid_delimiter',
				__( 'The delimiter must be a single ASCII character or "tab".', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}
		return true;
	}

	/**
	 * Prepare step: validate and stage the upload, parse the headers, open a session.
	 *
	 * The prior session of the user is kept until the new upload is stored and parsed, so
	 * a rejected upload (400 or 413) leaves it intact. ImportSession::create() decides which
	 * session is replaced, so two overlapping prepares by the same user each delete the
	 * staged file of the session they retired. A 500 from a session that could not be
	 * stored comes after that point, so the prior session is gone on that path too.
	 * The replacement takes the run lock of the user, so a session is never replaced while
	 * one of its chunks is being imported: that prepare gets a 409 and its upload is dropped.
	 *
	 * @since 11.4.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The incoming multipart request.
	 * @return array|WP_Error
	 */
	protected function handle_prepare( WP_REST_Request $request ) {
		$delimiter_param = (string) $request->get_param( 'delimiter' );
		$notify          = (bool) $request->get_param( 'notify_customer' );
		$update          = (bool) $request->get_param( 'update_existing' );
		$user_id         = get_current_user_id();

		$staged = $this->stage_uploaded_csv( $request );
		if ( $staged instanceof WP_Error ) {
			return $staged;
		}
		$file_path     = (string) $staged['file'];
		$attachment_id = (int) $staged['id'];

		$parsed = ( new FulfillmentsCsvImporter( $file_path ) )->parse_headers( $delimiter_param );
		if ( isset( $parsed['error'] ) ) {
			$this->discard_failed_upload( $file_path, $attachment_id );
			return new WP_Error(
				'woocommerce_fulfillments_csv_parse_error',
				(string) $parsed['error']['message'],
				array(
					'status' => WP_Http::BAD_REQUEST,
					'reason' => (string) $parsed['error']['code'],
				)
			);
		}

		$total = (int) ( $parsed['total'] ?? 0 );
		if ( 0 === $total ) {
			$this->discard_failed_upload( $file_path, $attachment_id );
			return new WP_Error(
				'woocommerce_fulfillments_csv_parse_error',
				__( 'The CSV file has no data rows.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}
		if ( $total > FulfillmentsCsvImporter::MAX_IMPORT_ROWS ) {
			$this->discard_failed_upload( $file_path, $attachment_id );
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

		// Creating the session deletes the user's previous one, so it happens under the run
		// lock: a session is never replaced while one of its chunks is being imported.
		$session = $this->with_run_lock(
			$user_id,
			function () use ( $user_id, $file_path, $parsed, $total, $notify, $update, $attachment_id ) {
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

				// The replaced session's record and cleanup action are already gone, so its staged
				// file goes too, whether or not the new session could be stored. The path comes from
				// stored session state, so the containment check in ImportSession applies.
				$replaced = $session->replaced();
				if ( $replaced instanceof ImportSession ) {
					ImportSession::delete_staged_file( $replaced->file(), $replaced->attachment_id() );
				}
				return $session;
			}
		);
		if ( $session instanceof WP_Error ) {
			$this->discard_failed_upload( $file_path, $attachment_id );
			return $session;
		}

		// Not stored, or stored without a scheduled cleanup: either way the new staged file
		// would have no owner, so the prepare fails and the file is removed now.
		if ( ! $session->persisted() ) {
			$this->discard_failed_upload( $file_path, $attachment_id );
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
	 * Run step: import the next chunk of the staged CSV and record the progress.
	 *
	 * One lock per user is held from before the session is loaded until the chunk is
	 * recorded, so two requests do not import the same rows. A user has one session at a
	 * time and the prepare step replaces it under the same lock. A request that finds the
	 * lock taken gets a 409 and has imported nothing. The one exception is a chunk that
	 * runs longer than RUN_LOCK_TTL: its lock can be taken over, and it then answers with
	 * a lock_lost error instead of recording rows another request is importing too.
	 *
	 * @since 11.5.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The incoming JSON request.
	 * @return array|WP_Error
	 */
	protected function handle_run( WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		return $this->with_run_lock(
			$user_id,
			fn( string $lock ) => $this->handle_run_locked( $request, $user_id, $lock )
		);
	}

	/**
	 * Run a callback while holding the run lock of a user.
	 *
	 * @template T
	 *
	 * @param int      $user_id  User ID.
	 * @param callable $callback Receives the lock value and returns the result.
	 * @return mixed The result of the callback, or a WP_Error when the lock could not be taken.
	 *
	 * @phpstan-param callable(string): T $callback
	 * @phpstan-return T|WP_Error
	 */
	private function with_run_lock( int $user_id, callable $callback ) {
		$lock = $this->acquire_run_lock( $user_id );
		if ( false === $lock ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_lock_failed',
				__( 'The import could not be locked because of a database error, so nothing was imported. Please try again.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}
		if ( null === $lock ) {
			return $this->chunk_in_progress_error();
		}

		// A fatal error, a timeout or an exit() in a row's hooks skips the finally block.
		// Without this the next request would get a 409 until the lock expires.
		$state = (object) array( 'locked' => true );
		register_shutdown_function(
			function () use ( $state, $user_id, $lock ) {
				if ( $state->locked ) {
					$this->release_run_lock( $user_id, $lock );
				}
			}
		);

		try {
			return $callback( $lock );
		} finally {
			$this->release_run_lock( $user_id, $lock );
			$state->locked = false;
		}
	}

	/**
	 * Process one run request while the lock of the user is held.
	 *
	 * The offset decides what happens. The recorded progress imports the next chunk. The
	 * offset of the last recorded chunk answers with the stored rows of that chunk and
	 * imports nothing, so a client that lost a response can ask again without a second
	 * write or a second customer email. Any other offset is refused.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request The incoming JSON request.
	 * @param int             $user_id Current user ID.
	 * @param string          $lock    Value acquire_run_lock() returned.
	 * @return array|WP_Error
	 */
	private function handle_run_locked( WP_REST_Request $request, int $user_id, string $lock ) {
		$session = ImportSession::load( $user_id, (string) $request->get_param( 'token' ) );
		if ( ! $session instanceof ImportSession ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_token_invalid',
				__( 'The import session is missing or has expired. Please upload the CSV file again.', 'woocommerce' ),
				array( 'status' => WP_Http::BAD_REQUEST )
			);
		}

		$offset = (int) $request->get_param( 'offset' );
		$last   = $session->last_chunk();
		if ( null !== $last && $offset === $last['offset'] ) {
			$this->complete_finished_import( $session );
			return $this->build_run_response( $session, $last['rows'] );
		}
		if ( $session->done() || $offset !== $session->processed() ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_offset_mismatch',
				__( 'The import is out of step with its recorded progress. Please start the import again.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		if ( $this->staged_file_changed( $session ) ) {
			// The session's scheduled cleanup goes with it, so the file is removed here.
			ImportSession::delete_staged_file( $session->file(), $session->attachment_id() );
			$session->delete();
			return new WP_Error(
				'woocommerce_fulfillments_import_file_changed',
				__( 'The uploaded CSV file was changed or removed. Please upload it again.', 'woocommerce' ),
				array( 'status' => WP_Http::CONFLICT )
			);
		}

		$frozen = $this->freeze_run_settings( $session, $request );
		if ( $frozen instanceof WP_Error ) {
			return $frozen;
		}

		$chunk = $this->import_next_chunk( $session, (int) $request->get_param( 'limit' ) );
		if ( $chunk instanceof WP_Error ) {
			return $chunk;
		}

		// Only a chunk that outlived the lock gets here without it. The request that took
		// the lock over is importing the same rows and records them, so this one must not.
		// The rows of this chunk are written, so the caller is not told it can simply resend.
		if ( ! $this->holds_run_lock( $user_id, $lock ) ) {
			wc_get_logger()->warning(
				sprintf( 'A fulfillments import chunk of user %d ended without its lock, so its rows may have been imported twice.', $user_id ),
				array( 'source' => 'fulfillments-importer' )
			);
			return new WP_Error(
				'woocommerce_fulfillments_import_lock_lost',
				__( 'This part of the import took too long and its result could not be confirmed. Please try again.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		// The rows are written by now. Sending the chunk again reports them as skipped or
		// updated, never as created a second time.
		if ( ! $session->record_chunk( $offset, $chunk ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_progress_failed',
				__( 'The progress of the import could not be saved. Please try again.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}

		$this->complete_finished_import( $session );
		return $this->build_run_response( $session, (array) ( $chunk['rows'] ?? array() ) );
	}

	/**
	 * Import the next chunk of the session's file under its stored settings.
	 *
	 * @param ImportSession $session Session of the run, with its settings stored.
	 * @param int           $limit   Most rows to import; 0 uses the chunk size of the store.
	 * @return array<string, mixed>|WP_Error The importer's result, or the error when it could not read the chunk.
	 */
	private function import_next_chunk( ImportSession $session, int $limit ) {
		$importer = new FulfillmentsCsvImporter(
			$session->file(),
			array(
				'notify_customer' => $session->notify_customer(),
				'update_existing' => $session->update_existing(),
				'delimiter'       => $session->delimiter(),
			)
		);
		$chunk    = $importer->import_chunk(
			$limit,
			(array) $session->frozen_mapping(),
			array(
				'byte_offset'         => $session->byte_offset(),
				'next_record_number'  => $session->next_record_number(),
				'seen_tracking_pairs' => $session->seen_tracking_pairs(),
			)
		);

		// The importer wrote nothing, so the session is kept and the same chunk can be sent again.
		if ( isset( $chunk['error'] ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_chunk_failed',
				(string) $chunk['error']['message'],
				array(
					'status' => WP_Http::INTERNAL_SERVER_ERROR,
					'reason' => (string) $chunk['error']['code'],
				)
			);
		}
		return $chunk;
	}

	/**
	 * Complete a session whose last row has been processed, once.
	 *
	 * Releases the staged file and fires the completed action. A callback that throws is
	 * logged: the rows are imported and recorded, so it cannot turn the request into an error.
	 *
	 * @param ImportSession $session Session after its chunk was recorded.
	 */
	private function complete_finished_import( ImportSession $session ): void {
		if ( ! $session->complete() ) {
			return;
		}

		$hook_depth = count( $GLOBALS['wp_current_filter'] );
		try {
			/**
			 * Fires once after a fulfillments CSV import has processed its last row.
			 *
			 * @since 11.5.0
			 *
			 * @param array $counts  Number of rows per result: created, updated, skipped and failed,
			 *                       plus notified, the rows whose notification action ran.
			 * @param int   $user_id User who ran the import.
			 */
			do_action( 'woocommerce_fulfillments_csv_import_completed', $session->counts(), $session->user_id() );
		} catch ( \Throwable $e ) {
			// WordPress leaves the hook on the stack when a callback throws.
			array_splice( $GLOBALS['wp_current_filter'], $hook_depth );
			wc_get_logger()->error(
				'A callback of woocommerce_fulfillments_csv_import_completed failed: ' . $e->getMessage(),
				array( 'source' => 'fulfillments-importer' )
			);
		}
	}

	/**
	 * Store the mapping and run options on the session unless an earlier chunk already did.
	 *
	 * Every chunk of a session imports under the settings of its first chunk, so a later
	 * request cannot import the rest of the file under different rules.
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param ImportSession   $session Session of the run.
	 * @param WP_REST_Request $request The incoming JSON request.
	 * @return WP_Error|null The error to return, or null when the session holds its settings.
	 */
	private function freeze_run_settings( ImportSession $session, WP_REST_Request $request ): ?WP_Error {
		if ( null !== $session->frozen_mapping() ) {
			return null;
		}

		$columns = count( $session->headers() );
		$mapping = array();
		foreach ( (array) $request->get_param( 'mapping' ) as $index => $column ) {
			// "01" passes the schema and would land on column 1 a second time.
			if ( (string) (int) $index !== (string) $index || (int) $index >= $columns ) {
				return $this->mapping_invalid_error(
					/* translators: %s: column index sent in the mapping. */
					sprintf( __( 'The mapping names column %s, which the file does not have.', 'woocommerce' ), (string) $index )
				);
			}
			if ( in_array( $column, $mapping, true ) ) {
				return $this->mapping_invalid_error(
					/* translators: %s: canonical column name, e.g. tracking_number. */
					sprintf( __( 'The mapping assigns %s to more than one column.', 'woocommerce' ), (string) $column )
				);
			}
			$mapping[ (int) $index ] = (string) $column;
		}

		$missing = FulfillmentsCsvImporter::find_missing_required_columns( FulfillmentsCsvImporter::mapping_to_header_map( $mapping ) );
		if ( array() !== $missing ) {
			return $this->mapping_invalid_error(
				/* translators: %s: comma-separated list of missing column names. */
				sprintf( __( 'The column mapping is missing required columns: %s.', 'woocommerce' ), implode( ', ', $missing ) )
			);
		}

		$options = (array) $request->get_param( 'options' );
		$notify  = array_key_exists( 'notify_customer', $options ) ? (bool) $options['notify_customer'] : $session->notify_customer();
		$update  = array_key_exists( 'update_existing', $options ) ? (bool) $options['update_existing'] : $session->update_existing();

		if ( ! $session->freeze_run_settings( $mapping, $notify, $update ) ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_progress_failed',
				__( 'The settings of the import could not be saved. Please try again.', 'woocommerce' ),
				array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
			);
		}
		return null;
	}

	/**
	 * Whether the staged CSV is no longer the file the prepare step measured.
	 *
	 * The stored byte offset is only valid for that file, so a missing or changed file
	 * ends the session. An existing file that cannot be read is left to the importer,
	 * which reports it without ending the session.
	 *
	 * @param ImportSession $session Session of the run.
	 * @return bool
	 */
	private function staged_file_changed( ImportSession $session ): bool {
		$file = $session->file();
		clearstatcache( true, $file );
		if ( '' === $file || ! file_exists( $file ) || ! ImportSession::is_staged_path( $file ) ) {
			return true;
		}
		if ( $session->file_size() > 0 && (int) filesize( $file ) !== $session->file_size() ) {
			return true;
		}
		if ( $session->file_mtime() > 0 && (int) filemtime( $file ) !== $session->file_mtime() ) {
			return true;
		}

		$hash = ImportSession::hash_file_head( $file );
		return '' !== $hash && '' !== $session->file_head_hash() && $hash !== $session->file_head_hash();
	}

	/**
	 * Build the response of a run request from the recorded progress.
	 *
	 * @param ImportSession                    $session Session after the chunk was recorded.
	 * @param array<int, array<string, mixed>> $rows    Row results of the chunk being answered.
	 * @return array<string, mixed>
	 */
	private function build_run_response( ImportSession $session, array $rows ): array {
		// The edit URL needs the type of each order. One query loads the orders of the chunk,
		// which would otherwise be read one by one with HPOS.
		$order_ids = array_values( array_unique( array_map( 'intval', array_column( $rows, 'order_id' ) ) ) );
		if ( array() !== $order_ids ) {
			wc_get_orders(
				array(
					'post__in' => $order_ids,
					'limit'    => count( $order_ids ),
					'status'   => array_keys( wc_get_order_statuses() ),
				)
			);
		}
		foreach ( $rows as $index => $row ) {
			if ( isset( $row['order_id'] ) ) {
				$rows[ $index ]['order_edit_url'] = OrderUtil::get_order_admin_edit_url( (int) $row['order_id'] );
			}
		}

		$response = array(
			'processed' => $session->processed(),
			'total'     => $session->total(),
			'done'      => $session->done(),
			'counts'    => $session->counts(),
			'rows'      => array_values( $rows ),
		);
		if ( $response['done'] ) {
			$response['summary'] = $session->summary();
		}
		return $response;
	}

	/**
	 * Lock the import of a user with an option row, as WC_Install::create_lock() does, taking over an expired lock.
	 *
	 * @param int $user_id User ID.
	 * @return string|false|null The lock value to release it with, null while another request holds the lock, or false on a database error.
	 */
	private function acquire_run_lock( int $user_id ) {
		global $wpdb;

		$name    = self::RUN_LOCK_PREFIX . $user_id;
		$expires = time() + self::RUN_LOCK_TTL;

		// INSERT IGNORE affects no rows while another request holds the lock, so contention is not logged as a DB error.
		$locked = $wpdb->query(
			$wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %d, 'no')", $name, $expires )
		);
		if ( 0 === $locked ) {
			$locked = $wpdb->query(
				$wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %d WHERE option_name = %s AND option_value < %d", $expires, $name, time() )
			);
		}
		if ( false === $locked ) {
			return false;
		}

		return $locked ? (string) $expires : null;
	}

	/**
	 * Whether the lock row of the user still holds the value of this request.
	 *
	 * A read that fails counts as a lost lock: the caller cannot confirm it is alone.
	 *
	 * @param int    $user_id User ID.
	 * @param string $lock    Value acquire_run_lock() returned.
	 * @return bool
	 */
	private function holds_run_lock( int $user_id, string $lock ): bool {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::RUN_LOCK_PREFIX . $user_id )
		) === $lock;
	}

	/**
	 * Release the lock of a user, unless another request took it over after it expired.
	 *
	 * @param int    $user_id User ID.
	 * @param string $lock    Value acquire_run_lock() returned.
	 */
	private function release_run_lock( int $user_id, string $lock ): void {
		global $wpdb;

		$released = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::RUN_LOCK_PREFIX . $user_id, $lock )
		);
		if ( false === $released ) {
			wc_get_logger()->warning(
				sprintf( 'The fulfillments import lock of user %d could not be released and blocks that user until it expires.', $user_id ),
				array( 'source' => 'fulfillments-importer' )
			);
		}
	}

	/**
	 * Build the 409 error for a request that arrives while another one holds the run lock.
	 *
	 * The holder is a chunk being imported or, for a moment, an upload being stored.
	 *
	 * @return WP_Error
	 */
	private function chunk_in_progress_error(): WP_Error {
		return new WP_Error(
			'woocommerce_fulfillments_import_chunk_in_progress',
			__( 'Another request of your import is still being processed. Please try again in a moment.', 'woocommerce' ),
			array( 'status' => WP_Http::CONFLICT )
		);
	}

	/**
	 * Build the 400 error for a mapping the import cannot run with.
	 *
	 * @param string $message Translated reason.
	 * @return WP_Error
	 */
	private function mapping_invalid_error( string $message ): WP_Error {
		return new WP_Error(
			'woocommerce_fulfillments_import_mapping_invalid',
			$message,
			array( 'status' => WP_Http::BAD_REQUEST )
		);
	}

	/**
	 * Validate the multipart file, hand it to CSVUploadHelper, and return the staged file details.
	 *
	 * @since 11.4.0
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @param WP_REST_Request $request Incoming request carrying the multipart upload.
	 * @return array{file:string, id:int}|WP_Error Staged absolute path and the attachment post ID created for it.
	 */
	protected function stage_uploaded_csv( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return $this->no_file_error();
		}

		$rejected = $this->check_uploaded_file( $files['file'] );
		if ( $rejected instanceof WP_Error ) {
			return $rejected;
		}

		return $this->store_uploaded_file( $files['file'] );
	}

	/**
	 * Check an uploaded file against the site's upload limit and PHP's upload result.
	 *
	 * Nothing is stored yet, so a rejection here leaves nothing to clean up.
	 *
	 * @param array $file Multipart file entry, in the $_FILES format.
	 * @return WP_Error|null The error to return, or null when the file can be stored.
	 */
	private function check_uploaded_file( array $file ): ?WP_Error {
		// On multisite WordPress hooks fix_import_form_size() onto this filter on every request
		// but defines it in wp-admin/includes/ms.php, which only wp-admin loads.
		if ( is_multisite() && ! function_exists( 'fix_import_form_size' ) ) {
			require_once ABSPATH . 'wp-admin/includes/ms.php';
		}

		/** This filter is documented in wp-admin/includes/import.php. */
		$upload_limit = (int) apply_filters( 'import_upload_size_limit', wp_max_upload_size() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		// On multisite the limit is the site's remaining upload space, so 0 means no space left
		// and no file could be stored, whatever PHP reported for it.
		if ( $upload_limit <= 0 ) {
			return new WP_Error(
				'woocommerce_fulfillments_import_file_too_large',
				__( 'This site has no upload space left for the file.', 'woocommerce' ),
				array( 'status' => WP_Http::REQUEST_ENTITY_TOO_LARGE )
			);
		}

		// PHP reports a file it refused for size with error 1 or 2 and size 0, so the error
		// code is checked before the size, or the merchant would get the generic message.
		$upload_error = (int) ( $file['error'] ?? UPLOAD_ERR_OK );
		if ( UPLOAD_ERR_INI_SIZE === $upload_error || UPLOAD_ERR_FORM_SIZE === $upload_error ) {
			return $this->file_too_large_error( $upload_limit );
		}
		if ( UPLOAD_ERR_NO_FILE === $upload_error ) {
			return $this->no_file_error();
		}
		if ( UPLOAD_ERR_OK !== $upload_error ) {
			wc_get_logger()->warning(
				sprintf( 'Fulfillments importer upload rejected by PHP with upload error code %d.', $upload_error ),
				array( 'source' => 'fulfillments-importer' )
			);
			return $this->upload_failed_error();
		}

		$file_size = (int) ( $file['size'] ?? 0 );
		if ( 0 === $file_size ) {
			return new WP_Error(
				'woocommerce_fulfillments_csv_parse_error',
				__( 'CSV file is empty.', 'woocommerce' ),
				array(
					'status' => WP_Http::BAD_REQUEST,
					'reason' => 'empty_csv',
				)
			);
		}
		if ( $file_size > $upload_limit ) {
			return $this->file_too_large_error( $upload_limit );
		}

		return null;
	}

	/**
	 * Store a checked file under wc-imports through CSVUploadHelper.
	 *
	 * Every failure removes whatever this call stored. CSVUploadHelper can throw after the
	 * file and its attachment exist (when wc_is_file_valid_csv() rejects the stored file), and
	 * its exception does not say what it stored, so the stored path and attachment are
	 * recorded from the upload hooks while the helper runs.
	 *
	 * @param array $file Multipart file entry, in the $_FILES format.
	 * @return array{file:string, id:int}|WP_Error Staged absolute path and the attachment post ID created for it.
	 */
	private function store_uploaded_file( array $file ) {
		// CSVUploadHelper ultimately calls wp_handle_upload(), which is only loaded
		// on wp-admin page loads; REST requests must pull it in explicitly.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$stored = array(
			'file' => '',
			'id'   => 0,
		);

		// Runs last, so it sees the path after any other filter changed it.
		$record_file = static function ( $upload ) use ( &$stored ) {
			if ( is_array( $upload ) && empty( $upload['error'] ) && isset( $upload['file'] ) && is_string( $upload['file'] ) ) {
				$stored['file'] = $upload['file'];
			}
			return $upload;
		};
		// Runs first, so the attachment for the stored file is recorded before any other
		// callback could insert one of its own.
		$record_attachment = static function ( $post_id ) use ( &$stored ) {
			if ( 0 === $stored['id'] && '' !== $stored['file'] ) {
				$stored['id'] = (int) $post_id;
			}
		};

		// CSVUploadHelper reads from $_FILES under a configurable key. Stage the REST file under
		// that key and unset it again in finally so the assignment cannot outlive this call.
		$_FILES['fulfillment_import_file'] = $file;
		add_filter( 'wp_handle_upload', $record_file, PHP_INT_MAX );
		add_action( 'add_attachment', $record_attachment, PHP_INT_MIN );
		try {
			try {
				$upload = $this->csv_upload_helper->handle_csv_upload(
					'fulfillment',
					'fulfillment_import_file',
					array(
						'csv' => 'text/csv',
						'txt' => 'text/plain',
					)
				);
				$stored = array(
					'file' => (string) ( $upload['file'] ?? '' ),
					'id'   => (int) ( $upload['id'] ?? 0 ),
				);

				FilesystemUtil::validate_upload_file_path( $stored['file'] );

				// Every later delete of this path refuses anything outside wc-imports, so a file an
				// upload_dir filter moved elsewhere is refused here, while it can still be discarded.
				if ( ! ImportSession::is_staged_path( $stored['file'] ) ) {
					$this->discard_failed_upload( $stored['file'], $stored['id'] );
					wc_get_logger()->warning(
						sprintf( 'Fulfillments importer upload rejected: the staged file is outside the import directory: %s', $stored['file'] ),
						array( 'source' => 'fulfillments-importer' )
					);
					return $this->upload_failed_error();
				}
			} catch ( \Exception $e ) {
				// The upload handler's message can carry server paths, so it is logged, not returned.
				$this->discard_failed_upload( $stored['file'], $stored['id'] );
				wc_get_logger()->warning(
					'Fulfillments importer upload rejected: ' . html_entity_decode( $e->getMessage(), ENT_QUOTES, 'UTF-8' ),
					array( 'source' => 'fulfillments-importer' )
				);
				return $this->upload_failed_error();
			} catch ( \Throwable $e ) {
				$this->discard_failed_upload( $stored['file'], $stored['id'] );
				wc_get_logger()->error(
					'Fulfillments importer upload failed: ' . html_entity_decode( $e->getMessage(), ENT_QUOTES, 'UTF-8' ),
					array( 'source' => 'fulfillments-importer' )
				);
				return new WP_Error(
					'woocommerce_fulfillments_import_upload_failed',
					__( 'The upload could not be processed. Please try again.', 'woocommerce' ),
					array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
				);
			}
		} finally {
			remove_filter( 'wp_handle_upload', $record_file, PHP_INT_MAX );
			remove_action( 'add_attachment', $record_attachment, PHP_INT_MIN );
			unset( $_FILES['fulfillment_import_file'] );
		}

		return $stored;
	}

	/**
	 * Build the 400 error for a request without a file.
	 *
	 * @return WP_Error
	 */
	private function no_file_error(): WP_Error {
		return new WP_Error(
			'woocommerce_fulfillments_import_no_file',
			__( 'No CSV file was uploaded.', 'woocommerce' ),
			array( 'status' => WP_Http::BAD_REQUEST )
		);
	}

	/**
	 * Build the 400 error for a file that could not be uploaded or staged.
	 *
	 * The message is fixed because the underlying reason can carry server paths; the reason is logged instead.
	 *
	 * @return WP_Error
	 */
	private function upload_failed_error(): WP_Error {
		return new WP_Error(
			'woocommerce_fulfillments_import_upload_failed',
			__( 'The file could not be uploaded.', 'woocommerce' ),
			array( 'status' => WP_Http::BAD_REQUEST )
		);
	}

	/**
	 * Build the 413 error for an upload above the size limit.
	 *
	 * @param int $upload_limit Effective limit in bytes.
	 * @return WP_Error
	 */
	private function file_too_large_error( int $upload_limit ): WP_Error {
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

	/**
	 * Remove a file this request just staged, including its attachment post.
	 *
	 * Only for a path the upload handler stored in this request, whether returned by
	 * stage_uploaded_csv() or recorded by store_uploaded_file() before a throw: it is wherever
	 * the upload handler wrote it, so no containment check applies and a misplaced file is
	 * still removed. A path read back from a session goes through
	 * ImportSession::delete_staged_file() instead.
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
