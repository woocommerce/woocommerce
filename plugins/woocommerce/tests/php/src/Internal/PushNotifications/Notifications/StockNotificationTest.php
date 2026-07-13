<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Notifications;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\StockNotification;
use InvalidArgumentException;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the StockNotification class.
 */
class StockNotificationTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Should return store_stock as the notification type.
	 */
	public function test_type_is_store_stock(): void {
		$notification = new StockNotification( 1 );

		$this->assertSame( 'store_stock', $notification->get_type() );
	}

	/**
	 * @testdox Should return the product ID as the resource ID.
	 */
	public function test_resource_id_matches_product_id(): void {
		$notification = new StockNotification( 42 );

		$this->assertSame( 42, $notification->get_resource_id() );
	}

	/**
	 * @testdox Should return the event type passed to the constructor.
	 * @dataProvider event_types_provider
	 *
	 * @param string $event_type The event type constant.
	 */
	public function test_get_event_type_returns_constructor_value( string $event_type ): void {
		$notification = new StockNotification( 1, $event_type );

		$this->assertSame( $event_type, $notification->get_event_type() );
	}

	/**
	 * @testdox Should default event_type to low_stock when not provided.
	 */
	public function test_constructor_defaults_event_type_to_low_stock(): void {
		$notification = new StockNotification( 1 );

		$this->assertSame( StockNotification::EVENT_LOW_STOCK, $notification->get_event_type() );
	}

	/**
	 * @testdox Should throw for an invalid event type.
	 */
	public function test_constructor_throws_for_invalid_event_type(): void {
		$this->expectException( InvalidArgumentException::class );

		new StockNotification( 1, 'invalid_event' );
	}

	/**
	 * @testdox Should return a payload with all required keys for an existing product.
	 */
	public function test_to_payload_contains_required_keys(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
			)
		);

		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$payload      = $notification->to_payload();

		$this->assertArrayHasKey( 'type', $payload );
		$this->assertArrayHasKey( 'timestamp', $payload );
		$this->assertArrayHasKey( 'resource_id', $payload );
		$this->assertArrayHasKey( 'title', $payload );
		$this->assertArrayHasKey( 'format', $payload['title'] );
		$this->assertArrayHasKey( 'args', $payload['title'] );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'format', $payload['message'] );
		$this->assertArrayHasKey( 'args', $payload['message'] );
		$this->assertArrayNotHasKey( 'icon', $payload );
		$this->assertArrayHasKey( 'meta', $payload );
		$this->assertArrayHasKey( 'product_id', $payload['meta'] );
		$this->assertArrayHasKey( 'event_type', $payload['meta'] );
	}

	/**
	 * @testdox Should vary title format by event type.
	 * @dataProvider event_type_title_provider
	 *
	 * @param string $event_type      The event type constant.
	 * @param string $expected_prefix The expected start of the title format.
	 */
	public function test_to_payload_varies_title_by_event_type( string $event_type, string $expected_prefix ): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new StockNotification( $product->get_id(), $event_type );
		$payload      = $notification->to_payload();

		$this->assertStringStartsWith( $expected_prefix, $payload['title']['format'] );
	}

	/**
	 * @testdox Should include the event-specific emoji as the last title arg.
	 * @dataProvider event_type_emoji_provider
	 *
	 * @param string $event_type     The event type constant.
	 * @param string $expected_emoji The emoji expected for that event type.
	 */
	public function test_to_payload_title_args_contain_event_emoji( string $event_type, string $expected_emoji ): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new StockNotification( $product->get_id(), $event_type );
		$payload      = $notification->to_payload();

		$this->assertSame( $expected_emoji, $payload['title']['args'][1] );
	}

	/**
	 * @testdox Should include stock quantity in the low_stock message args.
	 */
	public function test_to_payload_includes_stock_quantity_for_low_stock(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 5,
			)
		);

		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$payload      = $notification->to_payload();

		$this->assertSame( '5', $payload['message']['args'][1] );
	}

	/**
	 * @testdox Should prefer the trigger-time stock snapshot over the product's current stock so cache lag in the dispatcher process can't surface a stale value.
	 */
	public function test_to_payload_uses_stock_quantity_at_trigger_for_low_stock(): void {
		// Product currently shows stock=5 — simulating what a stale-cache re-fetch in the dispatcher process might return.
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 5,
			)
		);

		// But at trigger time, the actual post-decrement stock was 1. That's what the merchant should see in the push.
		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK, 1 );
		$payload      = $notification->to_payload();

		$this->assertSame( '1', $payload['message']['args'][1] );
	}

	/**
	 * @testdox For a simple product, meta.product_id should equal the product ID.
	 */
	public function test_to_payload_meta_product_id_for_simple_product(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 0,
			)
		);

		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$payload      = $notification->to_payload();

		$this->assertSame( $product->get_id(), $payload['meta']['product_id'] );
		$this->assertSame( $product->get_id(), $payload['resource_id'] );
	}

	/**
	 * @testdox For a variation, meta.product_id should be the parent product ID so mobile can navigate to the product details screen, while resource_id keeps the variation ID for identification.
	 */
	public function test_to_payload_meta_product_id_for_variation(): void {
		$parent     = WC_Helper_Product::create_variation_product();
		$variations = $parent->get_children();
		$variation  = wc_get_product( $variations[0] );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 0 );
		$variation->save();

		$notification = new StockNotification( $variation->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$payload      = $notification->to_payload();

		$this->assertSame( $parent->get_id(), $payload['meta']['product_id'] );
		$this->assertSame( $variation->get_id(), $payload['resource_id'] );
		$this->assertNotSame( $variation->get_id(), $payload['meta']['product_id'] );
	}

	/**
	 * @testdox Should return null when the product no longer exists.
	 */
	public function test_to_payload_returns_null_for_deleted_product(): void {
		$notification = new StockNotification( 999999 );

		$this->assertNull( $notification->to_payload() );
	}

	/**
	 * @testdox to_array should include event_type alongside type and resource_id.
	 */
	public function test_to_array_includes_event_type(): void {
		$notification = new StockNotification( 42, StockNotification::EVENT_OUT_OF_STOCK );
		$data         = $notification->to_array();

		$this->assertArrayHasKey( 'event_type', $data );
		$this->assertSame( StockNotification::EVENT_OUT_OF_STOCK, $data['event_type'] );
		$this->assertSame( 'store_stock', $data['type'] );
		$this->assertSame( 42, $data['resource_id'] );
	}

	/**
	 * @testdox hydrate should restore event_type from serialized data.
	 */
	public function test_hydrate_restores_event_type(): void {
		$notification = new StockNotification( 1 );
		$this->assertSame( StockNotification::EVENT_LOW_STOCK, $notification->get_event_type() );

		$notification->hydrate( array( 'event_type' => StockNotification::EVENT_ON_BACKORDER ) );

		$this->assertSame( StockNotification::EVENT_ON_BACKORDER, $notification->get_event_type() );
	}

	/**
	 * @testdox hydrate should throw on an unrecognized event_type so corrupt safety-net jobs are dropped instead of dispatching the wrong subtype.
	 */
	public function test_hydrate_throws_on_invalid_event_type(): void {
		$notification = new StockNotification( 1, StockNotification::EVENT_OUT_OF_STOCK );

		$this->expectException( \InvalidArgumentException::class );

		$notification->hydrate( array( 'event_type' => 'not-a-valid-event' ) );
	}

	/**
	 * @testdox hydrate should keep current event_type when key is missing.
	 */
	public function test_hydrate_keeps_current_when_key_missing(): void {
		$notification = new StockNotification( 1, StockNotification::EVENT_OUT_OF_STOCK );

		$notification->hydrate( array() );

		$this->assertSame( StockNotification::EVENT_OUT_OF_STOCK, $notification->get_event_type() );
	}

	/**
	 * @testdox to_array should include the trigger-time stock snapshot when present.
	 */
	public function test_to_array_includes_stock_quantity_at_trigger(): void {
		$notification = new StockNotification( 42, StockNotification::EVENT_LOW_STOCK, 1 );
		$data         = $notification->to_array();

		$this->assertArrayHasKey( 'stock_quantity_at_trigger', $data );
		$this->assertSame( 1, $data['stock_quantity_at_trigger'] );
	}

	/**
	 * @testdox hydrate should restore stock_quantity_at_trigger from serialized data so the safety-net path keeps the threshold-crossing value.
	 */
	public function test_hydrate_restores_stock_quantity_at_trigger(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 5,
			)
		);

		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$notification->hydrate(
			array(
				'event_type'                => StockNotification::EVENT_LOW_STOCK,
				'stock_quantity_at_trigger' => 1,
			)
		);

		$payload = $notification->to_payload();
		$this->assertSame( '1', $payload['message']['args'][1] );
	}

	/**
	 * @testdox get_identifier should differ for the same product with different event types.
	 */
	public function test_get_identifier_differs_for_different_event_types(): void {
		$low  = new StockNotification( 42, StockNotification::EVENT_LOW_STOCK );
		$out  = new StockNotification( 42, StockNotification::EVENT_OUT_OF_STOCK );
		$back = new StockNotification( 42, StockNotification::EVENT_ON_BACKORDER );

		$this->assertNotSame( $low->get_identifier(), $out->get_identifier() );
		$this->assertNotSame( $out->get_identifier(), $back->get_identifier() );
		$this->assertNotSame( $low->get_identifier(), $back->get_identifier() );
	}

	/**
	 * @testdox should_send_to_user should return true when enabled and event sub-flag is true.
	 */
	public function test_should_send_to_user_when_enabled_and_sub_flag_true(): void {
		$notification = new StockNotification( 1, StockNotification::EVENT_LOW_STOCK );

		$this->assertTrue(
			$notification->should_send_to_user(
				array(
					'enabled'   => true,
					'low_stock' => true,
				)
			)
		);
	}

	/**
	 * @testdox should_send_to_user should return false when enabled but event sub-flag is false.
	 */
	public function test_should_not_send_to_user_when_sub_flag_false(): void {
		$notification = new StockNotification( 1, StockNotification::EVENT_ON_BACKORDER );

		$this->assertFalse(
			$notification->should_send_to_user(
				array(
					'enabled'      => true,
					'on_backorder' => false,
				)
			)
		);
	}

	/**
	 * @testdox should_send_to_user should return false when notification is disabled regardless of sub-flags.
	 */
	public function test_should_not_send_to_user_when_disabled(): void {
		$notification = new StockNotification( 1, StockNotification::EVENT_LOW_STOCK );

		$this->assertFalse(
			$notification->should_send_to_user(
				array(
					'enabled'   => false,
					'low_stock' => true,
				)
			)
		);
	}

	/**
	 * @testdox should_send_to_user should default to true when event sub-flag key is missing.
	 */
	public function test_should_send_to_user_when_sub_flag_missing(): void {
		$notification = new StockNotification( 1, StockNotification::EVENT_LOW_STOCK );

		$this->assertTrue(
			$notification->should_send_to_user( array( 'enabled' => true ) )
		);
	}

	/**
	 * @testdox should_send_to_user should return true when pref_value is null.
	 */
	public function test_should_send_to_user_when_pref_null(): void {
		$notification = new StockNotification( 1 );

		$this->assertTrue( $notification->should_send_to_user( null ) );
	}

	/**
	 * @testdox Meta operations should use product post meta namespaced by event type.
	 */
	public function test_meta_operations_use_product_meta(): void {
		$product      = WC_Helper_Product::create_simple_product();
		$notification = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$meta_key     = '_test_meta';

		$this->assertFalse( $notification->has_meta( $meta_key ) );

		$notification->write_meta( $meta_key );
		$this->assertTrue( $notification->has_meta( $meta_key ) );

		$notification->delete_meta( $meta_key );
		$this->assertFalse( $notification->has_meta( $meta_key ) );
	}

	/**
	 * @testdox Meta keys should be namespaced by event type to avoid collisions.
	 */
	public function test_meta_key_is_namespaced_by_event_type(): void {
		$product  = WC_Helper_Product::create_simple_product();
		$low      = new StockNotification( $product->get_id(), StockNotification::EVENT_LOW_STOCK );
		$out      = new StockNotification( $product->get_id(), StockNotification::EVENT_OUT_OF_STOCK );
		$meta_key = '_test_meta';

		$low->write_meta( $meta_key );

		$this->assertTrue( $low->has_meta( $meta_key ) );
		$this->assertFalse( $out->has_meta( $meta_key ) );
	}

	/**
	 * Data provider for all valid event types.
	 *
	 * @return array<string, array{string}>
	 */
	public function event_types_provider(): array {
		return array(
			'low_stock'    => array( StockNotification::EVENT_LOW_STOCK ),
			'out_of_stock' => array( StockNotification::EVENT_OUT_OF_STOCK ),
			'on_backorder' => array( StockNotification::EVENT_ON_BACKORDER ),
		);
	}

	/**
	 * Data provider mapping event types to expected title prefixes.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function event_type_title_provider(): array {
		return array(
			'low_stock'    => array( StockNotification::EVENT_LOW_STOCK, 'Low stock:' ),
			'out_of_stock' => array( StockNotification::EVENT_OUT_OF_STOCK, 'Out of stock:' ),
			'on_backorder' => array( StockNotification::EVENT_ON_BACKORDER, 'Backordered:' ),
		);
	}

	/**
	 * Data provider mapping event types to their expected title emoji.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function event_type_emoji_provider(): array {
		return array(
			'low_stock'    => array( StockNotification::EVENT_LOW_STOCK, StockNotification::EMOJI_LOW_STOCK ),
			'out_of_stock' => array( StockNotification::EVENT_OUT_OF_STOCK, StockNotification::EMOJI_OUT_OF_STOCK ),
			'on_backorder' => array( StockNotification::EVENT_ON_BACKORDER, StockNotification::EMOJI_ON_BACKORDER ),
		);
	}
}
