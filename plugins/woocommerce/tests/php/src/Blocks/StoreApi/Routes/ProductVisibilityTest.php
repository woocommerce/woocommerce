<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Tests for product visibility filtering in Store API routes.
 *
 * The Store API has nuanced visibility filtering based on query type:
 * - products/$id: Bypasses visibility and stock filtering (for product pages)
 * - products?include=$ids: Bypasses catalog visibility, applies stock filtering
 * - products?slug=$slug: Bypasses catalog visibility, applies stock filtering
 * - products?sku=$sku: Bypasses catalog visibility, applies stock filtering
 * - products?search=$term: Applies search visibility filtering, applies stock filtering
 * - products (no params): Applies catalog visibility filtering, applies stock filtering
 *
 * Password-protected products are always filtered from all Store API responses.
 */
class ProductVisibilityTest extends ControllerTestCase {

	/**
	 * Visible product.
	 *
	 * @var \WC_Product
	 */
	private $visible_product;

	/**
	 * Hidden product.
	 *
	 * @var \WC_Product
	 */
	private $hidden_product;

	/**
	 * Catalog-only product (Shop only - visible in catalog, hidden from search).
	 *
	 * @var \WC_Product
	 */
	private $catalog_only_product;

	/**
	 * Search-only product (Search results only - visible in search, hidden from catalog).
	 *
	 * @var \WC_Product
	 */
	private $search_only_product;

	/**
	 * Out of stock product.
	 *
	 * @var \WC_Product
	 */
	private $out_of_stock_product;

	/**
	 * Password-protected product.
	 *
	 * @var \WC_Product
	 */
	private $password_protected_product;

	/**
	 * Draft product.
	 *
	 * @var \WC_Product
	 */
	private $draft_product;

	/**
	 * Store original option value.
	 *
	 * @var string
	 */
	private $original_hide_out_of_stock;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->original_hide_out_of_stock = get_option( 'woocommerce_hide_out_of_stock_items', 'no' );

		$fixtures = new FixtureData();

		$this->visible_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Visible Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$this->visible_product->set_catalog_visibility( CatalogVisibility::VISIBLE );
		$this->visible_product->save();

		$this->hidden_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Hidden Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$this->hidden_product->set_catalog_visibility( CatalogVisibility::HIDDEN );
		$this->hidden_product->save();

		$this->catalog_only_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Catalog Only Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$this->catalog_only_product->set_catalog_visibility( CatalogVisibility::CATALOG );
		$this->catalog_only_product->save();

		$this->search_only_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Search Only Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$this->search_only_product->set_catalog_visibility( CatalogVisibility::SEARCH );
		$this->search_only_product->save();

