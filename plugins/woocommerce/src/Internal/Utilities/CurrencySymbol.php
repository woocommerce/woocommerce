<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use Automattic\WooCommerce\Enums\CurrencyPosition;

/**
 * Provides the currency symbol and its position for non-HTML consumers (client settings payloads, APIs).
 *
 * The position is read from the filtered price format rather than the `woocommerce_currency_pos`
 * option, so extensions that filter `woocommerce_price_format` (e.g. currency switchers) apply here too.
 *
 * @since 11.3.0
 */
class CurrencySymbol {

	/**
	 * Get the store currency symbol with HTML entities decoded.
	 *
	 * ENT_HTML5 matches how browsers parse the entity in HTML output, as in PriceSeparators.
	 *
	 * @return string
	 */
	public static function get_symbol(): string {
		return html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 );
	}

	/**
	 * Get the currency symbol position, as one of the CurrencyPosition values.
	 *
	 * Any text between the symbol and the amount in the price format is treated as a space.
	 * Falls back to the option when the format is missing either placeholder.
	 *
	 * @return string
	 */
	public static function get_position(): string {
		$format     = (string) get_woocommerce_price_format();
		$symbol_pos = strpos( $format, '%1$s' );
		$amount_pos = strpos( $format, '%2$s' );

		if ( false === $symbol_pos || false === $amount_pos ) {
			return (string) get_option( 'woocommerce_currency_pos' );
		}

		// Placeholders are adjacent when they are exactly one placeholder length apart.
		$has_space = abs( $amount_pos - $symbol_pos ) > strlen( '%1$s' );

		if ( $symbol_pos < $amount_pos ) {
			return $has_space ? CurrencyPosition::LEFT_SPACE : CurrencyPosition::LEFT;
		}

		return $has_space ? CurrencyPosition::RIGHT_SPACE : CurrencyPosition::RIGHT;
	}
}
