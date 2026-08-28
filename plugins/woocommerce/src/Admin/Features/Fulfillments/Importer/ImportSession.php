<?php
/**
 * Per-user import session backed by a WordPress transient.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Internal\Utilities\FilesystemUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps the per-user transient that keeps state across the chunked CSV import workflow.
 *
 * One session is in flight per user at a time: creating a new session deletes any
 * prior one for the same user via a user-scoped index transient.
 *
 * @since 11.2.0
 */
final class ImportSession {

	/**
	 * Transient TTL for an in-flight import session.
	 */
	private const TTL = HOUR_IN_SECONDS;

	/**
	 * Action Scheduler hook fired to clean up an abandoned staged CSV file.
	 */
	public const CLEANUP_HOOK = 'woocommerce_fulfillments_import_session_cleanup';

	/**
	 * Grace period (in seconds) added to TTL before the cleanup action fires.
	 */
	private const CLEANUP_GRACE = 5 * MINUTE_IN_SECONDS;

	/**
	 * Prefix for session payload transients.
	 */
	private const PREFIX = 'wc_fulfillment_import_';

	/**
	 * Prefix for the per-user active-token pointer transient.
	 */
	private const INDEX_PREFIX = 'wc_fulfillment_import_active_';

	/**
	 * Owning user ID.
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Session token (also embedded in the transient key).
	 *
	 * @var string
	 */
	private string $token;

	/**
	 * Stored payload.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Payload as it was last read from or written to the transient.
	 *
	 * Lets persist() skip a no-op write without re-reading the transient, which would move
	 * the whole dedupe set across the object cache on every chunk.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $stored_data = null;

	/**
	 * Whether the last write of the payload transient succeeded.
	 *
	 * @var bool
	 */
	private bool $persisted = true;

	/**
	 * Constructor.
	 *
	 * @param int                  $user_id User who owns the session.
	 * @param string               $token   Opaque session token.
	 * @param array<string, mixed> $data    Stored payload (see ::create()).
	 */
	private function __construct( int $user_id, string $token, array $data ) {
		$this->user_id = $user_id;
		$this->token   = $token;
		$this->data    = $data;
	}

	/**
	 * Create a fresh import session for a user, replacing any existing one.
	 *
	 * @since 11.2.0
	 *
	 * @param int                $user_id       User ID.
	 * @param string             $file          Absolute path to the staged CSV file.
	 * @param string             $delimiter     Effective delimiter (resolved by parse_headers()).
	 * @param array<int, string> $headers       Header row.
	 * @param int                $total         Total number of CSV records after the header.
	 * @param bool               $notify        Whether to fire customer notifications when running chunks.
	 * @param bool               $update        Whether to update existing fulfillments on tracking-number match.
	 * @param int                $attachment_id Attachment post created for the staged CSV by the upload handler.
	 * @return self
	 */
	public static function create( int $user_id, string $file, string $delimiter, array $headers, int $total, bool $notify, bool $update, int $attachment_id = 0 ): self {
		// Drop any prior session this user had open so only one import is in flight per admin.
		$existing_token = get_transient( self::INDEX_PREFIX . $user_id );
		if ( is_string( $existing_token ) && '' !== $existing_token ) {
			delete_transient( self::PREFIX . $user_id . '_' . $existing_token );
		}

		$token = self::generate_token();
		$data  = array(
			'file'                => $file,
			'attachment_id'       => max( 0, $attachment_id ),
			'file_size'           => file_exists( $file ) ? (int) filesize( $file ) : 0,
			'file_mtime'          => file_exists( $file ) ? (int) filemtime( $file ) : 0,
			'file_head_hash'      => self::hash_file_head( $file ),
			'delimiter'           => $delimiter,
			'headers'             => array_values( array_map( 'strval', $headers ) ),
			'total'               => max( 0, $total ),
			'processed'           => 0,
			'byte_offset'         => 0,
			'notify_customer'     => $notify,
			'update_existing'     => $update,
			'seen_tracking_pairs' => array(),
			'counts'              => array(
				'created'  => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'failed'   => 0,
				'notified' => 0,
			),
		);

		$session = new self( $user_id, $token, $data );
		$session->persist();

		self::schedule_cleanup( $user_id, $token, $file, max( 0, $attachment_id ) );

		return $session;
	}

