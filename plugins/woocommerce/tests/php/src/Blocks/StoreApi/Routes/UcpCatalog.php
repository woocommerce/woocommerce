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
	 * Enable the UCP routes before the REST server is built.
	 */
	protected function setUp(): void {
		add_filter( 'woocommerce_ucp_enabled', '__return_true' );
		parent::setUp();
	}

	/**
	 * Restore the default disabled state.
	 */
	protected function tearDown(): void {
		remove_filter( 'woocommerce_ucp_enabled', '__return_true' );
		parent::tearDown();
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
		remove_filter( 'woocommerce_ucp_enabled', '__return_true' );
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
	 * Lookup resolves both product IDs and SKUs, and reports misses as messages.
	 */
	public function test_lookup_resolves_ids_and_skus(): void {
		$newest   = end( self::$product_ids );
		$response = $this->post( '/catalog/lookup', array( 'ids' => array( (string) $newest, self::SKU, 'no-such-sku' ) ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );

		// Both identifiers name the same product, so it appears once carrying both.
		$this->assertCount( 1, $data['products'] );
		$this->assertSame( (string) $newest, $data['products'][0]['id'] );
		$this->assertSame(
			array( (string) $newest, self::SKU ),
			wp_list_pluck( $data['products'][0]['variants'][0]['inputs'], 'id' )
		);

		$this->assertSame( 'not_found', $data['messages'][0]['code'] );
		$this->assertSame( '$.ids', $data['messages'][0]['path'] );
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
	 * The opaque cursor walks forward through the result set.
	 */
	public function test_search_cursor_advances_to_the_next_page(): void {
		$first = $this->post( '/catalog/search', array( 'pagination' => array( 'limit' => 1 ) ) )->get_data();

		$this->assertCount( 1, $first['products'] );
		$this->assertTrue( $first['pagination']['has_next_page'] );
		$this->assertNotEmpty( $first['pagination']['cursor'] );
		$this->assertGreaterThan( 1, $first['pagination']['total_count'] );

		$second = $this->post(
			'/catalog/search',
			array(
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
