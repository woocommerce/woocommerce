<?php
/**
 * StagedMethod class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Utilities;

defined( 'ABSPATH' ) || exit;

/**
 * A WC_Data method that sets and saves in one call, paired with the method that only stages the change in memory.
 * The first method runs the stage method through WC_Data::write_through(), then saves. For example: update_status()
 * and stage_status().
 *
 * A class that overrides the first method with rules of its own must override the stage method too, at the same
 * level. Otherwise a direct call of the stage method skips those rules, so the stage method refuses the class.
 * The class keeps working through the first method. A stage method starts with
 * WC_Data::refuse_unstaged_override(), and PHPStan reports one that does not. An override of a stage method must
 * call the parent's stage method, because that call runs the refusal.
 *
 * @internal
 * @since 11.4.0
 */
final class StagedMethod {

	/**
	 * Name of the first class, from the object's class upward, that declares the method that sets and saves without
	 * declaring the stage method. Null when the object's class can stage the change.
	 *
	 * @param object $subject      Object to change, for example an order.
	 * @param string $method       Method that sets and saves, for example update_status.
	 * @param string $stage_method Method that only stages the change, for example stage_status.
	 * @return string|null
	 */
	public static function unstaged_class( object $subject, string $method, string $stage_method ): ?string {
		$class_name = get_class( $subject );
		while ( $class_name ) {
			if ( self::declares( $class_name, $method ) && ! self::declares( $class_name, $stage_method ) ) {
				return $class_name;
			}
			$class_name = get_parent_class( $class_name );
		}

		return null;
	}

	/**
	 * Whether the class itself declares the method, rather than inherits it.
	 *
	 * @param string $class_name Class name.
	 * @param string $method     Method name.
	 * @return bool
	 */
	private static function declares( string $class_name, string $method ): bool {
		return method_exists( $class_name, $method ) && ( new \ReflectionMethod( $class_name, $method ) )->class === $class_name;
	}
}
