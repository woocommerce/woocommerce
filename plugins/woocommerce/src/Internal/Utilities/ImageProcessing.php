<?php
/**
 * WooCommerce Image Processing Utilities.
 */

namespace Automattic\WooCommerce\Internal;

/**
 * Contains backend logic for the activity panel feature.
 */
class ImageProcessing {

	/**
	 * Returns true if Photon image CDN is active on the site
	 *
	 * In case Photon is active, don't attempt to generate thumbnails, as those are generated on the fly
	 * and served from the Photon CDN. Thus, they're unnecessary and just take disk space and CPU cycles to generate.
	 * Additionally, when Photon is active, WP will accept even pretty large images and attempting to resize them
	 * will crash the image processing library.
	 *
	 * https://developer.wordpress.com/docs/photon/
	 *
	 * @return bool true if Photon image CDN is active on the site, false otherwise
	 */
	public static function is_photon_active(): bool {
		return class_exists( 'Jetpack' ) && method_exists( 'Jetpack', 'get_active_modules' ) && in_array( 'photon', Jetpack::get_active_modules(), true );
	}

	/**
	 * Return the memory limit used for image processing in WP.
	 *
	 * @return int Number of bytes of available memory for the PHP process.
	 */
	public static function get_image_memory_limit(): int {
		$mem_limit_bytes = 0;
		$mem_limit       = self::get_wp_image_memory_limit();

		if ( false === $mem_limit ) {
			$mem_limit = ini_get( 'memory_limit' );
		}

		if ( $mem_limit ) {
			$mem_limit_bytes = wp_convert_hr_to_bytes( $mem_limit );
		}

		return $mem_limit_bytes;
	}

	/**
	 * Returns the memory limit that would be used after running wp_raise_memory_limit( 'image' ) function,
	 * without changing the limit.
	 *
	 * This is a reduced copy of core's wp_raise_memory_limit.
	 * It would be great if there was a way to call wp_raise_memory_limit that would return the new memory limit
	 * with no change in the memory limit, but there isn't one.
	 *
	 * @see wp_raise_memory_limit
	 *
	 * @return false|int|string
	 */
	public static function get_wp_image_memory_limit() {
		if ( false === wp_is_ini_value_changeable( 'memory_limit' ) ) {
			return false;
		}

		$current_limit     = ini_get( 'memory_limit' );
		$current_limit_int = wp_convert_hr_to_bytes( $current_limit );

		if ( -1 === $current_limit_int ) {
			return false;
		}

		$wp_max_limit     = WP_MAX_MEMORY_LIMIT;
		$wp_max_limit_int = wp_convert_hr_to_bytes( $wp_max_limit );
		$filtered_limit   = $wp_max_limit;

		/**
		 * This is a WP core filter from wp_raise_memory_limit().
		 *
		 * Filters the memory limit allocated for image manipulation.
		 *
		 * @since 3.5.0
		 * @since 4.6.0 The default now takes the original `memory_limit` into account.
		 *
		 * @param int|string $filtered_limit Maximum memory limit to allocate for images.
		 *                                   Default `WP_MAX_MEMORY_LIMIT` or the original
		 *                                   php.ini `memory_limit`, whichever is higher.
		 *                                   Accepts an integer (bytes), or a shorthand string
		 *                                   notation, such as '256M'.
		 */
		$filtered_limit = apply_filters( 'image_memory_limit', $filtered_limit );

		$filtered_limit_int = wp_convert_hr_to_bytes( $filtered_limit );

		if ( -1 === $filtered_limit_int || ( $filtered_limit_int > $wp_max_limit_int && $filtered_limit_int > $current_limit_int ) ) {
			return $filtered_limit;
		} elseif ( -1 === $wp_max_limit_int || $wp_max_limit_int > $current_limit_int ) {
			return $wp_max_limit;
		}

		return false;
	}
}
