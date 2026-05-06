<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Utilities;

/**
 * Helper for appending UTM campaign parameters to outgoing stock-notification email links.
 *
 * Centralizes the `utm_source` / `utm_medium` values so order attribution stays consistent
 * across all three email types (verify, verified, back-in-stock).
 *
 * @internal
 */
class UtmHelper {

	/**
	 * UTM source value used for all stock notification emails.
	 */
	public const UTM_SOURCE = 'back-in-stock-notifications';

	/**
	 * Default UTM medium for stock notification emails.
	 */
	public const UTM_MEDIUM_EMAIL = 'email';

	/**
	 * Append the standard email UTM parameters to a URL.
	 *
	 * @param string $url    The URL to annotate.
	 * @param string $medium The UTM medium (defaults to `email`).
	 * @return string
	 */
	public static function add_email_utm_params( string $url, string $medium = self::UTM_MEDIUM_EMAIL ): string {
		if ( empty( $url ) ) {
			return $url;
		}

		// Defensive: lock down the medium to a safe URL-friendly slug, falling back to the default
		// if sanitization strips everything. Prevents any future caller from piping user-controlled
		// input into the outbound tracking URL.
		$sanitized_medium = sanitize_key( $medium );
		if ( '' === $sanitized_medium ) {
			$sanitized_medium = self::UTM_MEDIUM_EMAIL;
		}

		return add_query_arg(
			array(
				'utm_source' => self::UTM_SOURCE,
				'utm_medium' => $sanitized_medium,
			),
			$url
		);
	}
}
