<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\ShippingLabelButton;
use WC_Helper_Product;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the ShippingLabelButton class.
 */
class ShippingLabelButtonTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShippingLabelButton
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShippingLabelButton();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Create an order containing one product.
	 *
	 * @param bool $virtual Whether the product is virtual.
	 * @return WC_Order
	 */
	private function create_order_with_product( bool $virtual ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( $virtual );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product );
		$order->save();

		return $order;
	}

	/**
	 * @testdox Should render for an order with a product that needs shipping.
	 */
	public function test_renders_for_order_with_shippable_product(): void {
		$order = $this->create_order_with_product( false );

		$this->assertTrue( $this->sut->should_render_for_order( $order ), 'Orders with shippable products should show the button' );
	}

	/**
	 * @testdox Should not render for an order with only virtual products.
	 */
	public function test_does_not_render_for_order_without_shippable_products(): void {
		$order = $this->create_order_with_product( true );

		$this->assertFalse( $this->sut->should_render_for_order( $order ), 'Orders without shippable products should not show the button' );
	}

	/**
	 * @testdox Should not render for users who cannot install plugins.
	 */
	public function test_does_not_render_for_users_without_install_plugins_capability(): void {
		$order = $this->create_order_with_product( false );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );

		$this->assertFalse( $this->sut->should_render_for_order( $order ), 'Users who cannot install plugins should not see the button' );
	}

	/**
	 * @testdox Should not render when marketplace suggestions are turned off.
	 */
	public function test_does_not_render_when_marketplace_suggestions_are_disabled(): void {
		$order = $this->create_order_with_product( false );
		update_option( 'woocommerce_show_marketplace_suggestions', 'no' );

		$this->assertFalse( $this->sut->should_render_for_order( $order ), 'Disabled marketplace suggestions should hide the button' );
	}

	/**
	 * @testdox Should not render when marketplace suggestions are disallowed by filter.
	 */
	public function test_does_not_render_when_marketplace_suggestions_are_filtered_out(): void {
		$order = $this->create_order_with_product( false );
		add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );

		$this->assertFalse( $this->sut->should_render_for_order( $order ), 'Filtered-out marketplace suggestions should hide the button' );
	}

	/**
	 * @testdox Should let extensions remove the button and pass them the order.
	 */
	public function test_filter_can_remove_the_button(): void {
		$order          = $this->create_order_with_product( false );
		$filtered_order = null;
		$filtered_value = null;
		add_filter(
			'woocommerce_admin_order_should_render_shipping_label_button',
			function ( $render, $filter_order ) use ( &$filtered_order, &$filtered_value ) {
				$filtered_value = $render;
				$filtered_order = $filter_order;
				return false;
			},
			10,
			2
		);

		$this->assertFalse( $this->sut->should_render_for_order( $order ), 'The filter should be able to remove the button' );
		$this->assertTrue( $filtered_value, 'The filter should receive true when no label extension is active' );
		$this->assertSame( $order->get_id(), $filtered_order->get_id(), 'The filter should receive the order being edited' );
	}

	/**
	 * @testdox Should not run the filter when the order cannot be shipped.
	 */
	public function test_filter_does_not_override_hard_checks(): void {
		$order = $this->create_order_with_product( true );
		add_filter( 'woocommerce_admin_order_should_render_shipping_label_button', '__return_true' );

		$this->assertFalse( $this->sut->should_render_for_order( $order ), 'The filter should not show the button for orders that cannot be shipped' );
	}

	/**
	 * @testdox Should render nothing before the order edit screen sets up the metabox.
	 */
	public function test_meta_box_renders_nothing_without_set_up_order(): void {
		ob_start();
		$this->sut->render_meta_box();
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'Nothing should render before the order edit screen sets up the metabox' );
	}

	/**
	 * @testdox Should put the metabox first in a saved box order that doesn't place it yet.
	 */
	public function test_saved_box_order_gets_metabox_first(): void {
		$order = $this->sut->handle_get_user_option_meta_box_order(
			array(
				'side'   => 'woocommerce-order-actions',
				'normal' => 'woocommerce-order-data,woocommerce-order-items',
			)
		);

		$this->assertSame( 'woocommerce-create-shipping-label,woocommerce-order-data,woocommerce-order-items', $order['normal'], 'The metabox should be prepended to the main column' );
	}

	/**
	 * @testdox Should keep a saved box order that already places the metabox.
	 */
	public function test_saved_box_order_keeps_user_position(): void {
		$saved = array(
			'side'   => 'woocommerce-create-shipping-label,woocommerce-order-actions',
			'normal' => 'woocommerce-order-data',
		);

		$this->assertSame( $saved, $this->sut->handle_get_user_option_meta_box_order( $saved ), 'A position the user chose should not change' );
	}
}
