<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Products Controller Tests.
 */
class Products extends ControllerTestCase {
	/**
	 * Product IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * All product IDs owned by the class, including grouped children.
	 *
	 * @var int[]
	 */
	private static $owned_product_ids = array();

	/**
	 * Create immutable catalog rows shared by all test methods.
	 */
	public static function wpSetUpBeforeClass(): void {
		$products = self::with_direct_product_attribute_lookup_updates(
			static function () {
				$fixtures = new FixtureData();

				return array(
					$fixtures->get_simple_product(
						array(
							'name'          => 'Test Product 1',
							'stock_status'  => ProductStockStatus::IN_STOCK,
							'regular_price' => 10,
							'weight'        => '2.5',
							'length'        => '10',
							'width'         => '5',
							'height'        => '3',
						)
					),
					$fixtures->get_simple_product(
						array(
							'name'          => 'Test Product 2',
							'stock_status'  => ProductStockStatus::IN_STOCK,
							'regular_price' => 10,
						)
					),
					$fixtures->get_grouped_product( array() ),
				);
			}
		);

		self::$product_ids       = array_map(
			static fn( $product ) => $product->get_id(),
			$products
		);
		self::$owned_product_ids = array_merge( self::$product_ids, $products[2]->get_children() );
	}

	/**
	 * Delete class products through WooCommerce data stores.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( self::$owned_product_ids );
	}

	/**
	 * Reload shared test product data before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->products = array_map(
			'wc_get_product',
			self::$product_ids
		);
	}

	/**
	 * Test getting item.
	 */
	public function test_get_item() {
		$fixtures = new FixtureData();
		$image_id = $fixtures->create_image_attachment( $this->products[0]->get_id() );
		$this->products[0]->set_image_id( $image_id );
		$this->products[0]->save();

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( $this->products[0]->get_title(), $data['name'] );
		$this->assertEquals( $this->products[0]->get_slug(), $data['slug'] );
		$this->assertEquals( $this->products[0]->get_permalink(), $data['permalink'] );
		$this->assertEquals( $this->products[0]->get_sku(), $data['sku'] );
		$this->assertEquals( $this->products[0]->get_price(), $data['prices']->price / ( 10 ** $data['prices']->currency_minor_unit ) );
		$this->assertEquals( $this->products[0]->get_average_rating(), $data['average_rating'] );
		$this->assertEquals( $this->products[0]->get_review_count(), $data['review_count'] );
		$this->assertEquals( $this->products[0]->has_options(), $data['has_options'] );
		$this->assertEquals( $this->products[0]->is_purchasable(), $data['is_purchasable'] );
		$this->assertEquals( $this->products[0]->is_in_stock(), $data['is_in_stock'] );
		$this->assertEquals( $this->products[0]->add_to_cart_text(), $data['add_to_cart']->text );
		$this->assertEquals( $this->products[0]->add_to_cart_description(), $data['add_to_cart']->description );
		$this->assertEquals( $this->products[0]->single_add_to_cart_text(), $data['add_to_cart']->single_text );
		$this->assertEquals( $this->products[0]->is_on_sale(), $data['on_sale'] );
		$this->assertEquals( $this->products[0]->get_weight(), $data['weight'] );
		$this->assertEquals( $this->products[0]->get_length(), $data['dimensions']->length );
		$this->assertEquals( $this->products[0]->get_width(), $data['dimensions']->width );
		$this->assertEquals( $this->products[0]->get_height(), $data['dimensions']->height );
		$this->assertEquals( wc_format_weight( (float) $this->products[0]->get_weight() ), $data['formatted_weight'] );
		$this->assertEquals( html_entity_decode( wc_format_dimensions( (array) $this->products[0]->get_dimensions( false ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ), $data['formatted_dimensions'] );
		$this->assertCount( 0, $data['grouped_products'] );

		$this->assertCount( 1, $data['images'] );
		$this->assertIsObject( $data['images'][0] );
		$this->assertEquals( $this->products[0]->get_image_id(), $data['images'][0]->id );
		$this->assertNotEmpty( wp_parse_url( $data['images'][0]->src, PHP_URL_HOST ) );
		$this->assertNotEmpty( wp_parse_url( $data['images'][0]->thumbnail, PHP_URL_HOST ) );

		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test get grouped product.
	 */
	public function test_grouped_product() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[2]->get_id() ) );
		$data     = $response->get_data();

		$grouped_product_ids = array_map(
			function ( $child ) {
				return $child->get_id();
			},
			$this->products[2]->get_visible_children(),
		);
		$total_ids           = count( $grouped_product_ids );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( $total_ids, $data['grouped_products'] );

		for ( $index = 0; $index < $total_ids; $index++ ) {
			$this->assertEquals( $grouped_product_ids[ $index ], $data['grouped_products'][ $index ] );
		}
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$product_ids = array_merge(
			array_map(
				function ( $product ) {
					return $product->get_id();
				},
				$this->products
			),
			$this->products[2]->get_children()
		);
		$request     = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$response    = rest_get_server()->dispatch( $request );
		$data        = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEqualsCanonicalizing( $product_ids, array_column( $data, 'id' ) );
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'variation', $data[0] );
		$this->assertArrayHasKey( 'permalink', $data[0] );
		$this->assertArrayHasKey( 'description', $data[0] );
		$this->assertArrayHasKey( 'on_sale', $data[0] );
		$this->assertArrayHasKey( 'sku', $data[0] );
		$this->assertArrayHasKey( 'prices', $data[0] );
		$this->assertArrayHasKey( 'average_rating', $data[0] );
		$this->assertArrayHasKey( 'review_count', $data[0] );
		$this->assertArrayHasKey( 'images', $data[0] );
		$this->assertArrayHasKey( 'has_options', $data[0] );
		$this->assertArrayHasKey( 'is_purchasable', $data[0] );
		$this->assertArrayHasKey( 'is_in_stock', $data[0] );
		$this->assertArrayHasKey( 'weight', $data[0] );
		$this->assertArrayHasKey( 'dimensions', $data[0] );
		$this->assertArrayHasKey( 'add_to_cart', $data[0] );
		$this->assertArrayHasKey( 'extensions', $data[0] );
	}

	/**
	 * Test searching by SKU.
	 */
	public function test_search_by_sku() {
		$product = new \WC_Product_Simple();
		$product->set_sku( 'search-for-this-value' );
		$product->save();

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'search', 'search-for-this' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $data ) );
		$this->assertArrayHasKey( 'sku', $data[0] );
		$this->assertEquals( 'search-for-this-value', $data[0]['sku'] );
	}

	/**
	 * Test conversion of product to rest response.
	 */
	public function test_prepare_item() {
		$schemas    = new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend );
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( $schemas );
		$schema     = $schemas->get( 'product' );
		$controller = $routes->get( 'products' );
		$response   = $controller->prepare_item_for_response( $this->products[0], new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'variation', $data );
		$this->assertArrayHasKey( 'permalink', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'on_sale', $data );
		$this->assertArrayHasKey( 'sku', $data );
		$this->assertArrayHasKey( 'prices', $data );
		$this->assertArrayHasKey( 'average_rating', $data );
		$this->assertArrayHasKey( 'review_count', $data );
		$this->assertArrayHasKey( 'images', $data );
		$this->assertArrayHasKey( 'has_options', $data );
		$this->assertArrayHasKey( 'is_purchasable', $data );
		$this->assertArrayHasKey( 'is_in_stock', $data );
		$this->assertArrayHasKey( 'weight', $data );
		$this->assertArrayHasKey( 'dimensions', $data );
		$this->assertArrayHasKey( 'add_to_cart', $data );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'products' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertArrayHasKey( 'search', $params );
		$this->assertArrayHasKey( 'after', $params );
		$this->assertArrayHasKey( 'before', $params );
		$this->assertArrayHasKey( 'date_column', $params );
		$this->assertArrayHasKey( 'exclude', $params );
		$this->assertArrayHasKey( 'include', $params );
		$this->assertArrayHasKey( 'offset', $params );
		$this->assertArrayHasKey( 'order', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'parent', $params );
		$this->assertArrayHasKey( 'parent_exclude', $params );
		$this->assertArrayHasKey( 'type', $params );
		$this->assertArrayHasKey( 'sku', $params );
		$this->assertArrayHasKey( 'featured', $params );
		$this->assertArrayHasKey( 'category', $params );
		$this->assertArrayHasKey( 'tag', $params );
		$this->assertArrayHasKey( 'on_sale', $params );
		$this->assertArrayHasKey( 'min_price', $params );
		$this->assertArrayHasKey( 'max_price', $params );
		$this->assertArrayHasKey( 'stock_status', $params );
		$this->assertArrayHasKey( 'category_operator', $params );
		$this->assertArrayHasKey( 'tag_operator', $params );
		$this->assertArrayHasKey( 'attribute_relation', $params );
		$this->assertArrayHasKey( 'attributes', $params );
		$this->assertArrayHasKey( 'catalog_visibility', $params );
		$this->assertArrayHasKey( 'rating', $params );
		$this->assertArrayHasKey( 'related', $params );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		// Give the product an image so the nested image schema is validated too.
		$fixtures = new FixtureData();
		$image_id = $fixtures->create_image_attachment( $this->products[0]->get_id() );
		$this->products[0]->set_image_id( $image_id );
		$this->products[0]->save();

		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'products' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( $this->products[0], new \WP_REST_Request() );
		$validate   = new ValidateSchema( $schema );

		$this->assertNotEmpty( $response->get_data()['images'], 'The product response must include an image so its schema is exercised.' );
		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) ); // @phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

		wp_delete_attachment( $image_id, true );
	}

	/**
	 * Test return types when no image is available.
	 */
	public function test_without_image() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'              => 'Test Product 1',
				'stock_status'      => ProductStockStatus::IN_STOCK,
				'regular_price'     => 10,
				'image_id'          => '',
				'gallery_image_ids' => array(),
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		$this->assertIsArray( $data['images'] );
		$this->assertCount( 0, $data['images'] );

		$image_id = $fixtures->create_image_attachment();
		$product  = $fixtures->get_simple_product(
			array(
				'name'              => 'Test Product 1',
				'stock_status'      => ProductStockStatus::IN_STOCK,
				'regular_price'     => 10,
				'image_id'          => $image_id,
				'gallery_image_ids' => array(),
			)
		);
		wp_delete_attachment( $image_id, true );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		$this->assertIsArray( $data['images'] );
		$this->assertCount( 0, $data['images'] );
	}

	/**
	 * Test product category image return types.
	 */
	public function test_product_category_image_return_types() {
		$fixtures = new FixtureData();
		$image_id = $fixtures->create_image_attachment();
		$term     = wp_insert_term( 'Test Category', 'product_cat' );

		update_term_meta( $term['term_id'], 'thumbnail_id', $image_id );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $term['term_id'] ) );
		$data     = $response->get_data();

		$this->assertIsObject( $data['image'] );
		$this->assertEquals( $data['image']->id, $image_id );

		delete_term_meta( $term['term_id'], 'thumbnail_id' );

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/categories/' . $term['term_id'] ) );
		$data     = $response->get_data();

		$this->assertNull( $data['image'] );
	}

	/**
	 * @testdox Single product response should include self and collection _links.
	 */
	public function test_single_product_has_self_and_collection_links() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'self', $links );
		$this->assertArrayHasKey( 'collection', $links );
		$this->assertStringContainsString( '/wc/store/v1/products/' . $this->products[0]->get_id(), $links['self'][0]['href'] );
		$this->assertStringContainsString( '/wc/store/v1/products', $links['collection'][0]['href'] );
	}

	/**
	 * @testdox Product with upsells should include embeddable upsells link.
	 */
	public function test_product_with_upsells_has_embeddable_upsells_link() {
		$fixtures       = new FixtureData();
		$upsell_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Upsell Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 20,
			)
		);

		$main_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Main Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
				'upsell_ids'    => array( $upsell_product->get_id() ),
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $main_product->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'upsells', $links );
		$this->assertStringContainsString( 'include=' . $upsell_product->get_id(), $links['upsells'][0]['href'] );
		$this->assertArrayHasKey( 'embeddable', $links['upsells'][0]['attributes'] );
		$this->assertTrue( $links['upsells'][0]['attributes']['embeddable'] );
	}

	/**
	 * @testdox Product with cross-sells should include embeddable cross_sells link.
	 */
	public function test_product_with_cross_sells_has_embeddable_cross_sells_link() {
		$fixtures           = new FixtureData();
		$cross_sell_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Cross-sell Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 15,
			)
		);

		$main_product = $fixtures->get_simple_product(
			array(
				'name'           => 'Main Product',
				'stock_status'   => ProductStockStatus::IN_STOCK,
				'regular_price'  => 10,
				'cross_sell_ids' => array( $cross_sell_product->get_id() ),
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $main_product->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'cross_sells', $links );
		$this->assertStringContainsString( 'include=' . $cross_sell_product->get_id(), $links['cross_sells'][0]['href'] );
		$this->assertArrayHasKey( 'embeddable', $links['cross_sells'][0]['attributes'] );
		$this->assertTrue( $links['cross_sells'][0]['attributes']['embeddable'] );
	}

	/**
	 * @testdox Product without upsells should not include upsells link.
	 */
	public function test_product_without_upsells_has_no_upsells_link() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'upsells', $links );
	}

	/**
	 * @testdox Product without cross-sells should not include cross_sells link.
	 */
	public function test_product_without_cross_sells_has_no_cross_sells_link() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'cross_sells', $links );
	}

	/**
	 * @testdox Collection endpoint should return products with _links.
	 */
	public function test_collection_endpoint_returns_links() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertGreaterThan( 0, count( $data ) );

		foreach ( $data as $product ) {
			$this->assertArrayHasKey( '_links', $product );
			$this->assertArrayHasKey( 'self', $product['_links'] );
			$this->assertArrayHasKey( 'collection', $product['_links'] );
		}
	}

	/**
	 * @testdox Context parameter should accept embed value.
	 */
	public function test_context_accepts_embed_value() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() );
		$request->set_param( 'context', 'embed' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * @testdox Product variation should include up link to parent product.
	 */
	public function test_product_variation_has_up_link() {
		$fixtures  = new FixtureData();
		$attribute = FixtureData::get_product_attribute( 'color', array( 'red', 'blue' ) );

		$variable_product = $fixtures->get_variable_product(
			array(
				'name' => 'Variable Product',
			),
			array( $attribute )
		);

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array( 'pa_color' => 'red' ),
			array(
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $variation->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'up', $links );
		$this->assertStringContainsString( '/wc/store/v1/products/' . $variable_product->get_id(), $links['up'][0]['href'] );
	}

	/**
	 * @testdox A variation's visibility follows its parent product across ID, slug and SKU lookups.
	 */
	public function test_variation_visibility_follows_parent_product() {
		$fixtures  = new FixtureData();
		$attribute = FixtureData::get_product_attribute( 'color', array( 'red', 'blue' ) );

		// Variation under a non-public (draft) parent. The variation itself stays published.
		$hidden_parent    = $fixtures->get_variable_product( array( 'name' => 'Hidden Parent' ), array( $attribute ) );
		$hidden_variation = $fixtures->get_variation_product(
			$hidden_parent->get_id(),
			array( 'pa_color' => 'red' ),
			array(
				'regular_price' => 10,
				'sku'           => 'hidden-parent-variation',
			)
		);
		$hidden_slug      = get_post_field( 'post_name', $hidden_variation->get_id() );
		$hidden_parent->set_status( ProductStatus::DRAFT );
		$hidden_parent->save();

		// Variation under a published parent (regression).
		$public_parent    = $fixtures->get_variable_product( array( 'name' => 'Public Parent' ), array( $attribute ) );
		$public_variation = $fixtures->get_variation_product(
			$public_parent->get_id(),
			array( 'pa_color' => 'blue' ),
			array(
				'regular_price' => 10,
				'sku'           => 'public-parent-variation',
			)
		);

		// Hidden parent: the variation must not be exposed by ID, slug, or SKU/slug collection lookups.
		$by_id   = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $hidden_variation->get_id() ) );
		$by_slug = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $hidden_slug ) );
		$this->assertEquals( 404, $by_id->get_status() );
		$this->assertEquals( 404, $by_slug->get_status() );

		$sku_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$sku_request->set_param( 'sku', 'hidden-parent-variation' );
		$this->assertCount( 0, rest_get_server()->dispatch( $sku_request )->get_data() );

		$slug_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$slug_request->set_param( 'slug', $hidden_slug );
		$this->assertCount( 0, rest_get_server()->dispatch( $slug_request )->get_data() );

		// Published parent: the variation is still returned.
		$public_by_id = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $public_variation->get_id() ) );
		$this->assertEquals( 200, $public_by_id->get_status() );
		$this->assertSame( $public_variation->get_id(), $public_by_id->get_data()['id'] );

		$public_sku_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$public_sku_request->set_param( 'sku', 'public-parent-variation' );
		$this->assertCount( 1, rest_get_server()->dispatch( $public_sku_request )->get_data() );
	}

	/**
	 * @testdox Product should always include embeddable related link using related parameter format.
	 */
	public function test_product_has_related_link_with_related_parameter_format() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() ) );
		$links    = $response->get_links();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'related', $links );
		$this->assertStringContainsString( 'related=' . $this->products[0]->get_id(), $links['related'][0]['href'] );
		$this->assertStringContainsString( 'per_page=10', $links['related'][0]['href'] );
		$this->assertStringNotContainsString( 'include=', $links['related'][0]['href'] );
		$this->assertArrayHasKey( 'embeddable', $links['related'][0]['attributes'] );
		$this->assertTrue( $links['related'][0]['attributes']['embeddable'] );
	}

	/**
	 * @testdox Related query parameter should filter products to related products.
	 */
	public function test_related_query_parameter_filters_products() {
		$fixtures = new FixtureData();

		// Create products in the same category so they are related.
		$term = wp_insert_term( 'Related Category', 'product_cat' );

		$main_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Main Related Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);
		wp_set_object_terms( $main_product->get_id(), $term['term_id'], 'product_cat' );

		$related_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Related Product In Same Category',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 15,
			)
		);

		wp_set_object_terms( $related_product->get_id(), $term['term_id'], 'product_cat' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'related', $main_product->get_id() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data        = $response->get_data();
		$product_ids = array_map(
			function ( $product ) {
				return $product['id'];
			},
			$data
		);

		// Main product should not be in its own related products.
		$this->assertNotContains( $main_product->get_id(), $product_ids );

		// Related product should be returned.
		$this->assertContains( $related_product->get_id(), $product_ids );
	}

	/**
	 * Data provider for non-published product statuses.
	 *
	 * @return array<string, array{string}>
	 */
	public function provider_non_published_statuses() {
		return array(
			'draft'      => array( ProductStatus::DRAFT ),
			'pending'    => array( ProductStatus::PENDING ),
			'private'    => array( ProductStatus::PRIVATE ),
			'trash'      => array( ProductStatus::TRASH ),
			'auto-draft' => array( ProductStatus::AUTO_DRAFT ),
		);
	}

	/**
	 * @testdox Non-published products should not be returned when queried by ID ($status).
	 * @dataProvider provider_non_published_statuses
	 *
	 * @param string $status The product status to test.
	 */
	public function test_non_published_product_by_id_returns_404( $status ) {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Non Published Product',
				'regular_price' => 10,
			)
		);
		$product->set_status( $status );
		$product->save();

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $product->get_id() ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * @testdox Non-published products should not be included in the collection response ($status).
	 * @dataProvider provider_non_published_statuses
	 *
	 * @param string $status The product status to test.
	 */
	public function test_non_published_products_excluded_from_collection( $status ) {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Non Published Product In Collection',
				'regular_price' => 10,
			)
		);
		$product->set_status( $status );
		$product->save();

		$response    = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		$data        = $response->get_data();
		$product_ids = array_map(
			function ( $product ) {
				return $product['id'];
			},
			$data
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertNotContains( $product->get_id(), $product_ids );
	}

	/**
	 * @testdox Non-published products should not be returned when queried by slug ($status).
	 * @dataProvider provider_non_published_statuses
	 *
	 * @param string $status The product status to test.
	 */
	public function test_non_published_product_by_slug_returns_404( $status ) {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Non Published Product By Slug',
				'regular_price' => 10,
			)
		);
		$product->set_status( $status );
		$product->save();

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $product->get_slug() ) );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * @testdox Password-protected products in collection should have redacted content and is_password_protected true.
	 */
	public function test_password_protected_product_redacts_content_in_collection() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'              => 'Protected Product',
				'regular_price'     => 10,
				'short_description' => 'Secret short desc',
				'description'       => 'Secret full desc',
			)
		);

		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'testpass',
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products' ) );
		$data     = $response->get_data();

		$protected_product = null;
		foreach ( $data as $item ) {
			if ( $item['id'] === $product->get_id() ) {
				$protected_product = $item;
				break;
			}
		}

		$this->assertNotNull( $protected_product );
		$this->assertTrue( $protected_product['is_password_protected'] );
		$this->assertArrayHasKey( 'description', $protected_product );
		$this->assertSame( '', $protected_product['description'] );
		$this->assertArrayHasKey( 'short_description', $protected_product );
		$this->assertSame( '', $protected_product['short_description'] );
	}

	/**
	 * @testdox Password-protected product by ID should have redacted content.
	 */
	public function test_password_protected_product_by_id_redacts_content() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'              => 'Protected Product By ID',
				'regular_price'     => 10,
				'short_description' => 'Secret short desc',
				'description'       => 'Secret full desc',
			)
		);

		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'testpass',
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $product->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['is_password_protected'] );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertSame( '', $data['description'] );
		$this->assertArrayHasKey( 'short_description', $data );
		$this->assertSame( '', $data['short_description'] );
	}

	/**
	 * @testdox Password-protected product by slug should have redacted content.
	 */
	public function test_password_protected_product_by_slug_redacts_content() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'              => 'Protected Product By Slug',
				'regular_price'     => 10,
				'short_description' => 'Secret short desc',
				'description'       => 'Secret full desc',
			)
		);

		wp_update_post(
			array(
				'ID'            => $product->get_id(),
				'post_password' => 'testpass',
			)
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $product->get_slug() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['is_password_protected'] );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertSame( '', $data['description'] );
		$this->assertArrayHasKey( 'short_description', $data );
		$this->assertSame( '', $data['short_description'] );
	}

	/**
	 * @testdox Non-password-protected product should have is_password_protected false.
	 */
	public function test_non_password_protected_product_has_false_flag() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->products[0]->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $data['is_password_protected'] );
	}

	/**
	 * @testdox Related query parameter returns empty when no related products exist.
	 */
	public function test_related_query_parameter_returns_empty_when_no_related() {
		$fixtures = new FixtureData();

		// Create a product with unique category (no other products).
		$term = wp_insert_term( 'Unique Category ' . uniqid(), 'product_cat' );

		$lonely_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Lonely Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);
		wp_set_object_terms( $lonely_product->get_id(), $term['term_id'], 'product_cat' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'related', $lonely_product->get_id() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response->get_data() );
	}

	/**
	 * @testdox Related query parameter returns 404 for non-existent product and does not create a transient.
	 */
	public function test_related_query_parameter_returns_404_for_nonexistent_product() {
		$nonexistent_id = 999999999;

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'related', $nonexistent_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertFalse( get_transient( 'wc_related_' . $nonexistent_id ), 'No transient should be created for a non-existent product.' );
	}
}
