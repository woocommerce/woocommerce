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
 * - `in_memory_write`: `object_type`, derived from an InMemoryWriteAbility `ability_class`.
 *
 * Meta is listed in full by the REST API, so it holds plain data only.
 *
 * @since 11.3.0
 */
final class RegistrationArgs {

	public const META = 'woocommerce';

	/**
	 * Derive the write meta, add `extensions` to the schemas, and swap in the
	 * polyfilled ability class before WordPress 7.1.
	 *
	 * @param array $args Registration arguments.
	 * @return array
	 */
	public static function apply( array $args ): array {
		$class = $args['ability_class'] ?? null;
		if ( null === $class && PolyfilledAbility::is_active() ) {
			$args['ability_class'] = PolyfilledAbility::class;
		}
		if ( is_string( $class ) && is_a( $class, InMemoryWriteAbility::class, true ) ) {
			$args['meta'][ self::META ]['in_memory_write'] = array( 'object_type' => $class::object_type() );
		}

		$fields = $args['meta'][ self::META ]['extension_fields'] ?? null;
		if ( ! is_array( $fields ) ) {
			return $args;
		}

		$extensions = AbilityFields::schema( AbilityFields::get( (string) ( $fields['object_type'] ?? '' ) ) );
		if ( null === $extensions ) {
			return $args;
		}

		if ( isset( $args['meta'][ self::META ]['in_memory_write'], $args['input_schema']['properties'] ) && ! isset( $args['input_schema']['properties']['extensions'] ) ) {
			$args['input_schema']['properties']['extensions'] = $extensions;
		}
		if ( isset( $fields['output'], $args['output_schema'] ) ) {
			$args['output_schema'] = AbilityFields::add_to_output_schema( $args['output_schema'], (string) $fields['output'], $extensions );
		}
		return $args;
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
