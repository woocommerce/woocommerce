<?php
/**
 * UCP Catalog Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

/**
 * Tests for the /wc/ucp/v1/catalog/* endpoints.
 */
class UcpCatalog extends ControllerTestCase {
	/**
	 * Published product IDs shared by the class, oldest first.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * SKU of the newest fixture product.
	 *
	 * @var string
	 */
	private const SKU = 'ucp-catalog-sku';

	/**
	 * Create immutable fixtures before route registration starts.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		$products = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'UCP Older Product',
					'regular_price' => '5.00',
					'price'         => '5.00',
				),
				array(
					'name'          => 'UCP Nebula Mug',
					'regular_price' => '19.99',
					'sale_price'    => '14.50',
					'price'         => '14.50',
					'sku'           => self::SKU,
				),
			)
		);

		self::$product_ids = array_map(
			static function ( $product ) {
				return $product->get_id();
			},
			$products
		);
	}

	/**
	 * Delete class fixtures.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( self::$product_ids );
	}

	/**
	 * Option behind the `ucp` feature.
	 */
	private const FEATURE_OPTION = 'woocommerce_feature_ucp_enabled';

	/**
	 * Enable the UCP routes before the REST server is built.
	 */
	protected function setUp(): void {
		parent::setUp();
		update_option( self::FEATURE_OPTION, 'yes' );
		$this->initialize_store_api_server();
	}

	/**
	 * Dispatch a POST request with a JSON body.
	 *
	 * @param string $path Route path under /wc/ucp/v1.
	 * @param array  $body Request body.
	 * @return \WP_REST_Response
	 */
	private function post( string $path, array $body = array() ) {
		$request = new \WP_REST_Request( 'POST', '/wc/ucp/v1' . $path );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * The routes must not exist unless a site opts in.
	 */
	public function test_routes_are_not_registered_by_default(): void {
		delete_option( self::FEATURE_OPTION );
		$this->initialize_store_api_server();

		foreach ( array( '/catalog/search', '/catalog/lookup', '/catalog/product' ) as $path ) {
			$this->assertSame( 404, $this->post( $path )->get_status(), $path . ' should not be registered' );
		}
	}

	/**
	 * Search returns published products in the UCP shape, with amounts in minor units.
	 */
	public function test_search_returns_published_products_in_minor_units(): void {
		$response = $this->post( '/catalog/search', array( 'query' => 'Nebula Mug' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '2026-04-08', $data['ucp']['version'] );
		$this->assertSame( 'success', $data['ucp']['status'] );
		// The envelope names only the capability relevant to the operation answered.
		$this->assertSame( array( 'dev.ucp.shopping.catalog.search' ), array_keys( $data['ucp']['capabilities'] ) );
		$this->assertArrayHasKey( 'has_next_page', $data['pagination'] );

		$product = $this->find_product( $data['products'], end( self::$product_ids ) );

		$this->assertSame( 'UCP Nebula Mug', $product['title'] );
		$this->assertSame( 1450, $product['price_range']['min']['amount'] );
		$this->assertSame( get_woocommerce_currency(), $product['price_range']['min']['currency'] );
		// The regular price survives as the strike-through comparison.
		$this->assertSame( 1999, $product['list_price_range']['min']['amount'] );

		$this->assertCount( 1, $product['variants'] );
		$this->assertSame( self::SKU, $product['variants'][0]['sku'] );
		$this->assertSame( 1450, $product['variants'][0]['price']['amount'] );
		$this->assertTrue( $product['variants'][0]['availability']['available'] );
	}

	/**
	 * Price bounds are taken in minor units and applied against the stored price.
	 */
	public function test_search_price_filter_takes_minor_units(): void {
		list( $older, $newest ) = self::$product_ids;

		// Both fixtures carry the "UCP" prefix, so the query scopes the assertions to them.
		$above = $this->post(
			'/catalog/search',
			array(
				'query'   => 'UCP',
				'filters' => array( 'price' => array( 'min' => 1000 ) ),
			)
		)->get_data();
		$this->assertSame( array( (string) $newest ), wp_list_pluck( $above['products'], 'id' ) );

		$below = $this->post(
			'/catalog/search',
			array(
				'query'   => 'UCP',
				'filters' => array( 'price' => array( 'max' => 1000 ) ),
			)
		)->get_data();
		$this->assertSame( array( (string) $older ), wp_list_pluck( $below['products'], 'id' ) );
	}

	/**
	 * Lookup resolves product IDs only; a SKU is reported as a miss like any other unknown id.
	 */
	public function test_lookup_resolves_ids_and_reports_misses(): void {
		$newest   = end( self::$product_ids );
		$response = $this->post( '/catalog/lookup', array( 'ids' => array( (string) $newest, self::SKU ) ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );

		$this->assertCount( 1, $data['products'] );
		$this->assertSame( (string) $newest, $data['products'][0]['id'] );
		$this->assertSame( array( (string) $newest ), wp_list_pluck( $data['products'][0]['variants'][0]['inputs'], 'id' ) );

		$this->assertSame( 'not_found', $data['messages'][0]['code'] );
		$this->assertSame( '$.ids', $data['messages'][0]['path'] );
	}

	/**
	 * Lookup follows the storefront's hide-out-of-stock setting.
	 */
	public function test_lookup_hides_out_of_stock_products_when_the_store_does(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'stock_status' => 'outofstock' ) );
		$body    = array( 'ids' => array( (string) $product->get_id() ) );

		$data = $this->post( '/catalog/lookup', $body )->get_data();
		$this->assertFalse( $data['products'][0]['variants'][0]['availability']['available'] );

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
		$data = $this->post( '/catalog/lookup', $body )->get_data();
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );

		$this->assertSame( array(), $data['products'] );
		$this->assertSame( 'not_found', $data['messages'][0]['code'] );
	}

