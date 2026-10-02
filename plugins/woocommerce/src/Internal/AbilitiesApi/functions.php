<?php
/**
 * Functions to register ability fields and object validators, and to read,
 * update and check an object with them without saving, for example to build a
 * preview. Each defers to the WordPress function of the same name with a `wp_`
 * prefix once WordPress provides it.
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
	 * @param array<string, mixed> $args        `schema`, `get_callback( $object )` and `update_callback( $value, $object )`, which changes the object in memory and returns a WP_Error to reject the write. It must not save, send email or make HTTP requests. Nothing is saved until every step passes.
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
	 * @param callable $validator   Receives the object, returns true or a WP_Error, never changes it. It must not save, send email or make HTTP requests. Nothing is saved until every step passes.
	 */
	function wc_register_ability_object_validator( string $object_type, callable $validator ): void {
		if ( function_exists( 'wp_register_ability_object_validator' ) ) {
			wp_register_ability_object_validator( $object_type, $validator );
			return;
		}
		AbilityObjectValidators::register( $object_type, $validator );
	}
}

if ( ! function_exists( 'wc_get_ability_field_values' ) ) {
	/**
	 * The value of each field registered for an object type, read from the object, keyed by attribute. A field that reads null is left out.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject     Object.
	 * @param string $object_type Object type.
	 * @return array<string, mixed>
	 */
	function wc_get_ability_field_values( $subject, string $object_type ): array {
		if ( function_exists( 'wp_get_ability_field_values' ) ) {
			return wp_get_ability_field_values( $subject, $object_type );
		}
		return AbilityFields::read( AbilityFields::get( $object_type ), $subject );
	}
}

if ( ! function_exists( 'wc_update_ability_fields' ) ) {
	/**
	 * Run the update callbacks of the given fields on the object, in memory. Never saves.
	 *
	 * @since 11.3.0
	 *
	 * @param object               $subject     Object.
	 * @param string               $object_type Object type.
	 * @param array<string, mixed> $values      Values keyed by attribute, as under `extensions`.
	 * @return WP_Error|null The first rejection, or null when every value is applied.
	 */
	function wc_update_ability_fields( $subject, string $object_type, array $values ): ?WP_Error {
		if ( function_exists( 'wp_update_ability_fields' ) ) {
			return wp_update_ability_fields( $subject, $object_type, $values );
		}
		return AbilityFields::update( AbilityFields::get( $object_type ), $subject, $values );
	}
}

if ( ! function_exists( 'wc_validate_ability_object' ) ) {
	/**
	 * Run the validators registered for an object type on the object. Never saves.
	 *
	 * @since 11.3.0
	 *
	 * @param object $subject     Object.
	 * @param string $object_type Object type.
	 * @return WP_Error|null The first rejection, or null when every validator accepts.
	 */
	function wc_validate_ability_object( $subject, string $object_type ): ?WP_Error {
		if ( function_exists( 'wp_validate_ability_object' ) ) {
			return wp_validate_ability_object( $subject, $object_type );
		}
		return AbilityObjectValidators::validate( $subject, $object_type );
	}
}

if ( ! function_exists( 'wc_get_ability_fields_schema' ) ) {
	/**
	 * The `extensions` JSON schema of an object type, with each field's schema as registered.
	 *
	 * @since 11.3.0
	 *
	 * @param string $object_type Object type.
	 * @return array<string, mixed>|null Null when no field is registered.
	 */
	function wc_get_ability_fields_schema( string $object_type ): ?array {
		if ( function_exists( 'wp_get_ability_fields_schema' ) ) {
			return wp_get_ability_fields_schema( $object_type );
		}
		return AbilityFields::schema( AbilityFields::get( $object_type ) );
	}
}
