<?php
/**
 * BlockRegistrationContext class.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Domain;

/**
 * Decides whether WooCommerce block types and patterns should be registered for the current request.
 *
 * Runs during bootstrap on `plugins_loaded`, before the main query is parsed, so it inspects only $_SERVER,
 * $_GET and constants set before wp-load — not query-dependent helpers such as is_favicon()/is_robots().
 *
 * @internal
 *
 * @since 11.1.0
 */
class BlockRegistrationContext {

	/**
	 * Whether block types and patterns should be registered for the current request.
	 *
	 * @return bool True unless the request is a known non-rendering context.
	 */
	public function should_register(): bool {
		/**
		 * Filters whether WooCommerce should register its block types and patterns for the current request.
		 *
		 * Registration is skipped on known non-rendering contexts (the Store API and other WooCommerce REST
		 * namespaces, cron, AJAX, XML-RPC, favicon, robots.txt and XML sitemaps) as a performance optimisation.
		 * An extension that renders WooCommerce blocks in one of those contexts can return true here to opt back in.
		 *
		 * @since 11.1.0
		 *
		 * @param bool $should_register Whether block types and patterns should be registered for this request.
		 */
		return (bool) apply_filters( 'woocommerce_should_register_blocks', $this->is_rendering_request() );
	}

	/**
	 * Whether the current request may render or edit blocks.
	 *
	 * Blacklist of known non-rendering contexts: an unrecognised request keeps registering (the previous
	 * behaviour), so a missed case costs a little performance but never a rendering regression. Front-end,
	 * admin and wp/v2 (block/site editor) requests therefore keep registering.
	 *
	 * @return bool True unless the request is a known non-rendering context.
	 */
	private function is_rendering_request(): bool {
		// Store API renders no blocks.
		if ( wc()->is_store_api_request() ) {
			return false;
		}

		// Cron produces no output.
		if ( wp_doing_cron() ) {
			return false;
		}

		// AJAX (admin-ajax and wc-ajax) renders no blocks. wc-ajax's constants are set too late to use here, so
		// detect it from the request; ! empty() matches WC_AJAX::set_wc_ajax_argument_in_query().
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the endpoint only, no state change.
		if ( wp_doing_ajax() || ! empty( $_GET['wc-ajax'] ) ) {
			return false;
		}

		// XML-RPC renders no blocks; its constant is set before wp-load, so it is reliable here.
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}

		// Favicon, robots.txt and XML sitemaps render no blocks.
		if ( $this->is_non_rendering_path_request() ) {
			return false;
		}

		// WooCommerce REST namespaces render no blocks (Store API handled above). wp/v2 is left registering for
		// the block and site editors.
		if ( $this->is_woocommerce_rest_request() ) {
			return false;
		}

		// WooCommerce's own admin pages (Settings, Status, Analytics, Orders, ...) render no blocks. Core admin
		// screens and the block/site editor are intentionally left registering.
		if ( $this->is_woocommerce_admin_page() ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether the request targets a WooCommerce-owned admin page (admin.php?page=wc-*).
	 *
	 * These are WooCommerce's own settings/status/analytics screens, which render no blocks. Only WooCommerce's
	 * own pages are matched; core admin screens and the block/site editor are left registering. $pagenow is set
	 * in wp-includes/vars.php before the plugins_loaded action, so it is available here.
	 *
	 * @return bool
	 */
	private function is_woocommerce_admin_page(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		global $pagenow;
		if ( 'admin.php' !== $pagenow ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the page slug only, no state change.
		if ( ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the page slug only, no state change.
		$page = sanitize_key( wp_unslash( $_GET['page'] ) );

		// WooCommerce-owned admin page slugs. The WooCommerce Admin SPA (home, analytics, marketing) all use the
		// wc-admin slug with a path query parameter.
		$woocommerce_pages = array(
			'wc-admin',
			'wc-settings',
			'wc-orders',
			'wc-reports',
			'wc-status',
			'wc-addons',
		);

		return in_array( $page, $woocommerce_pages, true );
	}

	/**
	 * Whether the request targets a WordPress endpoint that renders no block content: the favicon, robots.txt or
	 * core XML sitemaps. The URI path is inspected directly because is_favicon()/is_robots() are unavailable this
	 * early. WP core serves these in wp-includes/template-loader.php and the WP_Sitemaps class.
	 *
	 * @return bool
	 */
	private function is_non_rendering_path_request(): bool {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = wp_parse_url( '/' . ltrim( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), '/' ), PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return false;
		}

		// Favicon, e.g. /favicon.ico (also matches subdirectory installs).
		if ( '/favicon.ico' === substr( $path, -12 ) ) {
			return true;
		}

		// robots.txt.
		if ( '/robots.txt' === substr( $path, -11 ) ) {
			return true;
		}

		// Core XML sitemaps, e.g. /wp-sitemap.xml or /wp-sitemap.xsl. The suffix check avoids matching a page
		// slug that merely contains "wp-sitemap".
		if ( false !== strpos( $path, 'wp-sitemap' ) && ( '.xml' === substr( $path, -4 ) || '.xsl' === substr( $path, -4 ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the request targets a WooCommerce-owned REST namespace other than the Store API, in either pretty
	 * (/wp-json/<namespace>) or plain (?rest_route=/<namespace>) permalink form.
	 *
	 * @return bool
	 */
	private function is_woocommerce_rest_request(): bool {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// WooCommerce-owned namespaces that render no blocks; mirrors wc_rest_should_load_namespace() (add new
		// versions here too). Store API (wc/store) is handled above; the trailing slash prevents matching a
		// longer, unrelated namespace.
		$namespaces = array(
			'wc/v1/',
			'wc/v2/',
			'wc/v3/',
			'wc/v4/',
			'wc/private/',
			'wc-admin/',
			'wc-analytics/',
			'wc-telemetry/',
		);

		// Match against the path only (a leading slash anchors the prefix) so a REST-like query argument such as
		// /some-page/?arg=/wp-json/wc/v3 is not mistaken for a REST request.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path        = wp_parse_url( '/' . ltrim( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), '/' ), PHP_URL_PATH );
		$rest_prefix = '/' . trailingslashit( rest_get_url_prefix() );

		// Pretty permalinks: /wp-json/<namespace>...
		if ( is_string( $path ) ) {
			foreach ( $namespaces as $namespace ) {
				if ( false !== strpos( $path, $rest_prefix . $namespace ) ) {
					return true;
				}
			}
		}

		// Plain permalinks: ?rest_route=/<namespace>...
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the route only, no state change.
		if ( isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the route only, no state change.
			$rest_route = '/' . ltrim( rawurldecode( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ) ), '/' );
			foreach ( $namespaces as $namespace ) {
				if ( 0 === strpos( $rest_route, '/' . $namespace ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
