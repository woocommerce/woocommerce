<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\Services\DriverAvailabilityService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Logger;
use WC_Unit_Test_Case;

/**
 * Tests for the DriverAvailabilityService class.
 *
 * @package WooCommerce\Tests\PushNotifications
 */
class DriverAvailabilityServiceTest extends WC_Unit_Test_Case {

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * Builds a DriverAvailabilityService with its dependency-check seams stubbed to
	 * the supplied values, so get_status() logic can be exercised in isolation.
	 *
	 * @param array<string, bool> $state The driver state to simulate. Keys: sync_installed, sync_enabled, blog_connected, user_connected, proxy_enabled.
	 * @return DriverAvailabilityService
	 */
	private function make_service( array $state ): DriverAvailabilityService {
		$defaults = array(
			'sync_installed' => false,
			'sync_enabled'   => true,
			'blog_connected' => true,
			'user_connected' => true,
			'proxy_enabled'  => true,
		);
		$state    = array_merge( $defaults, $state );

		/**
		 * The service under test with its dependency-check seams stubbed.
		 *
		 * @var DriverAvailabilityService&MockObject $service
		 */
		$service = $this->getMockBuilder( DriverAvailabilityService::class )
			->onlyMethods(
				array(
					'is_remote_proxy_enabled',
					'is_jetpack_sync_installed',
					'is_jetpack_sync_enabled',
					'has_blog_connection',
					'has_user_connection',
				)
			)
			->getMock();

		$service->method( 'is_remote_proxy_enabled' )->willReturn( $state['proxy_enabled'] );
		$service->method( 'is_jetpack_sync_installed' )->willReturn( $state['sync_installed'] );
		$service->method( 'is_jetpack_sync_enabled' )->willReturn( $state['sync_enabled'] );
		$service->method( 'has_blog_connection' )->willReturn( $state['blog_connected'] );
		$service->method( 'has_user_connection' )->willReturn( $state['user_connected'] );

		return $service;
	}

