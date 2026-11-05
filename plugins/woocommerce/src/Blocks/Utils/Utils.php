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

	/**
	 * Get the current page URL using the request path relative to home.
	 *
	 * @internal This function is used internally by WooCommerce blocks to get the current page URL. It is not intended for external use.
	 *
	 * @return string The current page URL.
	 *
	 * @since 11.1.0
	 */
	public static function get_current_page_url() {
		global $wp, $wp_rewrite;

		$request_path = is_object( $wp ) && isset( $wp->request ) && is_string( $wp->request ) ? $wp->request : '';

		// PATHINFO permalinks keep index.php in the public URL; $wp->request does not.
		if (
			'' !== $request_path &&
			$wp_rewrite instanceof \WP_Rewrite &&
			$wp_rewrite->using_index_permalinks()
		) {
			$index = is_string( $wp_rewrite->index ) && '' !== $wp_rewrite->index ? $wp_rewrite->index : 'index.php';
			if ( $index !== $request_path && ! str_starts_with( $request_path, $index . '/' ) ) {
				$request_path = $index . '/' . $request_path;
			}
		}

		$url = home_url( user_trailingslashit( $request_path ) );

		if ( isset( $_SERVER['QUERY_STRING'] ) && is_string( $_SERVER['QUERY_STRING'] ) && '' !== $_SERVER['QUERY_STRING'] ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Preserving the raw query string encoding and delimiters.
			$url .= '?' . wp_unslash( $_SERVER['QUERY_STRING'] );
		}

		return $url;
	}
}
