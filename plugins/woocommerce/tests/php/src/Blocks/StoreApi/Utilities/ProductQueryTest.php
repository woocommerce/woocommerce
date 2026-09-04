<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\ProductQuery;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Unit tests for the ProductQuery class.
 */
class ProductQueryTest extends \WC_Unit_Test_Case {

	/**
	 * @var ProductQuery
	 */
	private ProductQuery $product_query;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product_query = new ProductQuery();
		wp_cache_delete( 'last_modified', 'wc_products' );
	}

	/**
	 * @testdox get_last_modified returns null when no products exist.
	 */
	public function test_get_last_modified_returns_null_when_no_products(): void {
		global $wpdb;

		// Temporarily remove all product posts to test the null case.
		$original_posts = $wpdb->get_results(
			"SELECT ID, post_type, post_status FROM {$wpdb->posts} WHERE post_type IN ('product', 'product_variation')"
		);
		$wpdb->query(
			"UPDATE {$wpdb->posts} SET post_type = '_tmp_hidden' WHERE post_type IN ('product', 'product_variation')"
		);

		$result = $this->product_query->get_last_modified();

		// Restore original posts.
		$wpdb->query(
			"UPDATE {$wpdb->posts} SET post_type = REPLACE(post_type, '_tmp_hidden', '') WHERE post_type = '_tmp_hidden'"
		);

		// Restore correct post types from the saved data.
		foreach ( $original_posts as $post ) {
			$wpdb->update( $wpdb->posts, array( 'post_type' => $post->post_type ), array( 'ID' => $post->ID ) );
		}

		$this->assertNull( $result );
	}

	/**
	 * @testdox get_last_modified returns an HTTP-date formatted string.
	 */
	public function test_get_last_modified_returns_http_date_format(): void {
		$fixtures = new FixtureData();
		$fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		$result = $this->product_query->get_last_modified();

		$this->assertNotNull( $result );
		$this->assertStringEndsWith( 'GMT', $result );
		// Verify it parses as a valid date.
		$this->assertNotFalse( strtotime( $result ) );
	}

	/**
	 * @testdox get_last_modified caches the result in the object cache.
	 */
	public function test_get_last_modified_caches_result(): void {
		$fixtures = new FixtureData();
		$fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// First call seeds the cache.
		$result = $this->product_query->get_last_modified();

		// Verify cache is populated.
		$cached = wp_cache_get( 'last_modified', 'wc_products' );
		$this->assertNotFalse( $cached );
		$this->assertSame( $result, $cached );
	}

	/**
	 * @testdox get_last_modified returns cached value without querying the database.
	 */
	public function test_get_last_modified_uses_cached_value(): void {
		$sentinel = 'Thu, 01 Jan 2099 00:00:00 GMT';
		wp_cache_set( 'last_modified', $sentinel, 'wc_products' );

		$result = $this->product_query->get_last_modified();

		$this->assertSame( $sentinel, $result );
	}

	/**
	 * @testdox Cache is invalidated when a product post cache is cleaned.
	 */
	public function test_cache_invalidated_on_product_change(): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Seed the cache.
		$this->product_query->get_last_modified();
		$this->assertNotFalse( wp_cache_get( 'last_modified', 'wc_products' ) );

		// Simulate product change — clean_post_cache fires WC_Post_Data::invalidate_products_last_modified.
		clean_post_cache( $product->get_id() );

		$this->assertFalse( wp_cache_get( 'last_modified', 'wc_products' ) );
	}

	/**
	 * @testdox Cache is invalidated when a product variation post cache is cleaned.
	 */
	public function test_cache_invalidated_on_variation_change(): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Create a variation post directly to avoid complex variable product setup.
		$variation_id = wp_insert_post(
			array(
				'post_type'   => 'product_variation',
				'post_parent' => $product->get_id(),
				'post_status' => 'publish',
			)
		);

		// Seed the cache.
		$this->product_query->get_last_modified();
		$this->assertNotFalse( wp_cache_get( 'last_modified', 'wc_products' ) );

		// Clean variation post cache.
		clean_post_cache( $variation_id );

		$this->assertFalse( wp_cache_get( 'last_modified', 'wc_products' ) );
	}

	/**
	 * @testdox Cache is NOT invalidated when a non-product post cache is cleaned.
	 */
	public function test_cache_not_invalidated_on_non_product_change(): void {
		$fixtures = new FixtureData();
		$fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Seed the cache.
		$this->product_query->get_last_modified();
		$cached_value = wp_cache_get( 'last_modified', 'wc_products' );
		$this->assertNotFalse( $cached_value );

		// Create and clean a regular post.
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Regular Post',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		clean_post_cache( $post_id );

		$this->assertSame( $cached_value, wp_cache_get( 'last_modified', 'wc_products' ) );
	}

	/**
	 * @testdox get_last_modified re-seeds cache from DB after invalidation.
	 */
	public function test_get_last_modified_reseeds_after_invalidation(): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Seed the cache.
		$first_result = $this->product_query->get_last_modified();

		// Invalidate.
		clean_post_cache( $product->get_id() );

		// Next call should re-query DB and re-seed.
		$second_result = $this->product_query->get_last_modified();

		$this->assertNotNull( $second_result );
		$this->assertNotFalse( wp_cache_get( 'last_modified', 'wc_products' ) );
	}

	/**
	 * @testdox Slug filters are safely prepared when NO_BACKSLASH_ESCAPES is enabled.
	 */
	public function test_slug_filter_is_prepared_with_no_backslash_escapes(): void {
		global $wpdb;

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name' => 'Slug filter product',
				'slug' => 'slug-filter-product',
			)
		);
		$sql_mode = $wpdb->get_var( 'SELECT @@SESSION.sql_mode' );

		try {
			$wpdb->query( "SET SESSION sql_mode = 'NO_BACKSLASH_ESCAPES'" );
			$this->assertSame( 'NO_BACKSLASH_ESCAPES', $wpdb->get_var( 'SELECT @@SESSION.sql_mode' ), 'The test requires NO_BACKSLASH_ESCAPES.' );
			$matching_ids = $this->get_product_ids_for_slug_filter( $product->get_slug() . '") AND ("1"="1' );
		} finally {
			$wpdb->query( $wpdb->prepare( 'SET SESSION sql_mode = %s', $sql_mode ) );
		}

		$this->assertNotContains( $product->get_id(), $matching_ids, 'The slug value must not alter the query predicate.' );
	}

	/**
	 * @testdox Slug filters support single and comma-separated values when NO_BACKSLASH_ESCAPES is enabled.
	 */
	public function test_slug_filter_supports_valid_values_with_no_backslash_escapes(): void {
		global $wpdb;

		$fixtures = new FixtureData();
		$product1 = $fixtures->get_simple_product(
			array(
				'name' => 'First slug filter product',
				'slug' => 'first-slug-filter-product',
			)
		);
		$product2 = $fixtures->get_simple_product(
			array(
				'name' => 'Second slug filter product',
				'slug' => 'second-slug-filter-product',
			)
		);
		$sql_mode = $wpdb->get_var( 'SELECT @@SESSION.sql_mode' );

		try {
			$wpdb->query( "SET SESSION sql_mode = 'NO_BACKSLASH_ESCAPES'" );
			$single_slug_ids   = $this->get_product_ids_for_slug_filter( $product1->get_slug() );
			$multiple_slug_ids = $this->get_product_ids_for_slug_filter( $product1->get_slug() . ',' . $product2->get_slug() );
		} finally {
			$wpdb->query( $wpdb->prepare( 'SET SESSION sql_mode = %s', $sql_mode ) );
		}

		$this->assertSame( array( $product1->get_id() ), $single_slug_ids, 'A single slug should return the matching product.' );
		$this->assertEqualsCanonicalizing(
			array( $product1->get_id(), $product2->get_id() ),
			$multiple_slug_ids,
			'Comma-separated slugs should return all matching products.'
		);
	}

	/**
	 * Get product IDs matched by a Store API slug filter.
	 *
	 * @param string $slug_filter Slug filter value.
	 * @return int[]
	 */
	private function get_product_ids_for_slug_filter( string $slug_filter ): array {
		global $wpdb;

		$wp_query = new \WP_Query();
		$wp_query->set( 'slug', $slug_filter );
		$clauses = $this->product_query->add_query_clauses(
			array(
				'join'  => '',
				'where' => '',
			),
			$wp_query
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- The slug query clause is prepared by ProductQuery.
		return array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product'{$clauses['where']}" ) );
	}
}