	/**
	 * Whether the session payload is stored, so chunk progress can be recorded against it.
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	public function persisted(): bool {
		return $this->persisted;
	}

	/**
	 * Schedule a deferred cleanup action that will remove the staged file if the session is
	 * abandoned (transient expires without the wizard ever completing the import).
	 *
	 * @param int    $user_id       User ID.
	 * @param string $token         Session token.
	 * @param string $file          Absolute path to the staged CSV.
	 * @param int    $attachment_id Attachment post created for the staged CSV.
	 */
	private static function schedule_cleanup( int $user_id, string $token, string $file, int $attachment_id ): void {
		if ( '' === $file || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		/**
		 * Fires (via Action Scheduler) after a fulfillments import session's TTL plus a small grace
		 * window to clean up the staged CSV when the wizard never finishes the import.
		 *
		 * Listeners receive the session metadata and can bail out early by checking whether
		 * the matching session transient still exists. The default handler is
		 * {@see ImportSession::handle_cleanup_hook()}. Action Scheduler fires the hook, so this
		 * docblock sits on the scheduling call rather than on a do_action().
		 *
		 * @since 11.2.0
		 *
		 * @param int    $user_id       User who owned the session.
		 * @param string $token         Session token.
		 * @param string $file          Absolute path to the staged CSV.
		 * @param int    $attachment_id Attachment post created for the staged CSV.
		 */
		as_schedule_single_action(
			time() + self::TTL + self::CLEANUP_GRACE,
			self::CLEANUP_HOOK,
			array( $user_id, $token, $file, $attachment_id ),
			'woocommerce-fulfillments-importer'
		);
	}

	/**
	 * Hook callback for the cleanup action.
	 *
	 * The arguments arrive from a persisted Action Scheduler payload and from anything else
	 * that fires the hook, so they are coerced before reaching the typed method below.
	 *
	 * @since 11.2.0
	 *
	 * @param mixed $user_id       User the session belongs to.
	 * @param mixed $token         Session token.
	 * @param mixed $file          Absolute path to the staged CSV.
	 * @param mixed $attachment_id Attachment post created for the staged CSV.
	 */
	public static function handle_cleanup_hook( $user_id = 0, $token = '', $file = '', $attachment_id = 0 ): void {
		if ( ! is_scalar( $token ) || ! is_scalar( $file ) ) {
			return;
		}
		self::cleanup_abandoned_file(
			is_numeric( $user_id ) ? (int) $user_id : 0,
			(string) $token,
			(string) $file,
			is_numeric( $attachment_id ) ? (int) $attachment_id : 0
		);
	}

	/**
	 * Delete the staged CSV of a session that was never finished.
	 *
	 * Only deletes the file when the matching session transient has expired; if the user is
	 * still mid-import this is a no-op.
	 *
	 * @since 11.2.0
	 *
	 * @param int    $user_id       User the session belongs to.
	 * @param string $token         Session token.
	 * @param string $file          Absolute path to the staged CSV.
	 * @param int    $attachment_id Attachment post created for the staged CSV.
	 */
	public static function cleanup_abandoned_file( int $user_id, string $token, string $file, int $attachment_id = 0 ): void {
		if ( '' === $file ) {
			return;
		}
		if ( false !== get_transient( self::PREFIX . $user_id . '_' . $token ) ) {
			return;
		}

		$file_exists = file_exists( $file );
		if ( ! $file_exists && $attachment_id <= 0 ) {
			return;
		}

		// The Action Scheduler payload is persisted, so refuse to delete anything that does not
		// resolve inside the uploads directory even if the args were tampered with.
		if ( $file_exists && ! self::is_staged_path( $file ) ) {
			wc_get_logger()->warning(
				sprintf( 'Refusing to clean up staged fulfillments import file outside the uploads directory: %s', $file ),
				array( 'source' => 'fulfillments-csv-importer' )
			);
			return;
		}

		// Deleting the attachment also removes its file; only honor IDs that still
		// point at the staged path so a tampered ID cannot delete unrelated media.
		if ( $attachment_id > 0 && get_attached_file( $attachment_id ) === $file ) {
			wp_delete_attachment( $attachment_id, true );
		}
		if ( file_exists( $file ) ) {
			wp_delete_file( $file );
		}
	}

	/**
	 * Whether a path resolves inside the uploads directory the importer stages files in.
	 *
	 * FilesystemUtil::validate_upload_file_path() also accepts anything readable under
	 * ABSPATH, which is far wider than anything this importer ever writes.
	 *
	 * @since 11.2.0
	 *
	 * @param string $path Absolute path to check.
	 * @return bool
	 */
	public static function is_staged_path( string $path ): bool {
		if ( '' === $path ) {
			return false;
		}

		try {
			FilesystemUtil::validate_upload_file_path( $path );
		} catch ( \Exception $e ) {
			return false;
		}

		$upload_dir = wp_get_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return false;
		}

		$resolved = realpath( $path );
		$basedir  = realpath( $upload_dir['basedir'] );
		if ( false === $resolved || false === $basedir ) {
			return false;
		}

		return 0 === strpos( wp_normalize_path( $resolved ), trailingslashit( wp_normalize_path( $basedir ) ) );
	}

