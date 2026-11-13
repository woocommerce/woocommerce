<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the catalog fields of the product-create and product-update abilities.
 */
class ProductWritesTest extends WC_Unit_Test_Case {

	/**
	 * An administrator that every test acts as.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Log in as an administrator, who may create, edit and publish products.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$user_id );
	}

	/**
	 * @testdox Should read back every catalog field that product-create writes.
	 */
	public function test_create_round_trips_catalog_fields(): void {
		$category       = wp_insert_term( 'Hoodies', 'product_cat' )['term_id'];
		$tag            = wp_insert_term( 'Winter', 'product_tag' )['term_id'];
		$brand          = wp_insert_term( 'Acme', 'product_brand' )['term_id'];
		$shipping_class = wp_insert_term( 'Bulky', 'product_shipping_class' )['term_id'];
		$featured_image = $this->create_image( 'front.jpg' );
		$gallery_image  = $this->create_image( 'back.jpg' );

		$created = $this->execute(
			'woocommerce/product-create',
			array(
				'name'               => 'Hoodie',
				'regular_price'      => '60',
				'sale_price'         => '40',
				'categories'         => array( $category ),
				'tags'               => array( $tag ),
				'brands'             => array( $brand ),
				'images'             => array( $featured_image, $gallery_image ),
				'catalog_visibility' => 'search',
				'featured'           => true,
				'date_on_sale_from'  => '2030-01-01',
				'date_on_sale_to'    => '2030-01-31T18:30:00',
				'tax_status'         => ProductTaxStatus::SHIPPING,
				'sold_individually'  => true,
				'weight'             => '0.8',
				'dimensions'         => array(
					'length' => '30',
					'width'  => '20',
					'height' => '5',
				),
				'shipping_class'     => 'bulky',
			)
		);

		$product = $this->query_product( $created['product']['id'] );

		$this->assertSame( array( $category ), array_column( $product['categories'], 'id' ) );
		$this->assertSame( array( $tag ), array_column( $product['tags'], 'id' ) );
		$this->assertSame( array( $brand ), array_column( $product['brands'], 'id' ) );
		$this->assertSame( array( $featured_image, $gallery_image ), array_column( $product['images'], 'id' ) );
		$this->assertSame( 'search', $product['catalog_visibility'] );
		$this->assertTrue( $product['featured'] );
		$this->assertSame( '2030-01-01T00:00:00+00:00', $product['date_on_sale_from'] );
		$this->assertSame( '2030-01-31T18:30:00+00:00', $product['date_on_sale_to'] );
		$this->assertFalse( $product['on_sale'], 'The sale has not started yet.' );
		$this->assertSame( ProductTaxStatus::SHIPPING, $product['tax_status'] );
		$this->assertTrue( $product['sold_individually'] );
		$this->assertSame( '0.8', $product['weight'] );
		$this->assertSame( '30', $product['dimensions']['length'] );
		$this->assertSame( '20', $product['dimensions']['width'] );
		$this->assertSame( '5', $product['dimensions']['height'] );
		$this->assertSame( 'bulky', $product['shipping_class'] );
		$this->assertSame( $shipping_class, $product['shipping_class_id'] );
	}

