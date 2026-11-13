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
