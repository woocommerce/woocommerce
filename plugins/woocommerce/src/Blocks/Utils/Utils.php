<?php
namespace Automattic\WooCommerce\Blocks\Utils;

use Automattic\WooCommerce\Proxies\LegacyProxy;

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

		// Replace non-alphanumeric characters with a dot.
		$current_wp_version = preg_replace( '/[^0-9a-zA-Z\.]+/i', '.', $current_wp_version );
		$version            = preg_replace( '/[^0-9a-zA-Z\.]+/i', '.', $version );

		return version_compare( $current_wp_version, $version, $operator );
	}

	/**
	 * Resolve a (possibly relative) script src to an absolute URL the same way
	 * WordPress core does in WP_Scripts::do_item(): a relative src is resolved
	 * against the scripts base URL (the site URL), unless it already points at
	 * the content directory. This keeps the resulting URL consistent with what
	 * WordPress itself would emit, including on non-default directory layouts
	 * (e.g. a custom WP_CONTENT_DIR/WP_CONTENT_URL).
	 *
	 * @param string $src The script src, which may be relative or absolute.
	 * @return string The absolute script URL.
	 */
	public static function get_absolute_script_url( $src ) {
		$wp_scripts = wc_get_container()->get( LegacyProxy::class )->call_function( 'wp_scripts' );
		if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $wp_scripts->content_url && 0 === strpos( $src, $wp_scripts->content_url ) ) ) {
			$src = $wp_scripts->base_url . $src;
		}
		return $src;
	}
}
