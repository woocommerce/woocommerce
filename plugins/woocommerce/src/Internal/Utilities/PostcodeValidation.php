<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Postcode validation rules, shared by the server and both checkouts.
 */
final class PostcodeValidation {
	/**
	 * Cache of the rules.
	 *
	 * @var array|null
	 */
	private static ?array $rules = null;

	/**
	 * Get the postcode validation rules, keyed by country code.
	 *
	 * @since 11.2.0
	 *
	 * @return array Rules keyed by country code.
	 */
	public static function get_rules(): array {
		if ( ! isset( self::$rules ) ) {
			/**
			 * Full list of postcode patterns before being trimmed down and passed
			 * to Checkout block and shortcode, or used to validate incoming postcode.
			 *
			 * @since 11.2.0
			 *
			 * @param array $rules Rules keyed by country code.
			 */
			self::$rules = apply_filters( 'woocommerce_postcode_validation_rules', include WC_ABSPATH . 'i18n/postcode-validation-rules.php' );
		}

		return self::$rules;
	}

	/**
	 * Get the rules for a list of countries.
	 *
	 * Checkout sends only the rules for the countries a store sells to, rather than
	 * the whole set.
	 *
	 * @since 11.2.0
	 *
	 * @param array $country_codes Country codes to look up.
	 * @return array Rules keyed by country code.
	 */
	public static function get_rules_for_countries( array $country_codes ): array {
		return array_intersect_key( self::get_rules(), array_flip( $country_codes ) );
	}

	/**
	 * Validate a postcode against its country's rule.
	 *
	 * @since 11.2.0
	 *
	 * @param string $postcode Postcode to validate.
	 * @param string $country  Country code.
	 * @return bool|null Whether the postcode is valid, or null when the country has no usable rule.
	 */
	public static function validate( string $postcode, string $country ): ?bool {
		$pattern = self::get_rules()[ $country ] ?? null;

		if ( ! is_string( $pattern ) || '' === $pattern ) {
			return null;
		}

		$matched = preg_match( '~\A(?:' . $pattern . ')\z~i', $postcode );

		return false === $matched ? null : 1 === $matched;
	}
}
