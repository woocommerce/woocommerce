<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookSettings;

/**
 * Unit tests for AgenticWebhookSettings class.
 */
class AgenticWebhookSettingsTest extends \WC_Unit_Test_Case {
	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Clean up any existing configurations.
		delete_option( AgenticWebhookSettings::ENABLED_OPTION );
	}

	/**
	 * Test enabling and disabling webhooks.
	 */
	public function test_enable_disable_webhooks() {
		// Initially should be disabled.
		$this->assertFalse( AgenticWebhookSettings::is_enabled() );

		// Enable webhooks.
		AgenticWebhookSettings::enable();
		$this->assertTrue( AgenticWebhookSettings::is_enabled() );

		// Disable webhooks.
		AgenticWebhookSettings::disable();
		$this->assertFalse( AgenticWebhookSettings::is_enabled() );
	}

	/**
	 * Test getting setup instructions.
	 */
	public function test_get_setup_instructions() {
		$instructions = AgenticWebhookSettings::get_setup_instructions();

		$this->assertIsArray( $instructions );
		$this->assertArrayHasKey( 'title', $instructions );
		$this->assertArrayHasKey( 'steps', $instructions );
		$this->assertArrayHasKey( 'notes', $instructions );

		$this->assertEquals( 'Agentic Commerce Protocol Webhook Setup', $instructions['title'] );
		$this->assertIsArray( $instructions['steps'] );
		$this->assertIsArray( $instructions['notes'] );
		$this->assertNotEmpty( $instructions['steps'] );
	}

	/**
	 * Test creating webhooks.
	 */
	public function test_create_webhooks() {
		$base_url = 'https://example.com';
		$secret   = 'test_secret_key_123456';

		$results = AgenticWebhookSettings::create_webhooks( $base_url, $secret );

		$this->assertIsArray( $results );
		$this->assertArrayHasKey( 'created', $results );
		$this->assertArrayHasKey( 'updated', $results );

		// Verify webhooks were actually created in database.
		$created_webhook = new \WC_Webhook( $results['created'] );
		$this->assertEquals( 'Agentic Order Created', $created_webhook->get_name() );
		$this->assertEquals( 'action.woocommerce_agentic_order_created', $created_webhook->get_topic() );
		$this->assertEquals( $base_url, $created_webhook->get_delivery_url() );
		$this->assertEquals( 'active', $created_webhook->get_status() );
		$this->assertEquals( $secret, $created_webhook->get_secret() );

		$updated_webhook = new \WC_Webhook( $results['updated'] );
		$this->assertEquals( 'Agentic Order Updated', $updated_webhook->get_name() );
		$this->assertEquals( 'action.woocommerce_agentic_order_updated', $updated_webhook->get_topic() );
		$this->assertEquals( $base_url, $updated_webhook->get_delivery_url() );
		$this->assertEquals( 'active', $updated_webhook->get_status() );
		$this->assertEquals( $secret, $updated_webhook->get_secret() );
	}

	/**
	 * Test creating webhooks with invalid data.
	 */
	public function test_create_webhooks_invalid_data() {
		// Empty base URL.
		$result = AgenticWebhookSettings::create_webhooks( '', 'secret123' );
		$this->assertArrayHasKey( 'error', $result );

		// Empty secret.
		$result = AgenticWebhookSettings::create_webhooks( 'https://example.com', '' );
		$this->assertArrayHasKey( 'error', $result );

		// Invalid URL.
		$result = AgenticWebhookSettings::create_webhooks( 'not-a-url', 'secret123' );
		$this->assertArrayHasKey( 'error', $result );

		// Short secret.
		$result = AgenticWebhookSettings::create_webhooks( 'https://example.com', 'short' );
		$this->assertArrayHasKey( 'error', $result );
	}

	/**
	 * Test getting Agentic webhooks.
	 */
	public function test_get_agentic_webhooks() {
		// Initially no webhooks.
		$webhooks = AgenticWebhookSettings::get_agentic_webhooks();
		$this->assertIsArray( $webhooks );
		$this->assertCount( 0, $webhooks );

		// Create Agentic webhooks.
		AgenticWebhookSettings::create_webhooks( 'https://example.com', 'test_secret_key_123456' );

		// Should now have 2 webhooks.
		$webhooks = AgenticWebhookSettings::get_agentic_webhooks();
		$this->assertIsArray( $webhooks );
		$this->assertCount( 2, $webhooks );

		// Verify they are the right webhooks.
		$topics = array_map(
			function ( $webhook ) {
				return $webhook->get_topic();
			},
			$webhooks
		);
		$this->assertContains( 'action.woocommerce_agentic_order_created', $topics );
		$this->assertContains( 'action.woocommerce_agentic_order_updated', $topics );

		// Create a non-Agentic webhook.
		$other_webhook = new \WC_Webhook();
		$other_webhook->set_name( 'Other Webhook' );
		$other_webhook->set_topic( 'order.created' );
		$other_webhook->set_delivery_url( 'https://other.com/webhook' );
		$other_webhook->set_status( 'active' );
		$other_webhook->save();

		// Should still only return 2 Agentic webhooks.
		$webhooks = AgenticWebhookSettings::get_agentic_webhooks();
		$this->assertCount( 2, $webhooks );
	}
}