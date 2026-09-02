<?php
/**
 * Tests for WC_Webhook class.
 */

/**
 * Tests for WC_Webhook class.
 */
class WC_Webhook_Test extends WC_Unit_Test_Case {

	/**
	 * @testDox Check if valid resource is true when both arg and topic are valid.
	 */
	public function test_is_valid_resource() {
		$webhook = new WC_Webhook();
		$webhook->set_topic( 'order.created' );
		$order                  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$call_is_valid_function = function ( $arg ) {
			return $this->is_valid_resource( $arg );
		};
		$this->assertTrue( $call_is_valid_function->call( $webhook, $order->get_id() ) );
	}

	/**
	 * @testDox Check if valid resource is false when both arg and topic are different.
	 */
	public function test_is_valid_resource_false() {
		$webhook = new WC_Webhook();
		$webhook->set_topic( 'order.created' );
		$product                = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$call_is_valid_function = function ( $arg ) {
			return $this->is_valid_resource( $arg );
		};
		$this->assertFalse( $call_is_valid_function->call( $webhook, $product->get_id() ) );
	}

	/**
	 * @testDox Check that a deleted administrator user (with content re-assigned to another user)
	 * does not cause webhook payloads to fail.
	 */
	public function test_payload_for_deleted_user_id_with_reassign() {
		$admin_user_id_1 = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);

		$webhook = new WC_Webhook();
		$webhook->set_topic( 'order.created' );
		$webhook->set_user_id( $admin_user_id_1 );
		$webhook->save();

		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		$payload = $webhook->build_payload( $order->get_id() );
		$this->assertArrayNotHasKey( 'code', $payload );
		$this->assertArrayHasKey( 'id', $payload );
		$this->assertSame( $order->get_id(), $payload['id'] );

		// Create a second admin user and delete the first one, reassigning existing content to the second user.
		$admin_user_id_2 = wp_insert_user(
			array(
				'user_login' => 'test_admin2',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_delete_user( $admin_user_id_1, $admin_user_id_2 );

		// Re-load the webhook from the database.
		$webhook = new WC_Webhook( $webhook->get_id() );
		// Confirm user_id has been updated to the second admin user.
		$this->assertSame( $admin_user_id_2, $webhook->get_user_id() );

		$this->assertArrayNotHasKey( 'code', $payload );
		$this->assertArrayHasKey( 'id', $payload );
		$this->assertSame( $order->get_id(), $payload['id'] );
	}

	/**
	 * @testDox Check that a deleted administrator user (without content re-assigned to another user)
	 * has all webhooks changed to user_id zero.
	 */
	public function test_payload_for_deleted_user_id_without_reassign() {
		$admin_user_id = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);

		$webhook1 = new WC_Webhook();
		$webhook1->set_topic( 'order.created' );
		$webhook1->set_user_id( $admin_user_id );
		$webhook1->save();

		$webhook2 = new WC_Webhook();
		$webhook2->set_topic( 'order.created' );
		$webhook2->set_user_id( 999 );
		$webhook2->save();

		wp_delete_user( $admin_user_id );

		// Re-load the webhooks from the database.
		$webhook1 = new WC_Webhook( $webhook1->get_id() );
		$webhook2 = new WC_Webhook( $webhook2->get_id() );
		// Confirm user_id has been updated to zero for the first webhook only.
		$this->assertSame( 0, $webhook1->get_user_id() );
		$this->assertSame( 999, $webhook2->get_user_id() );
	}

	/**
	 * @testDox The woocommerce_webhook_enable_delivery_log filter toggles delivery logging without affecting failure tracking.
	 */
	public function test_delivery_logging_respects_enable_delivery_log_filter() {
		// Ensure logging is enabled and every level is handled so the delivery log write is observable.
		update_option( 'woocommerce_logs_logging_enabled', 'yes' );
		update_option( 'woocommerce_logs_level_threshold', 'none' );

		$logged  = 0;
		$log_spy = function ( $message, $level, $context ) use ( &$logged ) {
			if ( isset( $context['source'] ) && 'webhooks-delivery' === $context['source'] ) {
				++$logged;
			}
			return $message;
		};
		add_filter( 'woocommerce_logger_log_message', $log_spy, 10, 3 );

		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->save();

		$request = array(
			'method'     => 'POST',
			'user-agent' => 'WooCommerce',
			'headers'    => array(),
			'body'       => '{}',
		);
		$success = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'headers'  => array(),
			'body'     => '',
		);
		$failure = array(
			'response' => array(
				'code'    => 500,
				'message' => 'Internal Server Error',
			),
			'headers'  => array(),
			'body'     => '',
		);

		// By default the delivery is logged, and the filter receives the webhook ID.
		$received_id = null;
		$id_spy      = function ( $enable, $webhook_id ) use ( &$received_id ) {
			$received_id = $webhook_id;
			return $enable;
		};
		add_filter( 'woocommerce_webhook_enable_delivery_log', $id_spy, 10, 2 );
		$webhook->log_delivery( 'delivery-default', $request, $success, 0.1 );
		remove_filter( 'woocommerce_webhook_enable_delivery_log', $id_spy, 10 );

		$this->assertSame( $webhook->get_id(), $received_id, 'The filter should receive the webhook ID.' );
		$this->assertGreaterThan( 0, $logged, 'The delivery should be logged by default.' );

		// When the filter returns false the log is suppressed, but failure tracking still runs.
		$logged = 0;
		$webhook->set_failure_count( 0 );
		$webhook->save();
		add_filter( 'woocommerce_webhook_enable_delivery_log', '__return_false' );
		$webhook->log_delivery( 'delivery-disabled', $request, $failure, 0.1 );
		remove_filter( 'woocommerce_webhook_enable_delivery_log', '__return_false' );

		$this->assertSame( 0, $logged, 'The delivery log should be suppressed when the filter returns false.' );
		$this->assertGreaterThan(
			0,
			wc_get_webhook( $webhook->get_id() )->get_failure_count(),
			'Failure tracking must still run when delivery logging is disabled.'
		);

		remove_filter( 'woocommerce_logger_log_message', $log_spy, 10 );
	}

}
