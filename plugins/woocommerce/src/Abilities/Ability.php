<?php
/**
 * Ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * The base class of the WooCommerce abilities.
 *
 * WordPress validates the input of an ability against its schema, but only the
 * REST route converts it to the schema types. A direct execute() call, such as
 * one from the MCP adapter, gets a boolean field as the string "false". This
 * class converts valid input on every path, the same way the REST route does.
 *
 * @since 11.4.0
 */
class Ability extends \WP_Ability {

	/**
	 * Apply the schema default, then convert valid input to the schema types.
	 *
	 * Invalid input stays unchanged, so validate_input() refuses it as before.
	 *
	 * @since 11.4.0
	 *
	 * @param mixed $input Optional. The raw input provided for the ability. Default `null`.
	 * @return mixed The converted input, the input unchanged when it cannot be converted, or a WP_Error from the parent.
	 */
	public function normalize_input( $input = null ) {
		$input = parent::normalize_input( $input );
		if ( null === $input || is_wp_error( $input ) ) {
			return $input;
		}

		$schema = $this->get_input_schema();
		if ( empty( $schema ) || is_wp_error( $this->validate_input( $input ) ) ) {
			return $input;
		}

		$sanitized = rest_sanitize_value_from_schema( $input, $schema, 'input' );

		return self::contains_error( $sanitized ) ? $input : $sanitized;
	}

	/**
	 * Whether a converted value is, or contains, a WP_Error.
	 *
	 * Converting can fail on input that validated, such as items that are unique
	 * as strings but not as integers.
	 *
	 * @param mixed $value Converted value.
	 * @return bool
	 */
	private static function contains_error( $value ): bool {
		if ( is_wp_error( $value ) ) {
			return true;
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( self::contains_error( $item ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
