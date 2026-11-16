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
