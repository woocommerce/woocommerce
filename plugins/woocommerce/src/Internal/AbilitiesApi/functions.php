<?php
/**
 * Functions to register ability fields and object validators. Each defers to
 * the WordPress function of the same name with a `wp_` prefix once WordPress
 * provides it.
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityFields;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityObjectValidators;

if ( ! function_exists( 'wc_register_ability_field' ) ) {
	/**
	 * Register a field on an object type's abilities, under `extensions`.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $object_type Object type, such as `product`.
	 * @param string               $attribute   Attribute under `extensions`.
	 * @param array<string, mixed> $args        `schema`, `get_callback( $object )` and `update_callback( $value, $object )`, which changes the object in memory, never saves, and returns a WP_Error to reject the write.
	 */
	function wc_register_ability_field( string $object_type, string $attribute, array $args ): void {
		if ( function_exists( 'wp_register_ability_field' ) ) {
			wp_register_ability_field( $object_type, $attribute, $args );
			return;
		}
		AbilityFields::register( $object_type, $attribute, $args );
	}
}

if ( ! function_exists( 'wc_register_ability_object_validator' ) ) {
	/**
	 * Register a validator that checks an applied object of a type before an in-memory write saves it.
	 *
	 * @since 11.3.0
	 *
	 * @param string   $object_type Object type.
	 * @param callable $validator   Receives the object, returns true or a WP_Error, never changes it.
	 */
	function wc_register_ability_object_validator( string $object_type, callable $validator ): void {
		if ( function_exists( 'wp_register_ability_object_validator' ) ) {
			wp_register_ability_object_validator( $object_type, $validator );
			return;
		}
		AbilityObjectValidators::register( $object_type, $validator );
	}
}
