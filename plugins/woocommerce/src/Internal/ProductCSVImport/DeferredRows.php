<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\ProductCSVImport;

/**
 * Stores formatted rows awaiting a parent across CSV import requests.
 *
 * @internal
 */
class DeferredRows {

	/**
	 * Site-local option prefix for one user's source file.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Create a queue for a source file and the current user.
	 *
	 * @since 11.3.0
	 *
	 * @param string $file Source CSV path.
	 */
	public function __construct( string $file ) {
		$this->prefix = 'woocommerce_csv_deferred_' . md5( get_current_user_id() . ':' . wp_normalize_path( $file ) ) . '_';
	}

	/**
	 * Store a formatted row, using its source position to make resuming idempotent.
	 *
	 * @since 11.3.0
	 *
	 * @param int      $position Position immediately after the source row.
	 * @param array    $row Formatted row and unresolved parent reference.
	 * @param string[] $identifiers Product identities that must retain source order.
	 * @throws \RuntimeException When the row cannot be stored.
	 */
	public function save( int $position, array $row, array $identifiers = array() ): void {
		$key = $this->get_key( $position );
		if ( $identifiers ) {
			$row['_deferred_identifiers'] = $identifiers;
		}

		if ( ! update_option( $key, $row, false ) && get_option( $key ) !== $row ) {
			throw new \RuntimeException( 'Unable to store a deferred product import row.' );
		}

		$index_keys = array_map( array( $this, 'get_identifier_key' ), $identifiers );
		if ( $index_keys ) {
			wp_prime_option_caches( $index_keys );
		}
		foreach ( $index_keys as $index_key ) {
			$latest = max( $position, (int) get_option( $index_key, 0 ) );
			if ( ! update_option( $index_key, $latest, false ) && (int) get_option( $index_key, 0 ) !== $latest ) {
				throw new \RuntimeException( 'Unable to index a deferred product import row.' );
			}
		}
	}

	/**
	 * Check whether an earlier row for a product is still pending.
	 *
	 * @since 11.3.0
	 *
	 * @param string $identifier Product identity.
	 * @return bool
	 */
	public function has_identifier( string $identifier ): bool {
		return false !== get_option( $this->get_identifier_key( $identifier ), false );
	}

	/**
	 * Read a bounded number of rows in source order.
	 *
	 * @since 11.3.0
	 *
	 * @param int $limit Maximum rows to read.
	 * @return array<int, array>
	 */
	public function get_batch( int $limit = 30 ): array {
		$keys = $this->get_keys( $limit );
		wp_prime_option_caches( $keys );
		$rows = array();

		foreach ( $keys as $key ) {
			$row = get_option( $key );
			if ( is_array( $row ) ) {
				unset( $row['_deferred_identifiers'] );
				$rows[ (int) substr( $key, strlen( $this->prefix ) ) ] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Check whether the import still has pending rows.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public function has_rows(): bool {
		return ! empty( $this->get_keys( 1 ) );
	}

	/**
	 * Remove a row after its final attempt.
	 *
	 * @since 11.3.0
	 *
	 * @param int $position Source row position.
	 * @throws \RuntimeException When a stored row cannot be removed.
	 */
	public function delete( int $position ): void {
		$key = $this->get_key( $position );
		$row = get_option( $key );
		if ( ! delete_option( $key ) && false !== get_option( $key, false ) ) {
			throw new \RuntimeException( 'Unable to remove a deferred product import row.' );
		}
		$index_keys = array_map( array( $this, 'get_identifier_key' ), $row['_deferred_identifiers'] ?? array() );
		if ( $index_keys ) {
			wp_prime_option_caches( $index_keys );
		}
		foreach ( $index_keys as $index_key ) {
			if ( (int) get_option( $index_key, 0 ) === $position && ! delete_option( $index_key ) && false !== get_option( $index_key, false ) ) {
				throw new \RuntimeException( 'Unable to remove a deferred product import index.' );
			}
		}
	}

	/**
	 * Discard pending rows when restarting the same file from the beginning.
	 *
	 * @since 11.3.0
	 */
	public function clear(): void {
		$keys = $this->get_keys( 30 );
		while ( $keys ) {
			foreach ( $keys as $key ) {
				$this->delete( (int) substr( $key, strlen( $this->prefix ) ) );
			}
			$keys = $this->get_keys( 30 );
		}
	}

	/**
	 * Get an option key whose lexical order matches source order.
	 *
	 * @param int $position Source row position.
	 * @return string
	 */
	private function get_key( int $position ): string {
		return $this->prefix . sprintf( '%020d', $position );
	}

	/**
	 * Get an identity index key outside the source-row prefix.
	 *
	 * @param string $identifier Product identity.
	 * @return string
	 */
	private function get_identifier_key( string $identifier ): string {
		if ( 0 === strpos( $identifier, 'sku:' ) ) {
			$identifier = wc_strtolower( $identifier );
		}
		return '_' . $this->prefix . md5( $identifier );
	}

	/**
	 * Find queue entries without loading the entire import into memory.
	 *
	 * @param int $limit Maximum keys to read.
	 * @return string[]
	 */
	private function get_keys( int $limit ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- An indexed prefix query discovers queue entries across requests; values use the options cache.
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name LIMIT %d",
				$wpdb->esc_like( $this->prefix ) . '%',
				max( 1, $limit )
			)
		);
	}
}