		$this->out_of_stock_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Out of Stock Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
			)
		);
		$this->out_of_stock_product->set_catalog_visibility( CatalogVisibility::VISIBLE );
		$this->out_of_stock_product->save();

		$this->password_protected_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Password Protected Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
			)
		);
		$this->password_protected_product->set_catalog_visibility( CatalogVisibility::VISIBLE );
		$this->password_protected_product->save();

		wp_update_post(
			array(
				'ID'            => $this->password_protected_product->get_id(),
				'post_password' => 'secret',
			)
		);

		$this->draft_product = $fixtures->get_simple_product(
			array(
				'name'          => 'Draft Product',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'status'        => 'draft',
			)
		);
		$this->draft_product->set_catalog_visibility( CatalogVisibility::VISIBLE );
		$this->draft_product->save();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', $this->original_hide_out_of_stock );
		parent::tearDown();
	}

	/**
	 * @testdox Single product endpoint returns hidden product (bypasses visibility).
	 */
	public function test_single_product_returns_hidden_product(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->hidden_product->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Hidden product should be accessible by ID' );
		$this->assertEquals( $this->hidden_product->get_id(), $data['id'] );
	}

	/**
	 * @testdox Single product endpoint returns search-only product (bypasses visibility).
	 */
	public function test_single_product_returns_search_only_product(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->search_only_product->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Search-only product should be accessible by ID' );
		$this->assertEquals( $this->search_only_product->get_id(), $data['id'] );
	}

	/**
	 * @testdox Single product endpoint returns out of stock product (bypasses stock filtering).
	 */
	public function test_single_product_returns_out_of_stock_product(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->out_of_stock_product->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Out of stock product should be accessible by ID even when hide option is enabled' );
		$this->assertEquals( $this->out_of_stock_product->get_id(), $data['id'] );
	}

	/**
	 * @testdox Single product endpoint returns 404 for password-protected product.
	 */
	public function test_single_product_returns_404_for_password_protected(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->password_protected_product->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status(), 'Password-protected product should return 404' );
	}

	/**
	 * @testdox Single product endpoint returns 404 for draft product.
	 */
	public function test_single_product_returns_404_for_draft(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->draft_product->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status(), 'Draft product should return 404' );
	}

	/**
	 * @testdox Single product endpoint returns visible product.
	 */
	public function test_single_product_returns_visible_product(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->visible_product->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->visible_product->get_id(), $data['id'] );
	}

	/**
	 * @testdox Single product endpoint returns catalog-only product.
	 */
	public function test_single_product_returns_catalog_only_product(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->catalog_only_product->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->catalog_only_product->get_id(), $data['id'] );
	}

	/**
	 * @testdox Collection with include param returns hidden product (bypasses visibility).
	 */
	public function test_collection_with_include_returns_hidden_product(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'include', array( $this->hidden_product->get_id() ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->hidden_product->get_id(), $product_ids, 'Hidden product should be returned with include parameter' );
	}

	/**
	 * @testdox Collection with include param respects stock status filtering.
	 */
	public function test_collection_with_include_respects_stock_status(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'include', array( $this->out_of_stock_product->get_id() ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $data, 'Out of stock product should not be returned with include parameter when hide option is enabled' );
	}

	/**
	 * @testdox Collection with include param excludes password-protected product.
	 */
	public function test_collection_with_include_excludes_password_protected(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'include', array( $this->password_protected_product->get_id() ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $data, 'Password-protected product should not be returned with include parameter' );
	}

	/**
	 * @testdox Collection with slug param returns hidden product (bypasses visibility).
	 */
	public function test_collection_with_slug_returns_hidden_product(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'slug', $this->hidden_product->get_slug() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->hidden_product->get_id(), $product_ids, 'Hidden product should be returned with slug parameter' );
	}

	/**
	 * @testdox Collection with slug param excludes password-protected product.
	 */
	public function test_collection_with_slug_excludes_password_protected(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'slug', $this->password_protected_product->get_slug() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEmpty( $data, 'Password-protected product should not be returned with slug parameter' );
	}

	/**
	 * @testdox Products by slug endpoint returns hidden product (bypasses visibility).
	 */
	public function test_products_by_slug_endpoint_returns_hidden_product(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->hidden_product->get_slug() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status(), 'Hidden product should be accessible by slug' );
		$this->assertEquals( $this->hidden_product->get_id(), $data['id'] );
	}

	/**
	 * @testdox Products by slug endpoint returns 404 for password-protected product.
	 */
	public function test_products_by_slug_endpoint_returns_404_for_password_protected(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/' . $this->password_protected_product->get_slug() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status(), 'Password-protected product should return 404 by slug' );
	}

	/**
	 * @testdox Search query returns search-only product.
	 */
	public function test_search_returns_search_only_product(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'search', 'Search Only' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->search_only_product->get_id(), $product_ids, 'Search-only product should be returned in search results' );
	}

	/**
	 * @testdox Search query excludes catalog-only product.
	 */
	public function test_search_excludes_catalog_only_product(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'search', 'Catalog Only' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$product_ids = array_column( $data, 'id' );

		$this->assertNotContains( $this->catalog_only_product->get_id(), $product_ids, 'Catalog-only product should be excluded from search results' );
	}

	/**
	 * @testdox Search query excludes hidden product.
	 */
	public function test_search_excludes_hidden_product(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'search', 'Hidden' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$product_ids = array_column( $data, 'id' );

		$this->assertNotContains( $this->hidden_product->get_id(), $product_ids, 'Hidden product should be excluded from search results' );
	}

	/**
	 * @testdox Search query excludes password-protected product.
	 */
	public function test_search_excludes_password_protected_product(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'search', 'Password Protected' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$product_ids = array_column( $data, 'id' );

		$this->assertNotContains( $this->password_protected_product->get_id(), $product_ids, 'Password-protected product should be excluded from search results' );
	}

	/**
	 * @testdox Collection endpoint excludes hidden products by default.
	 */
	public function test_collection_excludes_hidden_products(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->visible_product->get_id(), $product_ids, 'Visible product should be included' );
		$this->assertContains( $this->catalog_only_product->get_id(), $product_ids, 'Catalog-only product should be included' );
		$this->assertNotContains( $this->hidden_product->get_id(), $product_ids, 'Hidden product should be excluded' );
		$this->assertNotContains( $this->search_only_product->get_id(), $product_ids, 'Search-only product should be excluded from catalog view' );
	}

	/**
	 * @testdox Collection endpoint excludes password-protected products.
	 */
	public function test_collection_excludes_password_protected_products(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = array_column( $data, 'id' );

		$this->assertNotContains( $this->password_protected_product->get_id(), $product_ids, 'Password-protected product should be excluded' );
	}

	/**
	 * @testdox Collection endpoint can explicitly request hidden products.
	 */
	public function test_collection_can_request_hidden_visibility(): void {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$request->set_param( 'catalog_visibility', CatalogVisibility::HIDDEN );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->hidden_product->get_id(), $product_ids, 'Hidden product should be returned when explicitly requested' );
		$this->assertNotContains( $this->visible_product->get_id(), $product_ids, 'Visible product should be excluded when requesting hidden visibility' );
	}

	/**
	 * @testdox Collection endpoint excludes out of stock products when option is enabled.
	 */
	public function test_collection_excludes_out_of_stock_when_option_enabled(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->visible_product->get_id(), $product_ids, 'In-stock product should be included' );
		$this->assertNotContains( $this->out_of_stock_product->get_id(), $product_ids, 'Out of stock product should be excluded' );
	}

	/**
	 * @testdox Collection endpoint includes out of stock products when option is disabled.
	 */
	public function test_collection_includes_out_of_stock_when_option_disabled(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = array_column( $data, 'id' );

		$this->assertContains( $this->visible_product->get_id(), $product_ids, 'In-stock product should be included' );
		$this->assertContains( $this->out_of_stock_product->get_id(), $product_ids, 'Out of stock product should be included when option is disabled' );
	}

	/**
	 * @testdox Collection endpoint excludes draft products.
	 */
	public function test_collection_excludes_draft_products(): void {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$product_ids = array_column( $data, 'id' );

		$this->assertNotContains( $this->draft_product->get_id(), $product_ids, 'Draft product should be excluded' );
	}
}
