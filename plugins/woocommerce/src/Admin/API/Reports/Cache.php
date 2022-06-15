<?php
/**
 * REST API Reports Cache.
 *
 * Handles report data object caching.
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Reports Cache class.
 */
class Cache {
	/**
	 * Cache version. Used to invalidate all cached values.
	 */
	const VERSION_OPTION = 'woocommerce_reports';

	/**
	 * Invalidate cache.
	 */
	public static function invalidate() {
		self::get_version( true );
	}

	/**
	 * Get cache version number.
	 *
	 * This is based on WC_Cache_Helper::get_transient_version, but rounds the Unix timestamp to the nearest
	 * increment to rate-limit cache invalidations.
	 *
	 * @param bool $refresh True to generate a new value.
	 *
	 * @return string
	 */
	public static function get_version( $refresh = false ) {
		$transient_name  = self::VERSION_OPTION . '-transient-version';
		$transient_value = get_transient( $transient_name );

		if ( false === $transient_value || true === $refresh ) {
			// Round to the nearest $minutes increment.
			$minutes = 10;
			$transient_value = (string) round( time() / ( MINUTE_IN_SECONDS * $minutes ) ) * ( MINUTE_IN_SECONDS * $minutes );

			set_transient( $transient_name, $transient_value );
		}

		return $transient_value;
	}

	/**
	 * Get cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$transient_version = self::get_version();
		$transient_value   = get_transient( $key );

		if (
			isset( $transient_value['value'], $transient_value['version'] ) &&
			$transient_value['version'] === $transient_version
		) {
			return $transient_value['value'];
		}

		return false;
	}

	/**
	 * Update cached value.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value New value.
	 * @return bool
	 */
	public static function set( $key, $value ) {
		$transient_version = self::get_version();
		$transient_value   = array(
			'version' => $transient_version,
			'value'   => $value,
		);

		$result = set_transient( $key, $transient_value, HOUR_IN_SECONDS );

		return $result;
	}
}
