<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Utilities;

use WC_Data;

/**
 * Utility methods for handling meta data in REST API requests.
 *
 * @since 10.8.0
 */
class MetaDataUtil {

	/**
	 * Normalize and process meta data entries from a REST API request.
	 *
	 * Skips entries without a key, applies defaults for missing 'value' and 'id'
	 * fields, then either calls update_meta_data on the given WC_Data object
	 * or invokes the callback for each valid entry.
	 *
	 * @since 10.8.0
	 *
	 * @param mixed           $meta_data  Raw meta data from the request (non-array values are ignored).
	 * @param WC_Data|callable $target     A WC_Data object (calls update_meta_data directly) or a callback
	 *                                     receiving each normalized entry as array{key: string, value: mixed, id: mixed}.
	 * @param mixed           $default_id Default value for 'id' when not provided (default '').
	 *
	 * @throws \InvalidArgumentException If $target is neither a WC_Data instance nor callable.
	 */
	public static function update( $meta_data, $target, $default_id = '' ): void {
		if ( ! ( $target instanceof WC_Data ) && ! is_callable( $target ) ) {
			throw new \InvalidArgumentException( 'The $target argument must be a WC_Data instance or a callable.' );
		}

		if ( ! is_array( $meta_data ) ) {
			return;
		}

		foreach ( self::normalize( $meta_data, $default_id ) as $meta ) {
			if ( $target instanceof WC_Data ) {
				$target->update_meta_data( $meta['key'], $meta['value'], $meta['id'] );
			} else {
				$target( $meta );
			}
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
			if ( isset( $meta['key'] ) ) {
				$normalized[] = array(
					'key'   => $meta['key'],
					'value' => $meta['value'] ?? null,
					'id'    => $meta['id'] ?? $default_id,
				);
			}
		}
		return $normalized;
	}
}
