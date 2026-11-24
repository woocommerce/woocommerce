<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Shortcode_Order_Tracking.
 *
 * @package WooCommerce\Tests\Shortcodes
 */

/**
 * Class WC_Shortcode_Order_Tracking_Test.
 */
class WC_Shortcode_Order_Tracking_Test extends WC_Unit_Test_Case {

	/**
	 * The tracking form template builds its action URL from the global post.
	 */
	public function setUp(): void {
		parent::setUp();
		$GLOBALS['post'] = $this->factory->post->create_and_get( array( 'post_type' => 'page' ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Restore the request and global post state touched by this test.
	 */
	public function tearDown(): void {
		unset( $_REQUEST['orderid'], $_REQUEST['order_email'], $_REQUEST['woocommerce-order-tracking-nonce'] );
		unset( $GLOBALS['post'] );

		parent::tearDown();
	}

	/**
	 * @testdox Tracking form errors should render inside the shared notices wrapper, above the form.
	 */
	public function test_prints_validation_error_inside_notices_wrapper(): void {
		$_REQUEST['orderid']                          = '';
		$_REQUEST['order_email']                      = 'shopper@example.com';
		$_REQUEST['woocommerce-order-tracking-nonce'] = wp_create_nonce( 'woocommerce-order_tracking' );

		ob_start();
		WC_Shortcode_Order_Tracking::output( array() );
		$output = (string) ob_get_clean();

		$this->assertMatchesRegularExpression(
			'#<div class="woocommerce-notices-wrapper">\s*<(ul|div) class="[^"]*(woocommerce-error|is-error)[^"]*"[^>]*>.*Please enter a valid order ID.*</div>.*<form[^>]*track_order#s',
			$output,
			'The validation error should be wrapped and printed before the tracking form.'
		);
	}

	/**
	 * Submit the tracking form for an order with its correct billing email.
	 *
	 * @param WC_Order $order Order to track.
	 * @return string Shortcode output.
	 */
	private function track( WC_Order $order ): string {
		$_REQUEST['orderid']                          = (string) $order->get_id();
		$_REQUEST['order_email']                      = $order->get_billing_email();
		$_REQUEST['woocommerce-order-tracking-nonce'] = wp_create_nonce( 'woocommerce-order_tracking' );

		ob_start();
		WC_Shortcode_Order_Tracking::output( array() );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox Tracking an account order asks a visitor who is not its customer to log in instead of showing it.
	 */
	public function test_account_order_requires_its_customer_to_log_in(): void {
		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$product     = WC_Helper_Product::create_simple_product( true, array( 'name' => 'Tracked Sweater' ) );
		$order       = WC_Helper_Order::create_order( $customer_id, $product );

		$output = $this->track( $order );
		$this->assertStringContainsString( 'Please log in to your account to view this order.', $output );
		$this->assertStringNotContainsString( 'Tracked Sweater', $output );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'customer' ) ) );
		$this->assertStringNotContainsString( 'Tracked Sweater', $this->track( $order ), 'Another customer should not see the order.' );

		wp_set_current_user( $customer_id );
		$this->assertStringContainsString( 'Tracked Sweater', $this->track( $order ), 'The logged-in customer should see their order.' );
	}

	/**
	 * @testdox Tracking a guest order shows its items but no order key, downloads, purchase note, customer note or address.
	 */
	public function test_guest_order_hides_key_downloads_purchase_note_and_address(): void {
		wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register::class )->add_approved_directory( 'https://always.trusted/' );
		$product = WC_Helper_Product::create_downloadable_product(
			array(
				array(
					'name' => 'Pattern PDF',
					'file' => 'https://always.trusted/pattern.pdf',
				),
			)
		);
		$product->set_purchase_note( 'Licence key ABC-123' );
		$product->save();

		$pending = WC_Helper_Order::create_order( 0, $product );
		$pending->set_status( \Automattic\WooCommerce\Enums\OrderStatus::PENDING );
		$pending->save();

		$output = $this->track( $pending );
		$this->assertStringContainsString( 'Downloadable Product', $output );
		$this->assertStringNotContainsString( $pending->get_order_key(), $output, 'Pay and cancel links carry the order key.' );

		$completed = WC_Helper_Order::create_order( 0, $product );
		$completed->set_status( \Automattic\WooCommerce\Enums\OrderStatus::COMPLETED );
		$completed->set_customer_note( 'Gate code 4321' );
		$completed->save();
		$this->assertNotEmpty( $completed->get_downloadable_items(), 'The order should have a download to leak.' );

		$output = $this->track( $completed );
		$this->assertStringContainsString( 'Downloadable Product', $output );
		$this->assertStringNotContainsString( $completed->get_order_key(), $output );
		$this->assertStringNotContainsString( 'Licence key ABC-123', $output );
		$this->assertStringNotContainsString( 'Gate code 4321', $output );
		$this->assertStringNotContainsString( 'WooAddress', $output );
	}

	/**
	 * @testdox The order-received page still shows a guest their address when the order key is in the URL.
	 */
	public function test_guest_order_details_show_address_with_order_key(): void {
		$order       = WC_Helper_Order::create_order( 0 );
		$_GET['key'] = $order->get_order_key();

		ob_start();
		woocommerce_order_details_table( $order->get_id() );
		$output = (string) ob_get_clean();
		unset( $_GET['key'] );

		$this->assertStringContainsString( 'WooAddress', $output );
	}

	/**
	 * @testdox The tracking form should render no wrapper when nothing was submitted.
	 */
	public function test_prints_no_wrapper_without_a_submission(): void {
		ob_start();
		WC_Shortcode_Order_Tracking::output( array() );
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'woocommerce-notices-wrapper', $output );
		$this->assertStringContainsString( 'track_order', $output );
	}
}