	/**
	 * @testdox Should set terms, images, visibility and featured when updating an external product.
	 */
	public function test_update_sets_common_catalog_fields_on_external_product(): void {
		$product  = WC_Helper_Product::create_external_product();
		$category = wp_insert_term( 'Gadgets', 'product_cat' )['term_id'];
		$image    = $this->create_image( 'gadget.jpg' );

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'                 => $product->get_id(),
				'categories'         => array( $category ),
				'images'             => array( $image ),
				'catalog_visibility' => 'hidden',
				'featured'           => true,
			)
		)['product'];

		$this->assertSame( array( $category ), array_column( $updated['categories'], 'id' ) );
		$this->assertSame( array( $image ), array_column( $updated['images'], 'id' ) );
		$this->assertSame( 'hidden', $updated['catalog_visibility'] );
		$this->assertTrue( $updated['featured'] );
	}

	/**
	 * @testdox Should remove all images and fall back to the default category when sent empty lists.
	 */
	public function test_update_with_empty_lists_clears_images_and_categories(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_category_ids( array( wp_insert_term( 'Old', 'product_cat' )['term_id'] ) );
		$product->set_image_id( $this->create_image( 'old.jpg' ) );
		$product->set_gallery_image_ids( array( $this->create_image( 'older.jpg' ) ) );
		$product->save();

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'categories' => array(),
				'images'     => array(),
			)
		)['product'];

		$this->assertSame( array(), $updated['images'] );
		$this->assertSame( array( (int) get_option( 'default_product_cat' ) ), array_column( $updated['categories'], 'id' ) );
	}

	/**
	 * @testdox Should change only the dimensions sent and remove a shipping class sent as an empty slug.
	 */
	public function test_update_changes_only_sent_dimensions(): void {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'length'            => '10',
				'width'             => '20',
				'height'            => '30',
				'shipping_class_id' => wp_insert_term( 'Small', 'product_shipping_class' )['term_id'],
			)
		);

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'             => $product->get_id(),
				'dimensions'     => array(
					'width'  => '25',
					'height' => '',
				),
				'shipping_class' => '',
			)
		)['product'];

		$this->assertSame( '10', $updated['dimensions']['length'] );
		$this->assertSame( '25', $updated['dimensions']['width'] );
		$this->assertSame( '', $updated['dimensions']['height'] );
		$this->assertSame( '', $updated['shipping_class'] );
	}

	/**
	 * @testdox Should end a sale at the end of the day in store time when the end date has no time.
	 */
	public function test_date_only_sale_end_lasts_the_whole_day(): void {
		update_option( 'timezone_string', 'Europe/Berlin' );

		$created = $this->execute(
			'woocommerce/product-create',
			array(
				'name'            => 'Sale tee',
				'regular_price'   => '20',
				'sale_price'      => '15',
				'date_on_sale_to' => '2030-06-30',
			)
		);

		$this->assertSame( '2030-06-30T23:59:59+02:00', $created['product']['date_on_sale_to'] );
	}

	/**
	 * @testdox Should set catalog fields on a variable product and the fields its variations inherit.
	 */
	public function test_variable_product_fields(): void {
		$category       = wp_insert_term( 'Shirts', 'product_cat' )['term_id'];
		$shipping_class = wp_insert_term( 'Bulky', 'product_shipping_class' )['term_id'];
		$image          = $this->create_image( 'shirt.jpg' );

		$created = $this->execute(
			'woocommerce/product-create',
			array(
				'name'              => 'Shirt',
				'type'              => 'variable',
				'categories'        => array( $category ),
				'images'            => array( $image ),
				'featured'          => true,
				'tax_status'        => ProductTaxStatus::NONE,
				'sold_individually' => true,
				'weight'            => '0.3',
				'shipping_class'    => 'bulky',
				'attributes'        => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M' ),
					),
				),
				'variations'        => array(
					array(
						'attributes'    => array(
							array(
								'name'   => 'Size',
								'option' => 'S',
							),
						),
						'regular_price' => '10',
					),
				),
			)
		)['product'];

		$this->assertSame( array( $category ), array_column( $created['categories'], 'id' ) );
		$this->assertSame( array( $image ), array_column( $created['images'], 'id' ) );
		$this->assertTrue( $created['featured'] );
		$this->assertSame( ProductTaxStatus::NONE, $created['tax_status'] );

		$variation = wc_get_product( $created['variation_ids'][0] );

		$this->assertSame( ProductTaxStatus::NONE, $variation->get_tax_status(), 'A variation reads its tax status from the parent.' );
		$this->assertTrue( $variation->get_sold_individually() );
		$this->assertSame( '0.3', $variation->get_weight() );
		$this->assertSame( $shipping_class, $variation->get_shipping_class_id() );
	}

	/**
	 * @testdox Should accept sale dates and the tax status on external products, but not sold individually.
	 */
	public function test_external_product_fields(): void {
		$created = $this->execute(
			'woocommerce/product-create',
			array(
				'name'              => 'Affiliate tee',
				'type'              => 'external',
				'external_url'      => 'https://example.com/tee',
				'date_on_sale_from' => '2030-01-01',
				'tax_status'        => ProductTaxStatus::NONE,
			)
		);

		$this->assertSame( '2030-01-01T00:00:00+00:00', $created['product']['date_on_sale_from'] );
		$this->assertArrayNotHasKey( 'date_on_sale_to', $created['product'], 'A sale without an end date should omit it.' );
		$this->assertSame( ProductTaxStatus::NONE, $created['product']['tax_status'] );

		$rejected = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'              => 'Affiliate tee',
				'type'              => 'external',
				'sold_individually' => true,
			)
		);

		$this->assertWPError( $rejected );
		$this->assertSame( 'woocommerce_product_field_unsupported', $rejected->get_error_code() );
	}

	/**
	 * @testdox Should reject shipping fields on an external product.
	 *
	 * @testWith ["weight", "1"]
	 *           ["shipping_class", "bulky"]
	 *
	 * @param string $field Shipping field.
	 * @param string $value Field value.
	 */
	public function test_external_product_rejects_shipping_fields( string $field, string $value ): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name' => 'Affiliate tee',
				'type' => 'external',
				$field => $value,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_field_unsupported', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject unknown terms, non-image attachments, unknown shipping classes and impossible dates without saving.
	 */
	public function test_rejects_unknown_references(): void {
		$product = WC_Helper_Product::create_simple_product();
		$image   = $this->create_image( 'kept.jpg' );
		$product->set_image_id( $image );
		$product->save();
		$document = self::factory()->attachment->create_object(
			array(
				'file'           => 'manual.pdf',
				'post_mime_type' => 'application/pdf',
			)
		);

		$cases = array(
			'woocommerce_ability_product_term_not_found' => array( 'tags' => array( 999999 ) ),
			'woocommerce_ability_product_image_invalid'  => array( 'images' => array( $document ) ),
			'woocommerce_ability_product_shipping_class_not_found' => array( 'shipping_class' => 'missing' ),
			'woocommerce_ability_product_date_invalid'   => array( 'date_on_sale_from' => '2030-02-30' ),
		);

		foreach ( $cases as $error_code => $input ) {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute( array( 'id' => $product->get_id() ) + $input );

			$this->assertWPError( $result );
			$this->assertSame( $error_code, $result->get_error_code() );
		}

		$unknown_class = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'             => $product->get_id(),
				'shipping_class' => 'missing',
			)
		);
		$this->assertStringContainsString( '"missing"', $unknown_class->get_error_message() );

		$out_of_range_time = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'              => $product->get_id(),
				'date_on_sale_to' => '2030-01-31T24:00:00',
			)
		);
		$this->assertWPError( $out_of_range_time );

		$stored = wc_get_product( $product->get_id() );

		$this->assertSame( array(), $stored->get_tag_ids() );
		$this->assertSame( $image, (int) $stored->get_image_id() );
		$this->assertSame( 0, $stored->get_shipping_class_id() );
		$this->assertNull( $stored->get_date_on_sale_from() );
	}

	/**
	 * Execute an ability and check it succeeded.
	 *
	 * @param string $ability Ability name.
	 * @param array  $input   Ability input.
	 * @return array
	 */
	private function execute( string $ability, array $input ): array {
		$result = wp_get_ability( $ability )->execute( $input );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );

		return $result;
	}

	/**
	 * Read a product through products-query, as an agent would after a write.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function query_product( int $product_id ): array {
		return $this->execute( 'woocommerce/products-query', array( 'id' => $product_id ) )['products'][0];
	}

	/**
	 * Create an image attachment.
	 *
	 * @param string $file File name.
	 * @return int
	 */
	private function create_image( string $file ): int {
		return self::factory()->attachment->create_object(
			array(
				'file'           => $file,
				'post_mime_type' => 'image/jpeg',
			)
		);
	}
}
