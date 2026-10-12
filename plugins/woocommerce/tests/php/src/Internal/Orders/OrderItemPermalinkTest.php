<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Orders\OrderItemPermalink;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderItemPermalink class.
 */
class OrderItemPermalinkTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderItemPermalink
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OrderItemPermalink();
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
	}

	/**
	 * @testdox Should restore the link to an out-of-stock purchased product that is hidden from the catalog.
	 *
	 * @testWith ["simple"]
	 *           ["variation"]
	 *
	 * @param string $product_type The type of product that was purchased.
	 */
	public function test_restores_link_for_out_of_stock_product( string $product_type ): void {
		$product = 'variation' === $product_type ? $this->create_out_of_stock_variation() : $this->create_out_of_stock_product();
		$order   = $this->create_order_with( $product );
		$item    = $this->get_first_item( $order );

		$this->assertFalse( $product->is_visible(), 'Precondition: the product should be hidden from the catalog' );

		$result = $this->sut->handle_woocommerce_order_item_permalink( '', $item, $order );

		$this->assertSame( $product->get_permalink( $item ), $result, 'Out-of-stock purchased products should keep their link' );
	}

	/**
	 * @testdox Should not link an out-of-stock purchased product that is hidden for a reason other than stock.
	 *
	 * @testWith ["hidden catalog visibility"]
	 *           ["draft status"]
	 *           ["visibility filter"]
	 *
	 * @param string $reason Why the product is hidden, besides being out of stock.
	 */
	public function test_keeps_link_empty_when_product_is_hidden_for_another_reason( string $reason ): void {
		$product = $this->create_out_of_stock_product();
		if ( 'hidden catalog visibility' === $reason ) {
			$product->set_catalog_visibility( CatalogVisibility::HIDDEN );
			$product->save();
		} elseif ( 'draft status' === $reason ) {
			$product->set_status( ProductStatus::DRAFT );
			$product->save();
		} else {
			add_filter( 'woocommerce_product_is_visible', '__return_false' );
		}
		$order = $this->create_order_with( $product );

		$result = $this->sut->handle_woocommerce_order_item_permalink( '', $this->get_first_item( $order ), $order );

		$this->assertSame( '', $result, "Products hidden because of {$reason} should not be linked" );
	}

	/**
	 * @testdox Should not change a permalink that was already provided.
	 */
	public function test_keeps_existing_permalink(): void {
		$product = $this->create_out_of_stock_product();
		$order   = $this->create_order_with( $product );

		$result = $this->sut->handle_woocommerce_order_item_permalink( 'https://example.com/custom', $this->get_first_item( $order ), $order );

		$this->assertSame( 'https://example.com/custom', $result, 'A permalink set earlier should be left alone' );
	}

	/**
	 * @testdox Should link out-of-stock purchased products in the customer's order details.
	 */
	public function test_order_details_link_out_of_stock_product(): void {
		$product = $this->create_out_of_stock_product();
		$order   = $this->create_order_with( $product );
		wp_set_current_user( $order->get_customer_id() );

		ob_start();
		woocommerce_order_details_table( $order->get_id() );
		$html = ob_get_clean();

		$this->assertStringContainsString(
			sprintf( '<a href="%s">%s</a>', $product->get_permalink(), $product->get_name() ),
			$html,
			'The order details should link to the out-of-stock product'
		);
	}

	/**
	 * Create a simple product that is out of stock.
	 *
	 * @return WC_Product
	 */
	private function create_out_of_stock_product(): WC_Product {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();

		return $product;
	}

	/**
	 * Create a product variation that is out of stock.
	 *
	 * @return WC_Product
	 */
	private function create_out_of_stock_variation(): WC_Product {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$variation->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$variation->save();

		return $variation;
	}

	/**
	 * Create a completed order for a new customer containing the given product.
	 *
	 * @param WC_Product $product The purchased product.
	 * @return WC_Order
	 */
	private function create_order_with( WC_Product $product ): WC_Order {
		$order = wc_create_order( array( 'customer_id' => $this->factory->user->create( array( 'role' => 'customer' ) ) ) );
		$order->add_product( $product, 1 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
	}

	/**
	 * Get the first product line item of an order.
	 *
	 * @param WC_Order $order The order.
	 * @return WC_Order_Item_Product
	 */
	private function get_first_item( WC_Order $order ): WC_Order_Item_Product {
		$items = $order->get_items();

		return reset( $items );
	}
}
