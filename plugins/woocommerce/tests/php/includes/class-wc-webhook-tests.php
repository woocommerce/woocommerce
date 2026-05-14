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
	 * @testDox The product.deleted topic listens to woocommerce_delete_product_variation so it fires when a variation is deleted.
	 */
	public function test_product_deleted_topic_includes_variation_delete_hook() {
		$webhook = new WC_Webhook();
		$webhook->set_topic( 'product.deleted' );

		$hooks = $webhook->get_hooks();

		$this->assertContains(
			'woocommerce_delete_product_variation',
			$hooks,
			'product.deleted webhook should listen to woocommerce_delete_product_variation so variation deletions fire the webhook.'
		);
		$this->assertContains(
			'wp_trash_post',
			$hooks,
			'product.deleted webhook should continue to listen to wp_trash_post for regular product deletions.'
		);
	}

	/**
	 * @testDox The product.deleted webhook is queued for delivery when a product variation is deleted.
	 */
	public function test_product_deleted_webhook_fires_for_variation_deletion() {
		// Create a variable product with one variation.
		$variable = new WC_Product_Variable();
		$variable->set_name( 'Webhook Variable Product' );
		$variable->set_status( 'publish' );
		$variable->save();

		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Color' );
		$attribute->set_options( array( 'Red', 'Blue' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );
		$variable->set_attributes( array( $attribute ) );
		$variable->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $variable->get_id() );
		$variation->set_attributes( array( 'color' => 'Red' ) );
		$variation->set_regular_price( '10.00' );
		$variation->set_status( 'publish' );
		$variation->save();
		$variation_id = $variation->get_id();

		// Create a product.deleted webhook and enqueue its hooks.
		$webhook = new WC_Webhook();
		$webhook->set_name( 'Test product.deleted variation webhook' );
		$webhook->set_topic( 'product.deleted' );
		$webhook->set_delivery_url( 'https://example.com/webhook-sink/' );
		$webhook->set_status( 'active' );
		$webhook->set_user_id( 1 );
		$webhook->save();
		$webhook->enqueue();

		// Capture topics queued for delivery via the should_deliver filter.
		$queued  = array();
		$capture = function ( $should_deliver, $hooked_webhook, $arg ) use ( &$queued ) {
			$queued[] = array(
				'topic' => $hooked_webhook->get_topic(),
				'arg'   => $arg,
			);
			// Prevent the actual HTTP delivery from being scheduled.
			return false;
		};
		add_filter( 'woocommerce_webhook_should_deliver', $capture, 10, 3 );

		try {
			// Delete the variation: this should trigger woocommerce_delete_product_variation.
			$variation->delete( true );
		} finally {
			remove_filter( 'woocommerce_webhook_should_deliver', $capture, 10 );
		}

		$queued_topics = wp_list_pluck( $queued, 'topic' );
		$this->assertContains(
			'product.deleted',
			$queued_topics,
			'product.deleted webhook should be queued when a product variation is deleted.'
		);

		$variation_queue_entries = array_filter(
			$queued,
			static function ( $entry ) use ( $variation_id ) {
				return 'product.deleted' === $entry['topic'] && (int) $entry['arg'] === (int) $variation_id;
			}
		);
		$this->assertNotEmpty(
			$variation_queue_entries,
			'product.deleted webhook should be queued with the deleted variation ID.'
		);
	}

}
