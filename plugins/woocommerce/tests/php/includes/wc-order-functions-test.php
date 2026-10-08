<?php
/**
 * Order functions tests
 *
 * @package WooCommerce\Tests\Order.
 */

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Utilities\Users;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class WC_Order_Functions_Test
 */
class WC_Order_Functions_Test extends \WC_Unit_Test_Case {
	/**
	 * Test that wc_restock_refunded_items() preserves order item stock metadata.
	 */
	public function test_wc_restock_refunded_items_stock_metadata() {
		// Create a product, with stock management enabled.
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		// Place an order for the product, qty 2.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->calculate_totals();

		$checkout = WC_Checkout::instance();
		$order    = new WC_Order();
		$checkout->set_data_from_cart( $order );
		$order->set_status( OrderInternalStatus::PROCESSING );
		$order->save();

		// Get the line item.
		$items     = $order->get_items();
		$line_item = reset( $items );

		// Force a restock of one item.
		$refunded_items                         = array();
		$refunded_items[ $line_item->get_id() ] = array(
			'qty' => 1,
		);
		wc_restock_refunded_items( $order, $refunded_items );

		// Verify metadata.
		$this->assertEquals( 1, (int) $line_item->get_meta( '_reduced_stock', true ) );
		$this->assertEquals( 1, (int) $line_item->get_meta( '_restock_refunded_items', true ) );

		// Force another restock of one item.
		wc_restock_refunded_items( $order, $refunded_items );

		// Verify metadata.
		$this->assertEquals( 0, (int) $line_item->get_meta( '_reduced_stock', true ) );
		$this->assertEquals( 2, (int) $line_item->get_meta( '_restock_refunded_items', true ) );
	}

