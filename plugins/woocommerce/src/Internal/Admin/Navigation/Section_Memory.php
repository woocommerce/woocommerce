<?php

declare( strict_types = 1 );

/**
 * Lightweight section-memory tracker for navigation_v2.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks whether the user is currently inside the WooCommerce admin section
 * via a cookie carrying the last visited Woo URL, and bounces fresh
 * `/wp-admin/` entries (login redirect, typed URL, external entry) to that
 * stored URL.
 */
class Section_Memory {

	public const COOKIE_NAME = 'wc_admin_last_woo';

	/**
	 * Cookie lifetime.
	 */
	private const TTL = 30 * DAY_IN_SECONDS;

	/**
	 * Register hooks.
	 *
	 * `admin_init` fires after `admin_menu`, so `Menu_Reconciler::get_tree()`
	 * is ready by the time we run. Priority 1 keeps us ahead of any other
	 * `admin_init` handlers that might start output (cookies require headers).
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'sync_section' ), 1 );
	}

	/**
	 * On every admin page load: refresh / delete the cookie based on the
	 * current page's Woo-ness, and redirect Dashboard root entries to the
	 * stored Woo URL when the cookie is set.
	 *
	 * @internal
	 */
	public function sync_section(): void {
		if ( $this->is_non_page_request() || headers_sent() ) {
			return;
		}

		$tree = Menu_Reconciler::get_tree();
		if ( null === $tree ) {
			return;
		}

		// Dashboard root entry: redirect BEFORE we touch the cookie. Otherwise
		// we'd delete (Dashboard isn't a Woo page) the value we want to
		// redirect to.
		if ( $this->is_dashboard_entry() && $this->is_fresh_entry() ) {
			$target = $this->read_cookie_target();
			if ( null !== $target ) {
				wp_safe_redirect( $target );
				exit;
			}
		}

		if ( Context::is_woo_page( $tree ) ) {
			$this->write_cookie( $this->current_path() );
		} else {
			$this->delete_cookie();
		}
	}

	/**
	 * Skip AJAX / REST / cron / CLI — we only act on actual admin page loads.
	 */
	private function is_non_page_request(): bool {
		if ( wp_doing_ajax() ) {
			return true;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		if ( wp_doing_cron() ) {
			return true;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		return false;
	}

	/**
	 * True when the current request is the WP Dashboard root. `/wp-admin/` and
	 * `/wp-admin/index.php` both resolve $pagenow to `index.php`.
	 */
	private function is_dashboard_entry(): bool {
		global $pagenow;
		return 'index.php' === $pagenow;
	}

	/**
	 * "Fresh entry" = the user just arrived in wp-admin (login redirect,
	 * typed URL, external link). Clicks from elsewhere inside wp-admin
	 * (referer starts with the admin URL) are treated as intentional
	 * Dashboard navigation and skip the redirect.
	 */
	private function is_fresh_entry(): bool {
		$referer = wp_get_referer();
		if ( false === $referer || '' === $referer ) {
			return true;
		}
		return 0 !== strpos( $referer, admin_url() );
	}

	/**
	 * The current request's path-and-query (admin-relative). Used as the
	 * cookie value when on a Woo page.
	 */
	private function current_path(): string {
		return esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
	}

	/**
	 * Write (or refresh) the cookie pointing at the current path.
	 *
	 * @param string $path Path to remember.
	 */
	private function write_cookie( string $path ): void {
		setcookie(
			self::COOKIE_NAME,
			rawurlencode( $path ),
			array(
				'expires'  => time() + self::TTL,
				'path'     => $this->cookie_path(),
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		// Keep the in-request $_COOKIE in sync so a later read on this same
		// request sees the value we just wrote.
		$_COOKIE[ self::COOKIE_NAME ] = rawurlencode( $path );
	}

	/**
	 * Delete the cookie (no-op if not set).
	 */
	private function delete_cookie(): void {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return;
		}
		setcookie(
			self::COOKIE_NAME,
			'',
			array(
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => $this->cookie_path(),
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	/**
	 * Cookie path — admin-only, derived from `admin_url()` so installs that
	 * live under a subdirectory still scope the cookie correctly.
	 */
	private function cookie_path(): string {
		$path = (string) wp_parse_url( admin_url(), PHP_URL_PATH );
		return '' === $path ? '/' : $path;
	}

	/**
	 * Read and validate the cookie target. Returns null when the cookie is
	 * missing, malformed, or doesn't point inside wp-admin (defense against
	 * a tampered cookie producing an open-redirect path).
	 */
	private function read_cookie_target(): ?string {
		$raw = isset( $_COOKIE[ self::COOKIE_NAME ] )
			? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) )
			: '';
		if ( '' === $raw ) {
			return null;
		}
		$path = rawurldecode( $raw );
		if ( 0 !== strpos( $path, $this->cookie_path() ) ) {
			return null;
		}
		return $path;
	}
}
