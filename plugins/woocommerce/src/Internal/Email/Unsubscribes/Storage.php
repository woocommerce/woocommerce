<?php
/**
 * Email unsubscribes Storage class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email\Unsubscribes;

/**
 * Storage and lookup for customer "do not send me this kind of email" preferences.
 *
 * Generic across email types: each row pairs a SHA-256 hash of the normalized
 * email with a free-form `email_kind` string (typically the email's `$this->id`,
 * e.g. `customer_checkout_recovery`). Multiple emails can route through the same
 * table without colliding.
 *
 * Stored in a dedicated table (`wp_wc_email_unsubscribes`) rather than user meta
 * so guest checkouts — common for the abandoned-checkout case — can opt out
 * without needing a WP_User record. The hash is computed from the lowercased +
 * trimmed email so casing or whitespace variations resolve to the same row.
 *
 * The table is installed via `WC_Install::get_schema()`. `init()` is auto-called
 * by the container after instantiation; it registers the GDPR personal-data
 * eraser so the WP "Erase Personal Data" tool clears this table too.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class Storage {

	/**
	 * The unqualified table name (no `$wpdb->prefix`).
	 */
	private const TABLE = 'wc_email_unsubscribes';

	/**
	 * Action label stored on the row. Kept as a varchar column rather than a
	 * boolean so we can add further actions (e.g. resubscribed) later without
	 * a schema migration. Today there's only one.
	 */
	public const ACTION_UNSUBSCRIBED = 'unsubscribed';

	/**
	 * Shape of a valid email hash: 64 lowercase hex chars, matching the output
	 * of `hash('sha256', …)`. Shared with the public unsubscribe endpoint so
	 * the two validation sites can't drift apart.
	 */
	public const HASH_PATTERN = '/^[a-f0-9]{64}$/';

	/**
	 * Register the GDPR personal-data eraser.
	 *
	 * The table itself is installed via `WC_Install::get_schema()` so it's
	 * present on every site (including the test bootstrap) regardless of
	 * whether the checkout-recovery feature flag is enabled.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_personal_data_eraser' ) );
	}

	/**
	 * Database schema for the unsubscribes table.
	 *
	 * Called from `WC_Install::get_schema()` so the table is created/updated
	 * alongside the rest of WC's tables on activate/upgrade.
	 *
	 * @return string SQL CREATE TABLE statement.
	 */
	public function get_database_schema(): string {
		global $wpdb;
		$table   = $this->get_table_name();
		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		return "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email_hash char(64) NOT NULL,
			email_kind varchar(64) NOT NULL,
			action varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY email_hash_kind (email_hash, email_kind)
		) {$collate};";
	}

	/**
	 * Hash a raw email address for use as the lookup key.
	 *
	 * Normalizes (trim + strtolower) before hashing so equivalent addresses
	 * collide on the same row. Returns an empty string for empty input so
	 * callers can early-out without raising.
	 *
	 * @param string $email Raw email address.
	 * @return string 64-char hex SHA-256 hash, or '' if input was empty.
	 */
	public static function hash_email( string $email ): string {
		$normalized = strtolower( trim( $email ) );
		if ( '' === $normalized ) {
			return '';
		}
		return hash( 'sha256', $normalized );
	}

	/**
	 * Whether the given email is currently unsubscribed from a specific kind.
	 *
	 * @param string $email Raw email address.
	 * @param string $kind  Email-kind identifier (the email class's `$this->id`).
	 * @return bool
	 */
	public function is_unsubscribed( string $email, string $kind ): bool {
		$hash = self::hash_email( $email );
		if ( '' === $hash || '' === $kind ) {
			return false;
		}

		global $wpdb;
		$table = $this->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name hard-coded above; values bound.
		$action = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT action FROM {$table} WHERE email_hash = %s AND email_kind = %s ORDER BY id DESC LIMIT 1",
				$hash,
				$kind
			)
		);
		// phpcs:enable

		return self::ACTION_UNSUBSCRIBED === $action;
	}

	/**
	 * Record an unsubscribe for the given email + kind. Idempotent — repeated
	 * calls append new rows but the lookup only cares about the most recent.
	 *
	 * @param string $email Raw email address.
	 * @param string $kind  Email-kind identifier.
	 * @return bool True if a row was written, false if input was empty.
	 */
	public function mark_unsubscribed( string $email, string $kind ): bool {
		return $this->record_action( self::hash_email( $email ), $kind, self::ACTION_UNSUBSCRIBED );
	}

	/**
	 * Record an unsubscribe directly by SHA-256 hash, for callers (e.g. the
	 * public unsubscribe endpoint) that operate on the hash already and never
	 * need to handle the raw email.
	 *
	 * Validates the hash matches `HASH_PATTERN` as defense in depth — the
	 * Endpoint already shape-checks the URL value, but any future caller that
	 * forgets to would otherwise insert a junk row.
	 *
	 * @param string $hash SHA-256 hex digest of the normalized email.
	 * @param string $kind Email-kind identifier.
	 * @return bool True if a row was written.
	 */
	public function mark_unsubscribed_by_hash( string $hash, string $kind ): bool {
		if ( 1 !== preg_match( self::HASH_PATTERN, $hash ) ) {
			return false;
		}
		return $this->record_action( $hash, $kind, self::ACTION_UNSUBSCRIBED );
	}

	/**
	 * Remove all rows (across every kind) for an email — used by the GDPR
	 * personal-data eraser so a customer's "right to be forgotten" request
	 * clears their opt-out record along with the rest of their data.
	 *
	 * @param string $email Raw email address.
	 * @return int Number of rows deleted.
	 */
	public function erase_for_email( string $email ): int {
		$hash = self::hash_email( $email );
		if ( '' === $hash ) {
			return 0;
		}

		global $wpdb;
		$table = $this->get_table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name hard-coded; hash bound.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE email_hash = %s",
				$hash
			)
		);
		// phpcs:enable

		return is_numeric( $deleted ) ? (int) $deleted : 0;
	}

	/**
	 * Filter callback that adds this repository's eraser to WP's GDPR registry.
	 *
	 * @internal
	 *
	 * @param array<string, array{eraser_friendly_name: string, callback: callable}> $erasers Existing erasers.
	 * @return array<string, array{eraser_friendly_name: string, callback: callable}>
	 */
	public function register_personal_data_eraser( array $erasers ): array {
		$erasers['wc-email-unsubscribes'] = array(
			'eraser_friendly_name' => __( 'WooCommerce Email Unsubscribes', 'woocommerce' ),
			'callback'             => array( $this, 'handle_personal_data_erasure' ),
		);
		return $erasers;
	}

	/**
	 * Callback for the WP personal-data eraser.
	 *
	 * @internal
	 *
	 * @param string $email Email address being erased.
	 * @return array{items_removed: bool, items_retained: bool, messages: string[], done: bool}
	 */
	public function handle_personal_data_erasure( string $email ): array {
		$removed = $this->erase_for_email( $email ) > 0;

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * Append an action row.
	 *
	 * @param string $hash   SHA-256 hex digest of the normalized email.
	 * @param string $kind   Email-kind identifier.
	 * @param string $action `unsubscribed` or `resubscribed`.
	 * @return bool
	 */
	private function record_action( string $hash, string $kind, string $action ): bool {
		if ( '' === $hash || '' === $kind ) {
			return false;
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- write to an internal preference table.
		$inserted = $wpdb->insert(
			$this->get_table_name(),
			array(
				'email_hash' => $hash,
				'email_kind' => $kind,
				'action'     => $action,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
		// phpcs:enable

		return false !== $inserted;
	}

	/**
	 * Fully-qualified table name including the wpdb prefix.
	 */
	private function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}
}
