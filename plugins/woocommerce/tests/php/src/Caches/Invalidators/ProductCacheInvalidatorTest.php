<?php
/**
 * ProductCacheInvalidatorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Caches\Invalidators;

use Automattic\WooCommerce\Caches\Invalidators\ProductCacheInvalidator;
use Automattic\WooCommerce\Caches\Invalidators\CacheInvalidatorInterface;

/**
 * Tests for the ProductCacheInvalidator class.
 */
class ProductCacheInvalidatorTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var ProductCacheInvalidator
	 */
	private $sut;

	/**
	 * Captured action parameters.
	 *
	 * @var array
	 */
	private $captured_actions = array();

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut              = new ProductCacheInvalidator();
		$this->captured_actions = array();

		$this->sut->init();

		add_action(
			'woocommerce_product_cache_invalidated',
			function ( $product_id, $operation, $context ) {
				$this->captured_actions[] = array(
					'product_id' => $product_id,
					'operation'  => $operation,
					'context'    => $context,
				);
			},
			10,
			3
		);
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		$this->captured_actions = array();
		remove_all_actions( 'woocommerce_product_cache_invalidated' );
		parent::tearDown();
	}

	/**
	 * @testdox Implements CacheInvalidatorInterface.
	 */
	public function test_implements_interface() {
		$this->assertInstanceOf( CacheInvalidatorInterface::class, $this->sut );
	}

	/**
	 * @testdox Creating a new product fires create operation.
	 */
	public function test_product_creation() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_CREATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Updating an existing product fires update operation.
	 */
	public function test_product_update() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->captured_actions = array();

		$product->set_name( 'Updated Product' );
		$product->save();

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Deleting a product fires delete operation.
	 */
	public function test_product_deletion() {
		$product    = \WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		$this->captured_actions = array();

		wp_delete_post( $product_id, true );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_DELETE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Trashing a product fires update operation.
	 */
	public function test_product_trash() {
		$product = \WC_Helper_Product::create_simple_product();

		$this->captured_actions = array();

		wp_trash_post( $product->get_id() );

		$this->assertNotEmpty( $this->captured_actions );
		$this->assertEquals( $product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Creating a variable product with variations fires appropriate invalidations.
	 */
	public function test_variable_product_creation() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();

		// Should have create event for parent + create events for each variation + update events for parent for each variation.
		// With 2 variations: 1 parent create + (2 variation creates + 2 parent updates) = 5 events.
		$this->assertGreaterThanOrEqual( 3, count( $this->captured_actions ) );

		$this->assertEquals( $parent_product->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_CREATE, $this->captured_actions[0]['operation'] );
	}

	/**
	 * @testdox Updating a product variation fires update for variation and parent.
	 */
	public function test_variation_update() {
		$parent_product = \WC_Helper_Product::create_variation_product();
		$variations     = $parent_product->get_children();
		$variation      = wc_get_product( $variations[0] );

		$this->captured_actions = array();

		$variation->set_regular_price( '99.99' );
		$variation->save();

		// Should have 2 actions: variation update + parent update.
		$this->assertCount( 2, $this->captured_actions );

		$this->assertEquals( $variation->get_id(), $this->captured_actions[0]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_UPDATE, $this->captured_actions[0]['operation'] );
		$this->assertEquals( $parent_product->get_id(), $this->captured_actions[0]['context']['parent_id'] );

		$this->assertEquals( $parent_product->get_id(), $this->captured_actions[1]['product_id'] );
		$this->assertEquals( CacheInvalidatorInterface::OPERATION_UPDATE, $this->captured_actions[1]['operation'] );
		$this->assertEquals( $variation->get_id(), $this->captured_actions[1]['context']['variation_id'] );
	}

	/**
	 * @testdox Invalidating a product fires woocommerce_product_cache_invalidated action with correct parameters.
	 */
	public function test_invalidate_method() {
		$product_id = 123;
		$operation  = CacheInvalidatorInterface::OPERATION_UPDATE;
		$context    = array( 'test' => 'value' );

		$this->sut->invalidate( $product_id, $operation, $context );

		$this->assertCount( 1, $this->captured_actions );
		$this->assertEquals( $product_id, $this->captured_actions[0]['product_id'] );
		$this->assertEquals( $operation, $this->captured_actions[0]['operation'] );
		$this->assertEquals( $context, $this->captured_actions[0]['context'] );
	}
}
