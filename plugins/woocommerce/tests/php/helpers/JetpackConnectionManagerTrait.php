<?php
/**
 * JetpackConnectionManagerTrait file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Helpers;

use Automattic\Jetpack\Connection\Manager;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;

/**
 * Replaces the connection manager JetpackConnection caches with a mock that records registration attempts.
 *
 * JetpackConnection keeps the manager in a static property that no base test class resets, so a test
 * class using this trait calls reset_jetpack_connection_manager() from its tearDown().
 */
trait JetpackConnectionManagerTrait {

	/**
	 * Make JetpackConnection use a manager mock that records try_registration() calls.
	 *
	 * @param bool           $is_connected        Whether the site is already registered.
	 * @param true|\WP_Error $registration_result What try_registration() returns.
	 * @return \ArrayObject<int, array<int, mixed>> The arguments of each try_registration() call.
	 */
	private function use_recording_jetpack_connection_manager( bool $is_connected = false, $registration_result = true ): \ArrayObject {
		$calls   = new \ArrayObject();
		$manager = $this->createMock( Manager::class );
		$manager->method( 'is_connected' )->willReturn( $is_connected );
		$manager->method( 'get_authorization_url' )->willReturn( 'https://jetpack.wordpress.com/jetpack.authorize/1/?client_id=1' );
		$manager->method( 'try_registration' )->willReturnCallback(
			static function ( ...$args ) use ( $calls, $registration_result ) {
				$calls[] = $args;
				return $registration_result;
			}
		);
		$this->set_jetpack_connection_manager( $manager );

		return $calls;
	}

	/**
	 * Let JetpackConnection create its own manager again.
	 */
	private function reset_jetpack_connection_manager(): void {
		$this->set_jetpack_connection_manager( null );
	}

	/**
	 * Replace the manager JetpackConnection caches.
	 *
	 * @param Manager|null $manager Manager to use, or null to reset.
	 */
	private function set_jetpack_connection_manager( ?Manager $manager ): void {
		$property = new \ReflectionProperty( JetpackConnection::class, 'manager' );
		$property->setAccessible( true );
		$property->setValue( null, $manager );
	}
}
