<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utils class
 */
class Utils {

	/**
	 * Compare the current WordPress version with a given version.
	 *
	 * @param string      $version The version to compare against.
	 * @param string|null $operator Optional. The comparison operator. Defaults to null.
	 * @return bool|int Returns true if the current WordPress version satisfies the comparison, false otherwise.
	 */
	public static function wp_version_compare( $version, $operator = null ) {
		$current_wp_version = get_bloginfo( 'version' );
		if ( preg_match( '/^([0-9]+\.[0-9]+)/', $current_wp_version, $matches ) ) {
			$current_wp_version = (float) $matches[1];
		}

		return version_compare( $current_wp_version, $version, $operator );
	}
}
