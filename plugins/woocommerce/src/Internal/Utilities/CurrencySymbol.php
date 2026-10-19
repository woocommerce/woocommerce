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
	 * Only visible whitespace between the symbol and the amount in the price format counts as a space,
	 * so markup is ignored and entities such as `&nbsp;` are decoded first.
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

		$placeholder_length = strlen( '%1$s' );
		$between            = (string) substr( $format, min( $symbol_pos, $amount_pos ) + $placeholder_length, abs( $amount_pos - $symbol_pos ) - $placeholder_length );
		$visible            = html_entity_decode( (string) preg_replace( '/<[^>]*>/', '', $between ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 );
		$has_space          = 1 === preg_match( '/[\s\p{Zs}]/u', $visible );

		if ( $symbol_pos < $amount_pos ) {
			return $has_space ? CurrencyPosition::LEFT_SPACE : CurrencyPosition::LEFT;
		}

		return $has_space ? CurrencyPosition::RIGHT_SPACE : CurrencyPosition::RIGHT;
	}
}
