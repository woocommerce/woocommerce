<?php
/**
 * Webflow Mapper Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow\WebflowMapper;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Webflow\Fixtures\MockWebflowData;

require_once __DIR__ . '/Fixtures/MockWebflowData.php';

/**
 * Tests for WebflowMapper.
 */
class WebflowMapperTest extends \WC_Unit_Test_Case {

	/**
	 * Mapper under test.
	 *
	 * @var WebflowMapper
	 */
	private WebflowMapper $mapper;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->mapper = new WebflowMapper();
	}

	/**
	 * Test the mapper implements the platform mapper interface.
	 */
	public function test_implements_platform_mapper_interface(): void {
		$this->assertInstanceOf( PlatformMapperInterface::class, $this->mapper );
	}

	/**
	 * Test basic field mapping for a simple product.
	 */
	public function test_simple_product_basic_fields(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertSame( 'Plain Tee', $result['name'] );
		$this->assertSame( 'plain-tee', $result['slug'] );
		$this->assertStringContainsString( 'Just a plain tee', $result['description'] );
		$this->assertSame( 'A tee.', $result['short_description'] );
		$this->assertSame( 'publish', $result['status'] );
		$this->assertSame( 'prod-simple-1', $result['original_product_id'] );
		$this->assertSame( '2024-01-01T00:00:00Z', $result['date_created_gmt'] );
		$this->assertFalse( $result['is_variable'] );
	}

	/**
	 * Test price mapping divides minor units by 100 and detects sale price.
	 */
	public function test_simple_product_prices(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertSame( '24.99', $result['regular_price'], 'compare-at-price greater than price becomes regular_price.' );
		$this->assertSame( '19.99', $result['sale_price'], 'price becomes sale_price when discounted.' );
	}

	/**
	 * Test that a zero price maps to '0.00' instead of being discarded.
	 */
	public function test_zero_price_maps_to_zero_regular_price(): void {
		$item = MockWebflowData::simple_product_item();

		$item->skus[0]->fieldData->price                = (object) array(
			'value' => 0,
			'unit'  => 'USD',
		);
		$item->skus[0]->fieldData->{'compare-at-price'} = null;

		$result = $this->mapper->map_product_data( $item );

		$this->assertSame( '0.00', $result['regular_price'] );
		$this->assertNull( $result['sale_price'] );
	}

	/**
	 * Zero-decimal currencies (e.g. JPY) are not divided by 100.
	 */
	public function test_zero_decimal_currency_price_is_not_divided(): void {
		$item = MockWebflowData::simple_product_item();

		$item->skus[0]->fieldData->price                = (object) array(
			'value' => 2000,
			'unit'  => 'JPY',
		);
		$item->skus[0]->fieldData->{'compare-at-price'} = null;

		$result = $this->mapper->map_product_data( $item );

		$this->assertSame( '2000', $result['regular_price'] );
	}

	/**
	 * Three-decimal currencies (e.g. KWD) divide minor units by 1000.
	 */
	public function test_three_decimal_currency_price_is_scaled_by_thousand(): void {
		$item = MockWebflowData::simple_product_item();

		$item->skus[0]->fieldData->price                = (object) array(
			'value' => 2000,
			'unit'  => 'KWD',
		);
		$item->skus[0]->fieldData->{'compare-at-price'} = null;

		$result = $this->mapper->map_product_data( $item );

		$this->assertSame( '2.000', $result['regular_price'] );
	}

	/**
	 * sku-properties present but only one SKU maps to a simple product (lone option dropped).
	 */
	public function test_single_sku_with_properties_is_simple(): void {
		$item       = MockWebflowData::variable_product_item();
		$item->skus = array( $item->skus[0] );

		$result = $this->mapper->map_product_data( $item );

		$this->assertFalse( $result['is_variable'] );
		$this->assertSame( array(), $result['attributes'] );
		$this->assertSame( array(), $result['variations'] );
	}

	/**
	 * Multiple SKUs but no sku-properties maps to a simple product.
	 */
	public function test_multiple_skus_without_properties_is_simple(): void {
		$item = MockWebflowData::variable_product_item();
		unset( $item->product->fieldData->{'sku-properties'} );

		$result = $this->mapper->map_product_data( $item );

		$this->assertFalse( $result['is_variable'] );
		$this->assertSame( array(), $result['attributes'] );
	}

	/**
	 * Test stock mapping for finite inventory.
	 */
	public function test_simple_product_stock(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertTrue( $result['manage_stock'] );
		$this->assertSame( 7, $result['stock_quantity'] );
		$this->assertSame( 'instock', $result['stock_status'] );
	}

	/**
	 * Test SKU passes through and weight is converted to the store unit.
	 */
	public function test_simple_product_sku_and_weight(): void {
		$original_unit = get_option( 'woocommerce_weight_unit' );
		update_option( 'woocommerce_weight_unit', 'kg' );

		try {
			$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

			$this->assertSame( 'PLAIN-001', $result['sku'] );

			// Fixture weight is 0.5 lb; converting to the store's kg unit pins the conversion
			// (0.5 lb ≈ 0.2268 kg) rather than accepting any positive float.
			$this->assertEqualsWithDelta( 0.2268, $result['weight'], 0.0005 );
		} finally {
			update_option( 'woocommerce_weight_unit', $original_unit );
		}
	}

	/**
	 * Test simple product dimensions pass through.
	 */
	public function test_simple_product_dimensions(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertSame( 10.0, $result['length'] );
		$this->assertSame( 5.0, $result['width'] );
		$this->assertSame( 2.0, $result['height'] );
	}

	/**
	 * Test that variation dimensions pass through and that null/missing dimensions stay null.
	 */
	public function test_variable_product_variation_dimensions(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$by_sku = array();
		foreach ( $result['variations'] as $variation ) {
			$by_sku[ $variation['sku'] ] = $variation;
		}

		// Red/S has dimensions in the fixture.
		$this->assertSame( 20.0, $by_sku['HOOD-RED-S']['length'] );
		$this->assertSame( 15.0, $by_sku['HOOD-RED-S']['width'] );
		$this->assertSame( 8.0, $by_sku['HOOD-RED-S']['height'] );

		// Other variants in the fixture omit dimensions — should stay null.
		$this->assertNull( $by_sku['HOOD-RED-M']['length'] );
		$this->assertNull( $by_sku['HOOD-BLUE-S']['width'] );
		$this->assertNull( $by_sku['HOOD-BLUE-M']['height'] );
	}

	/**
	 * @testdox Simple product omits the weight key entirely when weight is excluded from the fields to process.
	 */
	public function test_simple_product_omits_weight_key_when_field_excluded(): void {
		$mapper = new WebflowMapper( array( 'fields' => array( 'price', 'sku' ) ) );

		$result = $mapper->map_product_data( MockWebflowData::simple_product_item() );

		// The importer only touches weight when the key is present (array_key_exists), so an
		// excluded field must leave the key absent rather than null — a null would clear the
		// existing product's weight on a field-filtered re-run.
		$this->assertArrayNotHasKey( 'weight', $result, 'Excluded weight field must not leave a null weight key.' );
	}

	/**
	 * @testdox Simple product includes the weight key when weight is among the fields to process.
	 */
	public function test_simple_product_includes_weight_key_by_default(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertArrayHasKey( 'weight', $result, 'Weight key must be present when the field is processed.' );
	}

	/**
	 * @testdox Variations omit the weight key entirely when weight is excluded from the fields to process.
	 */
	public function test_variation_omits_weight_key_when_field_excluded(): void {
		$mapper = new WebflowMapper( array( 'fields' => array( 'price', 'sku' ) ) );

		$result = $mapper->map_product_data( MockWebflowData::variable_product_item() );

		$this->assertNotEmpty( $result['variations'], 'Fixture should produce variations.' );
		foreach ( $result['variations'] as $variation ) {
			$this->assertArrayNotHasKey( 'weight', $variation, 'Excluded weight field must not leave a null weight key on variations.' );
		}
	}

	/**
	 * Test SEO fields land in metafields.
	 */
	public function test_simple_product_seo_metafields(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertArrayHasKey( 'metafields', $result );
		$this->assertArrayHasKey( 'global_title_tag', $result['metafields'] );
		$this->assertArrayHasKey( 'global_description_tag', $result['metafields'] );
		$this->assertSame( 'Plain Tee — Buy Now', $result['metafields']['global_title_tag'] );
	}

	/**
	 * Test pre-resolved categories are propagated.
	 */
	public function test_simple_product_categories(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::simple_product_item() );

		$this->assertCount( 1, $result['categories'] );
		$this->assertSame( 'Shirts', $result['categories'][0]['name'] );
		$this->assertSame( 'shirts', $result['categories'][0]['slug'] );
	}

	/**
	 * Archived products map to draft status.
	 */
	public function test_archived_product_maps_to_draft(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::archived_product_item() );

		$this->assertSame( 'draft', $result['status'] );
	}

	/**
	 * Variable products are detected when multiple SKUs and sku-properties are present.
	 */
	public function test_variable_product_is_variable(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$this->assertTrue( $result['is_variable'] );
	}

	/**
	 * Variable product attributes use property names with all enum names as options.
	 */
	public function test_variable_product_attributes(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$this->assertCount( 2, $result['attributes'] );

		$by_name = array();
		foreach ( $result['attributes'] as $attr ) {
			$by_name[ $attr['name'] ] = $attr;
		}

		$this->assertArrayHasKey( 'Color', $by_name );
		$this->assertArrayHasKey( 'Size', $by_name );
		$this->assertSame( array( 'Red', 'Blue' ), $by_name['Color']['options'] );
		$this->assertSame( array( 'S', 'M' ), $by_name['Size']['options'] );
		$this->assertTrue( $by_name['Color']['is_variation'] );
	}

	/**
	 * Variable product variations resolve sku-values to attribute_name => option_name pairs.
	 */
	public function test_variable_product_variation_attributes_are_resolved(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$this->assertCount( 4, $result['variations'] );

		$by_sku = array();
		foreach ( $result['variations'] as $variation ) {
			$by_sku[ $variation['sku'] ] = $variation;
		}

		$this->assertSame( 'Red', $by_sku['HOOD-RED-S']['attributes']['Color'] );
		$this->assertSame( 'S', $by_sku['HOOD-RED-S']['attributes']['Size'] );
		$this->assertSame( 'Blue', $by_sku['HOOD-BLUE-M']['attributes']['Color'] );
		$this->assertSame( 'M', $by_sku['HOOD-BLUE-M']['attributes']['Size'] );
	}

	/**
	 * Variation with infinite inventory does not manage stock.
	 */
	public function test_variable_product_infinite_inventory_variation(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$by_sku = array();
		foreach ( $result['variations'] as $variation ) {
			$by_sku[ $variation['sku'] ] = $variation;
		}

		$this->assertFalse( $by_sku['HOOD-RED-M']['manage_stock'] );
		$this->assertNull( $by_sku['HOOD-RED-M']['stock_quantity'] );
		$this->assertSame( 'instock', $by_sku['HOOD-RED-M']['stock_status'] );
	}

	/**
	 * Variation with finite inventory at 0 is outofstock.
	 */
	public function test_variable_product_zero_inventory_variation_is_outofstock(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$by_sku = array();
		foreach ( $result['variations'] as $variation ) {
			$by_sku[ $variation['sku'] ] = $variation;
		}

		$this->assertTrue( $by_sku['HOOD-BLUE-S']['manage_stock'] );
		$this->assertSame( 0, $by_sku['HOOD-BLUE-S']['stock_quantity'] );
		$this->assertSame( 'outofstock', $by_sku['HOOD-BLUE-S']['stock_status'] );
	}

	/**
	 * Sale price detection on a variation (compare-at-price > price).
	 */
	public function test_variable_product_variation_sale_price(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$by_sku = array();
		foreach ( $result['variations'] as $variation ) {
			$by_sku[ $variation['sku'] ] = $variation;
		}

		$this->assertSame( '59.99', $by_sku['HOOD-BLUE-S']['regular_price'] );
		$this->assertSame( '54.99', $by_sku['HOOD-BLUE-S']['sale_price'] );
	}

	/**
	 * Images: product more-images + SKU main-images all appear in product.images[] and are deduped.
	 */
	public function test_images_deduped_across_product_and_variants(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$this->assertIsArray( $result['images'] );

		$urls = array_column( $result['images'], 'src' );
		$this->assertContains( 'https://cdn.webflow.test/gallery.jpg', $urls );
		$this->assertContains( 'https://cdn.webflow.test/red.jpg', $urls );
		$this->assertContains( 'https://cdn.webflow.test/blue.jpg', $urls );
		$this->assertSame( count( $urls ), count( array_unique( $urls ) ), 'Image URLs should be deduplicated.' );
	}

	/**
	 * Each variation's image_original_id keys into the product's images[].original_id.
	 */
	public function test_variation_image_keys_into_images_array(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$image_ids = array_column( $result['images'], 'original_id' );

		foreach ( $result['variations'] as $variation ) {
			$this->assertNotNull( $variation['image_original_id'], "Variation {$variation['sku']} should reference an image." );
			$this->assertContains( $variation['image_original_id'], $image_ids, "Variation {$variation['sku']} image_original_id must exist in images[]." );
		}
	}

	/**
	 * Exactly one image is marked is_featured.
	 */
	public function test_one_image_is_featured(): void {
		$result = $this->mapper->map_product_data( MockWebflowData::variable_product_item() );

		$featured_count = 0;
		foreach ( $result['images'] as $image ) {
			if ( ! empty( $image['is_featured'] ) ) {
				++$featured_count;
			}
		}
		$this->assertSame( 1, $featured_count );
	}

	/**
	 * Field selection: when 'images' is excluded, images and variation image refs are empty.
	 */
	public function test_excluding_images_field(): void {
		$mapper = new WebflowMapper(
			array(
				'fields' => array( 'name', 'slug', 'price', 'sku', 'stock', 'attributes' ),
			)
		);

		$result = $mapper->map_product_data( MockWebflowData::variable_product_item() );

		$this->assertSame( array(), $result['images'] );
		foreach ( $result['variations'] as $variation ) {
			$this->assertNull( $variation['image_original_id'] );
		}
	}
}
