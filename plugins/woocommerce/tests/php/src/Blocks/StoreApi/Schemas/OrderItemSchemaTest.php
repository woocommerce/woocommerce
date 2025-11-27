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
	 * Test that get_item_response handles custom attributes that conflict with global attribute slugs.
	 *
	 * When a simple product has a custom attribute with the same slug as a global attribute,
	 * the Store API should return the attribute value correctly without throwing a fatal error.
	 */
	public function test_get_item_response_with_custom_attribute_conflicting_with_global(): void {
		// Arrange - Create a global attribute.
		$attribute_id = wc_create_attribute(
			array(
				'name'         => 'Test Size',
				'slug'         => 'test-size',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);

		// Create a simple product with a custom attribute using the same slug.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product with Conflicting Attribute' );
		$product->set_regular_price( '10' );

		// Add a custom attribute with the same slug as the global attribute.
		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 ); // 0 means custom attribute, not taxonomy.
		$attribute->set_name( 'test-size' ); // Same slug as global attribute.
		$attribute->set_options( array( 'Custom XL' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );

		$product->set_attributes( array( 'test-size' => $attribute ) );
		$product->save();

		// Create an order with this product.
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->save();

		$items = $order->get_items();
		$item  = reset( $items );

		$result = $this->sut->get_item_response( $item );

		// Assert - Verify no error and variation data is correct.
		$this->assertArrayHasKey( 'variation', $result );
		$this->assertIsArray( $result['variation'] );
		$this->assertNotEmpty( $result['variation'] );

		// Find the test-size attribute in the variation array.
		$found_attribute = null;
		foreach ( $result['variation'] as $variation_attr ) {
			if ( 'test-size' === $variation_attr['raw_attribute'] ) {
				$found_attribute = $variation_attr;
				break;
			}
		}

		$this->assertNotNull( $found_attribute, 'Custom attribute should be in variation array' );
		$this->assertEquals( 'Custom XL', $found_attribute['value'], 'Custom attribute value should be preserved' );

		// Clean up.
		$product->delete( true );
		$order->delete( true );
		wc_delete_attribute( $attribute_id );
	}

	/**
	 * Test that get_item_response handles custom attributes without global conflicts.
	 *
	 * When a simple product has a custom attribute that doesn't conflict with any global attribute,
	 * the Store API should return the attribute value correctly.
	 */
	public function test_get_item_response_with_non_conflicting_custom_attribute(): void {
		// Arrange - Create a simple product with a custom attribute.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product with Custom Attribute' );
		$product->set_regular_price( '10' );

		// Add a custom attribute with a unique name (no global attribute with this slug).
		$attribute = new \WC_Product_Attribute();
		$attribute->set_id( 0 ); // 0 means custom attribute, not taxonomy.
		$attribute->set_name( 'unique-custom-attr' );
		$attribute->set_options( array( 'my custom value' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );

		$product->set_attributes( array( 'unique-custom-attr' => $attribute ) );
		$product->save();

		// Create an order with this product.
		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->save();

		$items = $order->get_items();
		$item  = reset( $items );

		// Act - Get item response.
		$result = $this->sut->get_item_response( $item );

		// Assert - Verify variation data is correct.
		$this->assertArrayHasKey( 'variation', $result );
		$this->assertIsArray( $result['variation'] );
		$this->assertNotEmpty( $result['variation'] );

		// Find the custom attribute in the variation array.
		$found_attribute = null;
		foreach ( $result['variation'] as $variation_attr ) {
			if ( 'unique-custom-attr' === $variation_attr['raw_attribute'] ) {
				$found_attribute = $variation_attr;
				break;
			}
		}

		$this->assertNotNull( $found_attribute, 'Custom attribute should be in variation array' );
		$this->assertEquals( 'unique-custom-attr', $found_attribute['attribute'], 'Custom attribute label should match name' );
		$this->assertEquals( 'my custom value', $found_attribute['value'], 'Custom attribute value should be preserved' );

		// Clean up.
		$product->delete( true );
		$order->delete( true );
	}
}
