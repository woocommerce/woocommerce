<?php
/**
 * Ability fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Fields that extensions add to the abilities of an object type, as
 * register_rest_field() does for the REST API.
 *
 * An ability opts in with `meta.woocommerce.extension_fields`: the
 * `object_type` of the fields and the `output` key that holds the object, or
 * the list of objects. Its output then carries the field values under
 * `extensions`, keyed by attribute.
 *
 * @since 11.3.0
 */
class AbilityFields {

	public const META = 'woocommerce';

	/**
	 * Fields keyed by object type, then attribute.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $fields = array();

	/**
	 * Hook the registration and execution filters.
	 *
	 * @internal
	 */
	final public static function init(): void {
		add_filter( 'wp_register_ability_args', array( __CLASS__, 'registration_args' ) );
		add_filter( 'wp_ability_execute_result', array( __CLASS__, 'execute_result' ), 10, 4 );
	}

	/**
	 * Register a field. A later registration of the same attribute replaces it.
	 *
	 * @param string               $object_type Object type.
	 * @param string               $attribute   Attribute under `extensions`.
	 * @param array<string, mixed> $args        `schema` and `get_callback( $object )`.
	 */
	public static function register( string $object_type, string $attribute, array $args ): void {
		self::$fields[ $object_type ][ $attribute ] = $args;
	}

	/**
	 * Fields of an object type, keyed by attribute.
	 *
	 * @param string $object_type Object type.
	 * @return array<string, array<string, mixed>>
	 */
	public static function get( string $object_type ): array {
		return self::$fields[ $object_type ] ?? array();
	}

	/**
	 * Add the `extensions` output schema to an ability that opts in. When the
	 * ability has no class of its own, run it as an ExtensibleAbility, so it
	 * gets the values before WordPress 7.1 too. With the feature off, drop the
	 * opt-in, so the ability registers as if it never declared it.
	 *
	 * @internal
	 *
	 * @param mixed $args Registration arguments.
	 * @return mixed
	 */
	public static function registration_args( $args ) {
		if ( ! is_array( $args ) ) {
			return $args;
		}

		$declaration = $args['meta'][ self::META ]['extension_fields'] ?? null;
		if ( ! is_array( $declaration ) ) {
			return $args;
		}
		if ( ! AbilityContracts::is_enabled() ) {
			unset( $args['meta'][ self::META ]['extension_fields'] );
			if ( empty( $args['meta'][ self::META ] ) ) {
				unset( $args['meta'][ self::META ] );
			}
			return $args;
		}

		if ( ! isset( $args['ability_class'] ) ) {
			$args['ability_class'] = ExtensibleAbility::class;
		}

		$key = $declaration['output'] ?? null;
		if ( is_string( $key ) && isset( $args['output_schema']['properties'][ $key ] ) ) {
			$extensions = self::schema( self::get( (string) ( $declaration['object_type'] ?? '' ) ) );
			if ( 'array' === ( $args['output_schema']['properties'][ $key ]['type'] ?? null ) ) {
				$args['output_schema']['properties'][ $key ]['items']['properties']['extensions'] = $extensions;
			} else {
				$args['output_schema']['properties'][ $key ]['properties']['extensions'] = $extensions;
			}
		}

		return $args;
	}

	/**
	 * Add the field values to the output of an ability that opts in. WordPress
	 * fires this filter from 7.1. Before 7.1, ExtensibleAbility does the same.
	 *
	 * @internal
	 *
	 * @param mixed  $result       Execute result.
	 * @param string $ability_name Ability name.
	 * @param mixed  $input        Input.
	 * @param mixed  $ability      Ability.
	 * @return mixed
	 */
	public static function execute_result( $result, $ability_name, $input, $ability ) {
		return $ability instanceof \WP_Ability && AbilityContracts::is_enabled() ? self::fill_result( $result, $ability ) : $result;
	}

	/**
	 * Add `extensions` to the object, or to each object of the list, at the
	 * output key the ability declares. Each object is loaded by the `id` its
	 * output carries.
	 *
	 * @param mixed       $result  Execute result.
	 * @param \WP_Ability $ability Ability.
	 * @return mixed
	 */
	public static function fill_result( $result, \WP_Ability $ability ) {
		$declaration = $ability->get_meta()[ self::META ]['extension_fields'] ?? null;
		$key         = is_array( $declaration ) ? ( $declaration['output'] ?? null ) : null;
		if ( ! is_string( $key ) || ! is_array( $result ) || ! is_array( $result[ $key ] ?? null ) ) {
			return $result;
		}

		$object_type = (string) ( $declaration['object_type'] ?? '' );
		$fields      = self::get( $object_type );
		if ( empty( $fields ) ) {
			return $result;
		}

		$fill = static function ( $item ) use ( $fields, $object_type ) {
			if ( ! is_array( $item ) || ! isset( $item['id'] ) ) {
				return $item;
			}

			/**
			 * Filters the object an ability output names by ID, so its extension fields can be read.
			 *
			 * @since 11.3.0
			 *
			 * @param object|null $subject     Object, or null when none is found.
			 * @param string      $object_type Object type.
			 * @param mixed       $id          Object ID.
			 */
			$subject = apply_filters( 'woocommerce_ability_object', null, $object_type, $item['id'] );
			$values  = is_object( $subject ) ? self::read( $fields, $subject ) : array();
			if ( ! empty( $values ) ) {
				$item['extensions'] = $values;
			}
			return $item;
		};

		$result[ $key ] = wp_is_numeric_array( $result[ $key ] ) ? array_map( $fill, $result[ $key ] ) : $fill( $result[ $key ] );
		return $result;
	}

	/**
	 * JSON schema for `extensions`. It lists the fields registered so far and
	 * allows others, because a field can be registered after the ability.
	 *
	 * @param array<string, array<string, mixed>> $fields Fields keyed by attribute.
	 * @return array<string, mixed>
	 */
	public static function schema( array $fields ): array {
		$schema = array(
			'type'        => 'object',
			'description' => __( 'Values that extensions add, keyed by attribute.', 'woocommerce' ),
		);
		if ( ! empty( $fields ) ) {
			$schema['properties'] = array_map(
				static function ( array $field ): array {
					return $field['schema'];
				},
				$fields
			);
		}
		return $schema;
	}

	/**
	 * Values the fields read from the object, keyed by attribute. A field that
	 * reads null is left out.
	 *
	 * @param array<string, array<string, mixed>> $fields  Fields keyed by attribute.
	 * @param object                              $subject Object to read.
	 * @return array<string, mixed>
	 */
	public static function read( array $fields, $subject ): array {
		$values = array();
		foreach ( $fields as $attribute => $field ) {
			$value = isset( $field['get_callback'] ) ? call_user_func( $field['get_callback'], $subject ) : null;
			if ( null !== $value ) {
				$values[ $attribute ] = $value;
			}
		}
		return $values;
	}
}
