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
	 * @var FraudProtectionController
	 */
	private $controller;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create controller instance.
		$this->controller = new FraudProtectionController();
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
	 * Test that hooks are not registered when feature is disabled.
	 */
	public function test_no_hooks_when_feature_disabled(): void {
		// Ensure feature is disabled.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		// Create a new controller instance.
		$controller = new FraudProtectionController();

		// Count hooks before calling maybe_init_hooks.
		global $wp_filter;
		$hook_count_before = count( $wp_filter );

		// Call maybe_init_hooks.
		$controller->maybe_init_hooks();

		// Count hooks after - should be the same (no new hooks registered).
		$hook_count_after = count( $wp_filter );

		// Note: This is a basic test. In a full implementation, we would check
		// for specific hooks that should be registered when enabled.
		$this->assertEquals( $hook_count_before, $hook_count_after );
	}

	/**
	 * Test that init action is registered on construction.
	 */
	public function test_init_action_registered(): void {
		// Create a fresh controller instance.
		remove_all_actions( 'init' );
		$new_controller = new FraudProtectionController();

		// Check if the init action is registered for our callback.
		$priority = has_action( 'init', array( $new_controller, 'maybe_init_hooks' ) );

		// The priority should be 0 as specified in the constructor.
		$this->assertSame( 0, $priority, 'Init action should be registered with priority 0' );
	}

	/**
	 * Test that is_fraud_protection_enabled returns true when feature is enabled.
	 */
	public function test_is_fraud_protection_enabled_returns_true_when_enabled(): void {
		// Enable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		// Create a new controller instance to pick up the option change.
		$controller = new FraudProtectionController();

		// Check if the method returns true.
		$this->assertTrue( $controller->is_fraud_protection_enabled() );
	}

	/**
	 * Test that is_fraud_protection_enabled returns false when feature is disabled.
	 */
	public function test_is_fraud_protection_enabled_returns_false_when_disabled(): void {
		// Disable the feature.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		// Create a new controller instance to pick up the option change.
		$controller = new FraudProtectionController();

		// Check if the method returns false.
		$this->assertFalse( $controller->is_fraud_protection_enabled() );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up any filters or options.
		remove_all_filters( 'woocommerce_logging_class' );
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );

		// Remove any init hooks registered by the controller.
		remove_all_actions( 'init', 0 );
	}
}
