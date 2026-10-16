<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\DriverAvailabilityService;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Error;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
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
	 * Mocks the Jetpack connection manager so both the blog and user connection
	 * checks return the supplied value. Both are stubbed because get_status()
	 * evaluates every driver, so leaving one unstubbed would run real Jetpack code.
	 *
	 * @param bool $connected The connection state the manager should report.
	 */
	private function mock_jetpack_connection( bool $connected ): void {
		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected', 'has_connected_owner' ) )
			->getMock();
		$manager->method( 'is_connected' )->willReturn( $connected );
		$manager->method( 'has_connected_owner' )->willReturn( $connected );

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $manager )
		);
	}

	/**
	 * @testdox The Jetpack Sync driver is omitted when the package is not installed.
	 */
	public function test_jetpack_sync_driver_omitted_when_not_installed() {
		$status = $this->make_service( array( 'sync_installed' => false ) )->get_status();

		$this->assertArrayNotHasKey( DriverAvailabilityService::DRIVER_JETPACK_SYNC, $status['installed_drivers'] );
		$this->assertArrayHasKey( DriverAvailabilityService::DRIVER_REMOTE_PROXY, $status['installed_drivers'] );
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

		$driver = $status['installed_drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];
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

		$driver = $status['installed_drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];
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

		$driver = $status['installed_drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];
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

		$driver = $status['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertSame( $expected_connected, $driver['connected'] );
		$this->assertSame( $proxy_enabled, $driver['enabled'] );
		$this->assertSame( $expected_available, $driver['available'] );
	}

	/**
	 * @testdox The remote proxy is the preferred driver when available, taking precedence over Jetpack Sync.
	 */
	public function test_preferred_driver_prefers_remote_proxy() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => true,
				'user_connected' => true,
				'blog_connected' => true,
				'proxy_enabled'  => true,
			)
		)->get_status();

		$this->assertSame( DriverAvailabilityService::DRIVER_REMOTE_PROXY, $status['preferred_driver'] );
	}

	/**
	 * @testdox Jetpack Sync is the preferred driver when the remote proxy is unavailable.
	 */
	public function test_preferred_driver_falls_back_to_jetpack_sync() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => true,
				'user_connected' => true,
				'blog_connected' => true,
				'proxy_enabled'  => false,
			)
		)->get_status();

		$this->assertSame( DriverAvailabilityService::DRIVER_JETPACK_SYNC, $status['preferred_driver'] );
	}

	/**
	 * @testdox The preferred driver is null when no driver is available.
	 */
	public function test_preferred_driver_null_when_none_available() {
		$status = $this->make_service(
			array(
				'sync_installed' => true,
				'sync_enabled'   => false,
				'user_connected' => true,
				'blog_connected' => false,
				'proxy_enabled'  => true,
			)
		)->get_status();

		$this->assertNull( $status['preferred_driver'] );
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
		$this->mock_jetpack_connection( $is_connected );

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
		$this->mock_jetpack_connection( $has_owner );

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
			$status['installed_drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ]['connected']
		);
	}

	/**
	 * @testdox A Jetpack connection failure is treated as disconnected and logged.
	 */
	public function test_connection_failure_returns_disconnected_and_logs() {
		$logger_mock = $this->createMock( WC_Logger::class );
		$logger_mock->expects( $this->once() )
			->method( 'error' )
			->with(
				$this->stringContains( '(exception)' ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

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

	/**
	 * An incompatible Jetpack raises an Error rather than an Exception, which is
	 * why the catch covers Throwable. Without this the widened catch is untested.
	 *
	 * @testdox An Error from the connection manager is caught, logged as an error, and treated as disconnected.
	 */
	public function test_connection_error_is_caught_and_logged_distinctly() {
		$logger_mock = $this->createMock( WC_Logger::class );
		$logger_mock->expects( $this->once() )
			->method( 'error' )
			->with(
				$this->stringContains( '(error)' ),
				array( 'source' => PushNotifications::FEATURE_NAME )
			);

		$this->register_legacy_proxy_function_mocks( array( 'wc_get_logger' => fn () => $logger_mock ) );

		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected' ) )
			->getMock();
		$manager->method( 'is_connected' )->willThrowException( new Error( 'Call to undefined method' ) );

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $manager )
		);

		$this->assertFalse( ( new DriverAvailabilityService() )->is_remote_proxy_available() );
	}

	/**
	 * The sync package alone is not enough: other plugins bundle it without the
	 * Jetpack plugin, and on those stores there is no Jetpack Sync flow for the
	 * apps to fall back to. Class presence is stubbed because neither class exists
	 * in the test environment, so asserting against real class_exists() calls would
	 * only compare the implementation with itself.
	 *
	 * @testdox The Jetpack Sync driver is installed only when both the Jetpack plugin and the sync package are present.
	 *
	 * @testWith [true, true, true]
	 *           [true, false, false]
	 *           [false, true, false]
	 *           [false, false, false]
	 *
	 * @param bool $plugin_present  Whether the Jetpack plugin class is loadable.
	 * @param bool $package_present Whether the Jetpack Sync settings class is loadable.
	 * @param bool $expected        Whether the driver should count as installed.
	 */
	public function test_jetpack_sync_installed_requires_both_plugin_and_package( bool $plugin_present, bool $package_present, bool $expected ) {
		/**
		 * The service with only its class-presence seam stubbed.
		 *
		 * @var DriverAvailabilityService&MockObject $service
		 */
		$service = $this->getMockBuilder( DriverAvailabilityService::class )
			->onlyMethods( array( 'class_is_present' ) )
			->getMock();

		$service->method( 'class_is_present' )->willReturnMap(
			array(
				array( DriverAvailabilityService::JETPACK_PLUGIN_CLASS, $plugin_present ),
				array( DriverAvailabilityService::JETPACK_SYNC_SETTINGS_CLASS, $package_present ),
			)
		);

		$reflection = new ReflectionMethod( DriverAvailabilityService::class, 'is_jetpack_sync_installed' );
		$reflection->setAccessible( true );

		$this->assertSame( $expected, $reflection->invoke( $service ) );
	}

	/**
	 * Reaching the is_callable guard means the class is present but the method is
	 * not, which is an incompatible Jetpack Sync rather than a merchant choice.
	 *
	 * @testdox An unusable Jetpack Sync settings class reports enabled as null, not false.
	 */
	public function test_unusable_sync_settings_class_reports_enabled_as_null() {
		$this->mock_jetpack_connection( true );

		/**
		 * Only the install seam is stubbed, so is_jetpack_sync_enabled() runs for real
		 * and hits the is_callable guard, the class being absent in tests.
		 *
		 * @var DriverAvailabilityService&MockObject $service
		 */
		$service = $this->getMockBuilder( DriverAvailabilityService::class )
			->onlyMethods( array( 'is_jetpack_sync_installed' ) )
			->getMock();
		$service->method( 'is_jetpack_sync_installed' )->willReturn( true );

		$driver = $service->get_status()['installed_drivers'][ DriverAvailabilityService::DRIVER_JETPACK_SYNC ];

		$this->assertNull( $driver['enabled'], 'An undeterminable sync state must be null rather than a definitive false.' );
		$this->assertFalse( $driver['available'] );
	}

	/**
	 * @testdox A cleanly unconnected store reports connected as false, not null.
	 */
	public function test_unconnected_store_reports_false_rather_than_null() {
		$this->mock_jetpack_connection( false );

		$driver = ( new DriverAvailabilityService() )->get_status()['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];

		$this->assertFalse( $driver['connected'], 'A check that ran and answered no must be false, not null.' );
		$this->assertFalse( $driver['available'] );
	}

	/**
	 * The two drivers depend on different connection checks, so a failure must be
	 * reported against the driver that asked, not across the whole response.
	 *
	 * @testdox A failing blog connection check nulls only the remote proxy driver, not Jetpack Sync.
	 */
	public function test_connection_check_failure_is_scoped_to_the_driver_that_asked() {
		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected', 'has_connected_owner' ) )
			->getMock();

		// The remote proxy's check throws; Jetpack Sync's answers cleanly.
		$manager->method( 'is_connected' )->willThrowException( new Error( 'Call to undefined method' ) );
		$manager->method( 'has_connected_owner' )->willReturn( true );

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $manager )
		);

		/**
		 * Only the sync-installed seam is stubbed, so both drivers appear and each
		 * runs its real connection check.
		 *
		 * @var DriverAvailabilityService&MockObject $service
		 */
		$service = $this->getMockBuilder( DriverAvailabilityService::class )
			->onlyMethods( array( 'is_jetpack_sync_installed', 'is_jetpack_sync_enabled' ) )
			->getMock();
		$service->method( 'is_jetpack_sync_installed' )->willReturn( true );
		$service->method( 'is_jetpack_sync_enabled' )->willReturn( true );

		$drivers = $service->get_status()['installed_drivers'];

		$this->assertNull(
			$drivers[ DriverAvailabilityService::DRIVER_REMOTE_PROXY ]['connected'],
			'The driver whose check threw should report connected as null.'
		);
		$this->assertFalse(
			$drivers[ DriverAvailabilityService::DRIVER_REMOTE_PROXY ]['available'],
			'An undetermined connection must not count as available.'
		);
		$this->assertTrue(
			$drivers[ DriverAvailabilityService::DRIVER_JETPACK_SYNC ]['connected'],
			'A driver whose check answered cleanly must not inherit the other driver’s failure.'
		);
	}

	/**
	 * @testdox An undetermined check is not carried over into a later call on the same shared instance.
	 */
	public function test_failed_check_state_does_not_leak_between_calls() {
		$manager = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_connected', 'has_connected_owner' ) )
			->getMock();
		$manager->method( 'is_connected' )
			->willReturnOnConsecutiveCalls( $this->throwException( new Error( 'boom' ) ), true );
		$manager->method( 'has_connected_owner' )->willReturn( false );

		wc_get_container()->get( LegacyProxy::class )->register_class_mocks(
			array( JetpackConnectionManager::class => $manager )
		);

		$service = new DriverAvailabilityService();

		$first = $service->get_status()['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertNull( $first['connected'] );

		$second = $service->get_status()['installed_drivers'][ DriverAvailabilityService::DRIVER_REMOTE_PROXY ];
		$this->assertTrue(
			$second['connected'],
			'The container shares this service, so a failure must not persist into a later call.'
		);
	}
}
