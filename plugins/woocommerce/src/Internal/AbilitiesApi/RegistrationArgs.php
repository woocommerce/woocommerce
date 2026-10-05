<?php
/**
 * Ability registration arguments class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Builds an ability's schemas and output from the plain data it declares in
 * `meta.woocommerce`:
 *
 * - `extension_fields`: `object_type` and the `output` key where the object, or list of objects, sits.
 *   An `output` of '' means the whole output is the object, or the list of objects.
 * - `in_memory_write`: `object_type`.
 * - `dry_run`: true when the ability class implements a dry run.
 *
 * Both are derived from an ObjectChangeAbility `ability_class`, with the object type as the output key, unless the ability declares them.
 *
 * Meta is listed in full by the REST API, so it holds plain data only.
 *
 * @since 11.3.0
 */
final class RegistrationArgs {

	public const META = 'woocommerce';

	/**
	 * Derive the write meta and add `extensions` to the schemas. A plain
	 * WP_Ability gets extension values only through wp_ability_execute_result,
	 * which WordPress fires from 7.1, so before 7.1 its output schema gets no
	 * `extensions` either. A DryRunAbility fills them itself on every version.
	 *
	 * @param array $args Registration arguments.
	 * @return array
	 */
	public static function apply( array $args ): array {
		$class = $args['ability_class'] ?? null;
		if ( is_string( $class ) && is_a( $class, ObjectChangeAbility::class, true ) ) {
			$args['meta'][ self::META ]['extension_fields'] = $args['meta'][ self::META ]['extension_fields'] ?? array(
				'object_type' => $class::object_type(),
				'output'      => $class::object_type(),
			);
			$args['meta'][ self::META ]['in_memory_write']  = $args['meta'][ self::META ]['in_memory_write'] ?? array( 'object_type' => $class::object_type() );
			$args['input_schema']                           = self::with_expected( $args['input_schema'] ?? array() );
		}
		if ( is_string( $class ) && DryRunAbility::has_dry_run( $class ) ) {
			$args['meta'][ self::META ]['dry_run'] = true;
		}

		$fields = $args['meta'][ self::META ]['extension_fields'] ?? null;
		if ( ! is_array( $fields ) ) {
			return $args;
		}

		$extensions = AbilityFields::schema( AbilityFields::get( (string) ( $fields['object_type'] ?? '' ) ) );

		if ( isset( $args['meta'][ self::META ]['in_memory_write'], $args['input_schema']['properties'] ) && ! isset( $args['input_schema']['properties']['extensions'] ) ) {
			$args['input_schema']['properties']['extensions'] = $extensions;
		}
		$fills = ( is_string( $class ) && is_a( $class, DryRunAbility::class, true ) ) || self::execute_result_hook_available();
		if ( $fills && isset( $fields['output'], $args['output_schema'] ) ) {
			$args['output_schema'] = AbilityFields::add_to_output_schema( $args['output_schema'], (string) $fields['output'], $extensions );
		}
		return $args;
	}

	/**
	 * Whether WordPress fires wp_ability_execute_result, added in 7.1.
	 */
	public static function execute_result_hook_available(): bool {
		return version_compare( get_bloginfo( 'version' ), '7.1-alpha', '>=' );
	}

	/**
	 * Accept the optional `expected` map in an input schema, and in each of its `oneOf` branches.
	 *
	 * @param array $schema Input schema.
	 * @return array
	 */
	private static function with_expected( array $schema ): array {
		$expected = array(
			'type'        => 'object',
			'description' => 'Optional. Values the object must still have, keyed by the field paths a dry run reports. When one differs, nothing is saved.',
		);
		if ( isset( $schema['properties'] ) ) {
			$schema['properties']['expected'] = $expected;
		}
		foreach ( $schema['oneOf'] ?? array() as $index => $branch ) {
			if ( isset( $branch['properties'] ) ) {
				$schema['oneOf'][ $index ]['properties']['expected'] = $expected;
			}
		}
		return $schema;
	}

	/**
	 * Add the extension field values to the output of an ability that declares `extension_fields`.
	 *
	 * @param mixed       $result  Execute result.
	 * @param \WP_Ability $ability Ability.
	 * @return mixed
	 */
	public static function fill_result( $result, \WP_Ability $ability ) {
		$fields = $ability->get_meta()[ self::META ]['extension_fields'] ?? null;
		return is_array( $fields ) ? AbilityFields::fill_output( $result, $fields ) : $result;
	}
}
