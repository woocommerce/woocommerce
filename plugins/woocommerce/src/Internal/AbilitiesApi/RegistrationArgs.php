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
	 * Derive the write meta, swap in the polyfilled ability class before
	 * WordPress 7.1 for an ability with this meta, and add `extensions` to the
	 * schemas.
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
		}
		if ( is_string( $class ) && PolyfilledAbility::has_dry_run( $class ) ) {
			$args['meta'][ self::META ]['dry_run'] = true;
		}
		if ( null === $class && isset( $args['meta'][ self::META ] ) && PolyfilledAbility::is_active() ) {
			$args['ability_class'] = PolyfilledAbility::class;
		}

		$fields = $args['meta'][ self::META ]['extension_fields'] ?? null;
		if ( ! is_array( $fields ) ) {
			return $args;
		}

		$extensions = AbilityFields::schema( AbilityFields::get( (string) ( $fields['object_type'] ?? '' ) ) );

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
