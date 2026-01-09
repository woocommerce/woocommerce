<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\AdminSettingsHandler;
use Automattic\WooCommerce\Internal\FraudProtection\JetpackConnectionManager;

/**
 * Tests for the AdminSettingsHandler class.
 */
class AdminSettingsHandlerTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var AdminSettingsHandler
	 */
	private $sut;

	/**
	 * Mock Jetpack connection manager.
	 *
	 * @var JetpackConnectionManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $connection_manager_mock;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create mock connection manager.
		$this->connection_manager_mock = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		// Get fresh instance from container.
		$this->sut = wc_get_container()->get( AdminSettingsHandler::class );
		$this->sut->init( $this->connection_manager_mock );
	}

	/**
	 * Test that register method registers the expected hooks.
	 */
	public function test_register_registers_hooks(): void {
		$this->sut->register();

		// Check if the settings filter is registered.
		$priority = has_filter( 'woocommerce_get_settings_advanced', array( $this->sut, 'add_jetpack_connection_field' ) );
		$this->assertSame( 100, $priority, 'Settings filter should be registered with priority 100' );

		// Check if the admin field action is registered.
		$priority = has_action( 'woocommerce_admin_field_jetpack_connection', array( $this->sut, 'handle_output_jetpack_connection_field' ) );
		$this->assertSame( 10, $priority, 'Admin field action should be registered with priority 10' );
	}

	/**
	 * Test that add_jetpack_connection_field returns settings unchanged on non-features section.
	 */
	public function test_add_jetpack_connection_field_returns_unchanged_on_non_features_section(): void {
		$this->sut->register();

		$settings        = array(
			array(
				'id'   => 'some_setting',
				'type' => 'text',
			),
		);
		$current_section = 'general';
		$result          = $this->sut->add_jetpack_connection_field( $settings, $current_section );

		$this->assertSame( $settings, $result, 'Settings should be unchanged on non-features section' );
	}

	/**
	 * Test that add_jetpack_connection_field adds field after fraud_protection on features section.
	 */
	public function test_add_jetpack_connection_field_adds_field_after_fraud_protection(): void {
		$this->sut->register();

		$settings = array(
			array(
				'id'   => 'some_other_feature',
				'type' => 'checkbox',
			),
			array(
				'id'   => 'woocommerce_feature_fraud_protection_enabled',
				'type' => 'checkbox',
			),
			array(
				'id'   => 'another_feature',
				'type' => 'checkbox',
			),
		);

		$result = $this->sut->add_jetpack_connection_field( $settings, 'features' );

		// Should have one more setting.
		$this->assertCount( 4, $result );

		// The new field should be added after fraud_protection.
		$this->assertSame( 'woocommerce_feature_fraud_protection_enabled', $result[1]['id'] );
		$this->assertSame( 'woocommerce_fraud_protection_jetpack_connection', $result[2]['id'] );
		$this->assertSame( 'jetpack_connection', $result[2]['type'] );
		$this->assertSame( 'another_feature', $result[3]['id'] );
	}

	/**
	 * Test that add_jetpack_connection_field doesn't add duplicate field.
	 */
	public function test_add_jetpack_connection_field_doesnt_duplicate(): void {
		$this->sut->register();

		$settings = array(
			array(
				'id'   => 'woocommerce_feature_fraud_protection_enabled',
				'type' => 'checkbox',
			),
		);

		// Call twice to check it doesn't duplicate.
		$result1 = $this->sut->add_jetpack_connection_field( $settings, 'features' );
		$result2 = $this->sut->add_jetpack_connection_field( $result1, 'features' );

		// Should still only have 2 settings (fraud_protection + 1 jetpack_connection).
		$this->assertCount( 2, $result2 );
	}

	/**
	 * Test that handle_output_jetpack_connection_field does nothing when fraud protection disabled.
	 */
	public function test_handle_output_jetpack_connection_field_returns_early_when_disabled(): void {
		// Disable fraud protection.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'no' );

		$this->sut->register();

		// Mock connection manager should not be called.
		$this->connection_manager_mock->expects( $this->never() )
			->method( 'get_connection_status' );

		// Capture output.
		ob_start();
		$this->sut->handle_output_jetpack_connection_field( array() );
		$output = ob_get_clean();

		// Should produce no output.
		$this->assertEmpty( $output );
	}

	/**
	 * Test that handle_output_jetpack_connection_field shows button when not connected.
	 */
	public function test_handle_output_jetpack_connection_field_shows_button_when_not_connected(): void {
		// Enable fraud protection.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		$this->sut->register();

		// Mock connection status - not connected.
		$this->connection_manager_mock->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn(
				array(
					'connected'  => false,
					'error'      => 'Not connected',
					'error_code' => 'not_connected',
					'blog_id'    => null,
				)
			);

		// Mock authorization URL.
		$this->connection_manager_mock->expects( $this->once() )
			->method( 'get_authorization_url' )
			->willReturn( 'https://example.com/connect' );

		// Capture output.
		ob_start();
		$this->sut->handle_output_jetpack_connection_field( array() );
		$output = ob_get_clean();

		// Should contain Connect button.
		$this->assertStringContainsString( 'Connect to Jetpack', $output );
		$this->assertStringContainsString( 'jetpack_connection_button', $output );
		$this->assertStringContainsString( 'https://example.com/connect', $output );
	}

	/**
	 * Test that handle_output_jetpack_connection_field shows connected status when connected.
	 */
	public function test_handle_output_jetpack_connection_field_shows_connected_status(): void {
		// Enable fraud protection.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		$this->sut->register();

		// Mock connection status - connected.
		$this->connection_manager_mock->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn(
				array(
					'connected'  => true,
					'error'      => '',
					'error_code' => '',
					'blog_id'    => 12345,
				)
			);

		// Should not call get_authorization_url when connected.
		$this->connection_manager_mock->expects( $this->never() )
			->method( 'get_authorization_url' );

		// Capture output.
		ob_start();
		$this->sut->handle_output_jetpack_connection_field( array() );
		$output = ob_get_clean();

		// Should show connected status.
		$this->assertStringContainsString( 'Connected to Jetpack', $output );
		$this->assertStringContainsString( 'Site ID: 12345', $output );
		$this->assertStringContainsString( 'dashicons-yes-alt', $output );
	}

	/**
	 * Test that handle_output_jetpack_connection_field shows error when authorization URL fails.
	 */
	public function test_handle_output_jetpack_connection_field_shows_error_when_url_fails(): void {
		// Enable fraud protection.
		update_option( 'woocommerce_feature_fraud_protection_enabled', 'yes' );

		$this->sut->register();

		// Mock connection status - not connected.
		$this->connection_manager_mock->expects( $this->once() )
			->method( 'get_connection_status' )
			->willReturn(
				array(
					'connected'  => false,
					'error'      => 'Jetpack not available',
					'error_code' => 'jetpack_not_available',
					'blog_id'    => null,
				)
			);

		// Mock authorization URL - returns null (failure).
		$this->connection_manager_mock->expects( $this->once() )
			->method( 'get_authorization_url' )
			->willReturn( null );

		// Capture output.
		ob_start();
		$this->sut->handle_output_jetpack_connection_field( array() );
		$output = ob_get_clean();

		// Should show error message.
		$this->assertStringContainsString( 'Jetpack not available', $output );
		$this->assertStringNotContainsString( 'Connect to Jetpack', $output );
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up options.
		delete_option( 'woocommerce_feature_fraud_protection_enabled' );

		// Remove hooks.
		remove_all_filters( 'woocommerce_get_settings_advanced' );
		remove_all_actions( 'woocommerce_admin_field_jetpack_connection' );
		remove_all_actions( 'admin_enqueue_scripts' );

		// Reset container.
		wc_get_container()->reset_all_resolved();

		// Clean up $_GET.
		unset( $_GET['section'] );
	}
}
