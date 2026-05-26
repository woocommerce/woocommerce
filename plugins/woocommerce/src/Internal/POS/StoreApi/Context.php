<?php
/**
 * Context class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

/**
 * Request-scoped detection of POS Store API requests.
 *
 * Mirrors the URI-prefix detection that WooCommerce uses for the Store API itself
 * (see WooCommerce::is_store_api_request). The result is consulted by the POS
 * session handler and by policy hooks that need to relax pipeline rules for POS
 * (e.g. allowing oversell). Detection is cheap and idempotent, so callers may
 * invoke it freely.
 *
 * A test override is provided so PHPUnit cases can simulate POS context without
 * constructing a full REST request.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Context {

	/**
	 * Test-only override. When non-null, takes precedence over URI detection.
	 *
	 * @var bool|null
	 */
	private static $test_override = null;

	/**
	 * REST URI prefix that identifies a POS Store API request.
	 */
	private const URI_PREFIX = 'wc/pos/';

	/**
	 * Whether the current request is a POS Store API request.
	 *
	 * @return bool
	 */
	public static function is_pos_request(): bool {
		if ( null !== self::$test_override ) {
			return self::$test_override;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = $_SERVER['REQUEST_URI'];

		return false !== strpos( $request_uri, trailingslashit( rest_get_url_prefix() ) . self::URI_PREFIX );
	}

	/**
	 * Override POS request detection for testing.
	 *
	 * Pass true/false to force a value, or null to clear and revert to URI detection.
	 *
	 * @param bool|null $value Override value.
	 */
	public static function set_test_override( ?bool $value ): void {
		self::$test_override = $value;
	}
}
