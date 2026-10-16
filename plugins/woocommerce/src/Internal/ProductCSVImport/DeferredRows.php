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
	 * @param string $file Source CSV path.
	 */
	public function __construct( string $file ) {
		$this->prefix = 'woocommerce_csv_deferred_' . md5( get_current_user_id() . ':' . wp_normalize_path( $file ) ) . '_';
	}

	/**
	 * Store a formatted row, using its source position to make resuming idempotent.
	 *
	 * @param int   $position Position immediately after the source row.
	 * @param array $row Formatted row and unresolved parent reference.
	 * @throws \RuntimeException When the row cannot be stored.
	 */
	public function save( int $position, array $row ): void {
		$key = $this->get_key( $position );

		if ( ! update_option( $key, $row, false ) && get_option( $key ) !== $row ) {
			throw new \RuntimeException( 'Unable to store a deferred product import row.' );
		}
	}

	/**
	 * Read a bounded number of rows in source order.
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
				$rows[ (int) substr( $key, strlen( $this->prefix ) ) ] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Check whether the import still has pending rows.
	 *
	 * @return bool
	 */
	public function has_rows(): bool {
		return ! empty( $this->get_keys( 1 ) );
	}

	/**
	 * Remove a row after its final attempt.
	 *
	 * @param int $position Source row position.
	 */
	public function delete( int $position ): void {
		delete_option( $this->get_key( $position ) );
	}

	/**
	 * Discard pending rows when restarting the same file from the beginning.
	 */
	public function clear(): void {
		$keys = $this->get_keys( 30 );
		while ( $keys ) {
			foreach ( $keys as $key ) {
				delete_option( $key );
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
