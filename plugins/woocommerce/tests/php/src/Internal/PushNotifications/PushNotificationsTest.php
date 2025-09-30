<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\WCCom\ConnectionHelper as WCComConnectionHelper;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Unit_Test_Case;

/**
 * PushNotifications test.
 *
 * @covers PushNotifications
 */
class PushNotificationsTest extends WC_Unit_Test_Case {
	/**
	 * @var JetpackConnectionManager|MockObject
	 */
	private $jetpack_connection_manager_mock;

	/**
	 * Tests the functionality is enabled if Jetpack and WooCommerce.com are
	 * connected.
	 */
	public function test_it_enables_push_notifications_if_jetpack_and_wccom_are_connected() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		update_option( 'woocommerce_helper_data', array( 'auth' => 'random token' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications = new PushNotifications();

		$this->assertTrue( $push_notifications->should_be_enabled() );
	}

	/**
	 * Tests the functionality is disabled if Jetpack is not connected but
	 * WooCommerce.com is.
	 */
	public function test_it_does_not_enable_push_notifications_if_jetpack_is_not_connected_but_wccom_is() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		update_option( 'woocommerce_helper_data', array( 'auth' => 'random token' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled() );
	}

	/**
	 * Tests the functionality is disabled if Jetpack is not connected but
	 * WooCommerce.com is.
	 */
	public function test_it_does_not_enable_push_notifications_if_wccom_is_not_connected_but_jetpack_is() {
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled() );
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
