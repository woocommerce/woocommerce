<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductsQuery;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the products-query ability.
 */
class ProductsQueryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should return the categories, tags and brands of each product with decoded names.
	 */
	public function test_returns_product_terms(): void {
		$category = wp_insert_term( 'Hats & Caps', 'product_cat' );
		$tag      = wp_insert_term( 'Summer', 'product_tag' );
		$brand    = wp_insert_term( 'Acme', 'product_brand' );
		$product  = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( array( $category['term_id'] ) );
		$product->set_tag_ids( array( $tag['term_id'] ) );
		$product->set_brand_ids( array( $brand['term_id'] ) );
		$product->save();

		$item = $this->query_single( $product->get_id() );

		$this->assertSame(
			array(
				array(
					'id'   => $category['term_id'],
					'name' => 'Hats & Caps',
					'slug' => 'hats-caps',
				),
			),
			$item['categories'],
			'Term names should be decoded.'
		);
		$this->assertSame( 'summer', $item['tags'][0]['slug'] );
		$this->assertSame( 'Acme', $item['brands'][0]['name'] );
	}

	/**
	 * @testdox Should return only products that match a category, tag or brand filter.
	 *
	 * @testWith ["category", "product_cat", "set_category_ids"]
	 *           ["tag", "product_tag", "set_tag_ids"]
	 *           ["brand", "product_brand", "set_brand_ids"]
	 *
	 * @param string $filter   Input filter.
	 * @param string $taxonomy Taxonomy.
	 * @param string $setter   Product setter.
	 */
	public function test_filters_by_term( string $filter, string $taxonomy, string $setter ): void {
		$term    = wp_insert_term( 'Matching ' . $filter, $taxonomy );
		$matched = WC_Helper_Product::create_simple_product();
		$matched->$setter( array( $term['term_id'] ) );
		$matched->save();
		WC_Helper_Product::create_simple_product();

		$result = ProductsQuery::execute( array( $filter => array( $term['term_id'] ) ) );

		$this->assertIsArray( $result );
		$this->assertSame( array( $matched->get_id() ), array_column( $result['products'], 'id' ) );
	}

	/**
	 * @testdox Should match products in a child term when filtering by its parent category or brand.
	 *
	 * @testWith ["category", "product_cat", "set_category_ids"]
	 *           ["brand", "product_brand", "set_brand_ids"]
	 *
	 * @param string $filter   Input filter.
	 * @param string $taxonomy Hierarchical taxonomy.
	 * @param string $setter   Product setter.
	 */
	public function test_filters_include_child_terms( string $filter, string $taxonomy, string $setter ): void {
		$parent  = wp_insert_term( 'Parent ' . $filter, $taxonomy );
		$child   = wp_insert_term( 'Child ' . $filter, $taxonomy, array( 'parent' => $parent['term_id'] ) );
		$matched = WC_Helper_Product::create_simple_product();
		$matched->$setter( array( $child['term_id'] ) );
		$matched->save();
		WC_Helper_Product::create_simple_product();

		$result = ProductsQuery::execute( array( $filter => array( $parent['term_id'] ) ) );

		$this->assertIsArray( $result );
		$this->assertSame( array( $matched->get_id() ), array_column( $result['products'], 'id' ) );
	}

	/**
	 * @testdox Should return the description, shipping, tax and stock fields of a product.
	 */
	public function test_returns_catalog_fields(): void {
		$shipping_class = wp_insert_term( 'Bulky', 'product_shipping_class' );
		$product        = WC_Helper_Product::create_simple_product(
			true,
			array(
				'description'       => '<p>Long</p>',
				'short_description' => 'Short',
				'weight'            => '1.5',
				'length'            => '10',
				'width'             => '20',
				'height'            => '',
				'shipping_class_id' => $shipping_class['term_id'],
				'tax_status'        => ProductTaxStatus::SHIPPING,
				'tax_class'         => 'reduced-rate',
				'manage_stock'      => true,
				'backorders'        => 'notify',
				'low_stock_amount'  => 3,
			)
		);

		$item = $this->query_single( $product->get_id() );

		$this->assertSame( '<p>Long</p>', $item['description'] );
		$this->assertSame( 'Short', $item['short_description'] );
		$this->assertSame( '1.5', $item['weight'] );
		$this->assertSame(
			array(
				'length' => '10',
				'width'  => '20',
				'height' => '',
			),
			$item['dimensions']
		);
		$this->assertTrue( $item['shipping_required'] );
		$this->assertSame( 'bulky', $item['shipping_class'] );
		$this->assertSame( $shipping_class['term_id'], $item['shipping_class_id'] );
		$this->assertSame( ProductTaxStatus::SHIPPING, $item['tax_status'] );
		$this->assertSame( 'reduced-rate', $item['tax_class'] );
		$this->assertSame( 'notify', $item['backorders'] );
		$this->assertSame( 3, $item['low_stock_amount'] );
	}

	/**
	 * @testdox Should report a product as on sale only while its sale dates include today.
	 *
	 * @testWith ["-1 day", true]
	 *           ["+1 day", false]
	 *
	 * @param string $sale_start Relative start date of the sale.
	 * @param bool   $expected   Expected on_sale value.
	 */
	public function test_on_sale_follows_sale_dates( string $sale_start, bool $expected ): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price'     => '10',
				'sale_price'        => '5',
				'date_on_sale_from' => gmdate( 'Y-m-d', strtotime( $sale_start ) ),
				'date_on_sale_to'   => gmdate( 'Y-m-d', strtotime( '+1 week' ) ),
			)
		);

		$item = $this->query_single( $product->get_id() );

		$this->assertSame( '5', $item['sale_price'], 'The sale price is set either way.' );
		$this->assertSame( $expected, $item['on_sale'] );
	}

	/**
	 * @testdox Should return the featured image first and then the gallery, without duplicates.
	 */
	public function test_returns_images_in_order(): void {
		$featured = $this->create_image( 'featured.jpg', 'Featured', 'Front view' );
		$gallery  = $this->create_image( 'gallery.jpg', 'Gallery', '' );
		$product  = WC_Helper_Product::create_simple_product();
		$product->set_image_id( $featured );
		$product->set_gallery_image_ids( array( $gallery, $featured ) );
		$product->save();

		$item = $this->query_single( $product->get_id() );

		$this->assertSame( array( $featured, $gallery ), array_column( $item['images'], 'id' ) );
		$this->assertSame( 'Featured', $item['images'][0]['name'] );
		$this->assertSame( 'Front view', $item['images'][0]['alt'] );
		$this->assertStringEndsWith( 'featured.jpg', $item['images'][0]['src'] );
	}

	/**
	 * @testdox Should return the image title as stored, without texturized quotes and dashes.
	 */
	public function test_returns_plain_image_names(): void {
		$image   = $this->create_image( 'mug.jpg', 'Bob\'s mug -- "x"', '' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( $image );
		$product->save();

		$item = $this->query_single( $product->get_id() );

		$this->assertSame( 'Bob\'s mug -- "x"', $item['images'][0]['name'] );
	}

	/**
	 * @testdox Should return no images and omit the low stock amount for a product without them.
	 */
	public function test_returns_empty_values_for_unset_fields(): void {
		$product = WC_Helper_Product::create_simple_product();

		$item = $this->query_single( $product->get_id() );

		$this->assertSame( array(), $item['images'], 'A product without images should get no placeholder.' );
		$this->assertArrayNotHasKey( 'low_stock_amount', $item );
	}

	/**
	 * @testdox Should match the output schema when listing products.
	 */
	public function test_list_output_matches_schema(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( $this->create_image( 'schema.jpg', 'Schema', '' ) );
		$product->save();

		$result = ProductsQuery::execute( array() );
		$schema = ProductsQuery::get_registration_args()['output_schema'];

		$this->assertTrue( rest_validate_value_from_schema( $result, $schema, 'products' ) );
	}

	/**
	 * @testdox Should return each product exactly once across pages when products share a creation date.
	 */
	public function test_pages_products_with_the_same_date_once_each(): void {
		$ids = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_date_created( '2026-01-01T10:00:00' );
			$product->save();
			$ids[] = $product->get_id();
		}

		$paged = array();
		for ( $page = 1; $page <= 3; $page++ ) {
			$result = ProductsQuery::execute(
				array(
					'page'     => $page,
					'per_page' => 3,
				)
			);
			$paged  = array_merge( $paged, array_column( $result['products'], 'id' ) );
		}

		rsort( $ids );
		$this->assertSame( $ids, $paged, 'Products with the same date should be sorted by ID, newest first.' );
	}

	/**
	 * Query one product by ID and check the result against the output schema.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function query_single( int $product_id ): array {
		$result = ProductsQuery::execute( array( 'id' => $product_id ) );
		$schema = ProductsQuery::get_registration_args()['output_schema'];

		$this->assertIsArray( $result );
		$this->assertTrue( rest_validate_value_from_schema( $result, $schema, 'products' ), 'The result should match the output schema.' );

		return $result['products'][0];
	}

	/**
	 * Create an image attachment.
	 *
	 * @param string $file  File name.
	 * @param string $title Attachment title.
	 * @param string $alt   Alt text.
	 * @return int
	 */
	private function create_image( string $file, string $title, string $alt ): int {
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => $file,
				'post_title'     => $title,
				'post_mime_type' => 'image/jpeg',
			)
		);
		if ( '' !== $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		}

		return $attachment_id;
	}
}
