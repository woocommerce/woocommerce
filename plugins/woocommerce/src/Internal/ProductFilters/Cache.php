<?php
/**
 * FilterDataProvider class file.
 */

namespace Automattic\WooCommerce\Internal\ProductFilters;

use WC_Cache_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class for filter counts.
 */
class Cache {
	const CACHE_GROUP = 'product_filters';

	/**
	 * Get filter data transient key.
	 *
	 * @param string $id   The id of entity.
	 * @param array  $args Data to be hashed for an unique key.
	 */
	public function get_transient_key( $id, ...$args ) {
		return sprintf(
			'wc_%s_%s_%s',
			self::CACHE_GROUP,
			$id,
			md5( wp_json_encode( $args ) )
		);
	}

	/**
	 * Get cached filter data.
	 *
	 * @param string $key Transient key.
	 */
	public function get_transient_cache( $key ) {
		$cache             = get_transient( $key );
		$transient_version = WC_Cache_Helper::get_transient_version( self::CACHE_GROUP );

		if ( empty( $cache['version'] ) ||
			empty( $cache['value'] ) ||
			$transient_version !== $cache['version']
		) {
			return null;
		}

		return $cache['value'];
	}

	/**
	 * Set the cache with transient version to invalidate all at once when needed.
	 *
	 * @param string $key   Transient key.
	 * @param mix    $value Value to set.
	 */
	public function set_transient_cache( $key, $value ) {
		$transient_version = WC_Cache_Helper::get_transient_version( self::CACHE_GROUP );
		$transient_value   = array(
			'version' => $transient_version,
			'value'   => $value,
		);

		$result = set_transient( $key, $transient_value );

		return $result;
	}

	public function get_object_cache_key( $id ) {
		return WC_Cache_Helper::get_cache_prefix( self::CACHE_GROUP ) . $id;
	}
}
