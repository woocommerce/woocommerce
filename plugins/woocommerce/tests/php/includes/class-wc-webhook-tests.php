<?php
/**
 * Tests for WC_Webhook class.
 */

/**
 * Tests for WC_Webhook class.
 */
class WC_Webhook_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Check that post-action validation uses the affected post ID.
	 *
	 * @dataProvider post_action_validation_provider
	 *
	 * @param string|null $post_type       Post type to create, or null for ID 0.
	 * @param string      $topic           Webhook topic.
	 * @param string|null $global_post_type Existing global post type, or null to unset it.
	 * @param bool        $expected        Expected validation result.
	 */
	public function test_is_valid_post_action_uses_post_id( $post_type, $topic, $global_post_type, $expected ): void {
		$had_global_post_type = array_key_exists( 'post_type', $GLOBALS );
		$original_post_type   = $GLOBALS['post_type'] ?? null;
		if ( null === $global_post_type ) {
			unset( $GLOBALS['post_type'] );
		} else {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolates arbitrary global request state.
			$GLOBALS['post_type'] = $global_post_type;
		}

		try {
			$post_id = null === $post_type ? 0 : $this->factory->post->create(
				array(
					'post_type'   => $post_type,
					'post_status' => 'publish',
				)
			);
			$webhook = new WC_Webhook();
			$webhook->set_topic( $topic );
			$this->assertSame( $expected, $this->call_is_valid_post_action( $webhook, $post_id ) );
		} finally {
			if ( $had_global_post_type ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global request state changed for this test.
				$GLOBALS['post_type'] = $original_post_type;
			} else {
				unset( $GLOBALS['post_type'] );
			}
		}
	}

	/**
	 * Call the private post-action validator.
	 *
	 * @param WC_Webhook $webhook Webhook to validate.
	 * @param mixed      $arg     Hook argument.
	 * @return bool Validation result.
	 */
	private function call_is_valid_post_action( WC_Webhook $webhook, $arg ): bool {
		$call_is_valid_function = function ( $arg ) {
			return $this->is_valid_post_action( $arg );
		};

		return $call_is_valid_function->call( $webhook, $arg );
	}

	/**
	 * Data provider for test_is_valid_post_action_uses_post_id().
	 *
	 * @return array<string, array{string|null, string, string|null, bool}> Test cases.
	 */
	public function post_action_validation_provider() {
		return array(
			'matching product without global'         => array( 'product', 'product.deleted', null, true ),
			'matching coupon without global'          => array( 'shop_coupon', 'coupon.deleted', null, true ),
			'matching order without global'           => array( 'shop_order', 'order.deleted', null, true ),
			'product resource with coupon ID'         => array( 'shop_coupon', 'product.deleted', null, false ),
			'coupon resource with product ID'         => array( 'product', 'coupon.deleted', null, false ),
			'product resource with unrelated post ID' => array( 'post', 'product.deleted', null, false ),
			'product resource with missing ID'        => array( null, 'product.deleted', null, false ),
			'matching product with stale global'      => array( 'product', 'product.deleted', 'page', true ),
			'product resource with stale global'      => array( 'shop_coupon', 'product.deleted', 'product', false ),
		);
	}

	/**
	 * @testdox Post-action validation rejects a falsy ID even when a global product exists.
	 */
	public function test_is_valid_post_action_rejects_falsy_id_with_global_post(): void {
		$had_global_post = array_key_exists( 'post', $GLOBALS );
		$original_post   = $GLOBALS['post'] ?? null;
		$product_id      = $this->factory->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);
		$webhook         = new WC_Webhook();
		$webhook->set_topic( 'product.deleted' );

		try {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolates arbitrary global request state.
			$GLOBALS['post'] = get_post( $product_id );
			$this->assertFalse( $this->call_is_valid_post_action( $webhook, 0 ) );
		} finally {
			if ( $had_global_post ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the global request state changed for this test.
				$GLOBALS['post'] = $original_post;
			} else {
				unset( $GLOBALS['post'] );
			}
		}
	}

	/**
	 * @testdox Post-action validation honors the order-webhooks registry.
	 */
	public function test_is_valid_post_action_honors_order_webhook_registry(): void {
		global $wc_order_types;

		$order_type          = 'shop_webhook_test';
		$excluded_order_type = 'shop_no_webhook';
		$registered          = wc_register_order_type(
			$order_type,
			array(
				'exclude_from_order_webhooks' => false,
			)
		);
		$excluded_registered = wc_register_order_type(
			$excluded_order_type,
			array(
				'exclude_from_order_webhooks' => true,
			)
		);

		try {
			$this->assertSame( array( true, true ), array( $registered, $excluded_registered ), 'The test order types should be registered.' );
			$post_id          = $this->factory->post->create(
				array(
					'post_type'   => $order_type,
					'post_status' => 'publish',
				)
			);
			$excluded_post_id = $this->factory->post->create(
				array(
					'post_type'   => $excluded_order_type,
					'post_status' => 'publish',
				)
			);
			$webhook          = new WC_Webhook();
			$webhook->set_topic( 'order.deleted' );

			$this->assertTrue( $this->call_is_valid_post_action( $webhook, $post_id ) );
			$this->assertFalse( $this->call_is_valid_post_action( $webhook, $excluded_post_id ) );
		} finally {
			foreach ( array( $order_type, $excluded_order_type ) as $test_order_type ) {
				if ( post_type_exists( $test_order_type ) ) {
					unregister_post_type( $test_order_type );
				}
				unset( $wc_order_types[ $test_order_type ] );
			}
		}
	}

	/**
	 * @testdox Product deletion webhooks are delivered for a variable product and all of its variations.
	 */
	public function test_product_deletion_webhook_delivers_for_variable_product_variations(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$expected_ids  = array_merge( array( $product->get_id() ), $product->get_children() );
		$delivered_ids = array();
		$webhook       = $this->create_active_webhook( 'product.deleted' );

		remove_action( 'woocommerce_webhook_process_delivery', 'wc_webhook_process_delivery', 10 );
		add_action(
			'woocommerce_webhook_process_delivery',
			function ( $delivering_webhook, $arg ) use ( $webhook, &$delivered_ids ) {
				if ( $webhook === $delivering_webhook ) {
					$delivered_ids[] = $arg;
				}
			},
			10,
			2
		);
		$webhook->enqueue();

		wp_trash_post( $product->get_id() );

		sort( $expected_ids );
		sort( $delivered_ids );
		$this->assertSame( $expected_ids, $delivered_ids );
	}

	/**
	 * @testdox Product restoration webhooks are delivered for a variable product and all of its variations.
	 */
	public function test_product_restoration_webhook_delivers_for_variable_product_variations(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$expected_ids  = array_merge( array( $product->get_id() ), $product->get_children() );
		$delivered_ids = array();
		wp_trash_post( $product->get_id() );

		$webhook = $this->create_active_webhook( 'product.restored' );
		remove_action( 'woocommerce_webhook_process_delivery', 'wc_webhook_process_delivery', 10 );
		add_action(
			'woocommerce_webhook_process_delivery',
			function ( $delivering_webhook, $arg ) use ( $webhook, &$delivered_ids ) {
				if ( $webhook === $delivering_webhook ) {
					$delivered_ids[] = $arg;
				}
			},
			10,
			2
		);
		$webhook->enqueue();

		wp_untrash_post( $product->get_id() );

		sort( $expected_ids );
		sort( $delivered_ids );
		$this->assertSame( $expected_ids, $delivered_ids );
	}

	/**
	 * Create an active webhook for integration tests.
	 *
	 * @param string $topic Webhook topic.
	 * @return WC_Webhook
	 */
	private function create_active_webhook( string $topic ): WC_Webhook {
		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( $topic );
		$webhook->set_delivery_url( 'https://example.com/webhook' );

		return $webhook;
	}

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
