<?php
/**
 * PostDataSlashing class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DataStores;

/**
 * Slashes post data for wp_insert_post() and wp_update_post(), which unslash it.
 *
 * @since 11.4.0
 */
class PostDataSlashing {

	/**
	 * Slash the string values in post data, so backslashes survive wp_insert_post() and wp_update_post().
	 *
	 * For backwards compatibility, values that are already slashed (such as the output of wp_filter_post_kses()) are
	 * passed as they are, so they're still unslashed once as before. This check is temporary and will be removed.
	 *
	 * @param mixed $data Post data.
	 * @return mixed
	 */
	public static function slash( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) && wp_slash( wp_unslash( $value ) ) !== $value ) {
				$data[ $key ] = wp_slash( $value );
			}
		}

		return $data;
	}
}
