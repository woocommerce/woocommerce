<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Provides the price separators decoded for non-HTML consumers (client settings payloads, APIs).
 *
 * Merchants may store a separator as an HTML entity such as `&nbsp;` or `&apos;`, which renders
 * correctly in HTML output but shows up as literal text when the raw value reaches JavaScript or
 * API responses. ENT_HTML5 matches how browsers parse the entity in HTML output.
 *
 * @since 11.2.0
 */
class PriceSeparators {

	/**
	 * Get the decimal separator with HTML entities decoded.
	 *
	 * @return string
	 */
	public static function get_decimal(): string {
		return html_entity_decode( wc_get_price_decimal_separator(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 );
	}

	/**
	 * Get the thousand separator with HTML entities decoded.
	 *
	 * @return string
	 */
	public static function get_thousand(): string {
		return html_entity_decode( wc_get_price_thousand_separator(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 );
	}
}
