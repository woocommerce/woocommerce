<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Class WC_Term_Functions_Tests.
 */
class WC_Term_Functions_Tests extends \WC_Unit_Test_Case {
	/**
	 * @var WP_Term[] Test terms.
	 */
	private $terms = array();

	/**
	 * @var WC_Product_Simple[] Test products.
	 */
	private $products = array();

	/**
	 * Setup before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->terms['parent'] = wp_insert_term( 'Parent term', 'product_cat' );
		$this->terms['child1'] = wp_insert_term( 'Child term 1', 'product_cat', array( 'parent' => $this->terms['parent']['term_id'] ) );
		$this->terms['child2'] = wp_insert_term( 'Child term 2', 'product_cat', array( 'parent' => $this->terms['parent']['term_id'] ) );

		$this->terms['tag1'] = wp_insert_term( 'Tag 1', 'product_tag' );
		$this->terms['tag2'] = wp_insert_term( 'Tag 2', 'product_tag' );

		$this->products['product1'] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $this->terms['child1']['term_id'] ),
				'tag_ids'      => array( $this->terms['tag1']['term_id'] ),
			)
		);
		$this->products['product2'] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $this->terms['child2']['term_id'] ),
				'tag_ids'      => array( $this->terms['tag2']['term_id'] ),
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);
		$this->products['product3'] = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $this->terms['parent']['term_id'] ),
				'tag_ids'      => array( $this->terms['tag1']['term_id'], $this->terms['tag2']['term_id'] ),
			)
		);
	}

	/**
	 * Teardown after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->terms as $term ) {
			wp_delete_term( $term['term_id'], $term['term_taxonomy_id'] );
		}
		$this->terms = array();

		foreach ( $this->products as $product ) {
			$product->delete();
		}
		$this->products = array();

		parent::tearDown();
	}

	/**
	 * @testdox Term product counts with default settings.
	 */
	public function test_term_count_baseline(): void {
		$terms       = get_terms(
			array(
				'taxonomy'   => array( 'product_cat', 'product_tag' ),
				'hide_empty' => false,
			)
		);
		$term_counts = wp_list_pluck( $terms, 'count', 'term_id' );

		$this->assertEquals( 3, $term_counts[ $this->terms['parent']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child1']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child2']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag1']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag2']['term_id'] ] );
	}

	/**
	 * @testdox Term product counts when a product is hidden from the catalog.
	 */
	public function test_product_visibility(): void {
		$this->products['product1']->set_catalog_visibility( 'hidden' );
		$this->products['product1']->save();

		wc_recount_all_terms();
		delete_transient( 'wc_term_counts' );

		$terms       = get_terms(
			array(
				'taxonomy'   => array( 'product_cat', 'product_tag' ),
				'hide_empty' => false,
			)
		);
		$term_counts = wp_list_pluck( $terms, 'count', 'term_id' );

		$this->assertEquals( 2, $term_counts[ $this->terms['parent']['term_id'] ] );
		$this->assertEquals( 0, $term_counts[ $this->terms['child1']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child2']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['tag1']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag2']['term_id'] ] );
	}

	/**
	 * @testdox Term product counts when a product is out of stock and OOS products are hidden from the catalog.
	 */
	public function test_hide_out_of_stock_products(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		wc_recount_all_terms( false );
		delete_transient( 'wc_term_counts' );

		$terms       = get_terms(
			array(
				'taxonomy'   => array( 'product_cat', 'product_tag' ),
				'hide_empty' => false,
			)
		);
		$term_counts = wp_list_pluck( $terms, 'count', 'term_id' );

		$this->assertEquals( 2, $term_counts[ $this->terms['parent']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['child1']['term_id'] ] );
		$this->assertEquals( 0, $term_counts[ $this->terms['child2']['term_id'] ] );
		$this->assertEquals( 2, $term_counts[ $this->terms['tag1']['term_id'] ] );
		$this->assertEquals( 1, $term_counts[ $this->terms['tag2']['term_id'] ] );

		delete_option( 'woocommerce_hide_out_of_stock_items' );
	}

	/**
	 * @testdox The call to WP Core's _update_post_term_count function in _wc_term_recount should receive
	 *          term_taxonomy_id values rather than term_id values for its first parameter.
	 */
	public function test_standard_callback_gets_correct_params(): void {
		$target_tt_id = $this->terms['parent']['term_taxonomy_id'];
		$success      = false;

		$action_callback = function ( $tt_id ) use ( $target_tt_id, &$success ) {
			if ( $tt_id === $target_tt_id ) {
				$success = true;
			}
		};

		add_action( 'edited_term_taxonomy', $action_callback );

		$target_term = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'include'    => $this->terms['parent']['term_id'],
				'hide_empty' => false,
				'fields'     => 'id=>parent',
			)
		);

		_wc_term_recount( $target_term, get_taxonomy( 'product_cat' ), true, false );

		$this->assertTrue( $success );

		remove_action( 'edited_term_taxonomy', $action_callback );
	}

	/**
	 * @testdox Featured term ID matches current site.
	 */
	public function test_get_product_visibility_term_ids_includes_featured(): void {
		$term_ids = wc_get_product_visibility_term_ids();

		$this->assertIsArray( $term_ids );
		$this->assertArrayHasKey( 'featured', $term_ids );

		$featured_term = get_term_by( 'name', 'featured', 'product_visibility' );

		$this->assertNotFalse( $featured_term );
		$this->assertSame( (int) $featured_term->term_taxonomy_id, (int) $term_ids['featured'] );
	}

	/**
	 * @testdox Featured filter returns only featured products.
	 */
	public function test_wc_get_products_featured_returns_featured_products(): void {
		$featured_product = WC_Helper_Product::create_simple_product( true );
		$featured_product->set_featured( true );
		$featured_product->save();

		$regular_product = WC_Helper_Product::create_simple_product( true );
		$regular_product->set_featured( false );
		$regular_product->save();

		$featured_products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 50,
				'featured' => true,
			)
		);

		$featured_ids = array_map( fn( $product ) => $product->get_id(), $featured_products );

		$this->assertSame( array( $featured_product->get_id() ), $featured_ids );

		$featured_product->delete();
		$regular_product->delete();
	}

	/**
	 * Insert a term and return its term_id, failing the test if `wp_insert_term`
	 * returns a `WP_Error`. Centralises the type-narrowing for the regression
	 * tests below.
	 *
	 * @param string               $name     Term name.
	 * @param string               $taxonomy Taxonomy slug.
	 * @param array<string, mixed> $args     Optional. Extra args for `wp_insert_term`.
	 * @return array{term_id: int, term_taxonomy_id: int}
	 */
	private function insert_term_or_fail( string $name, string $taxonomy, array $args = array() ): array {
		$result = wp_insert_term( $name, $taxonomy, $args );
		if ( is_wp_error( $result ) ) {
			$this->fail( 'wp_insert_term failed: ' . $result->get_error_message() );
		}

		return array(
			'term_id'          => (int) $result['term_id'],
			'term_taxonomy_id' => (int) $result['term_taxonomy_id'],
		);
	}

	/**
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/34213.
	 *
	 * When a `product_cat` term is created in a non-admin context (e.g. REST API
	 * or WP-CLI), the `order` meta key should still be initialised to `0` so the
	 * admin UI's sort order remains consistent with the read/write key.
	 *
	 * @testdox Creating a product_cat term initialises the `order` meta key in any context.
	 */
	public function test_create_term_initialises_order_meta_for_product_cat(): void {
		$term = $this->insert_term_or_fail( 'RSMAPGJ-396 Category', 'product_cat' );

		$order_meta = get_term_meta( $term['term_id'], 'order', true );

		$this->assertNotSame( '', $order_meta, 'The `order` meta key should be initialised on term creation.' );
		$this->assertSame( 0, (int) $order_meta );

		wp_delete_term( $term['term_id'], 'product_cat' );
	}

	/**
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/34213.
	 *
	 * Programmatically inserting a product attribute term (which is what the
	 * REST API and WP-CLI do) must initialise the `order` meta key under the
	 * canonical key, not the legacy `order_{$taxonomy}` key.
	 *
	 * @testdox Creating a product attribute term initialises the `order` meta key, not the legacy key.
	 */
	public function test_create_term_initialises_order_meta_for_attribute_taxonomy(): void {
		$attribute_id = wc_create_attribute(
			array(
				'name'     => 'RSMAPGJ-396 Color',
				'slug'     => 'rsmapgj396color',
				'order_by' => 'menu_order',
			)
		);
		$this->assertIsInt( $attribute_id );

		$taxonomy = wc_attribute_taxonomy_name( 'rsmapgj396color' );
		register_taxonomy( $taxonomy, array( 'product' ) );

		$term = $this->insert_term_or_fail( 'Red', $taxonomy, array( 'slug' => 'rsmapgj-396-red' ) );

		$order_meta        = get_term_meta( $term['term_id'], 'order', true );
		$legacy_order_meta = get_term_meta( $term['term_id'], 'order_' . $taxonomy, true );

		$this->assertSame( 0, (int) $order_meta, 'The canonical `order` meta key should be initialised.' );
		$this->assertSame( '', $legacy_order_meta, 'The legacy `order_{$taxonomy}` meta key should not be written.' );

		wp_delete_term( $term['term_id'], $taxonomy );
		wc_delete_attribute( (int) $attribute_id );
	}

	/**
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/34213.
	 *
	 * If a term still has a stale `order_{$taxonomy}` meta key around from a
	 * legacy WooCommerce version, calling `wc_set_term_order()` must clean it
	 * up so the two keys cannot diverge.
	 *
	 * @testdox wc_set_term_order() removes the legacy `order_{$taxonomy}` meta key.
	 */
	public function test_set_term_order_cleans_up_legacy_meta_key(): void {
		$attribute_id = wc_create_attribute(
			array(
				'name'     => 'RSMAPGJ-396 Size',
				'slug'     => 'rsmapgj396size',
				'order_by' => 'menu_order',
			)
		);
		$this->assertIsInt( $attribute_id );

		$taxonomy = wc_attribute_taxonomy_name( 'rsmapgj396size' );
		register_taxonomy( $taxonomy, array( 'product' ) );

		$term = $this->insert_term_or_fail( 'Large', $taxonomy, array( 'slug' => 'rsmapgj-396-large' ) );

		// Simulate a stale legacy meta key written by an old WooCommerce version.
		update_term_meta( $term['term_id'], 'order_' . $taxonomy, 99 );

		wc_set_term_order( $term['term_id'], 4, $taxonomy );

		$this->assertSame( 4, (int) get_term_meta( $term['term_id'], 'order', true ) );
		$this->assertSame(
			'',
			get_term_meta( $term['term_id'], 'order_' . $taxonomy, true ),
			'The legacy `order_{$taxonomy}` meta key should be removed after updating order.'
		);

		wp_delete_term( $term['term_id'], $taxonomy );
		wc_delete_attribute( (int) $attribute_id );
	}

	/**
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/34213.
	 *
	 * The legacy `WC_Admin_Taxonomies::create_term()` method must continue to
	 * initialise the `order` meta key for back-compat with any third-party code
	 * that calls it directly.
	 *
	 * @testdox WC_Admin_Taxonomies::create_term() still initialises the `order` meta key.
	 */
	public function test_legacy_admin_create_term_still_initialises_order_meta(): void {
		require_once WC_ABSPATH . 'includes/admin/class-wc-admin-taxonomies.php';

		$term = $this->insert_term_or_fail( 'RSMAPGJ-396 Manual Category', 'product_cat' );

		// Wipe the meta the global helper just set, then verify the admin method still sets it.
		delete_term_meta( $term['term_id'], 'order' );

		WC_Admin_Taxonomies::get_instance()->create_term( $term['term_id'], $term['term_taxonomy_id'], 'product_cat' );

		$this->assertSame( 0, (int) get_term_meta( $term['term_id'], 'order', true ) );

		wp_delete_term( $term['term_id'], 'product_cat' );
	}

	/**
	 * Non-product taxonomies (e.g. `category`, `post_tag`) should not have their
	 * `order` meta initialised by the WooCommerce helper.
	 *
	 * @testdox wc_init_term_order_meta() ignores non-product taxonomies.
	 */
	public function test_init_term_order_meta_ignores_non_product_taxonomies(): void {
		$term = $this->insert_term_or_fail( 'RSMAPGJ-396 Generic Tag', 'post_tag' );

		$this->assertSame( '', get_term_meta( $term['term_id'], 'order', true ) );

		wp_delete_term( $term['term_id'], 'post_tag' );
	}
}
