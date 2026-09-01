<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Provides postcode validation from the shared PHP/JavaScript rule artifact.
 */
final class PostcodeValidation {
	/**
	 * Cached generated rules.
	 *
	 * @var array<string, array{pattern: string, flags?: string, normalization?: string}>|null
	 */
	private static ?array $rules = null;

	/**
	 * Get all generated postcode rules.
	 *
	 * @return array<string, array{pattern: string, flags?: string, normalization?: string}>
	 */
	public static function get_rules(): array {
		if ( null !== self::$rules ) {
			return self::$rules;
		}

		$rules_file = dirname( __DIR__, 3 ) . '/i18n/postcode-validation-rules.json';
		if ( ! is_readable( $rules_file ) ) {
			self::$rules = array();
			return self::$rules;
		}

		$contents = file_get_contents( $rules_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data     = false !== $contents ? json_decode( $contents, true ) : null;
		$rules    = is_array( $data ) && isset( $data['rules'] ) && is_array( $data['rules'] ) ? $data['rules'] : array();

		self::$rules = array_filter(
			$rules,
			static function ( $rule ): bool {
				return is_array( $rule ) && isset( $rule['pattern'] ) && is_string( $rule['pattern'] );
			}
		);

		return self::$rules;
	}

	/**
	 * Validate a postcode if a shared rule exists for its country.
	 *
	 * @param string $postcode Postcode to validate.
	 * @param string $country  Country code.
	 * @return bool|null Whether the postcode is valid, or null when no usable rule exists.
	 */
	public static function validate( string $postcode, string $country ): ?bool {
		$rules = self::get_rules();
		if ( ! isset( $rules[ $country ] ) ) {
			return null;
		}

		$rule = $rules[ $country ];
		if ( isset( $rule['normalization'] ) ) {
			switch ( $rule['normalization'] ) {
				case 'removeSpaces':
					$postcode = str_replace( ' ', '', $postcode );
					break;
				case 'removeSpacesAndHyphens':
					$postcode = (string) preg_replace( '/[\s\-]/', '', trim( $postcode ) );
					break;
			}
		}

		$flags   = isset( $rule['flags'] ) && 'i' === $rule['flags'] ? 'i' : '';
		$matched = preg_match( '~\A(?:' . $rule['pattern'] . ')\z~' . $flags, $postcode );

		return false === $matched ? null : 1 === $matched;
	}
}
