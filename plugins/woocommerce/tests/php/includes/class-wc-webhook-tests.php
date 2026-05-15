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
	 * @testDox The customer.deleted webhook should be considered valid for any deleted user regardless of role.
	 *
	 * Mirrors the behavior of customer.created (user_register) and customer.updated (profile_update),
	 * which fire for any user. Previously the delete_user hook was gated to users with the
	 * 'customer' role only, which caused customer.deleted to silently skip non-customer users.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/36734
	 */
	public function test_customer_deleted_webhook_is_enqueued_for_any_role() {
		$subscriber_id = wp_insert_user(
			array(
				'user_login' => 'rsmapgj_294_subscriber',
				'user_email' => 'rsmapgj_294_subscriber@example.com',
				'user_pass'  => 'password',
				'role'       => 'subscriber',
			)
		);

		$customer_id = wp_insert_user(
			array(
				'user_login' => 'rsmapgj_294_customer',
				'user_email' => 'rsmapgj_294_customer@example.com',
				'user_pass'  => 'password',
				'role'       => 'customer',
			)
		);

		$webhook = new WC_Webhook();
		$webhook->set_name( 'rsmapgj-294-customer-deleted' );
		$webhook->set_topic( 'customer.deleted' );
		$webhook->set_status( 'active' );
		$webhook->set_delivery_url( 'https://example.test/webhook' );
		$webhook->set_secret( 'secret' );
		$webhook->save();

		// Spy on enqueued deliveries via the woocommerce_webhook_should_deliver filter,
		// which receives the webhook, the hook arg, and the current_action() at the point of evaluation.
		$delivered_for = array();
		$spy           = function ( $should_deliver, $hook_webhook, $arg ) use ( $webhook, &$delivered_for ) {
			if ( $hook_webhook->get_id() === $webhook->get_id() && $should_deliver ) {
				$delivered_for[] = (int) $arg;
			}
			// Prevent actual HTTP delivery in the test.
			return false;
		};
		add_filter( 'woocommerce_webhook_should_deliver', $spy, 10, 3 );

		try {
			wp_delete_user( $subscriber_id );
			wp_delete_user( $customer_id );
		} finally {
			remove_filter( 'woocommerce_webhook_should_deliver', $spy, 10 );
		}

		$this->assertContains(
			$subscriber_id,
			$delivered_for,
			'customer.deleted should be enqueued for a deleted subscriber user.'
		);
		$this->assertContains(
			$customer_id,
			$delivered_for,
			'customer.deleted should be enqueued for a deleted customer user.'
		);
	}

}
