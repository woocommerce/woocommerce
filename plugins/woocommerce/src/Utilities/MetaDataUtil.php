<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Utilities;

use WC_Data;

/**
 * Utility methods for handling order item meta data.
 *
 * @since 10.8.0
 */
class MetaDataUtil {

	/**
	 * Normalize and process meta data entries from a REST API request.
	 *
	 * Skips entries without a key, applies defaults for missing 'value' and 'id'
	 * fields, then calls update_meta_data on the given WC_Data object
	 * for each valid entry.
	 *
	 * @since 10.8.0
	 *
	 * @param mixed   $meta_data  Raw meta data from the request (non-array values are ignored).
	 * @param WC_Data $target     A WC_Data object to call update_meta_data on.
	 * @param mixed   $default_id Default value for 'id' when not provided (default '').
	 */
	public static function update( $meta_data, WC_Data $target, $default_id = '' ): void {
		if ( ! is_array( $meta_data ) ) {
			return;
		}

		foreach ( self::normalize( $meta_data, $default_id ) as $meta ) {
			$target->update_meta_data( $meta['key'], $meta['value'], $meta['id'] );
		}
	}

	/**
	 * Normalize an array of raw meta data entries from a REST API request.
	 *
	 * Filters out entries without a key and applies default values for
	 * missing 'value' and 'id' fields. Each returned entry is guaranteed
	 * to have 'key', 'value', and 'id' set.
	 *
	 * @since 10.8.0
	 *
	 * @param array $meta_data Raw meta data array from the request.
	 * @param mixed $default_id Default value for 'id' when not provided (default '').
	 * @return array[] Normalized meta data entries.
	 */
	public static function normalize( array $meta_data, $default_id = '' ): array {
		$normalized = array();
		foreach ( $meta_data as $meta ) {
			if ( is_array( $meta ) && isset( $meta['key'] ) ) {
				$normalized[] = array(
					'key'   => $meta['key'],
					'value' => $meta['value'] ?? null,
					'id'    => $meta['id'] ?? $default_id,
				);
			}
		}
		return $normalized;
	}

	/**
	 * Unserialize a meta value, if it is serialized, without instantiating any object it may contain.
	 *
	 * @since 11.3.1
	 *
	 * @param mixed $value Raw meta value, maybe serialized.
	 * @return mixed Unserialized value.
	 */
	public static function maybe_unserialize_no_classes( $value ) {
		if ( ! is_serialized( $value ) ) {
			return $value;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- maybe_unserialize() can't restrict classes, and @ hides the warning on bad input.
		return @unserialize( trim( $value ), array( 'allowed_classes' => false ) );
	}
}
