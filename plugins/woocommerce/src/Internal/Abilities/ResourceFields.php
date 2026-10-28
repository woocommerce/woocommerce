<?php
/**
 * Resource fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\PersonalData;

defined( 'ABSPATH' ) || exit;

/**
 * The fields that AbilityResources outputs for a resource: its root fields without withheld personal data,
 * then the `extensions` field while the experimental feature is on.
 */
final class ResourceFields {

	/**
	 * Output fields keyed by resource and feature state. Personal data is decided once per request, so a list
	 * of objects reuses the fields of the first one.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $output_fields = array();

	/**
	 * Extension fields with a value that failed their schema, so each is reported once.
	 *
	 * @var array<string, bool>
	 */
	private static array $reported = array();

	/**
	 * Fields to output for a resource. Cached until forget() runs.
	 *
	 * @param string                              $resource_name Resource.
	 * @param array<string, array<string, mixed>> $root_fields   Root fields of the resource, keyed by field.
	 * @return array<string, array<string, mixed>>
	 */
	public static function for_output( string $resource_name, array $root_fields ): array {
		$enabled = ExtensibleAbilities::is_enabled();
		$key     = $resource_name . ( $enabled ? '|extensions' : '' );
		if ( ! isset( self::$output_fields[ $key ] ) ) {
			$fields           = self::withhold( $root_fields, $resource_name );
			$extension_fields = $enabled ? self::get_extension_fields( $resource_name ) : array();
			if ( ! empty( $extension_fields ) ) {
				$fields['extensions'] = self::get_extensions_field( $resource_name, $extension_fields );
			}
			self::$output_fields[ $key ] = $fields;
		}
		return self::$output_fields[ $key ];
	}

	/**
	 * Drop the cached output fields after a field is registered.
	 */
	public static function forget(): void {
		self::$output_fields = array();
	}

	/**
	 * The fields without those flagged as personal data, while the resource withholds them.
	 *
	 * @param array<string, array<string, mixed>> $fields        Field arguments keyed by field.
	 * @param string                              $resource_name Resource.
	 * @return array<string, array<string, mixed>>
	 */
	private static function withhold( array $fields, string $resource_name ): array {
		$personal = array_filter( $fields, static fn( array $field_args ) => ! empty( $field_args['personal_data'] ) );
		if ( empty( $personal ) || PersonalData::is_shared( $resource_name ) ) {
			return $fields;
		}
		return array_diff_key( $fields, $personal );
	}

	/**
	 * Extension fields to output, after withholding personal data. Empty namespaces are dropped.
	 *
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	private static function get_extension_fields( string $resource_name ): array {
		$extension_fields = array_map(
			static fn( array $fields ) => self::withhold( $fields, $resource_name ),
			ExtensibleAbilities::get_fields( $resource_name )
		);
		return array_filter( $extension_fields );
	}

	/**
	 * The `extensions` field, whose value holds the extension fields keyed by namespace, then field.
	 * It is left out of an output where no extension field has a value.
	 *
	 * @param string $resource_name    Resource.
	 * @param array  $extension_fields Fields from get_extension_fields().
	 * @return array<string, mixed>
	 */
	private static function get_extensions_field( string $resource_name, array $extension_fields ): array {
		$schema = array(
			'type'                 => 'object',
			'description'          => __( 'Values that extensions add, keyed by extension namespace, then field.', 'woocommerce' ),
			'additionalProperties' => false,
		);
		foreach ( $extension_fields as $extension_namespace => $fields ) {
			$schema['properties'][ $extension_namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map( static fn( array $field_args ) => $field_args['schema'], $fields ),
				'additionalProperties' => false,
			);
		}
		return array(
			'resource'      => $resource_name,
			'field'         => 'extensions',
			'schema'        => $schema,
			'get_callback'  => static fn( $subject ) => self::get_values( $resource_name, $extension_fields, $subject ),
			'omit_if_empty' => true,
		);
	}

	/**
	 * Extension field values of an object, keyed by namespace, then field. Values are cast to their schema.
	 * A field that throws or fails its schema is left out.
	 *
	 * @param string $resource_name    Resource.
	 * @param array  $extension_fields Fields from get_extension_fields().
	 * @param mixed  $subject          Object to read.
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_values( string $resource_name, array $extension_fields, $subject ): array {
		$values = array();
		foreach ( $extension_fields as $extension_namespace => $fields ) {
			foreach ( $fields as $field => $args ) {
				try {
					$value = call_user_func( $args['get_callback'], $subject );
				} catch ( \Throwable $e ) {
					wc_get_logger()->error(
						sprintf( 'Ability field "%s.%s" of "%s" failed: %s', $extension_namespace, $field, $resource_name, $e->getMessage() ),
						array( 'source' => 'extensible-abilities' )
					);
					continue;
				}
				if ( ! self::matches_schema( $args, $value ) ) {
					continue;
				}
				$sanitized = rest_sanitize_value_from_schema( $value, $args['schema'], $field );
				if ( ! is_wp_error( $sanitized ) ) {
					$values[ $extension_namespace ][ $field ] = $sanitized;
				}
			}
		}
		return $values;
	}

	/**
	 * Whether a value matches its field schema. A mismatch other than null is logged and reported once per field.
	 *
	 * @param array<string, mixed> $args  Field arguments.
	 * @param mixed                $value Value that the get_callback returned.
	 * @return bool
	 */
	private static function matches_schema( array $args, $value ): bool {
		$valid = rest_validate_value_from_schema( $value, $args['schema'], $args['field'] );
		if ( true === $valid ) {
			return true;
		}

		$key = $args['resource'] . '.' . $args['namespace'] . '.' . $args['field'];
		if ( null === $value || isset( self::$reported[ $key ] ) ) {
			return false;
		}

		self::$reported[ $key ] = true;
		$message                = sprintf(
			'Ability field "%1$s.%2$s" of "%3$s" was left out because its value does not match its schema: %4$s',
			$args['namespace'],
			$args['field'],
			$args['resource'],
			$valid->get_error_message()
		);
		wc_get_logger()->error( $message, array( 'source' => 'extensible-abilities' ) );
		wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', $message, '11.4.0' );
		return false;
	}
}
