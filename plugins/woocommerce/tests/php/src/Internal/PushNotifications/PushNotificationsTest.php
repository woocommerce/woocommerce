<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * PushNotifications test.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\PushNotifications
 */
class PushNotificationsTest extends WC_Unit_Test_Case {
	/**
	 * @var JetpackConnectionManager|MockObject
	 */
	private $jetpack_connection_manager_mock;

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Reset the REST server to clear registered routes.
		global $wp_rest_server;
		$wp_rest_server = null;

		// Clear LegacyProxy mocks to avoid affecting other tests.
		wc_get_container()->get( LegacyProxy::class )->register_class_mocks( array() );
	}

	/**
	 * Tests the functionality is enabled if Jetpack is connected.
	 */
	public function test_it_can_tell_push_notifications_should_be_enabled_if_jetpack_is_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications = new PushNotifications();

		$this->assertTrue( $push_notifications->should_be_enabled() );
	}

	/**
	 * Tests the functionality is disabled if Jetpack is not connected.
	 */
	public function test_it_can_tell_push_notifications_should_not_be_enabled_if_jetpack_is_not_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled() );
	}

	/**
	 * Tests the endpoints haven't been registered if Jetpack is not connected.
	 */
	public function test_it_does_not_register_endpoints_if_disabled() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();
		$push_notifications->register();

		$routes = array_keys( rest_get_server()->get_routes() );

		$this->assertNotContains( '/wc-push-notifications/push-tokens', $routes );
		$this->assertNotContains( '/wc-push-notifications/push-tokens/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Tests the endpoints have been registered if Jetpack is connected.
	 */
	public function test_it_registers_endpoints_if_enabled() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications = new PushNotifications();
		$push_notifications->register();

		$routes = array_keys( rest_get_server()->get_routes() );

		$this->assertContains( '/wc-push-notifications/push-tokens', $routes );
		$this->assertContains( '/wc-push-notifications/push-tokens/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Sets up the Jetpack connection manager mocking.
	 *
	 * @param array $methods The methods that will be mocked.
	 */
	private function set_up_jetpack_connection_manager_mock( array $methods ) {
		$this->jetpack_connection_manager_mock = $this
			->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $this->jetpack_connection_manager_mock )
		);
	}
}
