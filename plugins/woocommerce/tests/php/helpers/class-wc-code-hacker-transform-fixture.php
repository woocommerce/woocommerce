<?php
/**
 * Native static dispatch subject for code transformation tests.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/** Keep native dispatch independent of WooCommerce application state. */
final class WC_Code_Hacker_Transform_Fixture {
	/** Return the native method result. */
	public static function value(): string {
		return 'native';
	}
}
