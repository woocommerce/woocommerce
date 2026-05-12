<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Schemas;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
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
	 * Build a ShopperListItem around a product.
	 *
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID, or 0.
	 * @param array  $variation    Variation attributes.
	 * @param string $title        Title snapshot.
	 */
	private function build_item( int $product_id, int $variation_id = 0, array $variation = array(), string $title = 'Snapshot Title' ): ShopperListItem {
		return ShopperListItem::from_array(
			array(
				'key'                   => md5( (string) $product_id ),
				'product_id'            => $product_id,
				'variation_id'          => $variation_id,
				'variation'             => $variation,
				'quantity'              => 1,
				'date_added_gmt'        => '2024-04-25 03:20:00',
				'product_title_at_save' => $title,
			)
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
	 * @testdox Should expose price_html for live products and an empty string for tombstones.
	 */
	public function test_price_html_is_populated_for_live_products(): void {
		$product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Priced T-Shirt',
				'regular_price' => 19.99,
			)
		);

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'price_html', $response, 'price_html must be present on the response' );
		$this->assertIsString( $response['price_html'] );
		$this->assertNotSame( '', $response['price_html'], 'price_html must be non-empty for a live priced product' );
		$this->assertStringContainsString( 'woocommerce-Price-amount', $response['price_html'], 'price_html should be the formatted markup from wc_price' );

		$product->delete( true );
	}

	/**
	 * @testdox Should expose image_html with the configured product thumbnail when one is set.
	 */
	public function test_image_html_uses_product_thumbnail_when_available(): void {
		$attachment_id = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$product       = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Imaged T-Shirt' ) );
		$product->set_image_id( $attachment_id );
		$product->save();

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'image_html', $response, 'image_html must be present on the response' );
		$this->assertIsString( $response['image_html'] );
		$this->assertStringContainsString( '<img', $response['image_html'], 'image_html must be a fully-formed <img> element' );
		$this->assertStringContainsString( 'srcset=', $response['image_html'], 'image_html must carry the responsive srcset attribute' );

		$product->delete( true );
		wp_delete_attachment( $attachment_id, true );
	}

	/**
	 * @testdox Should expose image_html using the WooCommerce placeholder when the product has no image.
	 */
	public function test_image_html_falls_back_to_placeholder_when_product_has_no_image(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'No-Image T-Shirt' ) );
		$product->set_image_id( 0 );
		$product->save();

		$response = $this->sut->get_item_response( $this->build_item( $product->get_id() ) );

		$this->assertArrayHasKey( 'image_html', $response );
		$this->assertSame(
			(string) wc_placeholder_img( 'woocommerce_thumbnail' ),
			$response['image_html'],
			'image_html for an image-less product must equal the configured placeholder markup'
		);

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
		$this->assertSame( 'Snapshot Title', $response['name'], 'Tombstone name should fall back to the at-save title snapshot' );
		$this->assertSame( '', $response['permalink'], 'permalink should be empty in tombstone path' );
		$this->assertSame( array(), $response['images'], 'No images should be returned for missing products' );
		$this->assertNull( $response['prices'], 'Live prices should be null for missing products' );
		$this->assertArrayNotHasKey( 'product_title_at_save', $response, 'Internal at-save title snapshot should not leak into the public response' );
	}

	/**
	 * @testdox Should expose an empty price_html and the placeholder image_html for tombstones.
	 */
	public function test_tombstone_returns_empty_price_html_and_placeholder_image_html(): void {
		$product    = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Going Away',
				'regular_price' => 24.99,
			)
		);
		$product_id = $product->get_id();
		$item       = $this->build_item( $product_id, 0, array(), 'Going Away' );
		wp_delete_post( $product_id, true );

		$response = $this->sut->get_item_response( $item );

		$this->assertArrayHasKey( 'price_html', $response );
		$this->assertSame( '', $response['price_html'], 'Tombstones must not advertise a price' );
		$this->assertArrayHasKey( 'image_html', $response );
		$this->assertSame(
			(string) wc_placeholder_img( 'woocommerce_thumbnail' ),
			$response['image_html'],
			'Tombstones must use the placeholder image markup'
		);
	}
}