	/**
	 * Mocks the Jetpack connection manager so the given method returns the supplied value.
	 *
	 * @param string $method The manager method to mock.
	 * @param bool   $value  The value the method should return.
	 */
	private function mock_manager_method( string $method, bool $value ): void {
		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( $method ) )
			->getMock();
		$manager->method( $method )->willReturn( $value );

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $manager )
		);
	}

	/**
	 * @testdox The Jetpack Sync driver is omitted when the package is not installed.
	 */
	public function test_jetpack_sync_driver_omitted_when_not_installed() {
		$status = $this->make_service( array( 'sync_installed' => false ) )->get_status();

		$this->assertArrayNotHasKey( DriverAvailabilityService::DRIVER_JETPACK_SYNC, $status['installed-drivers'] );
		$this->assertArrayHasKey( DriverAvailabilityService::DRIVER_REMOTE_PROXY, $status['installed-drivers'] );
	}

	/**
	 * @testdox The Jetpack Sync driver is connected and available when installed, user-connected, and not disabled.
	 */
	public function test_jetpack_sync_driver_connected_and_available() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => true,
				'user_connected' => true,
			)
		)->get_status();

		$driver = $status['installed-drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];
		$this->assertTrue( $driver['connected'] );
		$this->assertTrue( $driver['enabled'] );
		$this->assertTrue( $driver['available'] );
	}

	/**
	 * @testdox The Jetpack Sync driver is disabled and unavailable when sync is disabled, even if connected.
	 */
	public function test_jetpack_sync_driver_unavailable_when_disabled() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => false,
				'user_connected' => true,
			)
		)->get_status();

		$driver = $status['installed-drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];
		$this->assertTrue( $driver['connected'] );
		$this->assertFalse( $driver['enabled'] );
		$this->assertFalse( $driver['available'] );
	}

	/**
	 * @testdox The Jetpack Sync driver is enabled but not connected or available without a user connection.
	 */
	public function test_jetpack_sync_driver_unavailable_without_user_connection() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => true,
				'user_connected' => false,
			)
		)->get_status();

		$driver = $status['installed-drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];
		$this->assertFalse( $driver['connected'] );
		$this->assertTrue( $driver['enabled'] );
		$this->assertFalse( $driver['available'] );
	}

	/**
	 * @testdox The remote proxy driver reflects the blog connection and feature-enabled flags.
	 *
	 * @testWith [true, true, true, true]
	 *           [true, false, true, false]
	 *           [false, true, false, false]
	 *
	 * @param bool $blog_connected  Whether the Jetpack blog connection is present.
	 * @param bool $proxy_enabled Whether the remote proxy is enabled.
	 * @param bool $expected_connected Expected connected flag.
	 * @param bool $expected_available Expected available flag.
	 */
	public function test_remote_proxy_driver_reflects_connection_and_feature( bool $blog_connected, bool $proxy_enabled, bool $expected_connected, bool $expected_available ) {
		$status = $this->make_service(
			array(
				'blog_connected' => $blog_connected,
				'proxy_enabled'  => $proxy_enabled,
			)
		)->get_status();

		$driver = $status['installed-drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertSame( $expected_connected, $driver['connected'] );
		$this->assertSame( $proxy_enabled, $driver['enabled'] );
		$this->assertSame( $expected_available, $driver['available'] );
	}

	/**
	 * @testdox The remote proxy is the active driver when available, taking precedence over Jetpack Sync.
	 */
	public function test_active_driver_prefers_remote_proxy() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => true,
				'user_connected' => true,
				'blog_connected' => true,
				'proxy_enabled'  => true,
			)
		)->get_status();

		$this->assertSame( DriverAvailabilityService::DRIVER_REMOTE_PROXY, $status['active-driver'] );
	}

	/**
	 * @testdox Jetpack Sync is the active driver when the remote proxy is unavailable.
	 */
	public function test_active_driver_falls_back_to_jetpack_sync() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => true,
				'user_connected' => true,
				'blog_connected' => true,
				'proxy_enabled'  => false,
			)
		)->get_status();

		$this->assertSame( DriverAvailabilityService::DRIVER_JETPACK_SYNC, $status['active-driver'] );
	}

	/**
	 * @testdox The active driver is null when no driver is available.
	 */
	public function test_active_driver_null_when_none_available() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => false,
				'user_connected' => true,
				'blog_connected' => false,
				'proxy_enabled'  => true,
			)
		)->get_status();

		$this->assertNull( $status['active-driver'] );
	}

	/**
	 * @testdox is_remote_proxy_available() reflects the real Jetpack blog connection.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $is_connected Whether Jetpack reports a blog connection.
	 */
	public function test_is_remote_proxy_available_reflects_real_blog_connection( bool $is_connected ) {
		$this->mock_manager_method( 'is_connected', $is_connected );

		$this->assertSame( $is_connected, ( new DriverAvailabilityService() )->is_remote_proxy_available() );
	}

	/**
	 * @testdox The Jetpack Sync driver's connected flag reflects the real Jetpack user connection.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $has_owner Whether Jetpack reports a connected owner.
	 */
	public function test_jetpack_sync_connected_reflects_real_user_connection( bool $has_owner ) {
		$this->mock_manager_method( 'has_connected_owner', $has_owner );

		/**
		 * A service with only the Jetpack Sync package-detection seam stubbed, so
		 * the real user-connection check runs.
		 *
		 * @var DriverAvailabilityService&MockObject $service
		 */
		$service = $this->getMockBuilder( DriverAvailabilityService::class )
			->onlyMethods( array( 'is_jetpack_sync_installed' ) )
			->getMock();
		$service->method( 'is_jetpack_sync_installed' )->willReturn( true );

		$status = $service->get_status();

		$this->assertSame(
			$has_owner,
			$status['installed-drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ]['connected']
		);
	}

	/**
	 * @testdox A Jetpack connection failure is treated as disconnected and logged.
	 */
	public function test_connection_failure_returns_disconnected_and_logs() {
		$logger_mock = $this->createMock( WC_Logger::class );
		$logger_mock->expects( $this->once() )
			->method( 'error' )
			->with( $this->stringContains( 'Error determining Jetpack connection state for push notifications' ) );

		$this->register_legacy_proxy_function_mocks( array( 'wc_get_logger' => fn () => $logger_mock ) );

		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected' ) )
			->getMock();
		$manager->method( 'is_connected' )->willThrowException( new Exception( 'Connection check failed' ) );

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $manager )
		);

		$this->assertFalse( ( new DriverAvailabilityService() )->is_remote_proxy_available() );
	}
}
