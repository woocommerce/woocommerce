<?php
/**
 * Per-user import session backed by a WordPress transient.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments\Importer
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps the per-user transient that keeps state across the chunked CSV import workflow.
 *
 * One session is in flight per administrator at a time: creating a new session deletes any
 * prior one for the same user via a user-scoped index transient.
 *
 * @since 10.9.0
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
	 * Constructor.
	 *
	 * @param int                   $user_id User who owns the session.
	 * @param string                $token   Opaque session token.
	 * @param array<string, mixed>  $data    Stored payload (see ::create()).
	 */
	private function __construct( int $user_id, string $token, array $data ) {
		$this->user_id = $user_id;
		$this->token   = $token;
		$this->data    = $data;
	}

	/**
	 * Create a fresh import session for a user, replacing any existing one.
	 *
	 * @since 10.9.0
	 *
	 * @param int                $user_id   User ID.
	 * @param string             $file      Absolute path to the staged CSV file.
	 * @param string             $delimiter Effective delimiter (resolved by parse_headers()).
	 * @param array<int, string> $headers   Header row.
	 * @param int                $total     Total number of CSV records after the header.
	 * @param bool               $notify    Whether to fire customer notifications when running chunks.
	 * @param bool               $update    Whether to update existing fulfillments on tracking-number match.
	 * @return self
	 */
	public static function create( int $user_id, string $file, string $delimiter, array $headers, int $total, bool $notify, bool $update ): self {
		// Drop any prior session this user had open so only one import is in flight per admin.
		$existing_token = get_transient( self::INDEX_PREFIX . $user_id );
		if ( is_string( $existing_token ) && '' !== $existing_token ) {
			delete_transient( self::PREFIX . $user_id . '_' . $existing_token );
		}

		$token = self::generate_token();
		$data  = array(
			'file'                => $file,
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
			'rows'                => array(),
		);

		set_transient( self::PREFIX . $user_id . '_' . $token, $data, self::TTL );
		set_transient( self::INDEX_PREFIX . $user_id, $token, self::TTL );

		self::schedule_cleanup( $user_id, $token, $file );

		return new self( $user_id, $token, $data );
	}

	/**
	 * Schedule a deferred cleanup action that will remove the staged file if the session is
	 * abandoned (transient expires without the wizard ever completing the import).
	 *
	 * @param int    $user_id User ID.
	 * @param string $token   Session token.
	 * @param string $file    Absolute path to the staged CSV.
	 */
	private static function schedule_cleanup( int $user_id, string $token, string $file ): void {
		if ( '' === $file || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}
		as_schedule_single_action(
			time() + self::TTL + self::CLEANUP_GRACE,
			self::CLEANUP_HOOK,
			array( $user_id, $token, $file ),
			'woocommerce-fulfillments-importer'
		);
	}

	/**
	 * Cleanup callback fired by Action Scheduler after the TTL grace window.
	 *
	 * Only deletes the file when the matching session transient has expired; if the user is
	 * still mid-import the action becomes a no-op.
	 *
	 * @since 10.9.0
	 *
	 * @param int    $user_id User the session belongs to.
	 * @param string $token   Session token.
	 * @param string $file    Absolute path to the staged CSV.
	 */
	public static function cleanup_abandoned_file( int $user_id, string $token, string $file ): void {
		if ( '' === $file || ! file_exists( $file ) ) {
			return;
		}
		if ( false !== get_transient( self::PREFIX . $user_id . '_' . $token ) ) {
			return;
		}
		wp_delete_file( $file );
	}

	/**
	 * Load whichever session is currently active for a user, if any.
	 *
	 * @since 10.9.0
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
	 * @since 10.9.0
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
		return new self( $user_id, $token, $payload );
	}

	/**
	 * Delete the session and its index pointer.
	 *
	 * @since 10.9.0
	 */
	public function delete(): void {
		delete_transient( self::PREFIX . $this->user_id . '_' . $this->token );

		// Only clear the index pointer if it still points at this session — a newer session may have replaced it.
		$current = get_transient( self::INDEX_PREFIX . $this->user_id );
		if ( $current === $this->token ) {
			delete_transient( self::INDEX_PREFIX . $this->user_id );
		}

		if ( function_exists( 'as_unschedule_action' ) ) {
			as_unschedule_action(
				self::CLEANUP_HOOK,
				array( $this->user_id, $this->token, $this->file() ),
				'woocommerce-fulfillments-importer'
			);
		}
	}

	/**
	 * Update the cumulative processed-row count and persist.
	 *
	 * @since 10.9.0
	 *
	 * @param int $processed Cumulative processed-row count.
	 */
	public function update_processed( int $processed ): void {
		$this->data['processed'] = max( 0, $processed );
		$this->persist();
	}

	/**
	 * Replace the cross-chunk dedupe state and persist.
	 *
	 * @since 10.9.0
	 *
	 * @param array<string, true> $seen Map of "<order_id>|<lowercase_tracking>" => true.
	 */
	public function update_seen_tracking_pairs( array $seen ): void {
		$this->data['seen_tracking_pairs'] = $seen;
		$this->persist();
	}

	/**
	 * Session token.
	 *
	 * @return string
	 */
	public function token(): string {
		return $this->token;
	}

	/**
	 * Absolute path to the staged CSV file.
	 *
	 * @return string
	 */
	public function file(): string {
		return (string) ( $this->data['file'] ?? '' );
	}

	/**
	 * Effective CSV delimiter for this session.
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
	 * @return array<int, string>
	 */
	public function headers(): array {
		return array_values( array_map( 'strval', (array) ( $this->data['headers'] ?? array() ) ) );
	}

	/**
	 * Total number of CSV records after the header.
	 *
	 * @return int
	 */
	public function total(): int {
		return (int) ( $this->data['total'] ?? 0 );
	}

	/**
	 * Cumulative processed-row count.
	 *
	 * @return int
	 */
	public function processed(): int {
		return (int) ( $this->data['processed'] ?? 0 );
	}

	/**
	 * Whether customer notifications should fire for chunks of this session.
	 *
	 * @return bool
	 */
	public function notify_customer(): bool {
		return ! empty( $this->data['notify_customer'] );
	}

	/**
	 * Whether existing fulfillments should be updated on tracking-number match.
	 *
	 * @return bool
	 */
	public function update_existing(): bool {
		return ! empty( $this->data['update_existing'] );
	}

	/**
	 * End-of-file byte offset reached by the most recent chunk.
	 *
	 * @since 10.9.0
	 *
	 * @return int
	 */
	public function byte_offset(): int {
		return (int) ( $this->data['byte_offset'] ?? 0 );
	}

	/**
	 * Append the result of one chunk: advance processed, merge counts, append rows, replace dedupe state.
	 *
	 * @since 10.9.0
	 *
	 * @param int                                                                    $processed_after End-of-chunk processed count (offset + rows consumed by the chunk).
	 * @param array{created:int, updated:int, skipped:int, failed:int, notified:int} $counts          Per-chunk counts.
	 * @param array<int, array<string, mixed>>                                       $rows            Per-row result entries from this chunk.
	 * @param array<string, true>                                                    $seen            Cross-chunk dedupe state to persist.
	 * @param int                                                                    $byte_offset     Byte position the importer reached after this chunk, used to resume the next one without re-reading prior rows.
	 */
	public function record_chunk( int $processed_after, array $counts, array $rows, array $seen, int $byte_offset = 0 ): void {
		$prev                    = (int) ( $this->data['processed'] ?? 0 );
		$this->data['processed'] = min( $this->total(), max( $prev, max( 0, $processed_after ) ) );

		$current_counts = is_array( $this->data['counts'] ?? null ) ? $this->data['counts'] : array();
		foreach ( array( 'created', 'updated', 'skipped', 'failed', 'notified' ) as $key ) {
			$current_counts[ $key ] = (int) ( $current_counts[ $key ] ?? 0 ) + (int) ( $counts[ $key ] ?? 0 );
		}
		$this->data['counts'] = $current_counts;

		if ( ! empty( $rows ) ) {
			$existing            = is_array( $this->data['rows'] ?? null ) ? $this->data['rows'] : array();
			$this->data['rows']  = array_merge( $existing, $rows );
		}

		$this->data['seen_tracking_pairs'] = $seen;

		if ( $byte_offset > 0 ) {
			$prev_byte                 = (int) ( $this->data['byte_offset'] ?? 0 );
			$this->data['byte_offset'] = max( $prev_byte, $byte_offset );
		}

		$this->persist();
	}

	/**
	 * Cumulative counts across processed chunks.
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
	 * Per-row result entries collected across chunks.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function rows(): array {
		$rows = $this->data['rows'] ?? array();
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Final ImporterSummary-shaped payload for the wizard's "Done" step.
	 *
	 * @return array<string, mixed>
	 */
	public function summary(): array {
		return array_merge( $this->counts(), array( 'rows' => $this->rows() ) );
	}

	/**
	 * Cross-chunk dedupe state.
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
	 * @return int
	 */
	public function user_id(): int {
		return $this->user_id;
	}

	/**
	 * Persist the current payload back to its transient.
	 */
	private function persist(): void {
		set_transient( self::PREFIX . $this->user_id . '_' . $this->token, $this->data, self::TTL );
		// Keep the user-scoped index pointer alive too.
		set_transient( self::INDEX_PREFIX . $this->user_id, $this->token, self::TTL );
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
