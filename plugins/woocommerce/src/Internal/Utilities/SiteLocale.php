<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Closure;

/**
 * Utility for resolving the site's configured locale deterministically and running code under it.
 *
 * The get_locale() function reflects per-request state: temporary switches made through WP_Locale_Switcher,
 * per-visitor `locale` filters from multilingual plugins, and the `$GLOBALS['locale']` cache
 * (which can be stale across switch_to_blog() calls). Values that are persisted site-wide, or
 * compared against persisted values, must not depend on any of that — otherwise whichever
 * request happens to run first decides what gets stored.
 *
 * @since 11.1.0
 */
class SiteLocale {

	/**
	 * Resolve the site's configured locale from stored settings, bypassing request state.
	 *
	 * Mirrors get_locale()'s underlying resolution chain (WPLANG option, network option,
	 * WPLANG constant, localized build) but skips the `$GLOBALS['locale']` cache and the
	 * `locale` filter: the cache can be stale after switch_to_blog(), and the filter reflects
	 * a temporary locale switch or the current visitor's language on multilingual sites.
	 * The result is deterministic for a given site configuration, whichever request asks.
	 *
	 * @return string The site locale, e.g. 'en_US'.
	 */
	public static function get(): string {
		if ( is_multisite() && wp_installing() ) {
			$site_locale = get_site_option( 'WPLANG' );
		} else {
			$site_locale = get_option( 'WPLANG' );

			if ( false === $site_locale && is_multisite() ) {
				$site_locale = get_site_option( 'WPLANG' );
			}
		}

		if ( false === $site_locale ) {
			$site_locale = defined( 'WPLANG' ) ? WPLANG : ( $GLOBALS['wp_local_package'] ?? '' );
		}

		return empty( $site_locale ) ? 'en_US' : (string) $site_locale;
	}

	/**
	 * Run a callback with translations loaded for the site locale.
	 *
	 * Switches the locale — and reloads the WooCommerce textdomain — only when the current
	 * request locale differs from the site locale, and restores everything afterwards, even
	 * when the callback throws. The WooCommerce instance is read from the global rather than
	 * the WC() accessor so this stays safe before WooCommerce finishes initializing.
	 *
	 * Nesting-safe: the `plugin_locale` filter is only added when absent and only removed when
	 * this call added it, so an enclosing wc_switch_to_site_locale() window keeps its own
	 * registration intact.
	 *
	 * @param callable $callback The code to run under the site locale.
	 * @return mixed The callback's return value.
	 */
	public static function run( callable $callback ) {
		$site_locale                   = self::get();
		$locale_was_switched           = false;
		$reload_woocommerce_textdomain = null;
		$added_plugin_locale_filter    = false;

		try {
			// determine_locale() may reflect a temporary locale switch, a locale filter, or a different blog's cached locale.
			if ( determine_locale() !== $site_locale && function_exists( 'switch_to_locale' ) ) {
				$locale_was_switched = switch_to_locale( $site_locale );

				if ( $locale_was_switched ) {
					$woocommerce                      = $GLOBALS['woocommerce'] ?? null;
					$woocommerce_textdomain_candidate = array( $woocommerce, 'load_plugin_textdomain' );

					if ( is_callable( $woocommerce_textdomain_candidate ) ) {
						$reload_woocommerce_textdomain = Closure::fromCallable( $woocommerce_textdomain_candidate );

						if ( false === has_filter( 'plugin_locale', 'get_locale' ) ) {
							add_filter( 'plugin_locale', 'get_locale' );
							$added_plugin_locale_filter = true;
						}

						$reload_woocommerce_textdomain();
					}
				}
			}

			return $callback();
		} finally {
			if ( $locale_was_switched ) {
				restore_previous_locale();

				if ( null !== $reload_woocommerce_textdomain ) {
					if ( $added_plugin_locale_filter ) {
						remove_filter( 'plugin_locale', 'get_locale' );
					}

					$reload_woocommerce_textdomain();
				}
			}
		}
	}
}
