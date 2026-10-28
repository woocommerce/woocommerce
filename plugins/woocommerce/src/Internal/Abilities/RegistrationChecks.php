<?php
/**
 * Registration checks class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Argument checks that AbilityResources and ExtensibleAbilities share. Each reports the problem with
 * wc_doing_it_wrong() and returns false, so the registration is refused.
 */
final class RegistrationChecks {

	/**
	 * Whether the given arguments are slugs of lowercase letters, numbers, dashes and underscores, not only numbers.
	 *
	 * @param string                $method Method that was called.
	 * @param array<string, string> $values Argument values keyed by parameter name.
	 * @return bool
	 */
	public static function has_slugs( string $method, array $values ): bool {
		foreach ( $values as $name => $value ) {
			if ( ! is_string( $value ) || '' === $value || sanitize_key( $value ) !== $value || ctype_digit( $value ) ) {
				wc_doing_it_wrong(
					$method,
					sprintf( 'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores, not only numbers.', $name ),
					'11.4.0'
				);
				return false;
			}
		}
		return true;
	}

	/**
	 * Whether the arguments every field needs are valid: a schema with a `type`, no `required`, a get_callback,
	 * and a boolean personal_data flag.
	 *
	 * @param string               $method     Method that was called.
	 * @param array<string, mixed> $args       Field arguments.
	 * @param bool                 $needs_type Whether the schema needs a `type`. A schema that nests a resource has it from there.
	 * @return bool
	 */
	public static function has_field_args( string $method, array $args, bool $needs_type ): bool {
		$schema = $args['schema'] ?? null;
		if ( ! is_array( $schema ) || ( $needs_type && ! isset( $schema['type'] ) ) ) {
			wc_doing_it_wrong( $method, 'The "schema" argument must be an array with a "type".', '11.4.0' );
			return false;
		}
		if ( true === ( $schema['required'] ?? null ) ) {
			wc_doing_it_wrong( $method, 'The "schema" argument cannot set "required". A value that fails its schema is left out, so allow null for a field that is always present.', '11.4.0' );
			return false;
		}
		if ( ! is_callable( $args['get_callback'] ?? null ) ) {
			wc_doing_it_wrong( $method, 'The "get_callback" argument must be callable.', '11.4.0' );
			return false;
		}
		if ( isset( $args['personal_data'] ) && ! is_bool( $args['personal_data'] ) ) {
			wc_doing_it_wrong( $method, 'The "personal_data" argument must be a boolean.', '11.4.0' );
			return false;
		}
		return true;
	}

	/**
	 * Whether a schema has a `$ref` at any depth.
	 *
	 * @param array<string, mixed> $schema JSON schema.
	 * @return bool
	 */
	public static function contains_ref( array $schema ): bool {
		if ( isset( $schema['$ref'] ) ) {
			return true;
		}
		foreach ( $schema as $child ) {
			if ( is_array( $child ) && self::contains_ref( $child ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Report a field that is already registered.
	 *
	 * @param string $method        Method that was called.
	 * @param string $field         Field, with its namespace when it has one.
	 * @param string $resource_name Resource.
	 */
	public static function report_duplicate( string $method, string $field, string $resource_name ): void {
		wc_doing_it_wrong( $method, sprintf( 'The field "%1$s" of "%2$s" is already registered.', $field, $resource_name ), '11.4.0' );
	}

	/**
	 * Report a call that names a resource that is not registered.
	 *
	 * @param string $method        Method that was called.
	 * @param string $resource_name Resource.
	 */
	public static function report_unregistered( string $method, string $resource_name ): void {
		wc_doing_it_wrong(
			$method,
			sprintf( 'The "%s" resource is not registered. Call AbilityResources::register_resource() first.', $resource_name ),
			'11.4.0'
		);
	}
}
