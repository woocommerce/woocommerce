<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Orders\OutOfStockOrderItemLinks;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the OutOfStockOrderItemLinks class.
 *
 * @covers \Automattic\WooCommerce\Internal\Orders\OutOfStockOrderItemLinks
 */
class OutOfStockOrderItemLinksTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OutOfStockOrderItemLinks
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new OutOfStockOrderItemLinks();
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
	}

	/**
	 * Create an order item for a product with the given stock status and catalog visibility.
	 *
	 * @param string $stock_status       Stock status.
	 * @param string $catalog_visibility Catalog visibility.
	 * @return WC_Order_Item_Product
	 */
	private function create_item( string $stock_status, string $catalog_visibility = CatalogVisibility::VISIBLE ): WC_Order_Item_Product {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( $stock_status );
		$product->set_catalog_visibility( $catalog_visibility );
		$product->save();

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );

		return $item;
	}

	/**
	 * @testdox An out of stock product hidden by the "Hide out of stock items" setting keeps its link.
	 */
	public function test_restores_permalink_for_out_of_stock_product(): void {
		$item = $this->create_item( ProductStockStatus::OUT_OF_STOCK );

		$this->assertFalse( $item->get_product()->is_visible(), 'Product should be hidden from the catalog.' );
		$this->assertSame( $item->get_product()->get_permalink( $item ), $this->sut->maybe_restore_permalink( '', $item ) );
		$this->assertSame( 'yes', get_option( 'woocommerce_hide_out_of_stock_items' ), 'The setting should be left unchanged.' );
	}

	/**
	 * @testdox The order details template links a purchased product that is hidden because it is out of stock.
	 */
	public function test_order_details_item_template_links_out_of_stock_product(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = WC_Helper_Order::create_order( 0, $product );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();
		$item = current( $order->get_items() );

		$html = wc_get_template_html(
			'order/order-details-item.php',
			array(
				'order'              => $order,
				'item_id'            => $item->get_id(),
				'item'               => $item,
				'product'            => $item->get_product(),
				'show_purchase_note' => false,
				'purchase_note'      => '',
			)
		);

		$this->assertStringContainsString( '<a href="' . $product->get_permalink() . '">', $html );
	}

	/**
	 * @testdox The "Hide out of stock items" setting is restored even when the visibility check throws.
	 */
	public function test_restores_setting_when_visibility_check_throws(): void {
		$item     = $this->create_item( ProductStockStatus::OUT_OF_STOCK );
		$callback = function () {
			throw new \RuntimeException( 'Visibility check failed.' );
		};
		add_filter( 'woocommerce_product_is_visible', $callback );

		try {
			$this->sut->maybe_restore_permalink( '', $item );
			$this->fail( 'The exception should not be swallowed.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'Visibility check failed.', $e->getMessage() );
			$this->assertSame( 'yes', get_option( 'woocommerce_hide_out_of_stock_items' ), 'The setting should be restored.' );
		} finally {
			remove_filter( 'woocommerce_product_is_visible', $callback );
		}
	}

	/**
	 * @testdox An out of stock product that is also hidden from the catalog stays unlinked.
	 */
	public function test_keeps_empty_permalink_for_hidden_catalog_visibility(): void {
		$item = $this->create_item( ProductStockStatus::OUT_OF_STOCK, CatalogVisibility::HIDDEN );

		$this->assertSame( '', $this->sut->maybe_restore_permalink( '', $item ) );
	}

	/**
	 * @testdox Nothing changes when out of stock items are shown in the catalog.
	 */
	public function test_keeps_empty_permalink_when_setting_is_off(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$item = $this->create_item( ProductStockStatus::OUT_OF_STOCK, CatalogVisibility::HIDDEN );

		$this->assertSame( '', $this->sut->maybe_restore_permalink( '', $item ) );
	}

	/**
	 * @testdox An existing permalink is returned unchanged.
	 */
	public function test_keeps_existing_permalink(): void {
		$item = $this->create_item( ProductStockStatus::OUT_OF_STOCK );

		$this->assertSame( 'https://example.com/custom', $this->sut->maybe_restore_permalink( 'https://example.com/custom', $item ) );
	}
}