	/**
	 * Load whichever session is currently active for a user, if any.
	 *
	 * @since 11.2.0
	 *
	 * @param int $user_id User ID.
	 * @return self|null
	 */
	public static function active_for_user( int $user_id ): ?self {
		$token = get_transient( self::INDEX_PREFIX . $user_id );
		if ( ! is_string( $token ) || '' === $token ) {
			return null;
		}
		return self::load( $user_id, $token );
	}

	/**
	 * Load an existing session belonging to a user.
	 *
	 * @since 11.2.0
	 *
	 * @param int    $user_id User ID.
	 * @param string $token   Token previously returned by ::create()->token().
	 * @return self|null Null when the token is missing, expired, or owned by a different user.
	 */
	public static function load( int $user_id, string $token ): ?self {
		if ( '' === $token ) {
			return null;
		}
		$payload = get_transient( self::PREFIX . $user_id . '_' . $token );
		if ( ! is_array( $payload ) ) {
			return null;
		}
		$session              = new self( $user_id, $token, $payload );
		$session->stored_data = $payload;
		return $session;
	}

	/**
	 * Delete the session and its index pointer.
	 *
	 * @since 11.2.0
	 */
	public function delete(): void {
		delete_transient( self::PREFIX . $this->user_id . '_' . $this->token );

		// Only clear the index pointer if it still points at this session; a newer session may have replaced it.
		$current = get_transient( self::INDEX_PREFIX . $this->user_id );
		if ( $current === $this->token ) {
			delete_transient( self::INDEX_PREFIX . $this->user_id );
		}

		if ( function_exists( 'as_unschedule_action' ) ) {
			as_unschedule_action(
				self::CLEANUP_HOOK,
				array( $this->user_id, $this->token, $this->file(), $this->attachment_id() ),
				'woocommerce-fulfillments-importer'
			);
		}
	}

	/**
	 * Session token.
	 *
	 * @since 11.2.0
	 *
	 * @return string
	 */
	public function token(): string {
		return $this->token;
	}

	/**
	 * Absolute path to the staged CSV file.
	 *
	 * @since 11.2.0
	 *
	 * @return string
	 */
	public function file(): string {
		return (string) ( $this->data['file'] ?? '' );
	}

	/**
	 * Attachment post created for the staged CSV by the upload handler.
	 *
	 * @since 11.2.0
	 *
	 * @return int Attachment ID; 0 when the file was staged without one.
	 */
	public function attachment_id(): int {
		return max( 0, (int) ( $this->data['attachment_id'] ?? 0 ) );
	}

	/**
	 * Size of the staged CSV when the session was created, in bytes.
	 *
	 * @since 11.2.0
	 *
	 * @return int
	 */
	public function file_size(): int {
		return (int) ( $this->data['file_size'] ?? 0 );
	}

	/**
	 * Modification time of the staged CSV when the session was created.
	 *
	 * @since 11.2.0
	 *
	 * @return int Unix timestamp; 0 when unknown.
	 */
	public function file_mtime(): int {
		return (int) ( $this->data['file_mtime'] ?? 0 );
	}

	/**
	 * Hash of the first bytes of the staged CSV when the session was created.
	 *
	 * @since 11.2.0
	 *
	 * @return string Hash string; empty when the file could not be read.
	 */
	public function file_head_hash(): string {
		return (string) ( $this->data['file_head_hash'] ?? '' );
	}

