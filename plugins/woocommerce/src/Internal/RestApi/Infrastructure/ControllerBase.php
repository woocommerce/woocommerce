<?php
/**
 * ControllerBase class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure;

/**
 * Base class for all the controller classes.
 */
class ControllerBase {

	/**
	 * Check if a given user has a given capability, trhow an unauthorized response exception if not.
	 *
	 * @param \WP_User|null $user The user to check.
	 * @param string        $capability The name of the capability to check.
	 * @param mixed         ...$args Additional arguments for the capability check.
	 * @return void
	 * @throws ResponseException The exception thrown.
	 */
	public static function ensure_user_can( ?\WP_User $user, string $capability, ...$args ): void {
		if ( ! $user || ! $user->has_cap( $capability, ...$args ) ) {
			throw ResponseException::for_unauthoritzed();
		}
	}
}
