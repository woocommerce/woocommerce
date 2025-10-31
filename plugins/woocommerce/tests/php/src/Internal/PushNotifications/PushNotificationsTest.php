<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
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
	 * @var FeaturesController|MockObject
	 */
	private $features_controller_mock;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_up_features_controller_mock();
	}

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		$this->reset_container_replacements();
		wc_get_container()->reset_all_resolved();

		parent::tearDown();
	}

	/**
	 * @testdox Tests the functionality is disabled if the feature flag is
	 * disabled.
	 */
	public function test_it_can_tell_push_notifications_should_not_be_enabled_if_feature_is_disabled() {
		$this->set_up_features_controller_mock( false );
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->never() )
			->method( 'is_connected' );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled() );
	}

	/**
	 * @testdox Tests the functionality is enabled if feature flag is enabled
	 * and Jetpack is connected.
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
	 * @testdox Tests the functionality is disabled if Jetpack is not connected
	 * even when feature flag is enabled.
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
	 * @testdox Tests that errors are logged when exception is thrown during
	 * enablement check.
	 */
	public function test_it_logs_error_when_jetpack_connection_check_throws_exception() {
		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $errors = array();

			public function error( $message, $data = array() ) {
				$this->errors[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->register_legacy_proxy_function_mocks( array( 'wc_get_logger' => fn () => $fake_logger ) );
		$this->set_up_features_controller_mock( true );
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );

		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willThrowException( new Exception( 'Connection check failed' ) );

		$push_notifications = new PushNotifications();
		$result             = $push_notifications->should_be_enabled();

		$this->assertFalse( $result, 'Should be disabled when exception is thrown' );
		$this->assertCount( 1, $fake_logger->errors, 'Should have logged exactly one error' );
		$this->assertStringContainsString( 'Connection check failed', $fake_logger->errors[0]['message'] );

		$this->assertStringContainsString(
			'Error determining if PushNotifications feature should be enabled',
			$fake_logger->errors[0]['message']
		);
	}

	/**
	 * @testdox Tests that enablement state is cached within an instance.
	 */
	public function test_it_caches_enablement_state_correctly() {
		// First instance with Jetpack disconnected - should return false.
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( false );

		$push_notifications = new PushNotifications();

		$this->assertFalse( $push_notifications->should_be_enabled(), 'Should be disabled when Jetpack is not connected' );

		// Second call should return cached false without calling is_connected again.
		$this->assertFalse( $push_notifications->should_be_enabled(), 'Should return cached false value' );

		// Create a new instance with Jetpack connected.
		$this->set_up_jetpack_connection_manager_mock( array( 'is_connected' ) );
		$this->jetpack_connection_manager_mock
			->expects( $this->once() )
			->method( 'is_connected' )
			->willReturn( true );

		$push_notifications_2 = new PushNotifications();

		// Should now return true with the new instance.
		$this->assertTrue( $push_notifications_2->should_be_enabled(), 'Should be enabled with new instance when Jetpack connected' );

		// Subsequent call should return cached true.
		$this->assertTrue( $push_notifications_2->should_be_enabled(), 'Should return cached true value' );
	}

	/**
	 * Sets up the FeaturesController mock.
	 *
	 * @param bool $feature_enabled Whether the push_notifications feature should be enabled.
	 */
	private function set_up_features_controller_mock( bool $feature_enabled = true ) {
		$this->features_controller_mock = $this
			->getMockBuilder( FeaturesController::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'feature_is_enabled' ) )
			->getMock();

		$this->features_controller_mock
			->method( 'feature_is_enabled' )
			->willReturnCallback(
				function ( $feature_id ) use ( $feature_enabled ) {
					return PushNotifications::FEATURE_NAME === $feature_id ? $feature_enabled : false;
				}
			);

		wc_get_container()->replace( FeaturesController::class, $this->features_controller_mock );
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
