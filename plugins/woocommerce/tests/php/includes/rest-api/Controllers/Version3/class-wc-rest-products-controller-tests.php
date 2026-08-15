<?php

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;
use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;

/**
 * class WC_REST_Products_Controller_Tests.
 * Product Controller tests for V3 REST API.
 */
class WC_REST_Products_Controller_Tests extends WC_Unit_Test_Case {
	use CogsAwareUnitTestSuiteTrait;
	use MetaDataAssertionTrait;

	/**
	 * REST server used to dispatch product requests.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Products controller registered on the test server.
	 *
	 * @var WC_REST_Products_Controller
	 */
	protected $endpoint;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	protected $user;

	/**
	 * Administrator fixture user ID.
	 *
	 * @var int
	 */
	protected static $fixture_user;

	/**
	 * Saves the `woocommerce_hide_out_of_stock_items` option value for restoration after tests that modify it.
	 * @var mixed
	 */
	protected $original_hid_out_of_stock_value;

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->clear_rest_server();
		unset( $this->server, $this->endpoint );
		$this->disable_cogs_feature();
		update_option( 'woocommerce_hide_out_of_stock_items', $this->original_hid_out_of_stock_value );
	}

	/**
	 * @var WC_Product_Simple[]
	 */
	protected static $products = array();

	/**
	 * Create class fixtures for tests.
	 *
	 * @param WP_UnitTest_Factory $factory WordPress unit test factory.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::enable_direct_product_attribute_lookup_updates();
		self::$fixture_user = $factory->user->create( array( 'role' => 'administrator' ) );

		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Pancake',
				'sku'  => 'pancake-1',
			)
		);
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Waffle 1',
				'sku'  => 'pancake-2',
			)
		);
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'French Toast',
				'sku'  => 'waffle-2',
			)
		);
		self::$products[] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Waffle 3',
				'sku'  => 'waffle-3',
			)
		);

		foreach ( self::$products as $product ) {
			$product->add_meta_data( 'test1', 'test1', true );
			$product->add_meta_data( 'test2', 'test2', true );
			$product->save();
		}
		self::disable_direct_product_attribute_lookup_updates();
	}

	/**
	 * Clean up products after tests.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		self::enable_direct_product_attribute_lookup_updates();
		foreach ( self::$products as $product ) {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
		self::disable_direct_product_attribute_lookup_updates();
	}

	/**
	 * Get the IDs of the products owned by this test class.
	 *
	 * @return int[]
	 */
	private function get_fixture_product_ids(): array {
		return array_map(
			static function ( $product ) {
				return $product->get_id();
			},
			self::$products
		);
	}

	/**
	 * Create products whose sortable properties have independent orders.
	 *
	 * @return array{first: WC_Product_Simple, second: WC_Product_Simple, third: WC_Product_Simple, fourth: WC_Product_Simple, pending: WC_Product_Simple, variable: WC_Product_Variable} Products keyed by creation order.
	 */
	private function create_ordering_products(): array {
		$definitions = array(
			'first'   => array(
				'name'           => 'Slice 022 Delta',
				'slug'           => 'slice-022-charlie',
				'date_created'   => '2024-01-03 12:00:00',
				'price'          => '40',
				'total_sales'    => 3,
				'average_rating' => 4.0,
				'rating_counts'  => array( 4 => 3 ),
			),
			'second'  => array(
				'name'           => 'Slice 022 Alpha',
				'slug'           => 'slice-022-bravo',
				'date_created'   => '2024-01-01 12:00:00',
				'price'          => '20',
				'total_sales'    => 2,
				'average_rating' => 2.0,
				'rating_counts'  => array( 2 => 2 ),
			),
			'third'   => array(
				'name'           => 'Slice 022 Charlie',
				'slug'           => 'slice-022-delta',
				'date_created'   => '2024-01-06 12:00:00',
				'price'          => '10',
				'total_sales'    => 1,
				'average_rating' => 3.0,
				'rating_counts'  => array( 3 => 1 ),
			),
			'fourth'  => array(
				'name'           => 'Slice 022 Bravo',
				'slug'           => 'slice-022-alpha',
				'date_created'   => '2024-01-02 12:00:00',
				'price'          => '30',
				'total_sales'    => 4,
				'average_rating' => 1.0,
				'rating_counts'  => array( 1 => 4 ),
			),
			'pending' => array(
				'name'           => 'Slice 022 Echo',
				'date_created'   => '2024-01-04 12:00:00',
				'price'          => '25',
				'total_sales'    => 5,
				'average_rating' => 0.0,
				'rating_counts'  => array(),
				'status'         => ProductStatus::PENDING,
			),
		);
		$products    = array();

		try {
			foreach ( $definitions as $key => $definition ) {
				$props = array(
					'name'          => $definition['name'],
					'regular_price' => $definition['price'],
					'price'         => $definition['price'],
				);
				if ( isset( $definition['slug'] ) ) {
					$props['slug'] = $definition['slug'];
				}
				if ( isset( $definition['status'] ) ) {
					$props['status'] = $definition['status'];
				}
				$product = WC_Helper_Product::create_simple_product(
					false,
					$props
				);
				$product->set_date_created( $definition['date_created'] );
				$product->set_total_sales( $definition['total_sales'] );
				$product->set_average_rating( $definition['average_rating'] );
				$product->set_rating_counts( $definition['rating_counts'] );
				$product->set_review_count( array_sum( $definition['rating_counts'] ) );
				$products[ $key ] = $product;
				$product->save();
			}

			$variable = new WC_Product_Variable();
			$variable->set_name( 'Slice 022 Foxtrot' );
			$variable->set_slug( 'slice-022-echo' );
			$variable->set_date_created( '2024-01-05 12:00:00' );
			$variable->set_total_sales( 6 );
			$variable->set_average_rating( 5.0 );
			$variable->set_rating_counts( array( 5 => 6 ) );
			$variable->set_review_count( 6 );
			$products['variable'] = $variable;
			$variable->save();

			WC_Helper_Product::create_product_variation_object(
				$variable->get_id(),
				'SLICE 022 LOW ' . wp_generate_uuid4(),
				5,
				array()
			);
			WC_Helper_Product::create_product_variation_object(
				$variable->get_id(),
				'SLICE 022 HIGH ' . wp_generate_uuid4(),
				50,
				array()
			);
			WC_Product_Variable::sync( $variable->get_id() );
			$persisted_variable = wc_get_product( $variable->get_id() );
			if ( ! $persisted_variable instanceof WC_Product_Variable ) {
				throw new RuntimeException( 'The variable ordering fixture could not be reloaded.' );
			}
			$products['variable'] = $persisted_variable;
		} catch ( Throwable $throwable ) {
			$this->delete_ordering_products( $products );
			throw $throwable;
		}

		return $products;
	}

	/**
	 * Delete products created for an ordering test.
	 *
	 * @param WC_Product[] $products Products to delete.
	 */
	private function delete_ordering_products( array $products ): void {
		foreach ( $products as $product ) {
			if ( $product->get_id() ) {
				$persisted_product = wc_get_product( $product->get_id() );
				if ( $persisted_product instanceof WC_Product_Variable ) {
					foreach ( $persisted_product->get_children() as $child_id ) {
						WC_Helper_Product::delete_product( $child_id );
					}
				}
				$product->delete( true );
			}
		}
	}

	/**
	 * Dispatch an authenticated product collection request and assert exact order.
	 *
	 * @param int[]       $included_ids Product IDs that constrain the collection.
	 * @param int[]       $expected_ids Product IDs in the expected response order.
	 * @param string|null $orderby      REST orderby value, or null to use the default.
	 * @param string|null $order        REST order value, or null to use the default.
	 * @param string      $description  Assertion context.
	 */
	private function assert_product_collection_order( array $included_ids, array $expected_ids, ?string $orderby, ?string $order, string $description ): void {
		$query_params = array(
			'include'  => $included_ids,
			'per_page' => count( $included_ids ),
		);
		if ( null !== $orderby ) {
			$query_params['orderby'] = $orderby;
		}
		if ( null !== $order ) {
			$query_params['order'] = $order;
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params( $query_params );
		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertSame( 200, $response->get_status(), "$description should return HTTP 200." );
		$this->assertCount( count( $included_ids ), $response_data, "$description should return every included product." );
		$this->assertSame( $expected_ids, array_map( 'absint', wp_list_pluck( $response_data, 'id' ) ), "$description should return products in the expected order." );
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Products_Controller();
		$this->server   = $this->create_rest_server_with_routes(
			array( array( $this->endpoint, 'register_routes' ) ),
			true
		);
		$this->user     = self::$fixture_user;
		wp_set_current_user( $this->user );

		$this->original_hid_out_of_stock_value = get_option( 'woocommerce_hide_out_of_stock_items' );
	}

	/**
	 * Get all expected fields.
	 *
	 * @param bool $with_cogs_enabled Ture to get the fields expected when the Cost of Goods Sold feature is enabled.
	 */
	public function get_expected_response_fields( bool $with_cogs_enabled ) {
		$fields = array(
			'id',
			'name',
			'slug',
			'permalink',
			'date_created',
			'date_created_gmt',
			'date_modified',
			'date_modified_gmt',
			'type',
			'status',
			'featured',
			'catalog_visibility',
			'description',
			'short_description',
			'sku',
			'global_unique_id',
			'price',
			'regular_price',
			'sale_price',
			'date_on_sale_from',
			'date_on_sale_from_gmt',
			'date_on_sale_to',
			'date_on_sale_to_gmt',
			'price_html',
			'on_sale',
			'purchasable',
			'total_sales',
			'virtual',
			'downloadable',
			'downloads',
			'download_limit',
			'download_expiry',
			'external_url',
			'button_text',
			'tax_status',
			'tax_class',
			'manage_stock',
			'stock_quantity',
			'stock_status',
			'backorders',
			'backorders_allowed',
			'backordered',
			'low_stock_amount',
			'sold_individually',
			'weight',
			'dimensions',
			'shipping_required',
			'shipping_taxable',
			'shipping_class',
			'shipping_class_id',
			'reviews_allowed',
			'average_rating',
			'rating_count',
			'related_ids',
			'upsell_ids',
			'cross_sell_ids',
			'parent_id',
			'purchase_note',
			'categories',
			'tags',
			'brands',
			'images',
			'has_options',
			'attributes',
			'default_attributes',
			'variations',
			'grouped_products',
			'menu_order',
			'meta_data',
			'post_password',
		);

		if ( $with_cogs_enabled ) {
			$fields[] = 'cost_of_goods_sold';
		}

		return $fields;
	}

	/**
	 * Test that all expected response fields are present.
	 * Note: This has fields hardcoded intentionally instead of fetching from schema to test for any bugs in schema result. Add new fields manually when added to schema.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $with_cogs_enabled Ture test with the Cost of Goods Sold feature enabled.
	 */
	public function test_product_api_get_all_fields( bool $with_cogs_enabled ) {
		if ( $with_cogs_enabled ) {
			$this->enable_cogs_feature();
		}

		$expected_response_fields = $this->get_expected_response_fields( $with_cogs_enabled );

		$product  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );

		$this->assertEquals( 200, $response->get_status() );

		$response_fields = array_keys( $response->get_data() );

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEmpty( array_diff( $expected_response_fields, $response_fields ), 'These fields were expected but not present in API response: ' . print_r( array_diff( $expected_response_fields, $response_fields ), true ) );

		$this->assertEmpty( array_diff( $response_fields, $expected_response_fields ), 'These fields were not expected in the API response: ' . print_r( array_diff( $response_fields, $expected_response_fields ), true ) );
		// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}

	/**
	 * Test that `get_product_data` function works without silent `request` parameter as it used to.
	 * TODO: Fix the underlying design issue when DI gets available.
	 */
	public function test_get_product_data_should_work_without_request_param() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		// Workaround to call protected method.
		$call_product_data_wrapper = function () use ( $product ) {
			return $this->get_product_data( $product );
		};
		$response                  = $call_product_data_wrapper->call( new WC_REST_Products_Controller() );
		$this->assertArrayHasKey( 'id', $response );
	}

	/**
	 * Test that all fields are returned when requested one by one.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $with_cogs_enabled Ture test with the Cost of Goods Sold feature enabled.
	 */
	public function test_products_get_each_field_one_by_one( bool $with_cogs_enabled ) {
		if ( $with_cogs_enabled ) {
			$this->enable_cogs_feature();
		}

		$expected_response_fields = $this->get_expected_response_fields( $with_cogs_enabled );
		$product                  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		foreach ( $expected_response_fields as $field ) {
			$request = new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
			$request->set_param( '_fields', $field );
			$response = $this->server->dispatch( $request );
			$this->assertEquals( 200, $response->get_status() );
			$response_fields = array_keys( $response->get_data() );

			$this->assertContains( $field, $response_fields, "Field $field was expected but not present in product API response." );
		}
	}

	/**
	 * Test that the `search` parameter does partial matching in the product name, but not the SKU.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_param_only() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'  => 'waffle',
				'order'   => 'asc',
				'orderby' => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 1' );
		$this->assertEquals( $response_products[0]['sku'], 'pancake-2' );
		$this->assertEquals( $response_products[1]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[1]['sku'], 'waffle-3' );
	}

	/**
	 * Test that the `search_sku` parameter does partial matching in the product SKU, but not the name.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_sku_param_only() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_sku' => 'waffle',
				'order'      => 'asc',
				'orderby'    => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'French Toast' );
		$this->assertEquals( $response_products[0]['sku'], 'waffle-2' );
		$this->assertEquals( $response_products[1]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[1]['sku'], 'waffle-3' );
	}

	/**
	 * Test that using the `search` and `search_sku` parameters together only matches when both match.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_and_search_sku_param() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'     => 'waffle',
				'search_sku' => 'waffle',
				'order'      => 'asc',
				'orderby'    => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[0]['sku'], 'waffle-3' );
	}

	/**
	 * Test that the `search_sku` parameter does nothing when product SKUs are disabled.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_sku_when_skus_disabled() {
		add_filter( 'wc_product_sku_enabled', '__return_false' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search'     => 'waffle',
				'search_sku' => 'waffle',
				'order'      => 'asc',
				'orderby'    => 'id',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 2, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 1' );
		$this->assertEquals( $response_products[0]['sku'], 'pancake-2' );
		$this->assertEquals( $response_products[1]['name'], 'Waffle 3' );
		$this->assertEquals( $response_products[1]['sku'], 'waffle-3' );

		remove_filter( 'wc_product_sku_enabled', '__return_false' );
	}

	/**
	 * Test that the products endpoint can filter by global_unique_id.
	 *
	 * @return void
	 */
	public function test_products_query_by_global_unique_id_param() {
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'             => 'Waffle 6',
				'sku'              => 'waffle-6',
				'global_unique_id' => '6',
			)
		);
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'global_unique_id' => '6',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], 'Waffle 6' );
	}

	/**
	 * Test that the products endpoint can filter by global_unique_id and also return matched variations.
	 *
	 * @return void
	 */
	public function test_products_query_by_global_unique_id_param_for_variations() {
		$parent_product = WC_Helper_Product::create_variation_product();
		$variation      = $parent_product->get_available_variations()[0];
		$variation      = wc_get_product( $variation['variation_id'] );
		$unique_id      = $parent_product->get_id() . '-1';
		$variation->set_global_unique_id( $unique_id );
		$variation->save();
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'global_unique_id' => $unique_id,
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $response_products[0]['name'], $variation->get_name() );
	}

	/**
	 * Test that the `include_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_include_meta() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', 'test1' );
		$request->set_param( 'include', $this->get_fixture_product_ids() );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( count( self::$products ), $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta_keys = array_map(
				function ( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the `include_meta` param is skipped when empty.
	 */
	public function test_collection_param_include_meta_empty() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', '' );
		$request->set_param( 'include', $this->get_fixture_product_ids() );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( count( self::$products ), $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$meta_keys = array_map(
				function ( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
			$this->assertContains( 'test2', $meta_keys );
		}
	}

	/**
	 * Test that the `exclude_meta` param filters the `meta_data` prop correctly.
	 */
	public function test_collection_param_exclude_meta() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'exclude_meta', 'test1' );
		$request->set_param( 'include', $this->get_fixture_product_ids() );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( count( self::$products ), $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$meta_keys = array_map(
				function ( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test2', $meta_keys );
			$this->assertNotContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the `include_meta` param overrides the `exclude_meta` param.
	 */
	public function test_collection_param_include_meta_override() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', 'test1' );
		$request->set_param( 'exclude_meta', 'test1' );
		$request->set_param( 'include', $this->get_fixture_product_ids() );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertCount( count( self::$products ), $response_data );

		foreach ( $response_data as $order ) {
			$this->assertArrayHasKey( 'meta_data', $order );
			$this->assertEquals( 1, count( $order['meta_data'] ) );
			$meta_keys = array_map(
				function ( $meta_item ) {
					return $meta_item->get_data()['key'];
				},
				$order['meta_data']
			);
			$this->assertContains( 'test1', $meta_keys );
		}
	}

	/**
	 * Test that the meta_data property contains an array, and not an object, after being filtered.
	 */
	public function test_collection_param_include_meta_returns_array() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'include_meta', 'test2' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data       = $this->server->response_to_data( $response, false );
		$encoded_data_string = wp_json_encode( $response_data );
		$decoded_data_object = json_decode( $encoded_data_string, false ); // Ensure object instead of associative array.

		$this->assertIsArray( $decoded_data_object[0]->meta_data );
	}

	/**
	 * @testdox Product collection ordering returns exact core-field orders through the registered V3 route.
	 */
	public function test_collection_orderby_core_fields(): void {
		$products = $this->create_ordering_products();

		try {
			$first    = $products['first']->get_id();
			$second   = $products['second']->get_id();
			$third    = $products['third']->get_id();
			$fourth   = $products['fourth']->get_id();
			$pending  = $products['pending']->get_id();
			$variable = $products['variable']->get_id();
			$all      = array( $first, $second, $third, $fourth, $pending, $variable );

			$this->assertSame( ProductStatus::PENDING, $products['pending']->get_status(), 'The empty-slug fixture should remain unpublished.' );
			$this->assertSame( '', get_post_field( 'post_name', $pending ), 'The pending fixture should exercise the actual empty post_name boundary.' );
			$this->assert_product_collection_order( $all, array( $third, $variable, $pending, $first, $fourth, $second ), null, null, 'Default date DESC ordering' );
			$this->assert_product_collection_order( $all, array( $second, $fourth, $first, $pending, $variable, $third ), 'date', 'asc', 'Date ASC ordering' );
			$this->assert_product_collection_order( $all, array( $third, $variable, $pending, $first, $fourth, $second ), 'date', 'desc', 'Date DESC ordering' );
			$this->assert_product_collection_order( $all, array( $first, $second, $third, $fourth, $pending, $variable ), 'id', 'asc', 'ID ASC ordering' );
			$this->assert_product_collection_order( $all, array( $variable, $pending, $fourth, $third, $second, $first ), 'id', 'desc', 'ID DESC ordering' );
			$this->assert_product_collection_order( $all, array( $second, $fourth, $third, $first, $pending, $variable ), 'title', 'asc', 'Title ASC ordering' );
			$this->assert_product_collection_order( $all, array( $variable, $pending, $first, $third, $fourth, $second ), 'title', 'desc', 'Title DESC ordering' );
			$this->assert_product_collection_order( $all, array( $pending, $fourth, $second, $first, $third, $variable ), 'slug', 'asc', 'Slug ASC ordering' );
			$this->assert_product_collection_order( $all, array( $variable, $third, $first, $second, $fourth, $pending ), 'slug', 'desc', 'Slug DESC ordering' );
		} finally {
			$this->delete_ordering_products( $products );
		}
	}

	/**
	 * @testdox Product collection ordering returns exact lookup-field orders through the registered V3 route.
	 */
	public function test_collection_orderby_lookup_fields(): void {
		$products = $this->create_ordering_products();

		try {
			$first    = $products['first']->get_id();
			$second   = $products['second']->get_id();
			$third    = $products['third']->get_id();
			$fourth   = $products['fourth']->get_id();
			$pending  = $products['pending']->get_id();
			$variable = $products['variable']->get_id();
			$all      = array( $first, $second, $third, $fourth, $pending, $variable );

			$this->assertSame( '5.00', $products['variable']->get_variation_price( 'min' ), 'The variable fixture should expose its persisted minimum lookup price.' );
			$this->assertSame( '50.00', $products['variable']->get_variation_price( 'max' ), 'The variable fixture should expose a distinct persisted maximum lookup price.' );
			$this->assert_product_collection_order( $all, array( $variable, $third, $second, $pending, $fourth, $first ), 'price', 'asc', 'Price ASC ordering' );
			$this->assert_product_collection_order( $all, array( $variable, $first, $fourth, $pending, $second, $third ), 'price', 'desc', 'Price DESC ordering' );
			$this->assert_product_collection_order( $all, array( $variable, $first, $third, $second, $fourth, $pending ), 'rating', 'desc', 'Rating DESC ordering' );
			$this->assert_product_collection_order( $all, array( $variable, $pending, $fourth, $first, $second, $third ), 'popularity', 'desc', 'Popularity DESC ordering' );
		} finally {
			$this->delete_ordering_products( $products );
		}
	}

	/**
	 * @testdox Product collection include ordering preserves request order regardless of the order parameter.
	 */
	public function test_collection_orderby_include_order(): void {
		$products = $this->create_ordering_products();

		try {
			$included_ids = array(
				$products['pending']->get_id(),
				$products['fourth']->get_id(),
				$products['first']->get_id(),
				$products['variable']->get_id(),
				$products['third']->get_id(),
				$products['second']->get_id(),
			);

			$this->assert_product_collection_order( $included_ids, $included_ids, 'include', 'asc', 'Include ASC ordering' );
			$this->assert_product_collection_order( $included_ids, $included_ids, 'include', 'desc', 'Include DESC ordering' );
		} finally {
			$this->delete_ordering_products( $products );
		}
	}

	/**
	 * Test that the `include_status` parameter correctly filters products by a single status.
	 */
	public function test_collection_filter_with_single_include_status() {
		$draft_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Draft Product',
				'status' => ProductStatus::DRAFT,
			)
		);

		WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Pending Product',
				'status' => ProductStatus::PENDING,
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( ProductStatus::DRAFT ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( 1, $response_products );
		$this->assertEquals( $draft_product->get_id(), $response_products[0]['id'] );
		$this->assertEquals( ProductStatus::DRAFT, $response_products[0]['status'] );
	}

	/**
	 * Test that the `include_status` parameter correctly filters products by multiple statuses.
	 *
	 * @return void
	 */
	public function test_collection_filter_with_multiple_include_status() {
		WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Draft Product',
				'status' => ProductStatus::DRAFT,
			)
		);

		WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Pending Product',
				'status' => ProductStatus::PENDING,
			)
		);

		WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Private Product',
				'status' => ProductStatus::PRIVATE,
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( ProductStatus::DRAFT, ProductStatus::PENDING ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( 2, $response_products );

		$statuses = array_map(
			function ( $product ) {
				return $product['status'];
			},
			$response_products
		);

		$this->assertContains( ProductStatus::DRAFT, $statuses );
		$this->assertContains( ProductStatus::PENDING, $statuses );
		$this->assertNotContains( ProductStatus::PRIVATE, $statuses );
	}

	/**
	 * Test that the `include_status` parameter correctly handles invalid status values.
	 */
	public function test_collection_filter_with_invalid_include_status() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( 'invalid_status' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test that the `include_status` parameter with any status returns all products.
	 */
	public function test_collection_filter_with_include_status_any() {
		$all_statuses = get_post_statuses();
		foreach ( $all_statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( 'any' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$statuses = array_unique(
			array_map(
				function ( $product ) {
					return $product['status'];
				},
				$response_products
			)
		);
		$this->assertCount( 4, $statuses );
		$this->assertEqualsCanonicalizing(
			array_keys( $all_statuses ),
			$statuses
		);
	}

	/**
	 * Test that `exclude_status` parameter correctly excludes a single status.
	 */
	public function test_products_filter_with_single_exclude_status() {
		$all_statuses = get_post_statuses();
		foreach ( $all_statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_status' => array( ProductStatus::DRAFT ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$statuses = array_unique( array_column( $data, 'status' ) );

		$this->assertNotContains( ProductStatus::DRAFT, $statuses );
	}

	/**
	 * Test that `exclude_status` parameter correctly excludes multiple statuses.
	 */
	public function test_products_filter_with_multiple_exclude_status() {
		$all_statuses = get_post_statuses();
		foreach ( $all_statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_status' => array( ProductStatus::DRAFT, ProductStatus::PRIVATE ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$statuses = array_unique( array_column( $data, 'status' ) );

		$this->assertEqualsCanonicalizing(
			array( ProductStatus::PUBLISH, ProductStatus::PENDING ),
			$statuses
		);

		$this->assertNotContains( ProductStatus::DRAFT, $statuses );
		$this->assertNotContains( ProductStatus::PRIVATE, $statuses );
	}

	/**
	 * Test that empty `exclude_status` parameter returns all products.
	 */
	public function test_products_filter_with_empty_exclude_status() {
		$statuses = get_post_statuses();
		foreach ( $statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_status' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$statuses = array_unique( array_column( $data, 'status' ) );

		$this->assertEqualsCanonicalizing(
			array_keys( get_post_statuses() ),
			$statuses
		);
	}

	/**
	 * Test that `exclude_status` parameter validation handles invalid values.
	 */
	public function test_products_filter_with_valid_invalid_exclude_status() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_status' => array( ProductStatus::DRAFT, 'invalid_status' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
	}

	/**
	 * Test that `exclude_status` with all statuses returns empty.
	 */
	public function test_products_filter_exclude_status_with_all_statuses_returns_empty() {
		$statuses = get_post_statuses();
		foreach ( $statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_status' => array_keys( $statuses ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $response->get_data() );
	}

	/**
	 * Test that `exclude_status` parameter takes precedence over `include_status`.
	 */
	public function test_products_filter_exclude_status_precedence_over_include() {
		$statuses = get_post_statuses();
		foreach ( $statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( ProductStatus::DRAFT, ProductStatus::PRIVATE ),
				'exclude_status' => array( ProductStatus::PRIVATE ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$statuses = array_unique( array_column( $response->get_data(), 'status' ) );

		$this->assertContains( ProductStatus::DRAFT, $statuses );
		$this->assertNotContains( ProductStatus::PRIVATE, $statuses );
	}

	/**
	 * Test that `exclude_status` works correctly when `include_status` is 'any'.
	 */
	public function test_products_filter_exclude_status_with_include_any() {
		$statuses = get_post_statuses();
		foreach ( $statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( 'any' ),
				'exclude_status' => array( ProductStatus::PRIVATE, ProductStatus::DRAFT ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$statuses = array_unique( array_column( $response->get_data(), 'status' ) );

		$this->assertEqualsCanonicalizing(
			array( ProductStatus::PUBLISH, ProductStatus::PENDING ),
			$statuses
		);
		$this->assertNotContains( ProductStatus::PRIVATE, $statuses );
		$this->assertNotContains( ProductStatus::DRAFT, $statuses );
	}

	/**
	 * Test that `exclude_status` works correctly with specific `include_status` values.
	 */
	public function test_products_filter_exclude_status_with_specific_includes() {
		$statuses = get_post_statuses();
		foreach ( $statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_status' => array( ProductStatus::DRAFT, ProductStatus::PENDING, ProductStatus::PRIVATE ),
				'exclude_status' => array( ProductStatus::PRIVATE, ProductStatus::DRAFT ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$statuses = array_unique( array_column( $response->get_data(), 'status' ) );

		$this->assertEqualsCanonicalizing( array( ProductStatus::PENDING ), $statuses );
		$this->assertNotContains( ProductStatus::PRIVATE, $statuses );
		$this->assertNotContains( ProductStatus::DRAFT, $statuses );
		$this->assertNotContains( ProductStatus::PUBLISH, $statuses );
	}

	/**
	 * Test that `exclude_status` works correctly with the 'status' parameter.
	 */
	public function test_products_filter_exclude_status_with_status_param() {
		$statuses = get_post_statuses();
		foreach ( $statuses as $status => $label ) {
			WC_Helper_Product::create_simple_product(
				true,
				array(
					'name'   => "$label Product",
					'status' => $status,
				)
			);
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'status'         => ProductStatus::DRAFT,
				'exclude_status' => array( ProductStatus::DRAFT ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEmpty( $data, 'Should return no products when status is excluded' );
	}

	/**
	 * Test that the `include_types` parameter filters products by a single type.
	 */
	public function test_collection_filter_with_include_types() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_types' => array( ProductType::GROUPED ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertCount( 1, $response_products );
		$this->assertEquals( ProductType::GROUPED, $response_products[0]['type'] );
	}

	/**
	 * Test that the `include_types` parameter filters products by multiple types.
	 */
	public function test_collection_filter_with_multiple_include_types() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_types' => array( ProductType::EXTERNAL, ProductType::GROUPED ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_products = $response->get_data();
		$this->assertCount( 2, $response_products );

		$product_types = wp_list_pluck( $response_products, 'type' );
		$this->assertEqualsCanonicalizing( array( ProductType::EXTERNAL, ProductType::GROUPED ), $product_types );
	}

	/**
	 * Test that the `include_types` parameter handles invalid status values.
	 */
	public function test_collection_filter_with_invalid_include_types() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_types' => array( 'invalid_type' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Create one product of each parent product type for type-filter tests.
	 */
	private function create_products_for_type_filtering(): void {
		WC_Helper_Product::create_simple_product();

		$variable = new WC_Product_Variable();
		$variable->set_name( 'Variable product' );
		$variable->save();

		$grouped = new WC_Product_Grouped();
		$grouped->set_name( 'Grouped product' );
		$grouped->save();

		WC_Helper_Product::create_external_product();
	}

	/**
	 * Test that `exclude_types` parameter correctly excludes a single type.
	 */
	public function test_products_filter_with_single_exclude_types() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_types' => array( ProductType::SIMPLE ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$types = array_unique( array_column( $data, 'type' ) );

		$this->assertNotContains( ProductType::SIMPLE, $types );
	}

	/**
	 * Test that `exclude_types` parameter correctly excludes multiple types.
	 */
	public function test_products_filter_with_multiple_exclude_types() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_types' => array( ProductType::SIMPLE, ProductType::GROUPED ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$types = array_unique( wp_list_pluck( $data, 'type' ) );

		$this->assertEqualsCanonicalizing(
			array( ProductType::VARIABLE, ProductType::EXTERNAL ),
			$types
		);

		$this->assertNotContains( ProductType::SIMPLE, $types );
		$this->assertNotContains( ProductType::GROUPED, $types );
	}

	/**
	 * Test that empty `exclude_types` parameter returns all products.
	 */
	public function test_products_filter_with_empty_exclude_types() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_types' => array(),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$types = array_unique( wp_list_pluck( $data, 'type' ) );

		$this->assertEqualsCanonicalizing(
			array_keys( wc_get_product_types() ),
			$types
		);
	}

	/**
	 * Test that `exclude_types` parameter validation handles invalid values.
	 */
	public function test_products_filter_with_invalid_exclude_types() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_types' => array( ProductType::SIMPLE, 'invalid_type' ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test that `exclude_types` with all types returns empty result.
	 */
	public function test_products_filter_exclude_types_with_all_types_returns_empty() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'exclude_types' => array_keys( wc_get_product_types() ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $response->get_data() );
	}

	/**
	 * Test that `exclude_types` parameter takes precedence over `include_types`.
	 */
	public function test_products_filter_exclude_types_precedence_over_include() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'include_types' => array( ProductType::SIMPLE, ProductType::GROUPED ),
				'exclude_types' => array( ProductType::GROUPED ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$types = array_unique( wp_list_pluck( $response->get_data(), 'type' ) );

		$this->assertContains( ProductType::SIMPLE, $types );
		$this->assertNotContains( ProductType::GROUPED, $types );
		$this->assertEquals( 1, count( $types ) );
	}

	/**
	 * Test that `exclude_types` works correctly with the `type` param.
	 */
	public function test_products_filter_exclude_types_with_type_param() {
		$this->create_products_for_type_filtering();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'type'          => ProductType::SIMPLE,
				'exclude_types' => array( ProductType::SIMPLE ),
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEmpty( $data, 'Should return no products when type is excluded' );
	}

	/**
	 * Test `downloadable` filter returns only downloadable products.
	 */
	public function test_downloadable_filter_returns_only_downloadable_products() {
		WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'downloadable', true );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $products as $product ) {
			$this->assertTrue( $product['downloadable'] );
		}
	}

	/**
	 * Test `downloadable` filter returns only non-downloadable products when is false.
	 */
	public function test_downloadable_filter_returns_only_non_downloadable_products() {
		WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );
		WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'downloadable', false );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $products as $product ) {
			$this->assertFalse( $product['downloadable'] );
		}
	}

	/**
	 * Test invalid downloadable parameter type returns error.
	 */
	public function test_downloadable_filter_with_invalid_param() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'downloadable', 'invalid' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Test `virtual` filter returns only virtual products.
	 */
	public function test_virtual_filter_returns_only_virtual_products() {
		WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'virtual', true );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $products as $product ) {
			$this->assertTrue( $product['virtual'] );
		}
	}

	/**
	 * Test `virtual` filter returns only non-virtual products when is false.
	 */
	public function test_virtual_filter_returns_only_non_virtual_products() {
		WC_Helper_Product::create_simple_product( true, array( 'virtual' => true ) );
		WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'virtual', false );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		foreach ( $products as $product ) {
			$this->assertFalse( $product['virtual'] );
		}
	}

	/**
	 * Test invalid virtual parameter type returns error.
	 */
	public function test_virtual_filter_with_invalid_param() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'virtual', 'invalid' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * Test the duplicate product endpoint with simple products.
	 */
	public function test_duplicate_simple_product() {
		$product    = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Carrot Cake',
				'sku'  => 'carrot-cake-1',
			)
		);
		$product_id = $product->get_id();

		$request  = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product_id . '/duplicate' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertNotEquals( $product, $response_data['id'] );

		$duplicated_product = wc_get_product( $response_data['id'] );
		$this->assertEquals( $product->get_name() . ' (Copy)', $duplicated_product->get_name() );
		$this->assertEquals( ProductStatus::DRAFT, $duplicated_product->get_status() );
	}

	/**
	 * Test the duplicate product endpoint with variable products.
	 */
	public function test_duplicate_variable_product() {
		$variable_product = WC_Helper_Product::create_variation_product();
		$product_id       = $variable_product->get_id();

		$request  = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product_id . '/duplicate' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertNotEquals( $product_id, $response_data['id'] );

		$duplicated_product = wc_get_product( $response_data['id'] );
		$this->assertEquals( $variable_product->get_name() . ' (Copy)', $duplicated_product->get_name() );
		$this->assertTrue( $duplicated_product->is_type( ProductType::VARIABLE ) );
	}

	/**
	 * Test the duplicate product endpoint with extra args to also update the product.
	 */
	public function test_duplicate_product_with_extra_args() {
		$product    = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Tiramisu Cake',
				'sku'  => 'tiramisu-cake-1',
			)
		);
		$product_id = $product->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product_id . '/duplicate' );
		$request->set_param( 'sku', 'new-sku' );
		$request->set_param(
			'meta_data',
			array(
				array(
					'key'   => 'test',
					'value' => 'test',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertNotEquals( $product_id, $response_data['id'] );

		$duplicated_product = wc_get_product( $response_data['id'] );
		$this->assertEquals( 'new-sku', $duplicated_product->get_sku() );
		$this->assertEquals( 'test', $duplicated_product->get_meta( 'test', true ) );
	}
	/**
	 * Test the duplicate product endpoint with to update product's name and stock management.
	 */
	public function test_duplicate_product_with_extra_args_name_stock_management() {
		$product    = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Blueberry Cake',
				'sku'  => 'blueberry-cake-1',
			)
		);
		$product_id = $product->get_id();

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product_id . '/duplicate' );
		$request->set_param( 'name', 'new-name' );
		$request->set_param( 'manage_stock', true );
		$request->set_param( 'stock_quantity', 10 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertNotEquals( $product_id, $response_data['id'] );

		$duplicated_product = wc_get_product( $response_data['id'] );
		$this->assertEquals( 'new-name (Copy)', $duplicated_product->get_name() );
		$this->assertTrue( $duplicated_product->get_manage_stock() );
		$this->assertEquals( 10, $duplicated_product->get_stock_quantity() );
	}

	/**
	 * @testdox The product GET endpoint returns the expected Cost of Goods information for a simple product.
	 */
	public function test_cogs_values_received_for_simple_product() {
		$this->enable_cogs_feature();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_cogs_value( 12.34 );
		$product->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$expected = array(
			'values'      => array(
				array(
					'defined_value'   => 12.34,
					'effective_value' => 12.34,
				),
			),
			'total_value' => 12.34,
		);

		$this->assertEquals( $expected, $data['cost_of_goods_sold'] );
	}

	/**
	 * @testdox The product GET endpoint returns the expected Cost of Goods information for a variation.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $set_additive_flag Value of the "additive" flag to use.
	 */
	public function test_cogs_values_received_for_variation_product( bool $set_additive_flag ) {
		$this->enable_cogs_feature();

		$parent_product = WC_Helper_Product::create_variation_product();
		$parent_product->set_cogs_value( 12.34 );
		$parent_product->save();

		$variation = wc_get_product( $parent_product->get_children()[0] );
		$variation->set_cogs_value( 56.78 );
		$variation->set_cogs_value_is_additive( $set_additive_flag );
		$variation->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/products/' . $variation->get_id() ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$expected_total_value = $set_additive_flag ? 12.34 + 56.78 : 56.78;
		$expected             = array(
			'values'                    => array(
				array(
					'defined_value'   => 56.78,
					'effective_value' => 56.78,
				),
			),
			'defined_value_is_additive' => $set_additive_flag,
			'total_value'               => $expected_total_value,
		);

		$this->assertEquals( $expected, $data['cost_of_goods_sold'] );
	}

	/**
	 * @testdox The product POST endpoint properly updates the Cost of Goods information for a product.
	 */
	public function test_set_cogs_value_for_simple_product_via_post_request() {
		$this->enable_cogs_feature();

		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( 0, $product->get_cogs_value() );

		$request_body = array(
			'cost_of_goods_sold' => array(
				'values' => array(
					array(
						'defined_value' => 12.34,
					),
					array(
						'defined_value' => 56.78,
					),
				),
			),
		);

		$this->update_product_via_post_request( $product, $request_body );

		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 12.34 + 56.78, $product->get_cogs_value() );
	}

	/**
	 * @testdox The product POST endpoint properly cleans up orphaned images when product creation fails.
	 */
	public function test_create_product_with_duplicate_sku_trashed_original_cleans_up_images() {
		// The manual override is needed because of the way we dispatch the REST request.
		$_SERVER['REQUEST_URI'] = '/wp-json/wc/v3/products';
		$original_product_sku   = 'DUPLICATE_SKU_TEST_TRASHED';
		// This image `src` is used in other product API tests, using here for consistency.
		$shared_image_src = 'http://cldup.com/Dr1Bczxq4q.png';
		// The failed request below exercises upload processing; setup only needs a valid image attachment.
		$original_attachment_id = self::factory()->attachment->create(
			array(
				'file'           => WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png',
				'post_mime_type' => 'image/png',
			)
		);

		// 1. Create the original product with its image.
		$request_original_product = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$request_original_product->set_body_params(
			array(
				'name'          => 'Original Trashed Product',
				'sku'           => $original_product_sku,
				'type'          => 'simple',
				'regular_price' => '10',
				'images'        => array(
					array(
						'id' => $original_attachment_id,
					),
				),
			)
		);
		$response_original_product = $this->server->dispatch( $request_original_product );

		$this->assertEquals( 201, $response_original_product->get_status(), 'Failed to create the initial product with an image.' );

		$original_product_data = $response_original_product->get_data();
		$original_product_id   = $original_product_data['id'];

		// 2. Move the original product to trash.
		wp_trash_post( $original_product_id );

		$attachments_before_failed_attempt = count(
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'numberposts' => -1,
				)
			)
		);

		// 3. Attempt to create a new product with the same SKU and another image.
		$create_request_for_failure = new WP_REST_Request( 'POST', '/wc/v3/products' );
		$create_request_for_failure->set_body_params(
			array(
				'name'          => 'New Product Attempt That Fails',
				'sku'           => $original_product_sku, // Duplicate SKU.
				'type'          => 'simple',
				'regular_price' => '20',
				'images'        => array(
					array(
						'src' => $shared_image_src,
						'alt' => 'New Image To Be Cleaned Up',
					),
				),
			)
		);
		$failed_creation_response      = $this->server->dispatch( $create_request_for_failure );
		$failed_creation_response_data = $failed_creation_response->get_data();

		$this->assertEquals( 400, $failed_creation_response->get_status(), 'Product creation attempt with duplicate SKU should return HTTP 400.' );
		$this->assertEquals( 'woocommerce_rest_product_not_created', $failed_creation_response_data['code'] );

		$attachments_after_failed_attempt = count(
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
					'numberposts' => -1,
				)
			)
		);

		$this->assertEquals( $attachments_before_failed_attempt, $attachments_after_failed_attempt, 'Number of attachments should remain unchanged after the failed product creation attempt, indicating cleanup of the second image.' );
		$this->assertNotNull( get_post( $original_attachment_id ), 'Original attachment for the initially created product should still exist.' );

		wp_delete_post( $original_product_id, true );
		wp_delete_attachment( $original_attachment_id, true );
	}

	/**
	 * Test that the `search_fields` parameter works with single field.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_fields_single_field() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'             => 'Blue Shirt',
				'sku'              => 'SHIRT-123',
				'global_unique_id' => '987654321',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'name' ),
				'search'        => 'Blue',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( 'Blue Shirt', $response_products[0]['name'] );
	}

	/**
	 * Test that the `search_fields` parameter works with multiple fields.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_fields_multiple_fields() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'             => 'Red Scarf',
				'sku'              => 'SCARF-456',
				'global_unique_id' => '123456789',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'name', 'sku', 'global_unique_id' ),
				'search'        => 'SCARF',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( 'Red Scarf', $response_products[0]['name'] );
	}

	/**
	 * Test that the `search_fields` parameter supports cross-field matching.
	 *
	 * @return void
	 */
	public function test_products_search_supports_cross_field_matching() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'             => 'Winter Scarf',
				'sku'              => 'SCARF-W-789',
				'global_unique_id' => '987654321',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'name', 'sku', 'global_unique_id' ),
				'search'        => 'Winter 987',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $test_product->get_id(), $response_products[0]['id'] );
	}

	/**
	 * Test that the `search_fields` parameter takes precedence over other search parameters.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_fields_parameter_precedence() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'             => 'Blue Shirt',
				'sku'              => 'SHIRT-BLUE',
				'global_unique_id' => '111222333',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields'      => array( 'name' ),
				'search'             => 'Blue',
				'search_name_or_sku' => 'nonexistent',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( 'Blue Shirt', $response_products[0]['name'] );
	}

	/**
	 * Test that the `search_fields` parameter validates allowed fields.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_fields_invalid_field() {
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'invalid_field' ),
				'search'        => 'test',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test that the `search_fields` parameter works with partial matching.
	 *
	 * @return void
	 */
	public function test_products_search_with_search_fields_partial_matching() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'             => 'Premium Wool Scarf',
				'sku'              => 'SCARF-W-PREMIUM',
				'global_unique_id' => '9876543210123',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'global_unique_id' ),
				'search'        => '987',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( 'Premium Wool Scarf', $response_products[0]['name'] );
	}

	/**
	 * Test that the `search_fields` parameter works with description field.
	 *
	 * @return void
	 */
	public function test_products_search_with_description_field() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'        => 'Blue Widget',
				'description' => 'A premium quality winter scarf made from wool.',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'description' ),
				'search'        => 'winter wool',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $test_product->get_id(), $response_products[0]['id'] );
	}

	/**
	 * Test that the `search_fields` parameter works with short_description field.
	 *
	 * @return void
	 */
	public function test_products_search_with_short_description_field() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'              => 'Green Gadget',
				'short_description' => 'Perfect for summer activities.',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'short_description' ),
				'search'        => 'summer activities',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $test_product->get_id(), $response_products[0]['id'] );
	}

	/**
	 * Test that the `search_fields` parameter works with mixed content fields.
	 *
	 * @return void
	 */
	public function test_products_search_with_mixed_content_fields() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'              => 'Red Tool',
				'description'       => 'Essential tool for professionals.',
				'short_description' => 'High quality craftsmanship.',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_fields' => array( 'description', 'short_description' ),
				'search'        => 'quality professionals',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( $test_product->get_id(), $response_products[0]['id'] );
	}

	/**
	 * Test that backward compatibility is maintained with existing search parameters.
	 *
	 * @return void
	 */
	public function test_products_search_backward_compatibility() {
		$test_product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Classic Shirt',
				'sku'  => 'SHIRT-CLASSIC',
			)
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_query_params(
			array(
				'search_name_or_sku' => 'Classic',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response_products = $response->get_data();

		$this->assertEquals( 1, count( $response_products ) );
		$this->assertEquals( 'Classic Shirt', $response_products[0]['name'] );
	}

	/**
	 * Perform a REST POST request to update a product.
	 *
	 * @param WC_Product $product The product to update.
	 * @param array      $request_body Data to be sent (JSON-encoded) as the body of the request.
	 */
	private function update_product_via_post_request( WC_Product $product, array $request_body ) {
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $request_body ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * @testdox Should sanitize external product button text.
	 */
	public function test_update_external_product_sanitizes_button_text(): void {
		$shop_manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $shop_manager );

		$product = WC_Helper_Product::create_external_product();
		$this->update_product_via_post_request(
			$product,
			array(
				'button_text' => 'Buy now<style>.hidden { display: none; }</style>',
			)
		);

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertSame( 'Buy now', $updated_product->get_button_text(), 'HTML should be removed from the button text.' );

		$product->delete( true );
	}

	/**
	 * Test that batch create operations update term counts correctly.
	 *
	 * Verifies that when creating products via batch operations, the term counts
	 * are properly updated when hide out of stock is disabled.
	 */
	public function test_batch_create_updates_term_counts() {
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$term         = wp_insert_term( 'BatchTestCategory', 'product_cat' );
		$term_id      = $term['term_id'];
		$count_before = (int) get_term_meta( $term_id, 'product_count_product_cat', true );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
		$request->set_body_params(
			array(
				'create' => array(
					array(
						'name'         => 'Batch Product 1',
						'type'         => 'simple',
						'status'       => 'publish',
						'stock_status' => 'instock',
						'categories'   => array( array( 'id' => $term_id ) ),
					),
				),
			)
		);
		$this->server->dispatch( $request );

		$count_after = (int) get_term_meta( $term_id, 'product_count_product_cat', true );
		$this->assertEquals( $count_before + 1, $count_after, 'Batch create should update term count.' );
	}

	/**
	 * Test that batch create obeys hide out of stock setting.
	 *
	 * Verifies that when creating out of stock products via batch operations,
	 * the term counts are not increased when hide out of stock is enabled.
	 */
	public function test_batch_create_out_of_stock_obeys_hide_setting() {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$term         = wp_insert_term( 'BatchTestCategory', 'product_cat' );
		$term_id      = $term['term_id'];
		$count_before = (int) get_term_meta( $term_id, 'product_count_product_cat', true );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
		$request->set_body_params(
			array(
				'create' => array(
					array(
						'name'         => 'Batch Product 2',
						'type'         => 'simple',
						'status'       => 'publish',
						'stock_status' => 'outofstock',
						'categories'   => array( array( 'id' => $term_id ) ),
					),
				),
			)
		);
		$this->server->dispatch( $request );

		$count_after = (int) get_term_meta( $term_id, 'product_count_product_cat', true );
		$this->assertEquals( $count_before, $count_after, 'Out-of-stock products should not increment count with hide setting ON.' );
	}

	/**
	 * Test that batch update of stock status affects term counts.
	 *
	 * Verifies that updating product stock status via batch operations properly
	 * decrements term counts when hide out of stock is enabled.
	 */
	public function test_batch_update_stock_status_affects_term_counts() {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$term    = wp_insert_term( 'BatchTestCategory', 'product_cat' );
		$term_id = $term['term_id'];
		wp_set_object_terms( $product->get_id(), $term_id, 'product_cat' );
		update_post_meta( $product->get_id(), '_stock_status', 'instock' );

		$count_before = (int) get_term_meta( $term_id, 'product_count_product_cat', true );

		$update_request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
		$update_request->set_body_params(
			array(
				'update' => array(
					array(
						'id'           => $product->get_id(),
						'stock_status' => 'outofstock',
					),
				),
			)
		);
		$this->server->dispatch( $update_request );

		$count_after = (int) get_term_meta( $term_id, 'product_count_product_cat', true );
		$this->assertEquals( $count_before - 1, $count_after, 'Term count should decrease after hiding from catalog.' );
	}

	/**
	 * Test that batch update of product status affects term counts.
	 *
	 * Verifies that updating product status via batch operations properly
	 * decrements term counts when products are changed to draft status.
	 */
	public function test_batch_update_status_affects_term_counts() {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$term    = wp_insert_term( 'BatchTestCategory', 'product_cat' );
		$term_id = $term['term_id'];
		wp_set_object_terms( $product->get_id(), $term_id, 'product_cat' );
		update_post_meta( $product->get_id(), '_stock_status', 'instock' );

		$count_before = (int) get_term_meta( $term_id, 'product_count_product_cat', true );

		$update_request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
		$update_request->set_body_params(
			array(
				'update' => array(
					array(
						'id'     => $product->get_id(),
						'status' => 'draft',
					),
				),
			)
		);
		$this->server->dispatch( $update_request );

		$count_after = (int) get_term_meta( $term_id, 'product_count_product_cat', true );
		$this->assertEquals( $count_before - 1, $count_after, 'Term count should decrease after hiding from catalog.' );
	}

	/**
	 * Test that batch delete operations update term counts.
	 *
	 * Verifies that when deleting products via batch operations, the term counts
	 * are properly decremented immediately.
	 */
	public function test_batch_delete_product_updates_term_counts() {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$product = WC_Helper_Product::create_simple_product();
		$term    = wp_insert_term( 'BatchTestCategory', 'product_cat' );
		$term_id = $term['term_id'];
		wp_set_object_terms( $product->get_id(), $term_id, 'product_cat' );
		update_post_meta( $product->get_id(), '_stock_status', 'instock' );

		$count_before = (int) get_term_meta( $term_id, 'product_count_product_cat', true );

		$delete_request = new WP_REST_Request( 'POST', '/wc/v3/products/batch' );
		$delete_request->set_body_params( array( 'delete' => array( $product->get_id() ) ) );
		$this->server->dispatch( $delete_request );

		$count_after = (int) get_term_meta( $term_id, 'product_count_product_cat', true );
		$this->assertEquals( $count_before - 1, $count_after, 'Batch delete should decrement term count immediately.' );
	}

	/**
	 * Test `pos_products_only` filter returns only POS-visible products when true.
	 */
	public function test_pos_products_only_true_returns_only_pos_visible_products() {
		$visible_product = WC_Helper_Product::create_simple_product();
		$hidden_product  = WC_Helper_Product::create_simple_product();

		// Mark the hidden product as hidden from POS.
		wp_set_object_terms( $hidden_product->get_id(), 'pos-hidden', 'pos_product_visibility' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'pos_products_only', true );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = wp_list_pluck( $products, 'id' );
		$this->assertContains( $visible_product->get_id(), $product_ids );
		$this->assertNotContains( $hidden_product->get_id(), $product_ids );
	}

	/**
	 * Test `pos_products_only` filter returns all products when false.
	 */
	public function test_pos_products_only_false_returns_all_products() {
		$visible_product = WC_Helper_Product::create_simple_product();
		$hidden_product  = WC_Helper_Product::create_simple_product();

		// Mark the hidden product as hidden from POS.
		wp_set_object_terms( $hidden_product->get_id(), 'pos-hidden', 'pos_product_visibility' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		$request->set_param( 'pos_products_only', false );

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = wp_list_pluck( $products, 'id' );
		$this->assertContains( $visible_product->get_id(), $product_ids );
		$this->assertContains( $hidden_product->get_id(), $product_ids );
	}

	/**
	 * Test that omitting `pos_products_only` filter returns all products regardless of visibility in POS.
	 */
	public function test_pos_products_only_omitted_returns_all_products() {
		$visible_product = WC_Helper_Product::create_simple_product();
		$hidden_product  = WC_Helper_Product::create_simple_product();

		// Mark the hidden product as hidden from POS.
		wp_set_object_terms( $hidden_product->get_id(), 'pos-hidden', 'pos_product_visibility' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );
		// Do not set pos_products_only parameter.

		$response = $this->server->dispatch( $request );
		$products = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = wp_list_pluck( $products, 'id' );
		$this->assertContains( $visible_product->get_id(), $product_ids );
		$this->assertContains( $hidden_product->get_id(), $product_ids );
	}

	/**
	 * @testdox Updating a product with incomplete meta_data entries does not cause errors.
	 */
	public function test_update_meta_data_with_incomplete_entries(): void {
		$product = WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/' . $product->get_id() );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'meta_data' => $this->get_incomplete_meta_data_input() ) ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$this->assert_incomplete_meta_data_handled_correctly( wc_get_product( $product->get_id() ) );
	}
}
