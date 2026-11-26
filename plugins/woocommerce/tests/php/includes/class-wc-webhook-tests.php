<?php
/**
 * Tests for WC_Webhook class.
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails\CustomerVerifyEmail;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Tests for WC_Webhook class.
 */
class WC_Webhook_Test extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Ensure permanent HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::setup_cot_tables();
	}

	/**
	 * Whether HPOS was in use before a test changed the order storage, or null if it did not.
	 *
	 * @var bool|null
	 */
	private $prev_hpos_enabled = null;

	/**
	 * Restore the order storage a test switched with use_order_storage().
	 */
	public function tearDown(): void {
		global $wc_queued_webhooks;

		$wc_queued_webhooks = null;
		try {
			if ( null !== $this->prev_hpos_enabled ) {
				$this->toggle_cot_authoritative( $this->prev_hpos_enabled );
				$this->prev_hpos_enabled = null;
			}
		} finally {
			parent::tearDown();
		}
	}

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
	 * @testdox Variation-delete validation rejects a non-ID argument even when the global post is a variation.
	 *
	 * @testWith [0]
	 *           ["abc"]
	 *
	 * @param mixed $arg Hook argument that is not a valid post ID.
	 */
	public function test_is_valid_variation_delete_action_rejects_non_id_with_global_variation( $arg ): void {
		$had_global_post = array_key_exists( 'post', $GLOBALS );
		$original_post   = $GLOBALS['post'] ?? null;
		$product         = WC_Helper_Product::create_variation_product();
		$webhook         = new WC_Webhook();
		$webhook->set_topic( 'product.deleted' );
		$call_is_valid_variation_delete_action = function ( $arg ) {
			return $this->is_valid_variation_delete_action( $arg );
		};

		try {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Test isolates arbitrary global request state.
			$GLOBALS['post'] = get_post( $product->get_children()[0] );
			$this->assertFalse( $call_is_valid_variation_delete_action->call( $webhook, $arg ) );
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
	 * @testdox Post-action validation rejects non-ID values before coercion.
	 */
	public function test_is_valid_post_action_rejects_non_id_values_before_coercion(): void {
		$post_id = $this->factory->post->create(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
			)
		);
		$webhook = new WC_Webhook();
		$webhook->set_topic( 'product.deleted' );

		$cached_post_at_one = wp_cache_get( 1, 'posts', false, $had_cached_post_at_one );
		$post_at_one        = clone get_post( $post_id );
		$post_at_one->ID    = 1;
		wp_cache_set( 1, $post_at_one, 'posts' );

		try {
			$this->assertTrue( $this->call_is_valid_post_action( $webhook, (string) $post_id ) );

			$invalid_ids = array(
				'boolean'             => true,
				'array'               => array( $post_id ),
				'float'               => (float) $post_id,
				'negative integer'    => -$post_id,
				'decimal-like string' => $post_id . '.5',
			);

			foreach ( $invalid_ids as $description => $invalid_id ) {
				$this->assertFalse(
					$this->call_is_valid_post_action( $webhook, $invalid_id ),
					"A {$description} should not be treated as a post ID."
				);
			}
		} finally {
			if ( $had_cached_post_at_one ) {
				wp_cache_set( 1, $cached_post_at_one, 'posts' );
			} else {
				wp_cache_delete( 1, 'posts' );
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
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

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
		$this->record_deliveries( $this->create_active_webhook( 'product.restored' ), $delivered_ids );

		wp_untrash_post( $product->get_id() );

		sort( $expected_ids );
		sort( $delivered_ids );
		$this->assertSame( $expected_ids, $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are delivered when a variation is permanently deleted.
	 */
	public function test_product_deletion_webhook_delivers_for_permanently_deleted_variation(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_id  = $product->get_children()[0];
		$delivered_ids = array();
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		wc_get_product( $variation_id )->delete( true );

		$this->assertSame( array( $variation_id ), $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are delivered for the variations removed when a variable product changes type.
	 */
	public function test_product_deletion_webhook_delivers_for_variations_removed_by_type_change(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$expected_ids  = $product->get_children();
		$delivered_ids = array();
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		( new WC_Product_Simple( $product->get_id() ) )->save();

		sort( $expected_ids );
		sort( $delivered_ids );
		$this->assertSame( $expected_ids, $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are not delivered again when a trashed variable product is permanently deleted.
	 */
	public function test_product_deletion_webhook_skips_already_trashed_variations_of_deleted_product(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$delivered_ids = array();
		wp_trash_post( $product->get_id() );
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		wp_delete_post( $product->get_id(), true );

		$this->assertSame( array(), $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are not delivered again when a trashed variation is permanently deleted.
	 */
	public function test_product_deletion_webhook_skips_already_trashed_variation(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_id  = $product->get_children()[0];
		$delivered_ids = array();
		wc_get_product( $variation_id )->delete();
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		wc_get_product( $variation_id )->delete( true );

		$this->assertSame( array(), $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are delivered for a variation moved to trash without wp_trash_post().
	 */
	public function test_product_deletion_webhook_delivers_for_variation_trashed_without_wp_trash_post(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_id  = $product->get_children()[0];
		$variation     = wc_get_product( $variation_id );
		$delivered_ids = array();
		$variation->set_status( ProductStatus::TRASH );
		$variation->save();
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		$variation->delete( true );

		$this->assertSame( array( $variation_id ), $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are not delivered when a pre_delete_post filter cancels the variation deletion.
	 */
	public function test_product_deletion_webhook_skips_variation_deletion_cancelled_by_pre_delete_post(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_id  = $product->get_children()[0];
		$delivered_ids = array();
		add_filter(
			'pre_delete_post',
			function ( $check, $post ) use ( $variation_id ) {
				return $variation_id === $post->ID ? false : $check;
			},
			10,
			2
		);
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		wc_get_product( $variation_id )->delete( true );

		$this->assertNotNull( get_post( $variation_id ), 'The filter should have kept the variation' );
		$this->assertSame( array(), $delivered_ids );
	}

	/**
	 * @testdox Product deletion webhooks are not delivered when a post that is not a variation is permanently deleted.
	 */
	public function test_product_deletion_webhook_skips_permanently_deleted_non_variation_post(): void {
		$post_id       = $this->factory->post->create();
		$delivered_ids = array();
		$this->record_deliveries( $this->create_active_webhook( 'product.deleted' ), $delivered_ids );

		wp_delete_post( $post_id, true );

		$this->assertSame( array(), $delivered_ids );
	}

	/**
	 * @testdox Non-product webhooks that add before_delete_post by filter still deliver for trashed posts.
	 */
	public function test_non_product_webhook_on_before_delete_post_delivers_for_trashed_post(): void {
		$coupon_id     = WC_Helper_Coupon::create_coupon()->get_id();
		$delivered_ids = array();
		wp_trash_post( $coupon_id );
		add_filter(
			'woocommerce_webhook_topic_hooks',
			function ( $topic_hooks ) {
				$topic_hooks['coupon.deleted'][] = 'before_delete_post';
				return $topic_hooks;
			}
		);
		$this->record_deliveries( $this->create_active_webhook( 'coupon.deleted' ), $delivered_ids );

		wp_delete_post( $coupon_id, true );

		$this->assertSame( array( $coupon_id ), $delivered_ids, 'The variation-only check should not apply to coupon webhooks' );
	}

	/**
	 * @testdox Order updated webhooks are not delivered while the order is a checkout draft.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether orders are stored in the HPOS tables.
	 */
	public function test_order_updated_webhook_skips_checkout_draft_orders( bool $hpos_enabled ): void {
		$this->use_order_storage( $hpos_enabled );
		$order = new WC_Order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();
		$updated_ids = array();
		$created_ids = array();
		$this->record_deliveries( $this->create_active_webhook( 'order.updated' ), $updated_ids );
		$this->record_deliveries( $this->create_active_webhook( 'order.created' ), $created_ids );

		$order->set_billing_email( 'customer@example.com' );
		$order->save();
		$order->update_status( OrderStatus::PENDING );

		$this->assertSame( array(), $updated_ids, 'Saving a checkout draft should not deliver order.updated' );
		$this->assertSame( array( $order->get_id() ), $created_ids, 'Leaving checkout draft should deliver order.created' );
	}

	/**
	 * @testdox Order updated webhooks skipped for a checkout draft are still delivered once the order leaves draft.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_enabled Whether orders are stored in the HPOS tables.
	 */
	public function test_order_updated_webhook_delivers_after_checkout_draft( bool $hpos_enabled ): void {
		$this->use_order_storage( $hpos_enabled );
		$order = new WC_Order();
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();
		$updated_ids = array();
		$this->record_deliveries( $this->create_active_webhook( 'order.updated' ), $updated_ids );

		$order->set_billing_email( 'draft@example.com' );
		$order->save();
		$order->update_status( OrderStatus::PENDING );
		$order->set_billing_email( 'customer@example.com' );
		$order->save();

		$this->assertSame( array( $order->get_id() ), $updated_ids, 'The first save after leaving draft should deliver order.updated once' );
	}

	/**
	 * Store orders in the HPOS tables or in posts for the current test.
	 *
	 * @param bool $hpos_enabled Whether orders are stored in the HPOS tables.
	 */
	private function use_order_storage( bool $hpos_enabled ): void {
		if ( null === $this->prev_hpos_enabled ) {
			$this->prev_hpos_enabled = OrderUtil::custom_orders_table_usage_is_enabled();
		}
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->toggle_cot_authoritative( $hpos_enabled );
	}

	/**
	 * Enqueue a webhook and record the resource IDs it would deliver, without sending them.
	 *
	 * @param WC_Webhook   $webhook       Webhook to enqueue.
	 * @param array<mixed> $delivered_ids Receives each delivered resource ID.
	 */
	private function record_deliveries( WC_Webhook $webhook, array &$delivered_ids ): void {
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
	}

	/**
	 * @testdox Deleting a refund delivers an order.updated webhook with the parent order payload.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $hpos_on_or_off True to test with HPOS on, false to test with HPOS off.
	 */
	public function test_refund_deletion_delivers_order_updated_webhook( bool $hpos_on_or_off ): void {
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$this->toggle_cot_authoritative( $hpos_on_or_off );

		$order  = WC_Helper_Order::create_order();
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 1,
				'refund_payment' => false,
			)
		);

		$this->assertInstanceOf( WC_Order_Refund::class, $refund, 'The refund fixture should be created.' );
		$refund_id = $refund->get_id();

		$payloads = array();
		$webhook  = $this->create_active_webhook( 'order.updated' );
		$webhook->set_user_id( 1 );

		remove_action( 'woocommerce_webhook_process_delivery', 'wc_webhook_process_delivery', 10 );
		add_action(
			'woocommerce_webhook_process_delivery',
			function ( $delivering_webhook, $arg ) use ( $webhook, &$payloads ) {
				if ( $webhook === $delivering_webhook ) {
					$payloads[] = $delivering_webhook->build_payload( $arg );
				}
			},
			10,
			2
		);
		$webhook->enqueue();

		$refund->delete( true );

		$this->assertCount( 1, $payloads, 'The refund deletion should queue one webhook payload.' );
		$this->assertArrayNotHasKey( 'code', $payloads[0], 'The payload should not contain a REST API error.' );
		$this->assertArrayHasKey( 'id', $payloads[0] );
		$this->assertSame( $order->get_id(), $payloads[0]['id'], 'The payload should represent the parent order.' );
		$this->assertArrayHasKey( 'refunds', $payloads[0] );
		$this->assertNotContains( $refund_id, wp_list_pluck( $payloads[0]['refunds'], 'id' ), 'The payload should not contain the deleted refund.' );
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
	 * @testdox A successful delivery clears the pending ping, so no ping follows it.
	 */
	public function test_successful_delivery_clears_pending_ping(): void {
		$product = WC_Helper_Product::create_simple_product();
		$webhook = $this->create_active_webhook( 'product.updated' );
		$webhook->set_user_id( 1 );
		$webhook->save();

		$requests = array();
		add_filter(
			'pre_http_request',
			function ( $preempt, $args ) use ( &$requests ) {
				$is_ping    = 0 === strpos( (string) $args['body'], 'webhook_id=' );
				$requests[] = $is_ping ? 'ping' : 'delivery';

				// Like an endpoint that verifies signatures: the unsigned ping is rejected, the delivery accepted.
				return $this->mock_http_response( $is_ping ? 401 : 200 );
			},
			10,
			2
		);

		$webhook->deliver( $product->get_id() );

		$this->assertSame( array( 'delivery' ), $requests, 'A successful delivery should not be followed by a ping.' );
		$this->assertFalse( wc_get_webhook( $webhook->get_id() )->get_pending_delivery(), 'A successful delivery should clear the pending ping.' );
	}

	/**
	 * @testdox The ping counts any 2xx response as success, like deliveries do.
	 *
	 * @testWith [200, true]
	 *           [202, true]
	 *           [204, true]
	 *           [302, false]
	 *           [401, false]
	 *           [500, false]
	 *
	 * @param int  $response_code  HTTP status code the delivery URL answers with.
	 * @param bool $expect_success Whether the ping should succeed.
	 */
	public function test_ping_succeeds_on_any_2xx_response( int $response_code, bool $expect_success ): void {
		$webhook = $this->create_active_webhook( 'product.updated' );
		$webhook->save();

		add_filter(
			'pre_http_request',
			function () use ( $response_code ) {
				return $this->mock_http_response( $response_code );
			}
		);

		$result = $webhook->deliver_ping();

		if ( $expect_success ) {
			$this->assertTrue( $result, "A ping answered with {$response_code} should succeed." );
		} else {
			$this->assertInstanceOf( WP_Error::class, $result, "A ping answered with {$response_code} should fail with an error." );
		}
		$this->assertSame( ! $expect_success, wc_get_webhook( $webhook->get_id() )->get_pending_delivery(), 'Only a successful ping should clear the pending ping.' );
	}

	/**
	 * @testdox Checkout form internals are removed before persistence and before sending, while the local hook value is preserved.
	 *
	 * @dataProvider checkout_form_arguments
	 * @param string $hook Checkout hook.
	 * @param mixed  $argument The first hook argument.
	 */
	public function test_checkout_form_internals_are_redacted( string $hook, $argument ): void {
		$sut = $this->create_active_webhook( 'action.' . $hook . '.suffix' );
		$sut->save();
		$sut->enqueue();

		if ( 'woocommerce_after_checkout_validation' === $hook ) {
			do_action( $hook, $argument, new WP_Error() );
		} elseif ( 'woocommerce_checkout_update_order_review' === $hook ) {
			do_action( $hook, $argument );
		} else {
			$this->assertSame( $argument, apply_filters( $hook, $argument ) );
		}
		$body = $this->run_scheduled_webhook_delivery( $sut, 'fixture-password' );
		$this->assertStringContainsString( '[redacted]', $body );
		if ( 'woocommerce_process_checkout_field_account_password' !== $hook && 'woocommerce_process_checkout_password_field' !== $hook ) {
			$this->assertStringContainsString( 'fixture-city', $body );
		}
	}

	/**
	 * @testdox Queue and payload redaction use the topic supplied by the topic filter.
	 */
	public function test_redaction_uses_filtered_topic(): void {
		$sut = $this->create_active_webhook( 'action.woocommerce_add_to_cart' );
		$sut->save();
		$hook = 'woocommerce_process_checkout_field_account_password';
		$id   = $sut->get_id();
		add_filter( 'woocommerce_webhook_topic', static fn( $topic, $webhook_id ) => $id === $webhook_id ? 'action.' . $hook : $topic, 10, 2 );
		$sut->enqueue();

		$this->assertSame( 'fixture-password', apply_filters( $hook, 'fixture-password' ), 'The checkout filter should keep the original password.' );
		$this->assertSame( '[redacted]', $sut->build_payload( 'fixture-password' )['arg'], 'Payload redaction must also work for immediate delivery.' );
		$this->assertStringContainsString( '[redacted]', $this->run_scheduled_webhook_delivery( $sut, 'fixture-password' ) );
	}

	/**
	 * @testdox Plugin fields are masked alongside built-in fields before queueing and delivery.
	 */
	public function test_redacts_additional_fields_for_a_custom_hook(): void {
		$hook = 'wc_webhook_redaction_fixture';
		add_filter( 'woocommerce_webhook_redact_additional_fields', static fn( $fields, $source_hook ) => $hook === $source_hook ? array_merge( $fields, array( 'Gateway_Key' ) ) : $fields, 10, 2 );

		$sut = $this->create_active_webhook( 'action.' . $hook );
		$sut->save();
		$sut->enqueue();
		$argument = array(
			'gateway'  => array( 'gateway_key' => 'fixture-private' ),
			'secret'   => 'fixture-private',
			'currency' => 'USD',
		);

		$this->assertSame( $argument, apply_filters( $hook, $argument ) );
		$body = json_decode( $this->run_scheduled_webhook_delivery( $sut, 'fixture-private' ), true );
		$this->assertSame( '[redacted]', $body['arg']['gateway']['gateway_key'] );
		$this->assertSame( '[redacted]', $body['arg']['secret'] );
		$this->assertSame( 'USD', $body['arg']['currency'] );
	}

	/**
	 * @testdox Plugin fields in form data are masked while the format and unrelated values are preserved.
	 */
	public function test_redacts_additional_form_fields(): void {
		add_filter( 'woocommerce_webhook_redact_additional_fields', static fn() => array( 'gateway_key' ) );
		$sut     = $this->create_active_webhook( 'action.woocommerce_checkout_update_order_review' );
		$payload = $sut->build_payload( '?currency=USD&&Gateway%5FKey=fixture-private&gateway_key=&flag&note=a%3Db' );

		$this->assertSame( '?currency=USD&&Gateway%5FKey=[redacted]&gateway_key=&flag&note=a%3Db', $payload['arg'] );
	}

	/**
	 * Provide the checkout hooks that receive a password in their first argument.
	 *
	 * @return array
	 */
	public function checkout_form_arguments(): array {
		$posted = array(
			'account_password' => 'fixture-password',
			'billing_city'     => 'fixture-city',
		);

		return array(
			'before validation' => array( 'woocommerce_after_checkout_validation', $posted ),
			'posted data'       => array( 'woocommerce_checkout_posted_data', $posted ),
			'specific field'    => array( 'woocommerce_process_checkout_field_account_password', 'fixture-password' ),
			'password type'     => array( 'woocommerce_process_checkout_password_field', 'fixture-password' ),
			'serialized review' => array( 'woocommerce_checkout_update_order_review', 'billing_city=fixture-city&account%5Fpassword=fixture-password' ),
		);
	}

	/**
	 * @testdox An HTML email footer webhook keeps the email identity but leaves out its internal properties.
	 *
	 * @dataProvider email_footer_internal_properties
	 * @param string $email_class Email class.
	 * @param string $property    Internal property.
	 * @param string $email_id    Expected public email ID.
	 * @phpstan-param class-string<WC_Email> $email_class
	 */
	public function test_email_footer_leaves_out_internal_properties( string $email_class, string $property, string $email_id ): void {
		$sut = $this->create_active_webhook( 'action.woocommerce_email_footer' );
		$sut->save();
		$sut->enqueue();

		$user             = self::factory()->user->create_and_get( array( 'role' => 'administrator' ) );
		$email            = new $email_class();
		$email->$property = 'fixture-email-value';
		$email->object    = $user;
		do_action( 'woocommerce_email_footer', $email );

		$this->assertSame( 'fixture-email-value', $email->$property, 'The live email must retain its value.' );
		$body = $this->run_scheduled_webhook_delivery( $sut, 'fixture-email-value' );
		$this->assertStringNotContainsString( $user->user_pass, $body );
		$this->assertStringContainsString( $email_id, $body );
	}

	/**
	 * Email objects with public internal properties.
	 *
	 * @return array
	 */
	public function email_footer_internal_properties(): array {
		return array(
			'password reset'     => array( WC_Email_Customer_Reset_Password::class, 'reset_key', 'customer_reset_password' ),
			'account setup'      => array( WC_Email_Customer_New_Account::class, 'set_password_url', 'customer_new_account' ),
			'email verification' => array( CustomerVerifyEmail::class, 'verify_url', 'customer_verify_email' ),
		);
	}

	/**
	 * @testdox An ordinary custom action argument with a key query parameter is preserved.
	 */
	public function test_custom_action_keeps_ordinary_url(): void {
		$sut = $this->create_active_webhook( 'action.woocommerce_form_field_url' );
		$url = 'https://example.com/catalog?key=blue-widget';

		$this->assertSame( $url, $sut->build_payload( $url )['arg'] );
	}

	/**
	 * @testdox Immediate delivery redacts the HTTP body while existing local filters keep the original argument.
	 */
	public function test_immediate_webhook_preserves_local_argument(): void {
		global $wc_queued_webhooks;

		$sut = $this->create_active_webhook( 'action.woocommerce_checkout_posted_data' );
		$sut->save();
		$sut->enqueue();

		$original_arg = array( 'account_password' => 'fixture-password' );
		$received_arg = null;
		$body         = '';
		add_filter( 'woocommerce_webhook_deliver_async', '__return_false' );
		add_filter(
			'woocommerce_webhook_http_args',
			static function ( $args, $arg ) use ( &$received_arg ) {
				$received_arg = $arg;
				return $args;
			},
			10,
			2
		);
		add_filter(
			'pre_http_request',
			function ( $preempt, $args ) use ( &$body ) {
				$body = $args['body'];
				return $this->mock_http_response( 200 );
			},
			10,
			2
		);

		apply_filters( 'woocommerce_checkout_posted_data', $original_arg );
		wc_webhook_execute_queue();
		$wc_queued_webhooks = null;

		$this->assertSame( $original_arg, $received_arg );
		$this->assertStringContainsString( '[redacted]', $body );
		$this->assertStringNotContainsString( 'fixture-password', $body );
	}

	/**
	 * @testdox A queued delivery of an already stored blocked topic sends no HTTP request.
	 * @testWith ["woocommerce_login_credentials"]
	 *           ["woocommerce_paypal_capture_request"]
	 *           ["woocommerce_paypal_refund_request"]
	 * @param string $hook The blocked hook.
	 */
	public function test_worker_skips_previously_allowed_topic( string $hook ): void {
		global $wpdb;

		$sut = $this->create_active_webhook( 'action.woocommerce_add_to_cart' );
		$sut->save();
		$wpdb->update( $wpdb->prefix . 'wc_webhooks', array( 'topic' => 'action.' . $hook . '.extra' ), array( 'webhook_id' => $sut->get_id() ) );
		wp_cache_delete( $sut->get_id(), 'webhooks' );

		add_filter(
			'pre_http_request',
			function () {
				$this->fail( 'A worker must not send a delivery for a refused topic.' );
			}
		);
		wc_deliver_webhook_async( $sut->get_id(), array( 'password' => 'fixture-password' ) );

		$this->assertSame( '', wc_get_webhook( $sut->get_id() )->get_topic() );
	}

	/**
	 * @testdox A remapped PayPal request filter keeps its arguments local.
	 * @testWith ["woocommerce_paypal_capture_request"]
	 *           ["woocommerce_paypal_refund_request"]
	 * @param string $hook The blocked PayPal hook.
	 */
	public function test_blocked_paypal_hook_does_not_queue_delivery( string $hook ): void {
		global $wc_queued_webhooks;

		$sut = $this->create_active_webhook( 'action.woocommerce_add_to_cart' );
		$id  = $sut->get_id();
		add_filter( 'woocommerce_webhook_hooks', static fn( $hooks, $webhook_id ) => $id === $webhook_id ? array( $hook ) : $hooks, 10, 2 );
		$sut->enqueue();

		$request = array(
			'USER'      => 'fixture-user',
			'PWD'       => 'fixture-password',
			'SIGNATURE' => 'fixture-signature',
		);
		$this->assertSame( $request, apply_filters( $hook, $request ), 'The PayPal request must keep its values.' );
		$this->assertEmpty( $wc_queued_webhooks, 'A blocked hook must not schedule a webhook even if an extension changes the subscription.' );
	}

	/**
	 * @testdox Resource payloads retain order keys and support the product objects used by bulk edits.
	 */
	public function test_standard_resource_payloads_keep_existing_values(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$order = wc_create_order();
		$sut   = $this->create_active_webhook( 'order.created' );
		$sut->set_user_id( get_current_user_id() );
		$order_payload = $sut->build_payload( $order->get_id() );
		$this->assertSame( $order->get_order_key(), $order_payload['order_key'] );

		$product = WC_Helper_Product::create_simple_product();
		$sut->set_topic( 'product.updated' );
		$product_payload = $sut->build_payload( $product );
		$this->assertSame( $product->get_id(), $product_payload['id'] );
	}

	/**
	 * Persist a queued webhook and run the worker with the stored arguments.
	 *
	 * @param WC_Webhook $webhook Scheduled webhook.
	 * @param string     $hidden_value Value that the stored and sent copies should leave out.
	 * @return string
	 */
	private function run_scheduled_webhook_delivery( WC_Webhook $webhook, string $hidden_value ): string {
		global $wc_queued_webhooks;

		wc_webhook_execute_queue();
		$wc_queued_webhooks = null;

		$actions = as_get_scheduled_actions(
			array(
				'hook'   => 'woocommerce_deliver_webhook_async',
				'group'  => 'woocommerce-webhooks',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			)
		);
		$this->assertCount( 1, $actions, 'Exactly one delivery must be stored.' );
		$args = reset( $actions )->get_args();
		$this->assertSame( $webhook->get_id(), $args['webhook_id'] );
		$this->assertStringNotContainsString( $hidden_value, wp_json_encode( $args ) );

		$body = '';
		add_filter(
			'pre_http_request',
			function ( $preempt, $request ) use ( &$body ) {
				$body = $request['body'];
				return $this->mock_http_response( 200 );
			},
			10,
			2
		);
		wc_deliver_webhook_async( $args['webhook_id'], $args['arg'] );

		$this->assertNotSame( '', $body, 'The worker should send the scheduled delivery.' );
		$this->assertStringNotContainsString( $hidden_value, $body );

		return $body;
	}

	/**
	 * @testdox Two identical checkbox fields render even when the second webhook delivery is skipped.
	 */
	public function test_duplicate_webhook_keeps_rendered_checkbox(): void {
		global $wc_queued_webhooks;

		$sut = $this->create_active_webhook( 'action.woocommerce_form_field_checkbox' );
		$sut->enqueue();

		$args   = array(
			'type'   => 'checkbox',
			'return' => true,
		);
		$first  = woocommerce_form_field( 'fixture_checkbox', $args );
		$second = woocommerce_form_field( 'fixture_checkbox', $args );

		$this->assertStringContainsString( 'type="checkbox"', $first );
		$this->assertSame( $first, $second );
		$this->assertCount( 1, $wc_queued_webhooks );
		$wc_queued_webhooks = null;
	}

	/**
	 * @testdox Capture named mail callbacks and keep callbacks that contain objects out of the queue.
	 */
	public function test_mail_webhook_captures_only_named_callbacks(): void {
		global $wc_queued_webhooks;

		$hook = 'woocommerce_mail_callback';
		$sut  = $this->create_active_webhook( 'action.' . $hook );
		$sut->enqueue();

		$closure = static function (): bool {
			return true;
		};
		foreach ( array( $closure, array( $sut, 'get_name' ) ) as $callback ) {
			$this->assertSame( $callback, apply_filters( $hook, $callback ), 'The mail filter must keep the original callback.' );
			$this->assertEmpty( $wc_queued_webhooks, 'The webhook must not queue a callback that contains an object.' );
		}

		$named_callbacks = array( 'wp_mail', array( 'WC_Cache_Helper', 'get_transient_version' ) );
		foreach ( $named_callbacks as $callback ) {
			$this->assertSame( $callback, apply_filters( $hook, $callback ) );
		}
		$this->assertSame( $named_callbacks, array_column( $wc_queued_webhooks, 'arg' ) );
	}

	/**
	 * @testdox Two log subscribers deliver one ordinary line each and ignore delivery logs.
	 */
	public function test_log_webhooks_do_not_forward_delivery_logs(): void {
		global $wc_queued_webhooks;

		update_option( 'woocommerce_logs_logging_enabled', 'yes' );
		update_option( 'woocommerce_logs_level_threshold', 'none' );
		add_filter(
			'woocommerce_register_log_handlers',
			static function () {
				return array(
					new class() extends LogHandlerFileV2 {
						/**
						 * Run the real formatter without writing a log file.
						 *
						 * @param int    $timestamp Log timestamp.
						 * @param string $level Log level.
						 * @param string $message Log message.
						 * @param array  $context Log context.
						 * @return bool
						 */
						public function handle( $timestamp, $level, $message, $context ) {
							self::format_entry( $timestamp, $level, $message, $context );
							return true;
						}
					},
				);
			}
		);

		$formatter = $this->create_active_webhook( 'action.woocommerce_format_log_entry' );
		$messages  = $this->create_active_webhook( 'action.woocommerce_logger_log_message' );
		foreach ( array( $formatter, $messages ) as $sut ) {
			$sut->save();
			$sut->enqueue();
		}

		$delivery_logs = 0;
		add_filter(
			'woocommerce_format_log_entry',
			static function ( $entry, $details ) use ( &$delivery_logs ) {
				if ( 'webhooks-delivery' === ( $details['context']['source'] ?? '' ) ) {
					++$delivery_logs;
				}
				return $entry;
			},
			20,
			2
		);
		wc_get_logger()->info( 'Ordinary log entry for webhook test', array( 'source' => 'webhook-forwarding-test' ) );
		$this->assertCount( 2, $wc_queued_webhooks );
		wc_webhook_execute_queue();
		$wc_queued_webhooks = null;

		$actions = as_get_scheduled_actions(
			array(
				'hook'   => 'woocommerce_deliver_webhook_async',
				'group'  => 'woocommerce-webhooks',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			)
		);
		$this->assertCount( 2, $actions );
		$bodies = array();
		add_filter(
			'pre_http_request',
			function ( $preempt, $request ) use ( &$bodies ) {
				$bodies[ $request['headers']['X-WC-Webhook-ID'] ][] = $request['body'];
				return $this->mock_http_response( 200 );
			},
			10,
			2
		);

		foreach ( $actions as $action ) {
			$args = $action->get_args();
			wc_deliver_webhook_async( $args['webhook_id'], $args['arg'] );
			$this->assertEmpty( $wc_queued_webhooks, 'The worker must not capture the resulting delivery log.' );
		}

		foreach ( array( $formatter, $messages ) as $sut ) {
			$this->assertArrayHasKey( $sut->get_id(), $bodies );
			$this->assertCount( 1, $bodies[ $sut->get_id() ] );
			$this->assertStringContainsString( 'Ordinary log entry for webhook test', $bodies[ $sut->get_id() ][0] );
		}
		$ordinary = $this->create_active_webhook( 'action.woocommerce_add_to_cart' );
		$ordinary->save();
		wc_deliver_webhook_async( $ordinary->get_id(), 123 );
		$this->assertEmpty( $wc_queued_webhooks, 'Delivery logs from ordinary webhooks must also stay local.' );
		$this->assertSame( 3, $delivery_logs, 'Delivery logs still reach the local log handler.' );
	}

	/**
	 * @testdox Saving a webhook is observable, but delivering the observer does not queue another observer.
	 * @testWith [false]
	 *           [true]
	 * @param bool $remap_topic Whether to set the observer topic through a filter.
	 */
	public function test_webhook_observer_does_not_observe_its_own_worker( bool $remap_topic ): void {
		global $wc_queued_webhooks;

		$updates  = $this->create_active_webhook( $remap_topic ? 'action.woocommerce_add_to_cart' : 'action.woocommerce_webhook_updated' );
		$statuses = $this->create_active_webhook( 'action.woocommerce_webhook_statuses' );
		$source   = $this->create_active_webhook( 'action.woocommerce_add_to_cart' );
		$source->save();
		// Creation marks the initial ping as pending.
		$source->set_pending_delivery( false );
		foreach ( array( $updates, $statuses ) as $sut ) {
			$sut->save();
		}
		if ( $remap_topic ) {
			$id = $updates->get_id();
			add_filter( 'woocommerce_webhook_topic', static fn( $topic, $webhook_id ) => $id === $webhook_id ? 'action.woocommerce_webhook_updated' : $topic, 10, 2 );
		}
		$updates->enqueue();
		$statuses->enqueue();

		$source->set_name( 'Edited webhook' );
		$source->save();
		$this->assertCount( 1, $wc_queued_webhooks );
		$this->assertSame( $updates, $wc_queued_webhooks[0]['webhook'] );
		$this->assertSame( $source->get_id(), $wc_queued_webhooks[0]['arg'] );
		$wc_queued_webhooks = null;

		add_filter(
			'pre_http_request',
			function () {
				return $this->mock_http_response( 200 );
			}
		);
		wc_deliver_webhook_async( $updates->get_id(), $source->get_id() );
		$this->assertEmpty( $wc_queued_webhooks, 'Neither the load nor the save may start another observer delivery.' );
	}

	/**
	 * Build a pre_http_request response with the given status code.
	 *
	 * @param int $response_code HTTP status code.
	 * @return array
	 */
	private function mock_http_response( int $response_code ): array {
		return array(
			'response' => array(
				'code'    => $response_code,
				'message' => get_status_header_desc( $response_code ),
			),
			'headers'  => array(),
			'body'     => '',
		);
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
	}

	/**
	 * @testdox customer.deleted accepts users of any role, and rejects user IDs that don't exist.
	 */
	public function test_is_valid_user_action_accepts_any_role(): void {
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$customer_id   = $this->factory->user->create( array( 'role' => 'customer' ) );

		$webhook  = new WC_Webhook();
		$is_valid = function ( $arg ) {
			return $this->is_valid_user_action( $arg );
		};

		$this->assertTrue( $is_valid->call( $webhook, $subscriber_id ) );
		$this->assertTrue( $is_valid->call( $webhook, $customer_id ) );
		$this->assertTrue( $is_valid->call( $webhook, (string) $subscriber_id ) );
		$this->assertFalse( $is_valid->call( $webhook, -$subscriber_id ) );
		$this->assertFalse( $is_valid->call( $webhook, (string) -$subscriber_id ) );
		$this->assertFalse( $is_valid->call( $webhook, $subscriber_id + 0.5 ) );
		$this->assertFalse( $is_valid->call( $webhook, PHP_INT_MAX ) );
	}

	/**
	 * @testdox customer.deleted listens to users being removed from a site, which is how multisite deletes users.
	 */
	public function test_customer_deleted_topic_includes_remove_user_from_blog(): void {
		$topic_hooks = WC_Webhook::get_default_topic_hooks();

		$this->assertContains( 'remove_user_from_blog', $topic_hooks['customer.deleted'] );
	}

	/**
	 * @testdox customer.deleted skips users deleted from or removed from a site other than the webhook's own.
	 */
	public function test_is_valid_user_action_rejects_other_sites(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$webhook = $this->create_active_webhook( 'customer.deleted' );
		$webhook->enqueue();

		$is_valid = function ( $arg ) {
			return $this->is_valid_user_action( $arg );
		};
		$this->assertTrue( $is_valid->call( $webhook, $user_id ) );

		$set_blog_id = function ( int $blog_id ) {
			$this->enqueued_blog_id = $blog_id;
		};
		$set_blog_id->call( $webhook, get_current_blog_id() + 1 );
		$this->assertFalse( $is_valid->call( $webhook, $user_id ) );
	}

	/**
	 * @testdox Deleting a subscriber delivers customer.deleted exactly once, even on multisite where both user hooks fire.
	 */
	public function test_deleting_a_user_delivers_customer_deleted_once(): void {
		$user_id       = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$delivered_ids = array();
		$this->record_deliveries( $this->create_active_webhook( 'customer.deleted' ), $delivered_ids );

		wp_delete_user( $user_id );

		$this->assertSame( array( $user_id ), $delivered_ids );
	}

	/**
	 * @testdox Removing a site member directly only delivers customer.deleted to that site's webhook.
	 */
	public function test_removing_a_user_from_a_site_delivers_customer_deleted(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$site_id       = get_current_blog_id();
		$other_site_id = $this->factory->blog->create();
		$user_id       = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$non_member_id = $this->factory->user->create( array( 'role' => '' ) );
		remove_user_from_blog( $non_member_id, $site_id );
		$this->assertFalse( is_user_member_of_blog( $non_member_id, $site_id ) );
		add_user_to_blog( $other_site_id, $user_id, 'subscriber' );

		$other_deliveries = array();
		switch_to_blog( $other_site_id );
		try {
			$this->record_deliveries( $this->create_active_webhook( 'customer.deleted' ), $other_deliveries );
		} finally {
			restore_current_blog();
		}

		$deliveries = array();
		$this->record_deliveries( $this->create_active_webhook( 'customer.deleted' ), $deliveries );

		remove_user_from_blog( -$user_id, $site_id );
		remove_user_from_blog( $non_member_id, $site_id );
		$this->assertSame( array(), $deliveries, 'Invalid IDs and non-members must not trigger delivery.' );

		$this->assertTrue( remove_user_from_blog( $user_id, $site_id ) );
		$this->assertSame( array( $user_id ), $deliveries );
		$this->assertSame( array(), $other_deliveries, 'A webhook registered on another site must not deliver.' );
		$this->assertTrue( is_user_member_of_blog( $user_id, $other_site_id ) );
	}

	/**
	 * @testdox A class that overrides the saving delivery methods keeps working through them, and the stage methods refuse it.
	 */
	public function test_stage_delivery_methods_refuse_a_class_that_has_not_adopted_them() {
		// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found
		$webhook = new class() extends WC_Webhook {
			public function log_delivery( $delivery_id, $request, $response, $duration ) {
				parent::log_delivery( $delivery_id, $request, $response, $duration );
			}
			public function deliver_ping() {
				return parent::deliver_ping();
			}
		};
		// phpcs:enable Squiz.Commenting.FunctionComment.Missing, Generic.CodeAnalysis.UselessOverridingMethod.Found
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_failure_count( 0 );
		$webhook->save();

		// Saving an active webhook with a pending delivery pings its URL; answer locally.
		$this->http_responder = fn() => $this->mock_http_response( 200 );

		$response = array(
			'response' => array(
				'code'    => 500,
				'message' => 'Internal Server Error',
			),
			'headers'  => array(),
			'body'     => '',
		);
		$request  = array(
			'method'     => 'POST',
			'headers'    => array(),
			'body'       => '',
			'user-agent' => 'test',
		);
		$webhook->log_delivery( 'delivery', $request, $response, 0.1 );
		$this->assertSame( 1, wc_get_webhook( $webhook->get_id() )->get_failure_count() );

		$http = static fn() => array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'headers'  => array(),
			'body'     => '',
		);
		add_filter( 'pre_http_request', $http );
		$this->assertTrue( $webhook->deliver_ping() );
		remove_filter( 'pre_http_request', $http );

		$calls = array(
			'stage_delivery_result' => array( 500 ),
			'stage_delivery_ping'   => array(),
		);
		foreach ( $calls as $stage_method => $args ) {
			try {
				$webhook->$stage_method( ...$args );
				$this->fail( $stage_method . ' did not refuse the class.' );
			} catch ( WC_Data_Exception $e ) {
				$this->assertSame( 'woocommerce_data_staging_unsupported', $e->getErrorCode() );
				$this->assertStringContainsString( $stage_method . '()', $e->getMessage() );
			}
		}
	}

	/**
	 * @testdox stage_delivery_result() counts a failure in memory, and the next save writes it.
	 */
	public function test_stage_delivery_result_changes_the_webhook_in_memory_only() {
		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_failure_count( 0 );
		$webhook->save();

		// Saving an active webhook with a pending delivery pings its URL; answer locally.
		$this->http_responder = fn() => $this->mock_http_response( 200 );

		$webhook->stage_delivery_result( 500 );
		$this->assertSame( 1, $webhook->get_failure_count() );
		$this->assertSame( 0, wc_get_webhook( $webhook->get_id() )->get_failure_count() );

		$webhook->save();
		$this->assertSame( 1, wc_get_webhook( $webhook->get_id() )->get_failure_count() );

		$webhook->stage_delivery_result( 200 );
		$webhook->save();
		$this->assertSame( 0, wc_get_webhook( $webhook->get_id() )->get_failure_count() );
	}

	/**
	 * @testdox stage_delivery_result() leaves the disabled action to the next save, which fires it after the write.
	 */
	public function test_stage_delivery_result_fires_the_disabled_action_after_the_write() {
		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_failure_count( 6 );
		$webhook->save();

		$statuses = array();
		$listener = static function ( $webhook_id ) use ( &$statuses ) {
			$statuses[] = wc_get_webhook( $webhook_id )->get_status();
		};
		add_action( 'woocommerce_webhook_disabled_due_delivery_failures', $listener );

		$webhook->stage_delivery_result( 500 );
		$this->assertSame( 'disabled', $webhook->get_status() );
		$this->assertSame( array(), $statuses );

		$webhook->save();
		$webhook->save();

		remove_action( 'woocommerce_webhook_disabled_due_delivery_failures', $listener );
		$this->assertSame( array( 'disabled' ), $statuses );
	}

	/**
	 * @testdox log_delivery() after a staged disable fires the disabled action once.
	 */
	public function test_log_delivery_after_a_staged_disable_fires_the_disabled_action_once() {
		$webhook = $this->create_failing_webhook();
		$fired   = did_action( 'woocommerce_webhook_disabled_due_delivery_failures' );

		$webhook->stage_delivery_result( 500 );
		$webhook->log_delivery( 1, $this->delivery_request(), new WP_Error( 'http_request_failed', 'Failed' ), 0.1 );
		$webhook->save();

		$this->assertSame( 1, did_action( 'woocommerce_webhook_disabled_due_delivery_failures' ) - $fired );
	}

	/**
	 * @testdox save() does not fire the disabled action when a status change replaced the staged disable.
	 */
	public function test_save_skips_the_disabled_action_when_the_webhook_is_active_again() {
		$webhook = $this->create_failing_webhook();
		$fired   = did_action( 'woocommerce_webhook_disabled_due_delivery_failures' );

		$this->http_responder = fn() => $this->mock_http_response( 200 );

		$webhook->stage_delivery_result( 500 );
		$webhook->set_status( 'active' );
		$webhook->save();

		$this->assertSame( 0, did_action( 'woocommerce_webhook_disabled_due_delivery_failures' ) - $fired );
	}

	/**
	 * Create a saved, active webhook that the next failed delivery disables.
	 *
	 * @return WC_Webhook
	 */
	private function create_failing_webhook(): WC_Webhook {
		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_failure_count( 6 );
		$webhook->save();

		return $webhook;
	}

	/**
	 * Build the request array that log_delivery() reads.
	 *
	 * @return array
	 */
	private function delivery_request(): array {
		return array(
			'method'     => 'POST',
			'user-agent' => 'WooCommerce',
			'headers'    => array(),
			'body'       => '',
		);
	}

	/**
	 * @testdox log_delivery() fires the disabled action right away, before it saves.
	 */
	public function test_log_delivery_fires_the_disabled_action_once() {
		$webhook = new WC_Webhook();
		$webhook->set_status( 'active' );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->set_user_id( 1 );
		$webhook->set_failure_count( 6 );
		$webhook->save();
		$fired = did_action( 'woocommerce_webhook_disabled_due_delivery_failures' );

		$request = array(
			'method'     => 'POST',
			'user-agent' => 'WooCommerce',
			'headers'    => array(),
			'body'       => '',
		);
		$webhook->log_delivery( 1, $request, new WP_Error( 'http_request_failed', 'Failed' ), 0.1 );
		$webhook->save();

		$this->assertSame( 1, did_action( 'woocommerce_webhook_disabled_due_delivery_failures' ) - $fired );
		$this->assertSame( 'disabled', wc_get_webhook( $webhook->get_id() )->get_status() );
	}

	/**
	 * @testdox A webhook on a hook that passes $staged does not refuse a staged change, and processes the hook only after a save writes the order.
	 * @testWith ["woocommerce_order_before_calculate_totals"]
	 *           ["woocommerce_order_after_calculate_totals"]
	 *
	 * @param string $hook Hook of the webhook topic.
	 */
	public function test_a_webhook_on_a_staged_hook_does_not_refuse_a_staged_change( string $hook ): void {
		$sut = $this->create_active_webhook( 'action.' . $hook );
		$sut->save();
		$sut->enqueue();
		$order     = WC_Helper_Order::create_order();
		$processed = new ReflectionProperty( WC_Webhook::class, 'processed' );
		$processed->setAccessible( true );
		$fail_write = true;
		add_action(
			'woocommerce_before_order_object_save',
			static function () use ( &$fail_write ) {
				if ( $fail_write ) {
					$fail_write = false;
					throw new Exception( 'The write failed.' );
				}
			}
		);

		$order->mark_staged();
		$order->stage_totals();
		$order->save();
		$this->assertSame( array(), $processed->getValue( $sut ), 'The webhook waits for a save that writes the order' );

		$order->save();
		$this->assertSame( array( true ), $processed->getValue( $sut ) );
	}
}
