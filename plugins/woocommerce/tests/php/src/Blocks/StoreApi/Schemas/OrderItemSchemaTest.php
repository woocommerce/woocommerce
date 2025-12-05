<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\StoreApi\Schemas\V1\OrderItemSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use WC_Helper_Order;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * OrderItemSchemaTest class.
 */
class OrderItemSchemaTest extends TestCase {
	/**
	 * The system under test.
	 *
	 * @var OrderItemSchema
	 */
	private $sut;

	/**
	 * ExtendSchema instance.
	 *
	 * @var ExtendSchema
	 */
	private $mock_extend;

	/**
	 * SchemaController instance.
	 *
	 * @var SchemaController
	 */
	private $schema_controller;

	/**
	 * Set up before test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up formatters and extend schema.
		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$this->mock_extend       = new ExtendSchema( $formatters );
		$this->schema_controller = new SchemaController( $this->mock_extend );
		$this->sut               = $this->schema_controller->get( OrderItemSchema::IDENTIFIER );
	}

	/**
	 * Tear down after test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->sut               = null;
		$this->schema_controller = null;
		$this->mock_extend       = null;
	}

	/**
	 * Test that get_item_response handles deleted products gracefully.
	 *
	 * Reproduces the issue: when a product is deleted and an order
	 * containing that product is fetched via StoreAPI, it should not
	 * throw an error.
	 */
	public function test_get_item_response_with_deleted_product(): void {
		// Arrange - Create order with a product, then delete the product.
		$order   = WC_Helper_Order::create_order();
		$items   = $order->get_items();
		$item    = reset( $items );
		$product = $item->get_product();

		// Verify product exists initially.
		$this->assertInstanceOf( 'WC_Product', $product );

		// Delete the product.
		$product_id = $product->get_id();
		wp_delete_post( $product_id, true );

		// Verify product is now deleted (get_product returns false).
		$item_after_delete = new \WC_Order_Item_Product( $item->get_id() );
		$this->assertFalse( $item_after_delete->get_product() );

		// Act - Get item response for order item with deleted product.
		$result = $this->sut->get_item_response( $item_after_delete );

		// Verify key fields exist.
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'short_description', $result );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertArrayHasKey( 'sku', $result );
		$this->assertArrayHasKey( 'permalink', $result );
		$this->assertArrayHasKey( 'catalog_visibility', $result );
		$this->assertArrayHasKey( 'prices', $result );
		$this->assertArrayHasKey( 'images', $result );
		$this->assertArrayHasKey( 'variation', $result );
		$this->assertArrayHasKey( 'sold_individually', $result );

		// Verify product-specific fields have safe defaults.
		$this->assertEquals( '', $result['sku'] );
		$this->assertEquals( '', $result['permalink'] );
		$this->assertEquals( 'hidden', $result['catalog_visibility'] );
		$this->assertFalse( $result['sold_individually'] );
		$this->assertIsArray( $result['images'] );
		$this->assertEmpty( $result['images'] );
		$this->assertIsArray( $result['variation'] );
		$this->assertEmpty( $result['variation'] );

		// Verify order-level data is preserved.
		$this->assertEquals( $item_after_delete->get_id(), $result['id'] );
		$this->assertEquals( $item_after_delete->get_name(), $result['name'] );
		$this->assertEquals( $item_after_delete->get_quantity(), $result['quantity'] );
	}

	/**
	 * Test that get_item_response works correctly with existing product.
	 *
	 * Ensures the fix doesn't break normal behavior.
	 */
	public function test_get_item_response_with_existing_product(): void {
		// Arrange - Create order with a product.
		$order   = WC_Helper_Order::create_order();
		$items   = $order->get_items();
		$item    = reset( $items );
		$product = $item->get_product();

		// Verify product exists.
		$this->assertInstanceOf( 'WC_Product', $product );

		// Act - Get item response.
		$result = $this->sut->get_item_response( $item );

		// Verify key fields exist.
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'sku', $result );
		$this->assertArrayHasKey( 'permalink', $result );
		$this->assertArrayHasKey( 'prices', $result );

		// Verify product data is populated.
		$this->assertEquals( $product->get_sku(), $result['sku'] );
		$this->assertEquals( $product->get_permalink(), $result['permalink'] );
		$this->assertEquals( $product->get_catalog_visibility(), $result['catalog_visibility'] );
		$this->assertEquals( $product->is_sold_individually(), $result['sold_individually'] );

		// Verify order data.
		$this->assertEquals( $item->get_id(), $result['id'] );
		$this->assertEquals( $item->get_name(), $result['name'] );
		$this->assertEquals( $item->get_quantity(), $result['quantity'] );
	}

	/**
	 * Test that simple products return empty variation array.
	 *
	 * Simple products don't have variations, so the variation field should be empty.
	 * This is consistent with the cart endpoint behavior.
	 */
	public function test_get_item_response_simple_product_has_empty_variation(): void {
		// Arrange - Create a simple product with attributes.
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_attributes(
			array(
				'size' => new \WC_Product_Attribute(
					array(
						'name'    => 'size',
						'options' => array( 'Small', 'Medium' ),
					)
				),
			)
		);
		$product->save();

		// Create an order with this product.
		$order = new \WC_Order();
		$order->add_product( $product, 1 );
		$order->save();

		$items = $order->get_items();
		$item  = reset( $items );

		// Act - Get item response.
		$result = $this->sut->get_item_response( $item );

		// Assert - variation should be empty for simple products.
		$this->assertArrayHasKey( 'variation', $result );
		$this->assertIsArray( $result['variation'] );
		$this->assertEmpty( $result['variation'] );

		// Cleanup.
		$order->delete( true );
		$product->delete( true );
	}

	/**
	 * Test that product variations return variation attributes.
	 *
	 * Product variations should have their selected variation attributes
	 * included in the response.
	 */
	public function test_get_item_response_variation_product_has_variation_data(): void {
		// Arrange - Create a variable product with variations.
		$variable_product = \WC_Helper_Product::create_variation_product();
		$variations       = $variable_product->get_children();
		$variation        = wc_get_product( $variations[0] );

		// Create an order with the variation.
		$order = new \WC_Order();
		$order->add_product( $variation, 1 );
		$order->save();

		$items = $order->get_items();
		$item  = reset( $items );

		// Act - Get item response.
		$result = $this->sut->get_item_response( $item );

		// Assert - variation should contain the variation attributes.
		$this->assertArrayHasKey( 'variation', $result );
		$this->assertIsArray( $result['variation'] );
		$this->assertNotEmpty( $result['variation'] );

		// Verify structure of variation data.
		$first_variation = $result['variation'][0];
		$this->assertArrayHasKey( 'raw_attribute', $first_variation );
		$this->assertArrayHasKey( 'attribute', $first_variation );
		$this->assertArrayHasKey( 'value', $first_variation );

		// Cleanup.
		$order->delete( true );
		$variable_product->delete( true );
	}
}
