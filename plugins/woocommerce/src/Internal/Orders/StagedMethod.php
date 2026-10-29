<?php
/**
 * StagedMethod class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * A Core order method that sets and saves in one call, paired with the method that only stages the change in
 * memory. The first method calls the stage method, then save(). For example: update_status() and stage_status().
 *
 * A class that overrides the first method with rules of its own must override the stage method too, at the same
 * level or below. Otherwise an ability that stages the change skips those rules, so the ability refuses the class.
 *
 * @internal
 * @since 11.4.0
 */
final class StagedMethod {

	/**
	 * Whether the object's class can stage the change: no class below the one that declares the stage method
	 * overrides the method that sets and saves.
	 *
	 * @param object $subject      Object to change, for example an order.
	 * @param string $method       Method that sets and saves, for example update_status.
	 * @param string $stage_method Method that only stages the change, for example stage_status.
	 * @return bool
	 */
	public static function supports( object $subject, string $method, string $stage_method ): bool {
		return ! is_subclass_of( self::declaring_class( $subject, $method ), self::declaring_class( $subject, $stage_method ) );
	}

	/**
	 * Name of the class that declares the method on the object's class.
	 *
	 * @param object $subject Object.
	 * @param string $method  Method name.
	 * @return string
	 */
	private static function declaring_class( object $subject, string $method ): string {
		return ( new \ReflectionMethod( $subject, $method ) )->getDeclaringClass()->getName();
	}
}