	/**
	 * A category list resolving to no existing term matches nothing rather than everything.
	 */
	public function test_search_unknown_category_returns_no_products(): void {
		$newest = end( self::$product_ids );
		$term   = wp_insert_term( 'UCP Mugs', 'product_cat', array( 'slug' => 'ucp-mugs' ) );
		wp_set_object_terms( $newest, (int) $term['term_id'], 'product_cat' );

		$data = $this->post( '/catalog/search', array( 'filters' => array( 'categories' => array( 'ucp-mugs' ) ) ) )->get_data();
		$this->assertSame( array( (string) $newest ), wp_list_pluck( $data['products'], 'id' ) );

		$data = $this->post( '/catalog/search', array( 'filters' => array( 'categories' => array( 'does-not-exist' ) ) ) )->get_data();
		$this->assertSame( array(), $data['products'] );
		$this->assertFalse( $data['pagination']['has_next_page'] );
	}

	/**
	 * Search needs a query or a supported filter; an unrecognized filter alone does not count.
	 */
	public function test_search_without_recognized_input_returns_ucp_validation_error(): void {
		$response = $this->post( '/catalog/search', array( 'filters' => array( 'brand' => 'acme' ) ) );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'VALIDATION_ERROR', $data['messages'][0]['code'] );
	}

	/**
	 * A missing `ids` list is a UCP validation error, not a WordPress one.
	 */
	public function test_lookup_without_ids_returns_ucp_validation_error(): void {
		$response = $this->post( '/catalog/lookup' );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'VALIDATION_ERROR', $data['messages'][0]['code'] );
	}

	/**
	 * Product detail answers with `ucp.status`, and reports a miss as 200 + `not_found`.
	 */
	public function test_product_detail_reports_status_in_the_envelope(): void {
		$newest   = end( self::$product_ids );
		$response = $this->post( '/catalog/product', array( 'id' => (string) $newest ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $data['ucp']['status'] );
		$this->assertSame( array( 'dev.ucp.shopping.catalog.lookup' ), array_keys( $data['ucp']['capabilities'] ) );
		$this->assertSame( (string) $newest, $data['product']['id'] );
		$this->assertSame( 1450, $data['product']['variants'][0]['price']['amount'] );

		$missing = $this->post( '/catalog/product', array( 'id' => '99999999' ) );
		$data    = $missing->get_data();

		$this->assertSame( 200, $missing->get_status(), 'A missing product is reported in the envelope, not as a 404' );
		$this->assertSame( 'error', $data['ucp']['status'] );
		$this->assertSame( 'not_found', $data['messages'][0]['code'] );
		$this->assertSame( 'unrecoverable', $data['messages'][0]['severity'] );
	}

	/**
	 * A variant id puts that variation first, takes its options as the selection,
	 * and overrides a conflicting request selection.
	 */
	public function test_product_detail_by_variation_id_leads_with_that_variation(): void {
		$children = \WC_Helper_Product::create_variation_product()->get_children();
		// The helper's last variation: size huge, colour blue, number left as "Any".
		$variation_id = (string) end( $children );

		$data = $this->post(
			'/catalog/product',
			array(
				'id'       => $variation_id,
				'selected' => array(
					array(
						'name'  => 'colour',
						'label' => 'red',
					),
				),
			)
		)->get_data();

		$this->assertSame( $variation_id, $data['product']['variants'][0]['id'] );
		$this->assertSame(
			array(
				array(
					'name'  => 'size',
					'label' => 'huge',
				),
				array(
					'name'  => 'colour',
					'label' => 'blue',
				),
			),
			$data['product']['selected']
		);
		// The huge/blue variation with number 2 matches the same selection; nothing else does.
		$this->assertCount( 2, $data['product']['variants'] );
	}

	/**
	 * The opaque cursor walks forward through the result set.
	 */
	public function test_search_cursor_advances_to_the_next_page(): void {
		$first = $this->post(
			'/catalog/search',
			array(
				'query'      => 'UCP',
				'pagination' => array( 'limit' => 1 ),
			)
		)->get_data();

		$this->assertCount( 1, $first['products'] );
		$this->assertTrue( $first['pagination']['has_next_page'] );
		$this->assertNotEmpty( $first['pagination']['cursor'] );

		$second = $this->post(
			'/catalog/search',
			array(
				'query'      => 'UCP',
				'pagination' => array(
					'limit'  => 1,
					'cursor' => $first['pagination']['cursor'],
				),
			)
		)->get_data();

		$this->assertCount( 1, $second['products'] );
		$this->assertNotSame( $first['products'][0]['id'], $second['products'][0]['id'] );
	}

	/**
	 * Find a mapped product by id.
	 *
	 * @param array $products Mapped products.
	 * @param int   $product_id Product id to find.
	 * @return array
	 */
	private function find_product( array $products, int $product_id ): array {
		foreach ( $products as $product ) {
			if ( (string) $product_id === $product['id'] ) {
				return $product;
			}
		}

		$this->fail( 'Product ' . $product_id . ' was not returned by the search' );
	}
}
