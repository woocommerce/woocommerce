<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Helpers;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;

/**
 * Shared test helpers for the PushNotifications module.
 *
 * Mocks the Jetpack connection state and resets the memoized enablement flag on
 * the container's `PushNotifications` instance — the two things every
 * push-notifications-related controller test needs in setUp.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
trait PushNotificationsTestTrait {
	/**
	 * @var JetpackConnectionManager|MockObject|null
	 */
	protected $jetpack_connection_manager_mock;

	/**
	 * Mocks the JetpackConnectionManager so its `is_connected()` returns the
	 * supplied value, and resets the PushNotifications enablement cache so
	 * `should_be_enabled()` re-evaluates against the new mock.
	 *
	 * @param bool $is_connected Whether the manager should report Jetpack is connected.
	 */
	protected function mock_jetpack_connection_manager_is_connected( bool $is_connected = true ) {
		$this->jetpack_connection_manager_mock = $this
			->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected' ) )
			->getMock();

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $this->jetpack_connection_manager_mock )
		);

		$this->jetpack_connection_manager_mock
			->expects( $this->any() )
			->method( 'is_connected' )
			->willReturn( $is_connected );

		$this->reset_push_notifications_cache();
	}

	/**
	 * Resets the cached enablement state on the container's PushNotifications
	 * instance so subsequent `should_be_enabled()` calls re-evaluate.
	 */
	protected function reset_push_notifications_cache() {
		$push_notifications = wc_get_container()->get( PushNotifications::class );
		$reflection         = new ReflectionClass( $push_notifications );
		$property           = $reflection->getProperty( 'enabled' );

		$property->setAccessible( true );
		$property->setValue( $push_notifications, null );
	}
}
