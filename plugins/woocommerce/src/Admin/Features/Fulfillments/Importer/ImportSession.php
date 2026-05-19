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
			'notify_customer'     => $notify,
			'update_existing'     => $update,
			'seen_tracking_pairs' => array(),
		);

		set_transient( self::PREFIX . $user_id . '_' . $token, $data, self::TTL );
		set_transient( self::INDEX_PREFIX . $user_id, $token, self::TTL );

		return new self( $user_id, $token, $data );
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
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return (string) wp_generate_uuid4();
		}
		return bin2hex( random_bytes( 16 ) );
	}
}
