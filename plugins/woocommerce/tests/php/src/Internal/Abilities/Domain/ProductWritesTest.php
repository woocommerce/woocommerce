<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductUpdate;
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
	private static $administrator_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Log in as an administrator, who may create, edit and publish products.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$administrator_id );
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
	 * @testdox Should set and read back the GTIN of a product and of its variations.
	 */
	public function test_global_unique_id_round_trips(): void {
		$created = $this->execute(
			'woocommerce/product-create',
			array(
				'name'             => 'Mug',
				'global_unique_id' => '4006381333931',
			)
		)['product'];

		$this->assertSame( '4006381333931', $this->query_product( $created['id'] )['global_unique_id'] );

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'               => $created['id'],
				'global_unique_id' => '978-3-16-148410-X',
			)
		)['product'];

		$this->assertSame( '978-3-16-148410-X', $updated['global_unique_id'] );

		$variable = $this->execute(
			'woocommerce/product-create',
			array(
				'name'             => 'Cap',
				'type'             => 'variable',
				'global_unique_id' => '0012345678905',
				'attributes'       => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M' ),
					),
				),
				'variations'       => array(
					array(
						'attributes'       => array(
							array(
								'name'   => 'Size',
								'option' => 'S',
							),
						),
						'global_unique_id' => '0012345678912',
					),
				),
			)
		)['product'];

		$this->assertSame( '0012345678905', $variable['global_unique_id'] );
		$this->assertSame( '0012345678912', $this->query_product( $variable['variation_ids'][0] )['global_unique_id'] );
	}

	/**
	 * @testdox Should refuse a GTIN that another product has, that a variation repeats, or that has letters.
	 */
	public function test_rejects_duplicate_or_invalid_global_unique_id(): void {
		$taken = WC_Helper_Product::create_simple_product();
		$taken->set_global_unique_id( '4006381333931' );
		$taken->save();
		$product = WC_Helper_Product::create_simple_product();

		$duplicate = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'               => $product->get_id(),
				'global_unique_id' => '4006381333931',
			)
		);

		$this->assertWPError( $duplicate );
		$this->assertSame( 'product_invalid_global_unique_id', $duplicate->get_error_code() );
		$this->assertSame( '', wc_get_product( $product->get_id() )->get_global_unique_id() );

		$letters = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'               => $product->get_id(),
				'global_unique_id' => 'ABC123',
			)
		);

		$this->assertWPError( $letters );

		$misplaced_x = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'               => $product->get_id(),
				'global_unique_id' => '08044X2957',
			)
		);

		$this->assertWPError( $misplaced_x );
		$this->assertSame( 'product_invalid_global_unique_id_format', $misplaced_x->get_error_code() );

		$product->set_global_unique_id( '5901234123457' );
		$product->save();
		foreach ( array( "\n", "5901234123464\n" ) as $newline ) {
			$result = wp_get_ability( 'woocommerce/product-update' )->execute(
				array(
					'id'               => $product->get_id(),
					'global_unique_id' => $newline,
				)
			);

			$this->assertWPError( $result );
			$this->assertSame( '5901234123457', wc_get_product( $product->get_id() )->get_global_unique_id() );
		}

		$size      = static fn( string $option ): array => array(
			array(
				'name'   => 'Size',
				'option' => $option,
			),
		);
		$repeating = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'             => 'Cap',
				'type'             => 'variable',
				'global_unique_id' => '0012345678905',
				'attributes'       => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M' ),
					),
				),
				'variations'       => array(
					array(
						'attributes'       => $size( 'S' ),
						'global_unique_id' => '0012345678912',
					),
					array(
						'attributes'       => $size( 'M' ),
						'global_unique_id' => '0012345678905',
					),
				),
			)
		);

		$this->assertWPError( $repeating );
		$this->assertSame( 'woocommerce_product_variation_global_unique_id_duplicate', $repeating->get_error_code() );

		$mixed_case = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'       => 'Book',
				'type'       => 'variable',
				'attributes' => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M' ),
					),
				),
				'variations' => array(
					array(
						'attributes'       => $size( 'S' ),
						'global_unique_id' => '080442957X',
					),
					array(
						'attributes'       => $size( 'M' ),
						'global_unique_id' => '080442957x',
					),
				),
			)
		);

		$this->assertWPError( $mixed_case, 'An ISBN-10 check digit X is the same in either case.' );
		$this->assertSame( 'woocommerce_product_variation_global_unique_id_duplicate', $mixed_case->get_error_code() );
	}

	/**
	 * @testdox Should turn a simple product into a variable one with global attributes and priced variations, and keep its ID.
	 */
	public function test_update_turns_a_simple_product_into_a_variable_one(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'sku' => 'tee' ) );
		$size    = WC_Helper_Product::create_attribute( 'size', array( 'small', 'large' ) );
		$color   = WC_Helper_Product::create_attribute( 'color', array( 'red', 'blue' ) );
		$items   = array();
		$prices  = array( '11.00', '12.00', '13.00', '14.00' );
		foreach ( array( 'small', 'large' ) as $size_slug ) {
			foreach ( array( 'red', 'blue' ) as $color_slug ) {
				$items[] = array(
					'attributes'    => array(
						array(
							'id'     => $size['attribute_id'],
							'option' => $size_slug,
						),
						array(
							'id'     => $color['attribute_id'],
							'option' => $color_slug,
						),
					),
					'regular_price' => $prices[ count( $items ) ],
				);
			}
		}

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'type'       => 'variable',
				'attributes' => array(
					array(
						'id'      => $size['attribute_id'],
						'options' => array( 'small', 'large' ),
					),
					array(
						'id'      => $color['attribute_id'],
						'options' => array( 'red', 'blue' ),
					),
				),
				'variations' => $items,
			)
		)['product'];

		$this->assertSame( $product->get_id(), $updated['id'] );
		$this->assertSame( 'variable', $updated['type'] );
		$this->assertCount( 4, $updated['variation_ids'] );
		$this->assertSame( 'tee', $updated['sku'] );

		$variable = wc_get_product( $product->get_id() );
		$this->assertInstanceOf( \WC_Product_Variable::class, $variable );
		$this->assertSame( array( '11.00', '12.00', '13.00', '14.00' ), array_values( $variable->get_variation_prices()['regular_price'] ) );
		$this->assertSame( '', get_post_meta( $product->get_id(), '_regular_price', true ), 'The old price of the simple product should be gone.' );
		$this->assertSame( array( '11.00', '12.00', '13.00', '14.00' ), get_post_meta( $product->get_id(), '_price', false ) );
	}

	/**
	 * @testdox Should turn a $source product into a variable one with a variation, and keep its ID.
	 *
	 * @testWith ["grouped"]
	 *           ["external"]
	 *
	 * @param string $source Product type before the change.
	 */
	public function test_update_turns_a_grouped_or_external_product_into_a_variable_one( string $source ): void {
		$product = 'grouped' === $source ? WC_Helper_Product::create_grouped_product() : WC_Helper_Product::create_external_product();

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'         => $product->get_id(),
				'type'       => 'variable',
				'attributes' => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M' ),
					),
				),
				'variations' => array(
					array(
						'attributes'    => array(
							array(
								'name'   => 'Size',
								'option' => 'S',
							),
						),
						'regular_price' => '20',
					),
				),
			)
		)['product'];

		$this->assertSame( $product->get_id(), $updated['id'] );
		$this->assertSame( 'variable', $updated['type'] );
		$this->assertCount( 1, $updated['variation_ids'] );
		$this->assertSame( array( '20' ), get_post_meta( $product->get_id(), '_price', false ) );
	}

	/**
	 * @testdox Should clear the old price and sale dates when a simple product becomes variable without variations.
	 */
	public function test_update_to_variable_without_variations_clears_the_old_price(): void {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price'     => '10',
				'sale_price'        => '8',
				'date_on_sale_from' => '2020-01-01',
				'date_on_sale_to'   => '2040-01-01',
			)
		);

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'   => $product->get_id(),
				'type' => 'variable',
			)
		)['product'];

		$this->assertSame( 'variable', $updated['type'] );
		foreach ( array( '_price', '_regular_price', '_sale_price' ) as $meta_key ) {
			$this->assertFalse( metadata_exists( 'post', $product->get_id(), $meta_key ), "$meta_key should be gone." );
		}
		$this->assertEmpty( get_post_meta( $product->get_id(), '_sale_price_dates_from', true ) );
		$this->assertEmpty( get_post_meta( $product->get_id(), '_sale_price_dates_to', true ) );
		$this->assertSame(
			array(
				'min_price' => '0.0000',
				'max_price' => '0.0000',
			),
			$wpdb->get_row( $wpdb->prepare( "SELECT min_price, max_price FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product->get_id() ), ARRAY_A )
		);
	}

	/**
	 * @testdox Should turn a variable product into a simple one, keep its ID and delete its variations.
	 */
	public function test_update_turns_a_variable_product_into_a_simple_one(): void {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'            => $variable->get_id(),
				'type'          => 'simple',
				'regular_price' => '15',
			)
		);

		$this->assertGreaterThanOrEqual( 2, count( $variations ) );
		$this->assertSame( $variable->get_id(), $updated['product']['id'] );
		$this->assertSame( 'simple', $updated['product']['type'] );
		$this->assertSame( '15', $updated['product']['regular_price'] );
		$this->assertSame( array(), $updated['product']['variation_ids'] );
		$this->assertSame( array(), $updated['variations'] );
		foreach ( $variations as $variation_id ) {
			$this->assertNull( get_post( $variation_id ), "Variation $variation_id should be deleted." );
		}
	}

	/**
	 * @testdox Should drop the variation prices from the price lookup when a variable product becomes $type without a price.
	 *
	 * @testWith ["simple"]
	 *           ["external"]
	 *           ["grouped"]
	 *
	 * @param string $type New product type.
	 */
	public function test_change_out_of_variable_drops_the_variation_prices( string $type ): void {
		global $wpdb;

		$items  = array();
		$prices = array(
			'S' => '11',
			'M' => '12',
		);
		foreach ( $prices as $option => $price ) {
			$items[] = array(
				'attributes'    => array(
					array(
						'name'   => 'Size',
						'option' => $option,
					),
				),
				'regular_price' => $price,
			);
		}
		$created  = $this->execute(
			'woocommerce/product-create',
			array(
				'name'       => 'Shirt',
				'type'       => 'variable',
				'attributes' => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S', 'M' ),
					),
				),
				'variations' => $items,
			)
		);
		$variable = wc_get_product( $created['product']['id'] );

		$this->execute(
			'woocommerce/product-update',
			array(
				'id'   => $variable->get_id(),
				'type' => $type,
			)
		);

		$this->assertEmpty( array_filter( get_post_meta( $variable->get_id(), '_price', false ), 'strlen' ) );
		$this->assertLessThanOrEqual( 1, count( get_post_meta( $variable->get_id(), '_price', false ) ) );
		$lookup = $wpdb->get_row( $wpdb->prepare( "SELECT min_price, max_price FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $variable->get_id() ), ARRAY_A );
		$this->assertEmpty( (float) $lookup['min_price'] );
		$this->assertEmpty( (float) $lookup['max_price'] );
	}

	/**
	 * @testdox Should require the delete permission for a type change that deletes variations.
	 */
	public function test_type_change_that_deletes_variations_requires_the_delete_permission(): void {
		$variable = WC_Helper_Product::create_variation_product();
		$simple   = WC_Helper_Product::create_simple_product();
		$deny     = static fn( $caps, $cap ) => 'delete_post' === $cap ? array( 'do_not_allow' ) : $caps;

		add_filter( 'map_meta_cap', $deny, 10, 2 );
		$refused = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $variable->get_id(),
				'type' => 'simple',
			)
		);
		$allowed = ProductUpdate::can_update_product(
			array(
				'id'   => $simple->get_id(),
				'type' => 'external',
			)
		);
		remove_filter( 'map_meta_cap', $deny, 10 );

		$this->assertWPError( $refused );
		$this->assertSame( 'ability_invalid_permissions', $refused->get_error_code() );
		$this->assertTrue( $allowed, 'A type change that deletes nothing needs only the edit permission.' );
		$this->assertSame( 'variable', wc_get_product( $variable->get_id() )->get_type() );
		$this->assertNotEmpty( wc_get_product( $variable->get_id() )->get_children() );
	}

	/**
	 * @testdox Should keep the variations when the save of a type change fails after WooCommerce deleted them.
	 */
	public function test_failed_type_change_keeps_the_variations(): void {
		$variable   = WC_Helper_Product::create_variation_product();
		$variations = $variable->get_children();
		$fail       = static function () {
			throw new \Exception( 'Save failed.' );
		};

		add_action( 'woocommerce_product_type_changed', $fail, 20 );
		$failed = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $variable->get_id(),
				'type' => 'simple',
			)
		);
		remove_action( 'woocommerce_product_type_changed', $fail, 20 );

		$this->assertWPError( $failed );
		$this->assertSame( 'woocommerce_product_update_failed', $failed->get_error_code() );
		$this->assertSame( 'variable', wc_get_product( $variable->get_id() )->get_type() );
		foreach ( $variations as $variation_id ) {
			$this->assertInstanceOf( \WC_Product_Variation::class, wc_get_product( $variation_id ) );
		}
	}

	/**
	 * @testdox Should refuse a type change of a variation, variations sent with a change that deletes them, and a simple-product price on a variable product.
	 */
	public function test_update_refuses_type_changes_that_cannot_work(): void {
		$variable = WC_Helper_Product::create_variation_product();
		$simple   = WC_Helper_Product::create_simple_product();
		$ability  = wp_get_ability( 'woocommerce/product-update' );

		$from_variation = $ability->execute(
			array(
				'id'   => $variable->get_children()[0],
				'type' => 'simple',
			)
		);
		$with_items     = $ability->execute(
			array(
				'id'         => $variable->get_id(),
				'type'       => 'simple',
				'variations' => array( array( 'regular_price' => '1' ) ),
			)
		);
		$price          = $ability->execute(
			array(
				'id'            => $simple->get_id(),
				'type'          => 'variable',
				'regular_price' => '20',
			)
		);

		$this->assertSame( 'woocommerce_product_type_unsupported', $from_variation->get_error_code() );
		$this->assertSame( 'woocommerce_product_type_unsupported', $with_items->get_error_code() );
		$this->assertSame( 'woocommerce_product_field_unsupported', $price->get_error_code() );
		$this->assertSame( 'simple', wc_get_product( $simple->get_id() )->get_type() );
		$this->assertNotEmpty( wc_get_product( $variable->get_id() )->get_children() );
	}

	/**
	 * @testdox Should set the stock of a variable product, which its variations use, and refuse a stock status for it.
	 */
	public function test_variable_product_stock_fields(): void {
		$created = $this->execute(
			'woocommerce/product-create',
			array(
				'name'           => 'Shirt',
				'type'           => 'variable',
				'manage_stock'   => true,
				'stock_quantity' => 5,
				'attributes'     => array(
					array(
						'name'    => 'Size',
						'options' => array( 'S' ),
					),
				),
				'variations'     => array(
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

		$this->assertTrue( $created['manage_stock'] );
		$this->assertSame( 5, $created['stock_quantity'] );

		$updated = $this->execute(
			'woocommerce/product-update',
			array(
				'id'             => $created['id'],
				'stock_quantity' => 0,
			)
		)['product'];

		$variation = wc_get_product( $created['variation_ids'][0] );
		$this->assertSame( 0, $updated['stock_quantity'] );
		$this->assertSame( 'outofstock', $updated['stock_status'] );
		$this->assertSame( 0, $variation->get_stock_quantity(), 'A variation without its own stock uses the stock of the product.' );
		$this->assertFalse( $variation->is_in_stock() );

		$status = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'           => $created['id'],
				'stock_status' => 'instock',
			)
		);
		$this->assertWPError( $status );
		$this->assertSame( 'woocommerce_product_field_unsupported', $status->get_error_code() );
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
