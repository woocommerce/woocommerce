<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;

/**
 * Class WC_Emails_Tests.
 */
class WC_Emails_Tests extends \WC_Unit_Test_Case {

	/**
	 * Test that email_header hooks are compatible with do_action calls with only param.
	 * This test should be dropped after all extensions are using compatible do_action calls.
	 */
	public function test_email_header_is_compatible_with_legacy_do_action() {
		$email_object = new WC_Emails();
		// 10 is expected priority of the hook.
		$this->assertEquals( 10, has_action( 'woocommerce_email_header', array( $email_object, 'email_header' ) ) );
		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_header', 'header' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}

	/**
	 * Test that email_footer hooks are compatible with do_action calls with only param.
	 * This test should be dropped after all extensions are using compatible do_action calls.
	 */
	public function test_email_footer_is_compatible_with_legacy_do_action() {
		$email_object = new WC_Emails();
		// 10 is expected priority of the hook.
		$this->assertEquals( 10, has_action( 'woocommerce_email_footer', array( $email_object, 'email_footer' ) ) );
		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_email_footer' );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertFalse( empty( $content ) );
	}

	/**
	 * Test that replace_placeholders safely handles null values.
	 */
	public function test_replace_placeholders_handles_null_value() {
		$email_object = new WC_Emails();
		$this->assertSame( '', $email_object->replace_placeholders( null ) );
	}

	/**
	 * Test that replace_placeholders replaces known placeholders.
	 */
	public function test_replace_placeholders_replaces_site_title() {
		$email_object = new WC_Emails();
		$placeholder  = '{site_title}';
		$actual       = $email_object->replace_placeholders( $placeholder );
		$expected     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Test that order meta function outputs linked meta.
	 */
	public function test_order_meta() {
		add_filter(
			'woocommerce_email_order_meta_keys',
			function () {
				return array( 'dummy_key' );
			}
		);
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$order->add_meta_data( 'dummy_key', 'dummy_meta_value' );
		$order->save();

		$email_object = new WC_Emails();
		ob_start();
		$email_object->order_meta( $order, true, true );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'dummy_key', $content );
		$this->assertStringContainsString( 'dummy_meta_value', $content );
	}

	/**
	 * Test that fulfillment meta function outputs linked meta.
	 */
	public function test_fulfillment_meta() {
		// Ensure the FulfillmentsController is registered, which is necessary for the translation of meta keys.
		// Delete the DB tables flag to force recreation in case another test class left stale state.
		delete_option( 'woocommerce_fulfillments_db_tables_created' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$container  = wc_get_container();
		$controller = $container->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();

		$order       = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id'   => $order->get_id(),
				'entity_type' => 'WC_Order',
			)
		);

		add_filter(
			'woocommerce_fulfillment_meta_key_translations',
			function ( $translations ) {
				$translations['test_meta_key'] = __( 'Test meta key', 'woocommerce' );
				return $translations;
			}
		);

		$email_object = new WC_Emails();
		ob_start();
		$email_object->fulfillment_meta( $order, $fulfillment, true, true );
		$content = ob_get_contents();
		ob_end_clean();
		$this->assertStringContainsString( 'Test meta key', $content );
		$this->assertStringContainsString( 'test_meta_value', $content );
	}

	/**
	 * Build an order with a shipping address but no shipping line items.
	 *
	 * @return WC_Order
	 */
	private function create_order_with_shipping_address_only() {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();

		// Drop any shipping line items the helper may have added so the order has
		// a shipping address but no shipping method.
		foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
			$order->remove_item( $item_id );
		}

		$order->set_shipping_first_name( 'Jane' );
		$order->set_shipping_last_name( 'Doe' );
		$order->set_shipping_address_1( '456 Shipping Ave' );
		$order->set_shipping_city( 'Shipville' );
		$order->set_shipping_state( 'NY' );
		$order->set_shipping_postcode( '10001' );
		$order->set_shipping_country( 'US' );
		$order->save();

		return $order;
	}

	/**
	 * Render the email addresses template and return the markup.
	 *
	 * @param WC_Order $order      Order to render.
	 * @param bool     $plain_text Whether to render the plain-text template.
	 * @return string
	 */
	private function render_email_addresses( $order, $plain_text = false ) {
		$email_object = new WC_Emails();
		ob_start();
		$email_object->email_addresses( $order, false, $plain_text );
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * @testdox Should show the shipping address in HTML emails when the order has a shipping address but no shipping method.
	 */
	public function test_email_addresses_shows_shipping_address_when_no_shipping_method() {
		$order = $this->create_order_with_shipping_address_only();

		$this->assertFalse( $order->needs_shipping_address(), 'Sanity: order without a shipping method should not need a shipping address.' );
		$this->assertTrue( $order->has_shipping_address(), 'Sanity: order should have a shipping address on file.' );

		$content = $this->render_email_addresses( $order, false );

		$this->assertStringContainsString( '456 Shipping Ave', $content, 'Shipping address line should appear in the HTML invoice when a shipping address is set, even without a shipping method.' );
		$this->assertStringContainsString( 'Shipping address', $content, 'Shipping address heading should appear in the HTML invoice when a shipping address is set.' );
	}

	/**
	 * @testdox Should show the shipping address in plain text emails when the order has a shipping address but no shipping method.
	 */
	public function test_email_addresses_plain_shows_shipping_address_when_no_shipping_method() {
		$order = $this->create_order_with_shipping_address_only();

		$content = $this->render_email_addresses( $order, true );

		$this->assertStringContainsString( '456 Shipping Ave', $content, 'Shipping address line should appear in the plain-text invoice when a shipping address is set, even without a shipping method.' );
		$this->assertStringContainsString( wc_strtoupper( 'Shipping address' ), $content, 'Shipping address heading should appear in the plain-text invoice when a shipping address is set.' );
	}

	/**
	 * @testdox Should hide the shipping address when the store ships to the billing address only, even if a shipping address is set.
	 */
	public function test_email_addresses_hides_shipping_address_when_ship_to_billing_only() {
		$order = $this->create_order_with_shipping_address_only();

		add_filter( 'woocommerce_ship_to_billing_address_only', '__return_true' );
		try {
			$content       = $this->render_email_addresses( $order, false );
			$content_plain = $this->render_email_addresses( $order, true );
		} finally {
			remove_filter( 'woocommerce_ship_to_billing_address_only', '__return_true' );
		}

		$this->assertStringNotContainsString( '456 Shipping Ave', $content, 'Shipping address should be hidden in HTML when ship-to-billing-only is enabled.' );
		$this->assertStringNotContainsString( '456 Shipping Ave', $content_plain, 'Shipping address should be hidden in plain text when ship-to-billing-only is enabled.' );
	}

	/**
	 * @testdox Should let the woocommerce_email_show_shipping_address filter override the default visibility.
	 */
	public function test_email_addresses_respects_show_shipping_address_filter() {
		$order = $this->create_order_with_shipping_address_only();

		add_filter( 'woocommerce_email_show_shipping_address', '__return_false' );
		try {
			$content       = $this->render_email_addresses( $order, false );
			$content_plain = $this->render_email_addresses( $order, true );
		} finally {
			remove_filter( 'woocommerce_email_show_shipping_address', '__return_false' );
		}

		$this->assertStringNotContainsString( '456 Shipping Ave', $content, 'Filter returning false should suppress the shipping address in HTML emails.' );
		$this->assertStringNotContainsString( '456 Shipping Ave', $content_plain, 'Filter returning false should suppress the shipping address in plain-text emails.' );
	}
}
