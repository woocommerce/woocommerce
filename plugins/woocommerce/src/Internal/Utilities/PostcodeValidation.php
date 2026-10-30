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
			 * @since 11.3.0
			 *
			 * @param array $rules Rules keyed by country code.
			 */
			$rules = apply_filters( 'woocommerce_postcode_validation_rules', include WC_ABSPATH . 'i18n/postcodes.php' );

			self::$rules = array_filter(
				is_array( $rules ) ? $rules : array(),
				static fn( $rule ): bool => is_string( $rule ) && '' !== $rule
			);
		}

		return self::$rules;
	}

	/**
	 * Get the rules that checkout scripts check in the browser.
	 *
	 * Returns no rules when a `woocommerce_validate_postcode` callback exists, because
	 * the browser cannot run it and could reject a postcode the server accepts.
	 *
	 * @since 11.3.0
	 *
	 * @param string[] $country_codes Countries the store sells or ships to.
	 * @return array Rules keyed by country code.
	 */
	public static function get_client_rules( array $country_codes ): array {
		if ( has_filter( 'woocommerce_validate_postcode' ) ) {
			return array();
		}

		return array_intersect_key( self::get_rules(), array_flip( $country_codes ) );
	}

	/**
	 * Check whether shortcode checkout uses the expanded postcode rules.
	 *
	 * @since 11.3.0
	 *
	 * @return bool Whether expanded validation is enabled.
	 */
	public static function use_expanded_shortcode_rules(): bool {
		/**
		 * Filters whether shortcode checkout uses the expanded postcode rules.
		 * Return false to use the previous rules and stop inline postcode checks.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $enabled Whether expanded validation is enabled. Default true.
		 */
		$enabled = apply_filters( 'woocommerce_experimental_extensive_postcode_rules', true );

		return is_bool( $enabled ) ? $enabled : true;
	}

	/**
	 * Check a postcode in shortcode checkout, with the rules the experimental filter selects.
	 *
	 * @since 11.3.0
	 *
	 * @param string $postcode Postcode to validate.
	 * @param string $country  Country code.
	 * @return bool Whether the postcode is valid.
	 */
	public static function is_valid_for_shortcode_checkout( string $postcode, string $country ): bool {
		if ( self::use_expanded_shortcode_rules() ) {
			return (bool) \WC_Validation::is_postcode( $postcode, $country );
		}

		// Same character check as WC_Validation::is_postcode().
		$valid = '' === trim( (string) preg_replace( '/[\s\-A-Za-z0-9]/', '', $postcode ) )
			&& false !== self::validate_legacy( $postcode, $country );

		/**
		 * Filters whether a postcode is valid for its country.
		 *
		 * @since 2.4.0
		 *
		 * @param bool   $valid    Whether the postcode passed the country rule.
		 * @param string $postcode Postcode to validate.
		 * @param string $country  Country code.
		 */
		return (bool) apply_filters( 'woocommerce_validate_postcode', $valid, $postcode, $country );
	}

	/**
	 * Validate a postcode with the rules used before the expanded set.
	 *
	 * @param string $postcode Postcode to validate.
	 * @param string $country  Country code.
	 * @return bool|null Whether the postcode is valid, or null when no rule exists.
	 */
	private static function validate_legacy( string $postcode, string $country ): ?bool {
		static $rules = null;
		if ( null === $rules ) {
			$rules = include WC_ABSPATH . 'i18n/postcodes-legacy.php';
		}

		$rule = $rules[ $country ] ?? null;
		if ( null === $rule ) {
			return null;
		}

		switch ( $rule['normalization'] ?? '' ) {
			case 'removeSpaces':
				$postcode = str_replace( ' ', '', $postcode );
				break;
			case 'removeSpacesAndHyphens':
				$postcode = (string) preg_replace( '/[\s\-]/', '', trim( $postcode ) );
				break;
		}

		$flags   = 'i' === ( $rule['flags'] ?? '' ) ? 'i' : '';
		$matched = preg_match( '~\A(?:' . $rule['pattern'] . ')\z~' . $flags, $postcode );

		return false === $matched ? null : 1 === $matched;
	}

	/**
	 * Validate a postcode against its country's rule.
	 *
	 * Spaces and hyphens are ignored, so the result does not depend on how the
	 * postcode was typed or formatted.
	 *
	 * @since 11.2.0
	 *
	 * @param string $postcode Postcode to validate.
	 * @param string $country  Country code.
	 * @return bool|null Whether the postcode is valid, or null when the country has no usable rule.
	 */
	public static function validate( string $postcode, string $country ): ?bool {
		$pattern = self::get_rules()[ $country ] ?? null;
		if ( null === $pattern ) {
			return null;
		}

		$matched = preg_match( '~\A(?:' . $pattern . ')\z~i', wc_normalize_postcode( $postcode ) );

		return false === $matched ? null : 1 === $matched;
	}
}