	/**
	 * Hash the first 4 KB of a file, closing the size and mtime blind spot in
	 * the staged-file integrity check.
	 *
	 * @since 11.2.0
	 *
	 * @param string $file Absolute file path.
	 * @return string Hash string; empty when the file could not be read.
	 */
	public static function hash_file_head( string $file ): string {
		if ( '' === $file || ! is_readable( $file ) ) {
			return '';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a staged local file head for integrity hashing.
		$handle = fopen( $file, 'rb' );
		if ( false === $handle ) {
			return '';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- See above.
		$head = fread( $handle, 4096 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- See above.
		fclose( $handle );
		return false === $head ? '' : md5( $head );
	}

	/**
	 * Effective CSV delimiter for this session.
	 *
	 * @since 11.2.0
	 *
	 * @return string
	 */
	public function delimiter(): string {
		$delimiter = (string) ( $this->data['delimiter'] ?? ',' );
		return '' === $delimiter ? ',' : $delimiter;
	}

	/**
	 * Header row as parsed at prepare time.
	 *
	 * @since 11.2.0
	 *
	 * @return array<int, string>
	 */
	public function headers(): array {
		return array_values( array_map( 'strval', (array) ( $this->data['headers'] ?? array() ) ) );
	}

	/**
	 * Total number of CSV records after the header.
	 *
	 * @since 11.2.0
	 *
	 * @return int
	 */
	public function total(): int {
		return (int) ( $this->data['total'] ?? 0 );
	}

	/**
	 * Cumulative processed-row count.
	 *
	 * @since 11.2.0
	 *
	 * @return int
	 */
	public function processed(): int {
		return (int) ( $this->data['processed'] ?? 0 );
	}

	/**
	 * Whether customer notifications should fire for chunks of this session.
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	public function notify_customer(): bool {
		return ! empty( $this->data['notify_customer'] );
	}

	/**
	 * Whether existing fulfillments should be updated on tracking-number match.
	 *
	 * @since 11.2.0
	 *
	 * @return bool
	 */
	public function update_existing(): bool {
		return ! empty( $this->data['update_existing'] );
	}

	/**
	 * Column mapping frozen by the first processed chunk, or null when not frozen yet.
	 *
	 * @since 11.2.0
	 *
	 * @return array<int, string>|null CSV column index => canonical column key.
	 */
	public function frozen_mapping(): ?array {
		$mapping = $this->data['mapping'] ?? null;
		if ( ! is_array( $mapping ) ) {
			return null;
		}
		$out = array();
		foreach ( $mapping as $col => $canonical ) {
			$out[ (int) $col ] = (string) $canonical;
		}
		return $out;
	}

	/**
	 * Freeze the mapping and run options so every chunk of this session imports
	 * under the same rules regardless of what later requests send.
	 *
	 * Mutates in-memory state only; the values are persisted together with the
	 * next recorded chunk, so a failed first chunk simply re-freezes on retry.
	 *
	 * @since 11.2.0
	 *
	 * @param array<int, string> $mapping CSV column index => canonical column key.
	 * @param bool               $notify  Whether to fire customer notifications.
	 * @param bool               $update  Whether to update existing fulfillments.
	 */
	public function freeze_run_settings( array $mapping, bool $notify, bool $update ): void {
		$this->data['mapping']         = $mapping;
		$this->data['notify_customer'] = $notify;
		$this->data['update_existing'] = $update;
	}

	/**
	 * Byte offset in the CSV reached by the most recent chunk.
	 *
	 * @since 11.2.0
	 *
	 * @return int
	 */
	public function byte_offset(): int {
		return (int) ( $this->data['byte_offset'] ?? 0 );
	}

	/**
	 * Advance processed, merge counts, and persist dedupe/byte-offset state.
	 *
	 * Per-row results are streamed back in the chunk REST response instead of accumulating
	 * inside the session transient; otherwise a long import would persist every row result
	 * to the transient on every chunk and blow past max_allowed_packet on the final write.
	 *
	 * @since 11.2.0
	 *
	 * @param int                                                                    $processed_after End-of-chunk processed count (offset + rows consumed by the chunk).
	 * @param array{created:int, updated:int, skipped:int, failed:int, notified:int} $counts          Per-chunk counts.
	 * @param array<string, true>                                                    $seen            Cross-chunk dedupe state to persist.
	 * @param int                                                                    $byte_offset     Byte position reached after this chunk; the next chunk resumes here.
	 * @return bool Whether the updated session state is stored.
	 */
	public function record_chunk( int $processed_after, array $counts, array $seen, int $byte_offset = 0 ): bool {
		$prev                    = (int) ( $this->data['processed'] ?? 0 );
		$this->data['processed'] = min( $this->total(), max( $prev, max( 0, $processed_after ) ) );

		$current_counts = is_array( $this->data['counts'] ?? null ) ? $this->data['counts'] : array();
		foreach ( array( 'created', 'updated', 'skipped', 'failed', 'notified' ) as $key ) {
			$current_counts[ $key ] = (int) ( $current_counts[ $key ] ?? 0 ) + (int) ( $counts[ $key ] ?? 0 );
		}
		$this->data['counts'] = $current_counts;

		$this->data['seen_tracking_pairs'] = $seen;

		if ( $byte_offset > 0 ) {
			// Take the offset the importer just reported verbatim. Clamping with max() would
			// silently ignore a corrected (lower) offset on retry and cause rows to be skipped.
			// A zero value means the chunk never got a real ftell() position (e.g. open failed),
			// so we preserve the prior offset rather than rewinding to the start of the file.
			$this->data['byte_offset'] = $byte_offset;
		}

		return $this->persist();
	}

	/**
	 * Cumulative counts across processed chunks.
	 *
	 * @since 11.2.0
	 *
	 * @return array{created:int, updated:int, skipped:int, failed:int, notified:int}
	 */
	public function counts(): array {
		$counts = is_array( $this->data['counts'] ?? null ) ? $this->data['counts'] : array();
		return array(
			'created'  => (int) ( $counts['created'] ?? 0 ),
			'updated'  => (int) ( $counts['updated'] ?? 0 ),
			'skipped'  => (int) ( $counts['skipped'] ?? 0 ),
			'failed'   => (int) ( $counts['failed'] ?? 0 ),
			'notified' => (int) ( $counts['notified'] ?? 0 ),
		);
	}

	/**
	 * Final ImporterSummary-shaped payload for the wizard's "Done" step.
	 *
	 * Rows are not part of the persisted session; the wizard accumulates them from each chunk's
	 * REST response and rebuilds the summary client-side.
	 *
	 * @since 11.2.0
	 *
	 * @return array<string, mixed>
	 */
	public function summary(): array {
		// The rows key is part of the wizard's summary shape; the client fills it from the
		// per-chunk responses, so the server side of it is always empty.
		return array_merge( $this->counts(), array( 'rows' => array() ) );
	}

	/**
	 * Cross-chunk dedupe state.
	 *
	 * @since 11.2.0
	 *
	 * @return array<string, true>
	 */
	public function seen_tracking_pairs(): array {
		$seen = $this->data['seen_tracking_pairs'] ?? array();
		return is_array( $seen ) ? $seen : array();
	}

	/**
	 * Owning user ID.
	 *
	 * @since 11.2.0
	 *
	 * @return int
	 */
	public function user_id(): int {
		return $this->user_id;
	}

	/**
	 * Persist the current payload back to its transient.
	 *
	 * The set_transient() call returns false when the stored value is unchanged, so an
	 * unchanged payload is treated as already stored rather than as a lost write. The index
	 * pointer is rewritten every time so its TTL slides with the payload's.
	 *
	 * @return bool Whether the payload is stored.
	 */
	private function persist(): bool {
		$payload_key = self::PREFIX . $this->user_id . '_' . $this->token;

		$stored = $this->data === $this->stored_data;
		if ( ! $stored ) {
			$stored = set_transient( $payload_key, $this->data, self::TTL );
			if ( $stored ) {
				$this->stored_data = $this->data;
			}
		}

		set_transient( self::INDEX_PREFIX . $this->user_id, $this->token, self::TTL );

		if ( ! $stored ) {
			wc_get_logger()->error(
				sprintf(
					'Fulfillments import session %s could not be persisted; progress for this chunk may be lost.',
					$this->token
				),
				array( 'source' => 'fulfillments-csv-importer' )
			);
		}

		$this->persisted = (bool) $stored;
		return $this->persisted;
	}

	/**
	 * Generate an opaque session token.
	 *
	 * @return string
	 */
	private static function generate_token(): string {
		try {
			return bin2hex( random_bytes( 16 ) );
		} catch ( \Throwable $e ) {
			return (string) wp_generate_uuid4();
		}
	}
}
