<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\Edit;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order;
use WC_Product_Download;
use WP_Screen;

/**
 * Tests for the Edit class — covers the conditional default visibility of the
 * Downloadable product permissions meta box on the order edit screen.
 */
class EditTest extends \WC_Unit_Test_Case {

	/**
	 * Screen ID used for the order edit screen in these tests.
	 *
	 * @var string
	 */
	private const TEST_SCREEN_ID = 'woocommerce_page_wc-orders';

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		// Clean up filters registered during the test so they don't leak across tests.
		remove_all_filters( 'hidden_meta_boxes' );
		remove_all_filters( 'woocommerce_order_downloads_meta_box_hidden' );

		// Reset the meta boxes registered by add_order_meta_boxes() so global state stays clean.
		unset( $GLOBALS['wp_meta_boxes'][ self::TEST_SCREEN_ID ] );

		parent::tearDown();
	}

	/**
	 * @testdox Should leave the downloads meta box visible by default when the order has downloadable items.
	 */
	public function test_downloads_meta_box_stays_visible_for_orders_with_downloadable_items(): void {
		$order = $this->create_order_with_downloadable_item();

		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order', $order );

		$hidden = $this->apply_default_hidden_filter();

		$this->assertNotContains(
			'woocommerce-order-downloads',
			$hidden,
			'Orders with downloadable items should not have the downloads meta box hidden by default.'
		);
	}

	/**
	 * @testdox Should hide the downloads meta box by default when the order has no downloadable items.
	 */
	public function test_downloads_meta_box_is_hidden_for_orders_without_downloadable_items(): void {
		$order = WC_Helper_Order::create_order( 1, WC_Helper_Product::create_simple_product() );

		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order', $order );

		$hidden = $this->apply_default_hidden_filter();

		$this->assertContains(
			'woocommerce-order-downloads',
			$hidden,
			'Orders without downloadable items should have the downloads meta box hidden by default.'
		);
	}

	/**
	 * @testdox Should not affect default-hidden meta boxes on screens other than the order edit screen.
	 */
	public function test_filter_only_applies_to_the_registered_screen(): void {
		$order = WC_Helper_Order::create_order( 1, WC_Helper_Product::create_simple_product() );

		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order', $order );

		$unrelated_screen = WP_Screen::get( 'dashboard' );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Simulating core WP filter to exercise the meta box visibility rule under test.
		$hidden = apply_filters( 'hidden_meta_boxes', array(), $unrelated_screen );

		$this->assertNotContains(
			'woocommerce-order-downloads',
			$hidden,
			'The hidden-by-default rule must not leak to unrelated admin screens.'
		);
	}

	/**
	 * @testdox Should leave the downloads meta box visible when no order is passed.
	 */
	public function test_no_order_means_no_default_hidden_change(): void {
		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order', null );

		$hidden = $this->apply_default_hidden_filter();

		$this->assertNotContains(
			'woocommerce-order-downloads',
			$hidden,
			'When no order is provided we should not silently hide the meta box.'
		);
	}

	/**
	 * @testdox Should allow the woocommerce_order_downloads_meta_box_hidden filter to force the meta box visible.
	 */
	public function test_extension_filter_can_force_meta_box_visible(): void {
		$order = WC_Helper_Order::create_order( 1, WC_Helper_Product::create_simple_product() );

		add_filter( 'woocommerce_order_downloads_meta_box_hidden', '__return_false' );

		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order', $order );

		$hidden = $this->apply_default_hidden_filter();

		$this->assertNotContains(
			'woocommerce-order-downloads',
			$hidden,
			'The extension filter should be able to force the meta box visible even when there are no downloadable items.'
		);
	}

	/**
	 * @testdox Should allow the woocommerce_order_downloads_meta_box_hidden filter to force the meta box hidden.
	 */
	public function test_extension_filter_can_force_meta_box_hidden(): void {
		$order = $this->create_order_with_downloadable_item();

		add_filter( 'woocommerce_order_downloads_meta_box_hidden', '__return_true' );

		Edit::add_order_meta_boxes( self::TEST_SCREEN_ID, 'Order', $order );

		$hidden = $this->apply_default_hidden_filter();

		$this->assertContains(
			'woocommerce-order-downloads',
			$hidden,
			'The extension filter should be able to force the meta box hidden even when there are downloadable items.'
		);
	}

	/**
	 * Build an order containing one downloadable product with a download file.
	 *
	 * @return WC_Order
	 */
	private function create_order_with_downloadable_item(): WC_Order {
		$download = new WC_Product_Download();
		$download->set_name( 'Test download' );
		$download->set_id( 'edit-test-download' );
		$download->set_file( 'https://example.com/file.pdf' );

		$product = WC_Helper_Product::create_downloadable_product( array( $download ) );

		return WC_Helper_Order::create_order( 1, $product );
	}

	/**
	 * Apply the hidden_meta_boxes filter as WordPress would, against the test screen.
	 *
	 * @return array
	 */
	private function apply_default_hidden_filter(): array {
		$screen = WP_Screen::get( self::TEST_SCREEN_ID );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Simulating core WP filter to exercise the meta box visibility rule under test.
		return (array) apply_filters( 'hidden_meta_boxes', array(), $screen );
	}
}
