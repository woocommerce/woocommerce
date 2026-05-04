<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\StoreApi\Formatters;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ShopperListItemSchema;
use WC_Unit_Test_Case;

/**
 * ShopperListItemSchemaTest class.
 */
class ShopperListItemSchemaTest extends WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var ShopperListItemSchema
	 */
	private $sut;

	/**
	 * ExtendSchema instance.
	 *
	 * @var ExtendSchema
	 */
	private $extend;

	/**
	 * SchemaController instance.
	 *
	 * @var SchemaController
	 */
	private $schema_controller;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );

		$this->extend            = new ExtendSchema( $formatters );
		$this->schema_controller = new SchemaController( $this->extend );
		$this->sut               = new ShopperListItemSchema( $this->extend, $this->schema_controller );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->sut               = null;
		$this->extend            = null;
		$this->schema_controller = null;
	}

	/**
	 * Build a minimal stored item record around a product.
	 *
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID, or 0.
	 * @param array  $variation    Variation attributes.
	 * @param string $title        Title snapshot.
	 * @return array
	 */
	private function build_item( int $product_id, int $variation_id = 0, array $variation = array(), string $title = 'Snapshot Title' ): array {
		return array(
			'key'                   => md5( (string) $product_id ),
			'product_id'            => $product_id,
			'variation_id'          => $variation_id,
			'variation'             => $variation,
			'quantity'              => 1,
			'date_added_gmt'        => '2024-04-25 03:20:00',
			'product_title_at_save' => $title,
		);
	}

	/**
	 * @testdox Should serve live product data and product_exists=true when the product exists.
	 */
	public function test_returns_live_data_when_product_exists(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Live T-Shirt',
				'regular_price' => 19.99,
			)
		);

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id(), 0, array(), 'Snapshot T-Shirt' ) );

		$this->assertTrue( $response['product_exists'], 'product_exists must be true when the product still exists' );
		$this->assertSame( 'Live T-Shirt', $response['name'], 'Live name should be served, not the snapshot' );
		$this->assertSame( $product->get_permalink(), $response['permalink'] );
		$this->assertNotNull( $response['prices'], 'Live prices should be populated' );

		$product->delete( true );
	}

	/**
	 * @testdox Should fall back to at-save snapshot data when the product no longer exists.
	 */
	public function test_falls_back_to_snapshot_when_product_missing(): void {
		$product    = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'About to be Deleted' ) );
		$product_id = $product->get_id();
		$item       = $this->build_item( $product_id, 0, array(), 'Snapshot Title' );
		wp_delete_post( $product_id, true );

		$response = $this->sut->get_item_response( $item );

		$this->assertFalse( $response['product_exists'], 'product_exists must be false when the product is gone' );
		$this->assertSame( 'Snapshot Title', $response['name'], 'Tombstone name should fall back to product_title_at_save' );
		$this->assertSame( '', $response['permalink'], 'permalink should be empty in tombstone path' );
		$this->assertSame( array(), $response['images'], 'No images should be returned for missing products' );
		$this->assertNull( $response['prices'], 'Live prices should be null for missing products' );
		$this->assertSame( 'Snapshot Title', $response['product_title_at_save'] );
	}
}
