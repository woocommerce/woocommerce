<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionController;

/**
 * Tests for the FraudProtectionController class.
 */
class FraudProtectionControllerTest extends \WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Set jetpack_activation_source option to prevent "Cannot use bool as array" error
		// in Jetpack Connection Manager's apply_activation_source_to_args method.
		update_option( 'jetpack_activation_source', array( '', '' ) );
	}

	/**
	 * Get a fresh controller instance with reset container.
	 *
	 * @return FraudProtectionController
	 */
	private function get_fresh_controller(): FraudProtectionController {
		$container = wc_get_container();
		$container->reset_all_resolved();
		return $container->get( FraudProtectionController::class );
	}

	/**
	 * Test logging functionality.
	 */
	public function test_log_writes_to_woo_fraud_protection_source(): void {
		// Mock the logger.
		$logger = $this->getMockBuilder( \WC_Logger_Interface::class )
			->getMock();

		// Expect the log method to be called with correct parameters.
		$logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( 'info' ),
				$this->equalTo( 'Test message' ),
				$this->equalTo( array( 'source' => 'woo-fraud-protection' ) )
			);

		// Replace the logger with our mock.
		add_filter(
			'woocommerce_logging_class',
			function () use ( $logger ) {
				return $logger;
			}
		);

		// Call the log method.
		FraudProtectionController::log( 'info', 'Test message' );
	}

	/**
	 * Test logging with context data.
	 */
	public function test_log_merges_context_with_source(): void {
		// Mock the logger.
		$logger = $this->getMockBuilder( \WC_Logger_Interface::class )
			->getMock();

		$expected_context = array(
			'foo'    => 'bar',
			'source' => 'woo-fraud-protection',
		);

		// Expect the log method to be called with merged context.
		$logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->equalTo( 'debug' ),
				$this->equalTo( 'Test with context' ),
				$this->equalTo( $expected_context )
			);

		// Replace the logger with our mock.
		add_filter(
			'woocommerce_logging_class',
			function () use ( $logger ) {
				return $logger;
			}
		);

		// Call the log method with context.
		FraudProtectionController::log( 'debug', 'Test with context', array( 'foo' => 'bar' ) );
	}

	/**
	 * Test that on_init does nothing when feature is disabled.
	 */
	public function test_no_hooks_when_feature_disabled(): void {
		// Ensure feature is disabled.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		// Get a fresh controller instance.
		$controller = $this->get_fresh_controller();

		// Count hooks before calling on_init.
		global $wp_filter;
		$hook_count_before = count( $wp_filter );

		// Call on_init.
		$controller->on_init();

		// Count hooks after - should be the same (no new hooks registered).
		$hook_count_after = count( $wp_filter );

		// Note: This is a basic test. In a full implementation, we would check
		// for specific hooks that should be registered when enabled.
		$this->assertEquals( $hook_count_before, $hook_count_after );
	}

	/**
	 * Test that register method registers init action.
	 */
	public function test_register_registers_init_action(): void {
		// Get a fresh controller instance.
		$controller = $this->get_fresh_controller();

		// Call register.
		$controller->register();

		// Check if the init action is registered for our callback.
		$priority = has_action( 'init', array( $controller, 'on_init' ) );

		// The priority should be 10 (default).
		$this->assertSame( 10, $priority, 'Init action should be registered with default priority 10' );
	}

	/**
	 * Test that feature_is_enabled returns true when feature is enabled.
	 */
	public function test_feature_is_enabled_returns_true_when_enabled(): void {
		// Enable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		// Get a fresh controller instance to pick up the option change.
		$controller = $this->get_fresh_controller();

		// Check if the method returns true.
		$this->assertTrue( $controller->feature_is_enabled() );
	}

	/**
	 * Test that feature_is_enabled returns false when feature is disabled.
	 */
	public function test_feature_is_enabled_returns_false_when_disabled(): void {
		// Disable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		// Get a fresh controller instance to pick up the option change.
		$controller = $this->get_fresh_controller();

		// Check if the method returns false.
		$this->assertFalse( $controller->feature_is_enabled() );
	}

	/**
	 * Test that should_track returns false when feature is enabled but user is admin.
	 */
	public function test_should_track_returns_false_when_enabled_for_admin(): void {
		// Enable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		// Create an admin user and set as current user.
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// Get a fresh controller instance to pick up the option change.
		$controller = $this->get_fresh_controller();

		// Check if the method returns false (admins should NOT be tracked).
		$this->assertFalse( $controller->should_track() );

		// Clean up.
		wp_delete_user( $admin_id );
	}

	/**
	 * Test that should_track returns false when feature is disabled regardless of user role.
	 */
	public function test_should_track_returns_false_when_disabled_for_regular_user(): void {
		// Disable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		// Create a subscriber user (regular user, no admin capability).
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		// Get a fresh controller instance to pick up the option change.
		$controller = $this->get_fresh_controller();

		// Check if the method returns false (feature disabled = no tracking).
		$this->assertFalse( $controller->should_track() );

		// Clean up.
		wp_delete_user( $subscriber_id );
	}

	/**
	 * Test that should_track returns true when feature is enabled for regular users.
	 */
	public function test_should_track_returns_true_when_enabled_for_regular_user(): void {
		// Enable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		// Create a subscriber user (no manage_woocommerce capability) and set as current user.
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		// Get a fresh controller instance to pick up the option change.
		$controller = $this->get_fresh_controller();

		// Check if the method returns true (regular users should be tracked).
		$this->assertTrue( $controller->should_track() );

		// Clean up.
		wp_delete_user( $subscriber_id );
	}

	/**
	 * Test that should_track returns true when no user is logged in (guest).
	 */
	public function test_should_track_returns_true_when_no_user(): void {
		// Enable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		// Ensure no user is logged in.
		wp_set_current_user( 0 );

		// Get a fresh controller instance to pick up the option change.
		$controller = $this->get_fresh_controller();

		// Check if the method returns true (guests should be tracked).
		$this->assertTrue( $controller->should_track() );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up any filters or options.
		remove_all_filters( 'woocommerce_logging_class' );
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );
		delete_option( 'jetpack_activation_source' );

		// Remove any init hooks registered by the controller.
		remove_all_actions( 'init' );

		// Reset container.
		wc_get_container()->reset_all_resolved();
	}
}
