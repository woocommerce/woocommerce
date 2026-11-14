<?php
/**
 * ColumnFit - checks a string fits its column before a write, as `$wpdb->insert()` does.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;

defined( 'ABSPATH' ) || exit;

/**
 * Column-fit check shared by the repositories.
 */
final class ColumnFit {

	/**
	 * Refuse a string `$wpdb` would truncate or strip for its column: longer than the column
	 * (characters for CHAR/VARCHAR, bytes for TEXT types) or invalid for its charset. A column
	 * `$wpdb` cannot check is refused; a non-string column or a non-MySQL database is not checked.
	 *
	 * @param string $table  Prefixed table name.
	 * @param string $column Column name.
	 * @param string $value  Value about to be written.
	 * @throws \RuntimeException If the value does not fit the column, or the column cannot be checked.
	 */
	public static function assert_fits_column( string $table, string $column, string $value ): void {
		global $wpdb;

		// `wpdb::query()` refuses invalid charset but not over-long values, so hand-built writes check here.
		$checked = $wpdb->strip_invalid_text_for_column( $table, $column, $value );
		if ( $checked === $value ) {
			return;
		}

		if ( is_wp_error( $checked ) ) {
			throw new \RuntimeException(
				esc_html( sprintf( 'Failed to write %s: the value for "%s" could not be checked against the column (%s).', $table, $column, $checked->get_error_message() ) )
			);
		}

		$length = $wpdb->get_col_length( $table, $column );
		if ( is_array( $length ) ) {
			$max  = Coercion::coerce_int( $length['length'] ?? 0 );
			$size = 'byte' === ( $length['type'] ?? '' ) ? strlen( $value ) : mb_strlen( $value, 'UTF-8' );
			if ( $max > 0 && $size > $max ) {
				throw new \RuntimeException(
					esc_html( sprintf( 'Failed to write %s: the value for "%s" is longer than the column allows (%d).', $table, $column, $max ) )
				);
			}
		}

		throw new \RuntimeException(
			esc_html( sprintf( 'Failed to write %s: the value for "%s" has characters the column cannot store.', $table, $column ) )
		);
	}
}
