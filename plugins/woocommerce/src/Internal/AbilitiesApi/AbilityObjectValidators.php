<?php
/**
 * Ability object validators class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Validators keyed by object type that check a fully applied object before an
 * in-memory write saves it.
 *
 * @since 11.3.0
 */
final class AbilityObjectValidators {

	/**
	 * Validators keyed by object type.
	 *
	 * @var array<string, callable[]>
	 */
	private static $validators = array();

	/**
	 * Register a validator. It receives the applied object, returns true or a
	 * WP_Error, and never changes it.
	 *
	 * @param string   $object_type Object type.
	 * @param callable $validator   Validator.
	 */
	public static function register( string $object_type, callable $validator ): void {
		self::$validators[ $object_type ][] = $validator;
	}

	/**
	 * Run every validator for the object type.
	 *
	 * @param object $subject     Applied, unsaved object.
	 * @param string $object_type Object type.
	 * @return \WP_Error|null The first rejection, or null when every validator accepts.
	 */
	public static function validate( $subject, string $object_type ): ?\WP_Error {
		/**
		 * Filters the validators that run on an object type before an in-memory write saves it.
		 *
		 * @since 11.3.0
		 *
		 * @param callable[] $validators  Validators.
		 * @param string     $object_type Object type.
		 */
		$validators = (array) apply_filters( 'woocommerce_ability_object_validators', self::$validators[ $object_type ] ?? array(), $object_type );

		foreach ( $validators as $validator ) {
			$result = call_user_func( $validator, $subject );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		return null;
	}
}
