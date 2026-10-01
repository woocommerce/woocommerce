<?php
/**
 * Ability registration arguments class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the `in_memory_write` and `extension_fields` registration arguments,
 * removes them before WP_Ability sees them, and builds the ability's schemas
 * and execute callback from them.
 *
 * @since 11.3.0
 */
final class RegistrationArgs {

	public const KEYS = array( 'in_memory_write', 'extension_fields' );

	/**
	 * Declared keys, keyed by ability name.
	 *
	 * @var array<string, array<string, array>>
	 */
	private static $declared = array();

	/**
	 * The `in_memory_write` and `extension_fields` an ability declared.
	 *
	 * @param string $ability_name Ability name.
	 * @return array<string, array>
	 */
	public static function get( string $ability_name ): array {
		return self::$declared[ $ability_name ] ?? array();
	}

	/**
	 * Remove the keys, which WP_Ability does not accept.
	 *
	 * @param array $args Registration arguments.
	 * @return array
	 */
	public static function strip( array $args ): array {
		return array_diff_key( $args, array_flip( self::KEYS ) );
	}

	/**
	 * Build the ability's schemas and execute callback from its keys.
	 *
	 * @param array  $args         Registration arguments.
	 * @param string $ability_name Ability name.
	 * @return array
	 */
	public static function apply( array $args, string $ability_name ): array {
		$declared = array_filter( array_intersect_key( $args, array_flip( self::KEYS ) ), 'is_array' );
		$args     = self::strip( $args );
		unset( self::$declared[ $ability_name ] );

		if ( empty( $declared ) ) {
			return $args;
		}
		self::$declared[ $ability_name ] = $declared;

		$steps      = $declared['in_memory_write'] ?? null;
		$fields     = $declared['extension_fields'] ?? null;
		$extensions = null === $fields ? null : AbilityFields::schema( AbilityFields::get( $fields['object_type'] ) );

		if ( null !== $extensions && null !== $steps && isset( $args['input_schema']['properties'] ) && ! isset( $args['input_schema']['properties']['extensions'] ) ) {
			$args['input_schema']['properties']['extensions'] = $extensions;
		}
		if ( null !== $extensions && isset( $fields['output'], $args['output_schema'] ) ) {
			$args['output_schema'] = AbilityFields::add_to_output_schema( $args['output_schema'], $fields['output'], $extensions );
		}

		if ( null !== $steps ) {
			$args['execute_callback'] = static function ( $input = null ) use ( $ability_name, $steps, $fields ) {
				return InMemoryWriteRunner::run( $ability_name, $steps, $fields, is_array( $input ) ? $input : array() );
			};
		} elseif ( null !== $fields && isset( $args['execute_callback'] ) ) {
			$execute                  = $args['execute_callback'];
			$args['execute_callback'] = static function ( $input = null ) use ( $execute, $fields ) {
				return AbilityFields::fill_output( null === $input ? call_user_func( $execute ) : call_user_func( $execute, $input ), $fields );
			};
		}

		return $args;
	}
}
