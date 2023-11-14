<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utils class
 */
class Utils {

	/**
	 * Compare the current WordPress version with a given version. It's a wrapper around `version-compare`
	 * that additionally takes into account the suffix (like `-RC1`).
	 * For example: version 6.3 is considered lower than 6.3-RC2, so you can do
	 * wp_version_compare( '6.3', '>=' ) and that will return true for 6.3-RC2.
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
