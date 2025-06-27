<?php
/**
 * Order functions tests
 *
 * @package WooCommerce\Tests\Order.
 */

use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Order_Functions_Test
 */
class WC_Order_Functions_Test extends \WC_Unit_Test_Case {
	/**
	 * tearDown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		WC()->cart->empty_cart();
	}

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
}
