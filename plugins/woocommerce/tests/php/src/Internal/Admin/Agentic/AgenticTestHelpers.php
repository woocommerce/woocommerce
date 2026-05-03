<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Agentic;

use Automattic\Jetpack\Connection\Manager as JetpackConnectionManager;
use Automattic\Jetpack\Connection\Rest_Authentication as JetpackRestAuthentication;
use Automattic\WooCommerce\Internal\Admin\Agentic\AgenticWebhookManager;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;
use WC_Order;
use WC_Webhook;

/**
 * Shared test helpers for Agentic tests.
 */
trait AgenticTestHelpers {

	/**
	 * Mock for Jetpack Connection Manager.
	 *
	 * @var JetpackConnectionManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $jetpack_manager_mock;

	/**
	 * Clear the Jetpack REST_Authentication singleton instance.
	 *
	 * This allows tests to reset the authentication state between test cases.
	 */
	protected function clear_jetpack_auth_singleton(): void {
		if ( ! class_exists( JetpackRestAuthentication::class ) ) {
			return;
		}

		$reflection_class  = new \ReflectionClass( JetpackRestAuthentication::class );
		$instance_property = $reflection_class->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	/**
	 * Set up Jetpack authentication to simulate a valid blog token request.
	 *
	 * This mocks the Jetpack Connection Manager's verify_xml_rpc_signature method
	 * to return a successful blog token verification result.
	 */
	protected function mock_jetpack_blog_token_auth(): void {
		if ( ! class_exists( JetpackRestAuthentication::class ) ) {
			$this->markTestSkipped( 'Jetpack Connection package not available.' );
			return;
		}

		// Clear any existing singleton.
		$this->clear_jetpack_auth_singleton();

		// Initialize the REST Authentication singleton.
		$rest_auth = JetpackRestAuthentication::init();

		// Create a mock for the Connection Manager.
		$this->jetpack_manager_mock = $this->getMockBuilder( JetpackConnectionManager::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'verify_xml_rpc_signature', 'reset_saved_auth_state' ) )
			->getMock();

		// Configure the mock to return successful blog token verification.
		$this->jetpack_manager_mock->expects( $this->any() )
			->method( 'verify_xml_rpc_signature' )
			->willReturn(
				array(
					'type'      => 'blog',
					'token_key' => 'test_blog_token',
					'user_id'   => 0,
				)
			);

		$this->jetpack_manager_mock->expects( $this->any() )
			->method( 'reset_saved_auth_state' )
			->willReturn( null );

		// Inject the mock into the REST Authentication instance using reflection.
		$reflection_class = new \ReflectionClass( JetpackRestAuthentication::class );
		$manager_property = $reflection_class->getProperty( 'connection_manager' );
		$manager_property->setAccessible( true );
		$manager_property->setValue( $rest_auth, $this->jetpack_manager_mock );

		// Set up the $_GET parameters and $_SERVER to simulate a Jetpack-signed request.
		$_GET['_for']              = 'jetpack';
		$_GET['token']             = 'test_token';
		$_GET['signature']         = 'test_signature';
		$_SERVER['REQUEST_METHOD'] = 'POST';

		// Trigger the authentication.
		$rest_auth->wp_rest_authenticate( '' );
	}

	/**
	 * Set up Jetpack authentication to simulate a failed/missing authentication.
	 *
	 * This ensures that is_signed_with_blog_token() returns false.
	 */
	protected function mock_jetpack_auth_failure(): void {
		if ( ! class_exists( JetpackRestAuthentication::class ) ) {
			return;
		}

		// Clear any existing singleton - this ensures no authentication is set.
		$this->clear_jetpack_auth_singleton();

		// Clear Jetpack-related $_GET parameters.
		unset( $_GET['_for'], $_GET['token'], $_GET['signature'] );
	}

	/**
	 * Reset Jetpack authentication state after tests.
	 *
	 * Call this in tearDown() to ensure clean state between tests.
	 */
	protected function reset_jetpack_auth_state(): void {
		if ( class_exists( JetpackRestAuthentication::class ) ) {
			$this->clear_jetpack_auth_singleton();
		}

		// Clear Jetpack-related $_GET parameters.
		unset( $_GET['_for'], $_GET['token'], $_GET['signature'] );

		// Reset request method if it was set.
		unset( $_SERVER['REQUEST_METHOD'] );
	}
	/**
	 * Create an order with Agentic session ID.
	 *
	 * @param string $session_id Session ID to use. Defaults to 'test_session_123'.
	 * @param string $status     Order status. Defaults to 'pending'.
	 * @return WC_Order Created order.
	 */
	protected function create_agentic_order( $session_id = 'test_session_123', $status = 'pending' ) {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( OrderMetaKey::AGENTIC_CHECKOUT_SESSION_ID, $session_id );
		$order->set_status( $status );
		$order->save();
		return $order;
	}

	/**
	 * Add webhook sent meta to order.
	 *
	 * @param WC_Order $order Order object.
	 */
	protected function add_webhook_sent_meta( $order ) {
		$order->update_meta_data( '_acp_order_created_sent', 'sent' );
		$order->save();
	}

	/**
	 * Create an Agentic webhook.
	 *
	 * @param string $topic Topic for webhook. Defaults to 'action.woocommerce_agentic_order_created'.
	 * @return WC_Webhook Created webhook.
	 */
	protected function create_agentic_webhook( $topic = AgenticWebhookManager::WEBHOOK_TOPIC ) {
		$webhook = new WC_Webhook();
		$webhook->set_topic( $topic );
		$webhook->set_delivery_url( 'https://test.com' );
		$webhook->set_secret( 'test_secret' );
		$webhook->save();
		return $webhook;
	}

	/**
	 * Assert that payload has ACP structure.
	 *
	 * @param array  $payload      Payload to check.
	 * @param string $expected_type Expected type ('order_create' or 'order_update').
	 */
	protected function assert_agentic_payload_structure( $payload, $expected_type ) {
		$this->assertEquals( $expected_type, $payload['type'] );
		$this->assertArrayHasKey( 'data', $payload );
		$this->assertEquals( 'order', $payload['data']['type'] );
		$this->assertArrayHasKey( 'checkout_session_id', $payload['data'] );
		$this->assertArrayHasKey( 'status', $payload['data'] );
		$this->assertArrayHasKey( 'refunds', $payload['data'] );
	}

	/**
	 * Track action firing with a counter.
	 *
	 * @param string $action Action name to track.
	 * @return int Counter reference.
	 */
	protected function track_action( $action ) {
		$count = 0;
		add_action(
			$action,
			function () use ( &$count ) {
				$count++;
			}
		);
		return $count;
	}
}
