<?php
/**
 * Ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * The base class of the WooCommerce abilities.
 *
 * WordPress 7.2 converts valid ability input to the schema types with
 * WP_Ability::coerce_input() before the callbacks run. This class backports
 * that method, so the callbacks get converted input on WordPress 7.0 and 7.1 too.
 * It also prepares the input for callers that call check_permissions() directly.
 *
 * @since 11.4.0
 */
class Ability extends \WP_Ability {

	/**
	 * Apply the schema default. Before WordPress 7.2, also convert valid input to
	 * the schema types, as WordPress 7.2 does in execute().
	 *
	 * Invalid input stays unchanged, so validate_input() refuses it as before.
	 *
	 * Remove this override, coerce_input() and get_input_error() when WooCommerce
	 * requires WordPress 7.2, because WP_Ability::execute() converts the input there.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $input Optional. The raw input provided for the ability. Default `null`.
	 * @return mixed The input, converted before WordPress 7.2, or a WP_Error.
	 */
	final public function normalize_input( $input = null ) {
		$input = parent::normalize_input( $input );
		if ( method_exists( \WP_Ability::class, 'coerce_input' ) || null === $input || is_wp_error( $input ) || is_wp_error( $this->validate_input( $input ) ) ) {
			return $input;
		}

		return $this->coerce_input( $input );
	}

	/**
	 * Apply the schema default, validate and convert the input, then check the permissions.
	 *
	 * Some callers, such as older MCP adapter versions, call this method directly with
	 * raw input. The permission callback then gets the same input as through execute().
	 * Input that is already prepared passes through unchanged.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $input Optional. The raw input provided for the ability. Default `null`.
	 * @return bool|\WP_Error Whether the ability has the necessary permission, or the input error.
	 */
	final public function check_permissions( $input = null ) {
		$input = $this->normalize_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		$is_valid = $this->validate_input( $input );
		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		$input = $this->coerce_input( $input );
		if ( is_wp_error( $input ) ) {
			return $input;
		}

		return parent::check_permissions( $input );
	}

	/**
	 * Convert validated input to the schema types, the same way WordPress 7.2 does.
	 *
	 * Converting can fail on input that validated, such as items that are unique
	 * as strings but not as integers. The input is then refused.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $input Validated ability input.
	 * @return mixed|\WP_Error The converted input, or a WP_Error when it cannot be converted.
	 */
	public function coerce_input( $input ) {
		$schema = $this->get_input_schema();
		if ( null === $input || empty( $schema ) ) {
			return $input;
		}

		$sanitized = rest_sanitize_value_from_schema( $input, $schema, 'input' );

		$error = self::get_input_error( $sanitized );
		if ( null !== $error ) {
			return new \WP_Error(
				'ability_invalid_input',
				sprintf(
					/* translators: %1$s: ability name, %2$s: error message. */
					__( 'Ability "%1$s" has invalid input. Reason: %2$s', 'woocommerce' ),
					$this->name,
					$error->get_error_message()
				)
			);
		}

		return $sanitized;
	}

	/**
	 * Get the first WP_Error in a converted value.
	 *
	 * @param mixed $value Converted value.
	 * @return \WP_Error|null
	 */
	private static function get_input_error( $value ): ?\WP_Error {
		if ( is_wp_error( $value ) ) {
			return $value;
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$error = self::get_input_error( $item );
				if ( null !== $error ) {
					return $error;
				}
			}
		}
		return null;
	}
}