	/**
	 * Test update_total_sales_counts and check total_sales after order reflection.
	 *
	 * Tests the fix for issue #23796
	 */
	public function test_wc_update_total_sales_counts() {

		$product_id = WC_Helper_Product::create_simple_product()->get_id();

		WC()->cart->add_to_cart( $product_id );

		$order_id = WC_Checkout::instance()->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy',
			)
		);

		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		$order = new WC_Order( $order_id );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::CANCELLED );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::COMPLETED );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::REFUNDED );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		// Test trashing the order.
		$order->delete( false );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );

		// To successfully untrash, we need to grab a new instance of the order.
		wc_get_order( $order_id )->untrash();
		$this->assertEquals( 1, wc_get_product( $product_id )->get_total_sales() );

		// Test full deletion of the order (again, we need to grab a new instance of the order).
		wc_get_order( $order_id )->delete( true );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_total_sales() );
	}


	/**
	 * Test wc_update_coupon_usage_counts and check usage_count after order reflection.
	 *
	 * Tests the fix for issue #31245
	 */
	public function test_wc_update_coupon_usage_counts() {
		$coupon   = WC_Helper_Coupon::create_coupon( 'test' );
		$order_id = WC_Checkout::instance()->create_order(
			array(
				'billing_email'  => 'a@b.com',
				'payment_method' => 'dummy',
			)
		);

		$order = new WC_Order( $order_id );
		$order->apply_coupon( $coupon );

		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::CANCELLED );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PENDING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::FAILED );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::COMPLETED );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::REFUNDED );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		// Test trashing the order.
		$order->delete( false );
		$this->assertEquals( 0, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		// To successfully untrash, we need to grab a new instance of the order.
		$order = wc_get_order( $order_id );
		$order->untrash();
		$this->assertEquals( 1, $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );
	}

	/**
	 * Add a raw coupon item to an order and save it, bypassing apply_coupon().
	 *
	 * @param WC_Order $order Order.
	 * @param string   $code  Coupon code.
	 */
	private function add_raw_coupon_item( WC_Order $order, string $code ): void {
		$item = new WC_Order_Item_Coupon();
		$item->set_code( $code );
		$item->set_discount( 1 );
		$order->add_item( $item );
		$order->save();
	}

	/**
	 * @testdox Coupon usage is not marked as recorded while the order has no coupons, so coupons added later are counted.
	 */
	public function test_wc_update_coupon_usage_counts_counts_coupons_added_after_first_status_change() {
		$coupon = WC_Helper_Coupon::create_coupon( 'late-coupon' );
		$order  = WC_Helper_Order::create_order();
		$store  = $order->get_data_store();

		$this->assertFalse( $store->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::ON_HOLD );
		$this->assertFalse( $store->get_recorded_coupon_usage_counts( $order ) );

		$this->add_raw_coupon_item( $order, $coupon->get_code() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertTrue( $store->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::CANCELLED );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );
	}

	/**
	 * @testdox An order without coupons never gets the recorded flag through valid status changes.
	 */
	public function test_wc_update_coupon_usage_counts_does_not_flag_orders_without_coupons() {
		$order = WC_Helper_Order::create_order();
		$store = $order->get_data_store();

		foreach ( array( OrderStatus::PENDING, OrderStatus::ON_HOLD, OrderStatus::PROCESSING, OrderStatus::COMPLETED ) as $status ) {
			$order->update_status( $status );
			$this->assertFalse( $store->get_recorded_coupon_usage_counts( $order ), "Flag set for status {$status}." );
		}
	}

	/**
	 * @testdox A legacy coupon-less order carrying the recorded flag is counted once a coupon is added.
	 */
	public function test_wc_update_coupon_usage_counts_treats_legacy_flag_without_coupons_as_unrecorded() {
		$coupon = WC_Helper_Coupon::create_coupon( 'legacy-coupon' );
		$order  = WC_Helper_Order::create_order();
		$store  = $order->get_data_store();

		$store->set_recorded_coupon_usage_counts( $order, true );
		$this->assertTrue( $store->get_recorded_coupon_usage_counts( $order ) );

		$order->update_status( OrderStatus::ON_HOLD );
		$this->assertFalse( $store->get_recorded_coupon_usage_counts( $order ) );

		$order = wc_get_order( $order->get_id() );
		$this->add_raw_coupon_item( $order, $coupon->get_code() );
		$this->assertEquals( 0, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->update_status( OrderStatus::PROCESSING );
		$this->assertTrue( $store->get_recorded_coupon_usage_counts( $order ) );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );
	}

	/**
	 * @testdox Pinning: a coupon applied to an auto-draft order is counted once when the order is later paid.
	 */
	public function test_wc_update_coupon_usage_counts_admin_applied_coupon_paid_later_counts_once() {
		$coupon = WC_Helper_Coupon::create_coupon( 'admin-coupon' );
		$order  = WC_Helper_Order::create_order();
		$order->set_status( 'auto-draft' );
		$order->save();

		$result = $order->apply_coupon_using_edited_totals( $coupon->get_code() );
		$this->assertTrue( $result );

		$order->update_status( OrderStatus::PENDING );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );

		$order->payment_complete();
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 1, ( new WC_Coupon( $coupon ) )->get_usage_count() );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );

		\Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::sync_order_coupons( $order->get_id() );
		global $wpdb;
		$rows = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wc_order_coupon_lookup WHERE order_id = %d AND coupon_id = %d",
				$order->get_id(),
				$coupon->get_id()
			)
		);
		$this->assertEquals( 1, (int) $rows );
	}

	/**
	 * Test getting total refunded for an item with and without refunds.
	 */
	public function test_get_total_refunded_for_item() {
		// Create a product.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 99.99 );
		$product->save();

		// Create an order with the product.
		$order = new WC_Order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'total'    => 199.98,
			)
		);
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();

		// Get the item ID.
		$items   = $order->get_items();
		$item_id = array_key_first( $items );

		// Test that by default there is no refund.
		$this->assertEquals( 0, $order->get_total_refunded_for_item( $item_id ) );

		// Create first partial refund for 1 item.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 49.99,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0.5,
						'refund_total' => 49.99,
					),
				),
			)
		);

		// Verify the refunded amount for the item after first refund.
		$this->assertEquals( 49.99, $order->get_total_refunded_for_item( $item_id ) );

		// Create second partial refund for remaining amount.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 149.99,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1.5,
						'refund_total' => 149.99,
					),
				),
			)
		);

		// Verify the total refunded amount for the item after both refunds.
		$this->assertEquals( 199.98, $order->get_total_refunded_for_item( $item_id ) );
	}

	/**
	 * Test that creating a full refund with free items triggers fully refunded action.
	 */
	public function test_full_refund_with_free_items() {
		// Create a paid product.
		$paid_product = WC_Helper_Product::create_simple_product();
		$paid_product->set_regular_price( 10 );
		$paid_product->save();

		// Create a free product.
		$free_product = WC_Helper_Product::create_simple_product();
		$free_product->set_regular_price( 0 );
		$free_product->save();

		// Create an order with both products.
		$order = new WC_Order();

		// Add paid product.
		$paid_item = new WC_Order_Item_Product();
		$paid_item->set_props(
			array(
				'product'  => $paid_product,
				'quantity' => 1,
				'total'    => 10,
			)
		);
		$order->add_item( $paid_item );

		// Add free product.
		$free_item = new WC_Order_Item_Product();
		$free_item->set_props(
			array(
				'product'  => $free_product,
				'quantity' => 1,
				'total'    => 0,
			)
		);
		$order->add_item( $free_item );

		$order->calculate_totals();
		$order->save();

		// Track if fully refunded action was triggered.
		$fully_refunded_triggered = false;
		add_action(
			'woocommerce_order_fully_refunded',
			function () use ( &$fully_refunded_triggered ) {
				$fully_refunded_triggered = true;
			}
		);

		// Track if partially refunded action was triggered.
		$partially_refunded_triggered = false;
		add_action(
			'woocommerce_order_partially_refunded',
			function () use ( &$partially_refunded_triggered ) {
				$partially_refunded_triggered = true;
			}
		);

		// Create a full refund.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => $order->get_total(),
				'reason'   => 'Testing refund with free items',
			)
		);

		$this->assertNotWPError( $refund, 'Refund should be created successfully' );

		// Verify that fully refunded action was triggered and partially refunded was not.
		$this->assertTrue( $fully_refunded_triggered, 'Fully refunded action should be triggered' );
		$this->assertFalse( $partially_refunded_triggered, 'Partially refunded action should not be triggered' );
	}

	/**
	 * Test that wc_wptexturize_order_note() preserves URLs with double hyphens.
	 *
	 * @dataProvider url_protection_test_data
	 * @param string $input                            The input string to test.
	 * @param bool   $expected_contains_double_hyphens Whether the result should contain double hyphens.
	 * @param bool   $expected_contains_em_dash        Whether the result should contain em-dash.
	 * @param string $test_description                 Description of the test case.
	 */
	public function test_wc_wptexturize_order_note( $input, $expected_contains_double_hyphens, $expected_contains_em_dash, $test_description ) {
		// Test the function.
		$result = wc_wptexturize_order_note( $input );

		// Always make at least one assertion - that we got a string result.
		$this->assertIsString( $result, $test_description . ' - Result should be a string' );

		// For empty input, result should also be empty.
		if ( empty( $input ) ) {
			$this->assertEmpty( $result, $test_description . ' - Empty input should produce empty result' );
			return; // Exit early for empty input case.
		}

		// Check if URLs with double hyphens are preserved.
		if ( $expected_contains_double_hyphens ) {
			$this->assertStringContainsString( '--', $result, $test_description . ' - Should preserve double hyphens in URLs' );
		} else {
			// If we don't expect double hyphens, make sure there are none (except in URLs).
			$url_pattern          = '/\b(?:https?):\/\/[^\s<>"{}|\\^`\[\]]+/i';
			$content_without_urls = preg_replace( $url_pattern, '', $result );
			$this->assertStringNotContainsString( '--', $content_without_urls, $test_description . ' - Should not contain double hyphens outside URLs' );
		}

		// Check if non-URL double hyphens are converted to em-dashes (either Unicode or HTML entity).
		if ( $expected_contains_em_dash ) {
			$contains_em_dash = strpos( $result, '—' ) !== false || strpos( $result, '&#8212;' ) !== false;
			$this->assertTrue( $contains_em_dash, $test_description . ' - Should convert non-URL double hyphens to em-dashes (found: ' . $result . ')' );
		} else {
			// If we don't expect em-dash, verify it's not there.
			$contains_em_dash = strpos( $result, '—' ) !== false || strpos( $result, '&#8212;' ) !== false;
			$this->assertFalse( $contains_em_dash, $test_description . ' - Should not contain em-dashes (found: ' . $result . ')' );
		}

		// Ensure the result is not empty for non-empty input.
		$this->assertNotEmpty( $result, $test_description . ' - Result should not be empty for non-empty input' );
	}

	/**
	 * Data provider for URL protection tests.
	 *
	 * @return array Test data with format: [input, expected_double_hyphens, expected_em_dash, description]
	 */
	public function url_protection_test_data() {
		return array(
			// URL with double hyphens should be preserved.
			array(
				'Check API status at https://api.example.com/status--check for details',
				true,  // Should contain double hyphens.
				false, // Should not contain em-dash (no non-URL double hyphens).
				'URL with double hyphens in path',
			),
			// Multiple URLs with double hyphens.
			array(
				'First URL: https://api.test.com/endpoint--1 and second URL: https://api.test.com/endpoint--2',
				true,  // Should contain double hyphens.
				false, // Should not contain em-dash.
				'Multiple URLs with double hyphens',
			),
			// Text with double hyphens (not URLs) should be converted.
			array(
				'This is a test -- it should convert to em-dash',
				false, // Should not contain double hyphens.
				true,  // Should contain em-dash.
				'Non-URL double hyphens should be converted',
			),
			// Mixed content: URL with double hyphens + text with double hyphens.
			array(
				'Check the API at https://api.example.com/status--check -- this should work properly',
				true, // Should contain double hyphens (in URL).
				true, // Should contain em-dash (from text).
				'Mixed content: URL and text with double hyphens',
			),
			// HTTPS URL with complex path.
			array(
				'Visit https://example.com/path--with--multiple--hyphens/page.html',
				true,  // Should contain double hyphens.
				false, // Should not contain em-dash.
				'HTTPS URL with multiple double hyphens in path',
			),
			// HTTP URL with double hyphens.
			array(
				'API endpoint: http://legacy-api.example.com/v1/status--check',
				true,  // Should contain double hyphens.
				false, // Should not contain em-dash.
				'HTTP URL with double hyphens',
			),
			// No URLs, just regular text formatting.
			array(
				'Just some text -- with double hyphens -- to convert',
				false, // Should not contain double hyphens.
				true,  // Should contain em-dash.
				'Text without URLs should be texturized normally',
			),
			// Empty content.
			array(
				'',
				false, // Should not contain double hyphens.
				false, // Should not contain em-dash.
				'Empty content should be handled gracefully',
			),
			// URL at end of sentence.
			array(
				'Please check https://api.example.com/endpoint--status.',
				true,  // Should contain double hyphens.
				false, // Should not contain em-dash.
				'URL at end of sentence with punctuation',
			),
		);
	}

	/**
	 * Test that wc_wptexturize_order_note() works correctly with customer note content.
	 */
	public function test_wc_wptexturize_order_note_customer_note() {
		$content = 'Check API status at https://api.example.com/status--check -- this is important';

		$result = wc_wptexturize_order_note( $content );

		// Should preserve URL double hyphens.
		$this->assertStringContainsString( 'status--check', $result, 'Should preserve double hyphens in URLs' );

		// Should convert text double hyphens to em-dash (either Unicode or HTML entity).
		$contains_em_dash = strpos( $result, '—' ) !== false || strpos( $result, '&#8212;' ) !== false;
		$this->assertTrue( $contains_em_dash, 'Should convert text double hyphens to em-dash (found: ' . $result . ')' );
	}

	/**
	 * Test URL preservation with line breaks (specific to email templates).
	 */
	public function test_url_preservation_with_line_breaks() {
		$content_with_breaks = "Check API status:\nhttps://api.example.com/status--check\n\nThen verify the results -- everything should work.";

		// Test the core function.
		$result = wc_wptexturize_order_note( $content_with_breaks );
		$this->assertStringContainsString( 'status--check', $result, 'URLs should be preserved even with line breaks' );

		// Test that nl2br() doesn't affect URL preservation (used in HTML emails).
		$html_result = nl2br( $result );
		$this->assertStringContainsString( 'status--check', $html_result, 'URLs should remain preserved after nl2br()' );
		$this->assertStringContainsString( '<br />', $html_result, 'Line breaks should be converted to <br> tags' );

		// Verify em-dash conversion still works for non-URL content.
		$contains_em_dash = strpos( $result, '—' ) !== false || strpos( $result, '&#8212;' ) !== false;
		$this->assertTrue( $contains_em_dash, 'Non-URL double hyphens should still be converted to em-dash' );
	}

	/**
	 * Test handling of duplicate URLs in content.
	 */
	public function test_duplicate_url_handling() {
		// Content with multiple sets of duplicate URLs.
		$content = 'First URL: https://api.example.com/status--check appears here. ' .
					'Second URL: https://api.example.com/health--monitor is different. ' .
					'Third URL: https://api.example.com/debug--console is unique. ' .
					'Now repeat first: https://api.example.com/status--check again. ' .
					'And second: https://api.example.com/health--monitor once more. ' .
					'And first again: https://api.example.com/status--check third time. ' .
					'Plus some text -- that should convert to em-dash.';

		$result = wc_wptexturize_order_note( $content );

		// Verify each URL appears the correct number of times.
		$status_count = substr_count( $result, 'https://api.example.com/status--check' );
		$this->assertEquals( 3, $status_count, 'First URL should appear 3 times' );

		$health_count = substr_count( $result, 'https://api.example.com/health--monitor' );
		$this->assertEquals( 2, $health_count, 'Second URL should appear 2 times' );

		$debug_count = substr_count( $result, 'https://api.example.com/debug--console' );
		$this->assertEquals( 1, $debug_count, 'Third URL should appear 1 time' );

		// All double hyphens in URLs should remain intact.
		$this->assertStringContainsString( 'status--check', $result, 'First URL double hyphens should be preserved' );
		$this->assertStringContainsString( 'health--monitor', $result, 'Second URL double hyphens should be preserved' );
		$this->assertStringContainsString( 'debug--console', $result, 'Third URL double hyphens should be preserved' );

		// Verify text double hyphens were converted to em-dash.
		$contains_em_dash = strpos( $result, '—' ) !== false || strpos( $result, '&#8212;' ) !== false;
		$this->assertTrue( $contains_em_dash, 'Non-URL double hyphens should be converted to em-dash' );

		// Ensure no placeholders leaked into final output.
		$this->assertStringNotContainsString( '___WC_URL_PLACEHOLDER_', $result, 'No placeholders should remain in output' );
	}

	/**
	 * Test edge cases for URL detection regex.
	 */
	public function test_url_detection_edge_cases() {
		$edge_cases = array(
			// URL with query parameters and double hyphens.
			'https://api.example.com/search--results?query=test&type=--advanced',
			// URL with fragment and double hyphens.
			'https://docs.example.com/section--a#subsection--b',
			// Multiple protocols.
			'Check http://old.example.com/legacy--api and https://new.example.com/modern--api',
			// URL with port number.
			'https://localhost:8080/dev--server/status--check',
		);

		foreach ( $edge_cases as $content ) {
			$result = wc_wptexturize_order_note( $content );

			// All URLs should preserve their double hyphens.
			$this->assertStringContainsString( '--', $result, "Edge case should preserve double hyphens: {$content}" );
		}
	}

	/**
	 * Test `wc_delete_shop_order_transients`: purging user metas depending on the order state.
	 */
	public function test_wc_delete_shop_order_transients_usermeta_purge(): void {
		$customer    = WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$order       = WC_Helper_Order::create_order( $customer_id, null, array( 'status' => OrderStatus::COMPLETED ) );
		$order_id    = $order->get_id();

		// Verify the metas getting purged for order a state different from checkout draft.
		Users::update_site_user_meta( $customer_id, 'wc_order_count', 123 );
		Users::update_site_user_meta( $customer_id, 'wc_last_order', 456 );
		Users::update_site_user_meta( $customer_id, 'wc_money_spent', 789 );
		wc_delete_shop_order_transients( $order_id );
		$this->assertSame( '', Users::get_site_user_meta( $customer_id, 'wc_order_count' ) );
		$this->assertSame( '', Users::get_site_user_meta( $customer_id, 'wc_last_order' ) );
		$this->assertSame( '', Users::get_site_user_meta( $customer_id, 'wc_money_spent' ) );

		// Cleanup.
		$order->delete();
		$customer->delete();
	}

	/**
	 * @testdox Should record the logged-in user who issued the refund.
	 */
	public function test_wc_create_refund_records_the_current_user(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$order  = WC_Helper_Order::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
			)
		);

		$this->assertNotWPError( $refund );
		$this->assertSame( $user_id, $refund->get_refunded_by() );
		$this->assertSame(
			$user_id,
			wc_get_order( $refund->get_id() )->get_refunded_by(),
			'The refunding user should survive a round trip through the data store.'
		);
	}

	/**
	 * @testdox Should attribute a refund to nobody when no user is logged in, rather than to user 1.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/36329
	 */
	public function test_wc_create_refund_records_no_user_when_nobody_is_logged_in(): void {
		wp_set_current_user( 0 );

		$order  = WC_Helper_Order::create_order();
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
			)
		);

		$this->assertNotWPError( $refund );
		$this->assertSame( 0, $refund->get_refunded_by() );
		$this->assertSame(
			0,
			wc_get_order( $refund->get_id() )->get_refunded_by(),
			'An unattributed refund should stay unattributed after a round trip through the data store.'
		);
	}

	/**
	 * Create a processing order that has counted a coupon.
	 *
	 * @param WC_Coupon $coupon Coupon.
	 * @return WC_Order
	 */
	private function create_recorded_order( WC_Coupon $coupon ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$this->add_raw_coupon_item( $order, $coupon->get_code() );
		$order->update_status( OrderStatus::PROCESSING );

		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );

		return $order;
	}

	/**
	 * @testdox Should release coupon usage when a recorded order is deleted permanently.
	 */
	public function test_force_deleting_a_recorded_order_releases_coupon_usage() {
		$coupon = WC_Helper_Coupon::create_coupon( 'deleted-order' );
		$this->create_recorded_order( $coupon );
		$order = $this->create_recorded_order( $coupon );
		$this->assertSame( 2, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );

		$order->delete( true );

		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
	}

	/**
	 * @testdox Should release coupon usage once when a recorded order is trashed and then deleted.
	 */
	public function test_deleting_a_trashed_order_does_not_release_coupon_usage_twice() {
		$coupon = WC_Helper_Coupon::create_coupon( 'trashed-deleted-order' );
		$this->create_recorded_order( $coupon );
		$order = $this->create_recorded_order( $coupon );
		$this->assertSame( 2, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );

		$order->delete( false );
		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );

		$order->delete( true );
		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
	}

	/**
	 * @testdox Should not change coupon usage when an order that never counted its coupons is deleted.
	 */
	public function test_deleting_an_unrecorded_order_leaves_coupon_usage_unchanged() {
		$coupon = WC_Helper_Coupon::create_coupon( 'unrecorded-deleted-order' );
		$this->create_recorded_order( $coupon );
		$order = WC_Helper_Order::create_order();
		$this->add_raw_coupon_item( $order, $coupon->get_code() );
		$this->assertFalse( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );

		$order->delete( true );

		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
	}

	/**
	 * @testdox Should release coupon usage once when a recorded order is deleted with wp_delete_post.
	 */
	public function test_wp_delete_post_on_a_recorded_order_releases_coupon_usage_once() {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->markTestSkipped( 'Orders are not posts when HPOS is authoritative.' );
		}

		$coupon = WC_Helper_Coupon::create_coupon( 'wp-deleted-order' );
		$this->create_recorded_order( $coupon );
		$order = $this->create_recorded_order( $coupon );
		$this->assertSame( 2, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );

		wp_delete_post( $order->get_id(), true );

		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
	}

	/**
	 * @testdox Should release coupon usage when a recorded auto-draft order is deleted.
	 */
	public function test_deleting_a_recorded_auto_draft_order_releases_coupon_usage() {
		$coupon = WC_Helper_Coupon::create_coupon( 'auto-draft-order' );
		$order  = new WC_Order();
		$order->set_status( 'auto-draft' );
		$order->save();
		$order->apply_coupon( $coupon );

		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );

		$order->delete( true );

		$this->assertSame( 0, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
	}

	/**
	 * @testdox Should not touch coupon usage when a refund of a recorded order is deleted.
	 */
	public function test_deleting_a_refund_does_not_release_coupon_usage() {
		$coupon = WC_Helper_Coupon::create_coupon( 'refund-deleted' );
		$order  = $this->create_recorded_order( $coupon );
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);
		$this->assertNotWPError( $refund );

		$refund->delete( true );

		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
		$this->assertTrue( wc_get_order( $order->get_id() )->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
	}

	/**
	 * @testdox Should leave coupon usage untouched when an unrelated post is deleted.
	 */
	public function test_deleting_a_product_post_leaves_coupon_usage_unchanged() {
		$coupon  = WC_Helper_Coupon::create_coupon( 'product-deleted' );
		$order   = $this->create_recorded_order( $coupon );
		$product = WC_Helper_Product::create_simple_product();

		wp_delete_post( $product->get_id(), true );

		$this->assertSame( 1, ( new WC_Coupon( $coupon->get_code() ) )->get_usage_count() );
		$this->assertTrue( $order->get_data_store()->get_recorded_coupon_usage_counts( $order ) );
	}
}
