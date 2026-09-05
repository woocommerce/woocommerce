<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Admin\API\Reports\Stock\Controller as StockController;
use Automattic\WooCommerce\Caches\ProductCountCache;
use Automattic\WooCommerce\Internal\Utilities\ProductUtil;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for the internal ProductUtil class.
 */
class ProductUtilTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox `get_counts_for_type` returns per-status counts for the given post type.
	 */
	public function test_get_counts_for_type_returns_per_status_counts(): void {
		$before = wc_get_container()->get( ProductUtil::class )->get_counts_for_type( 'product' );

		$published = \WC_Helper_Product::create_simple_product();
		$draft     = \WC_Helper_Product::create_simple_product( true, array( 'status' => ProductStatus::DRAFT ) );
		$pending   = \WC_Helper_Product::create_simple_product( true, array( 'status' => ProductStatus::PENDING ) );

		$after = wc_get_container()->get( ProductUtil::class )->get_counts_for_type( 'product' );

		$this->assertSame( ( $before[ ProductStatus::PUBLISH ] ?? 0 ) + 1, $after[ ProductStatus::PUBLISH ] );
		$this->assertSame( ( $before[ ProductStatus::DRAFT ] ?? 0 ) + 1, $after[ ProductStatus::DRAFT ] );
		$this->assertSame( ( $before[ ProductStatus::PENDING ] ?? 0 ) + 1, $after[ ProductStatus::PENDING ] );

		$published->delete( true );
		$draft->delete( true );
		$pending->delete( true );
	}

	/**
	 * Data provider for injected status types that PHP may coerce when used as array keys.
	 *
	 * @return array
	 */
	public function provider_injected_status_types(): array {
		return array(
			'integer status (42)'   => array( 42, 7 ),
			'string status (42)'    => array( '42', 7 ),
			'string status (valid)' => array( 'custom-status', 25 ),
		);
	}

	/**
	 * @testdox get_counts_for_type normalizes injected status keys to strings and populates cache with integer values.
	 * @dataProvider provider_injected_status_types
	 *
	 * @param mixed $injected_status The status key to inject via wp_count_posts filter.
	 * @param int   $injected_count  The count value for the injected status.
	 */
	public function test_get_counts_for_type_normalizes_and_caches_injected_status( $injected_status, int $injected_count ): void {
		$cache = wc_get_container()->get( ProductCountCache::class );
		$cache->flush( 'product' );

		$filter = function ( $counts ) use ( $injected_status, $injected_count ) {
			$counts->{$injected_status} = $injected_count;
			return $counts;
		};
		add_filter( 'wp_count_posts', $filter, 10, 1 );
		wp_cache_delete( 'posts-product', 'counts' );

		try {
			$result = wc_get_container()->get( ProductUtil::class )->get_counts_for_type( 'product' );
		} finally {
			remove_filter( 'wp_count_posts', $filter, 10 );
		}

		// Second call without the filter — if the injected status is present, it came from cache.
		$cached = wc_get_container()->get( ProductUtil::class )->get_counts_for_type( 'product' );

		$this->assertSame( $result, $cached );
		$this->assertSame( $injected_count, $cached[ $injected_status ] );
		$this->assertSame( $injected_count, $cached[ (string) $injected_status ] );
	}

	/**
	 * @testdox delete_product_transients_for_products deletes fixed-name transients once and fires hooks once per product.
	 */
	public function test_delete_product_transients_for_products_deletes_fixed_transients_and_fires_hooks() {
		$product_ids = array( 0, 123, 123, 456 );
		$deleted_ids = array();
		$track_hook  = static function ( $product_id ) use ( &$deleted_ids ) {
			$deleted_ids[] = (int) $product_id;
		};

		set_transient( 'wc_products_onsale', 'before' );
		set_transient( 'product-transient-version', 'before' );
		set_transient( 'product_query-transient-version', 'before' );
		add_action( 'woocommerce_delete_product_transients', $track_hook );
		try {
			wc_get_container()->get( ProductUtil::class )->delete_product_transients_for_products( $product_ids );
		} finally {
			remove_action( 'woocommerce_delete_product_transients', $track_hook );
		}

		$this->assertSame( array( 0, 123, 456 ), $deleted_ids );
		$this->assertNotSame( 'before', get_transient( 'wc_products_onsale' ) );
		$this->assertNotSame( 'before', get_transient( 'product-transient-version' ) );
		$this->assertNotSame( 'before', get_transient( 'product_query-transient-version' ) );
	}

	/**
	 * @testdox delete_product_specific_transients deletes the transients for a product that is not a variation.
	 *
	 * @param bool $use_id True to pass the product id to delete_product_specific_transients, false to pass the product object.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_delete_product_specific_transients_deletes_transients_for_simple_product( bool $use_id ) {
		$product        = ProductHelper::create_simple_product();
		$transient_name = 'wc_related_' . $product->get_id();
		set_transient( $transient_name, 'foobar' );

		wc_get_container()->get( ProductUtil::class )->delete_product_specific_transients( $use_id ? $product->get_id() : $product );

		$this->assertFalse( get_transient( $transient_name ) );
	}

	/**
	 * delete_product_specific_transients deletes the transients for a variation product and also for its parent.
	 *
	 * @param bool $use_id True to pass the product id to delete_product_specific_transients, false to pass the product object.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_delete_product_specific_transients_deletes_transients_for_variation_and_parent( bool $use_id ) {
		$parent_product = ProductHelper::create_variation_product();
		$child_id       = $parent_product->get_children()[0];
		$child          = wc_get_product( $child_id );

		$parent_transient_name = 'wc_related_' . $parent_product->get_id();
		$child_transient_name  = 'wc_related_' . $child_id;

		set_transient( $parent_transient_name, 'foobar' );
		set_transient( $child_transient_name, 'foobar' );

		wc_get_container()->get( ProductUtil::class )->delete_product_specific_transients( $use_id ? $child_id : $child );

		$this->assertFalse( get_transient( $parent_transient_name ) );
		$this->assertFalse( get_transient( $child_transient_name ) );
	}

	/**
	 * @testdox delete_product_specific_transients_for_products deletes parent variation transients once for multiple variations.
	 */
	public function test_delete_product_specific_transients_for_products_coalesces_parent_variation_transient_deletes() {
		$parent_product  = ProductHelper::create_variation_product();
		$child_ids       = array_slice( $parent_product->get_children(), 0, 2 );
		$delete_attempts = 0;
		$track_deletes   = static function () use ( &$delete_attempts ) {
			++$delete_attempts;
		};

		add_action( 'delete_transient_wc_product_children_' . $parent_product->get_id(), $track_deletes );
		try {
			wc_get_container()->get( ProductUtil::class )->delete_product_specific_transients_for_products( $child_ids );
		} finally {
			remove_action( 'delete_transient_wc_product_children_' . $parent_product->get_id(), $track_deletes );
		}

		$this->assertSame( 1, $delete_attempts );
	}

	/**
	 * Join clauses that do not contain the wc_product_meta_lookup alias.
	 *
	 * %1$s is the prefixed lookup table name and %2$s is the posts table name.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_joins_without_the_lookup_alias(): array {
		return array(
			'empty clause'                     => array( '' ),
			'unrelated table join'             => array( ' LEFT JOIN unrelated_table unrelated ON %2$s.ID = unrelated.post_id ' ),
			'lookup table under another alias' => array( ' LEFT JOIN %1$s extension_lookup ON %2$s.ID = extension_lookup.product_id ' ),
		);
	}

	/**
	 * @testdox append_product_sorting_table_join appends the aliased lookup join when the clause does not contain the alias.
	 * @dataProvider provider_joins_without_the_lookup_alias
	 *
	 * @param string $join_template Join clause template.
	 */
	public function test_append_product_sorting_table_join_appends_when_the_alias_is_missing( string $join_template ): void {
		global $wpdb;
		$join = sprintf( $join_template, $wpdb->wc_product_meta_lookup, $wpdb->posts );

		$result = wc_get_container()->get( ProductUtil::class )->append_product_sorting_table_join( $join );

		if ( '' !== $join ) {
			$this->assertStringStartsWith( $join, $result, 'Existing joins should be preserved.' );
		}
		$this->assertStringContainsString(
			"LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON {$wpdb->posts}.ID = wc_product_meta_lookup.product_id",
			$result,
			'The wc_product_meta_lookup alias should be joined whenever the clause does not already define it, even when other joins to the table exist.'
		);
	}

	/**
	 * @testdox Non-string query clauses are normalized before appending the lookup join.
	 * @testWith [null, ""]
	 *           [false, ""]
	 *           [0, "0"]
	 *           [[], ""]
	 *
	 * @param mixed  $join   Filtered join clause.
	 * @param string $prefix Expected preserved clause.
	 */
	public function test_append_product_sorting_table_join_normalizes_non_strings( $join, string $prefix ): void {
		global $wpdb;

		$result = wc_get_container()->get( ProductUtil::class )->append_product_sorting_table_join( $join );

		$this->assertSame(
			$prefix . " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ",
			$result,
			'Non-string input must not cause a fatal or lose a convertible clause.'
		);
	}

	/**
	 * @testdox A stringable query clause retains its join without duplicating the lookup alias.
	 */
	public function test_append_product_sorting_table_join_preserves_stringable_clause(): void {
		$join = new class() {
			/**
			 * Return an existing lookup join.
			 *
			 * @return string
			 */
			public function __toString(): string {
				return ' LEFT JOIN lookup_table wc_product_meta_lookup ON posts.ID = wc_product_meta_lookup.product_id ';
			}
		};

		$result = wc_get_container()->get( ProductUtil::class )->append_product_sorting_table_join( $join );

		$this->assertSame( (string) $join, $result, 'Preserve the original join and do not append a duplicate alias.' );
	}

	/**
	 * @testdox Product queries still return products when an earlier filter supplies a null join.
	 * @testWith ["catalog_sort"]
	 *           ["stock_join"]
	 *           ["stock_sort"]
	 *
	 * @param string $path Query path to exercise.
	 */
	public function test_lookup_join_consumers_tolerate_null_from_filters( string $path ): void {
		global $wpdb;

		$product = \WC_Helper_Product::create_simple_product();

		if ( 'stock_join' === $path ) {
			add_filter( 'posts_join', '__return_null', 5 );
			add_filter( 'posts_join', array( StockController::class, 'add_wp_query_join' ), 10, 2 );
		} else {
			add_filter(
				'posts_clauses',
				static function ( $clauses ) {
					$clauses['join'] = null;
					return $clauses;
				},
				5
			);
			$callback = 'catalog_sort' === $path
				? array( WC()->query, 'order_by_price_asc_post_clauses' )
				: array( StockController::class, 'add_wp_query_orderby' );
			add_filter( 'posts_clauses', $callback, 10, 2 );
		}

		$query = new \WP_Query(
			array(
				'post_type'    => 'product',
				'post__in'     => array( $product->get_id() ),
				'fields'       => 'ids',
				'orderby'      => 'stock_quantity',
				'stock_status' => ProductStockStatus::IN_STOCK,
			)
		);

		$this->assertSame( '', $wpdb->last_error, 'The lookup join must produce valid SQL.' );
		$this->assertSame( array( $product->get_id() ), $query->posts, 'The product must remain visible after a null join from an earlier filter.' );
	}

	/**
	 * Join clauses that already define the wc_product_meta_lookup alias.
	 *
	 * %1$s is the prefixed lookup table name and %2$s is the posts table name.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_joins_with_the_lookup_alias(): array {
		return array(
			'implicit alias'   => array( ' LEFT JOIN %1$s wc_product_meta_lookup ON %2$s.ID = wc_product_meta_lookup.product_id ' ),
			'AS keyword'       => array( ' LEFT JOIN %1$s AS wc_product_meta_lookup ON %2$s.ID = wc_product_meta_lookup.product_id ' ),
			'backticked alias' => array( ' LEFT JOIN %1$s `wc_product_meta_lookup` ON %2$s.ID = `wc_product_meta_lookup`.product_id ' ),
		);
	}

	/**
	 * @testdox append_product_sorting_table_join leaves the clause alone when it already defines the alias.
	 * @dataProvider provider_joins_with_the_lookup_alias
	 *
	 * @param string $join_template Join clause template.
	 */
	public function test_append_product_sorting_table_join_is_a_noop_when_the_alias_exists( string $join_template ): void {
		global $wpdb;
		$join = sprintf( $join_template, $wpdb->wc_product_meta_lookup, $wpdb->posts );

		$result = wc_get_container()->get( ProductUtil::class )->append_product_sorting_table_join( $join );

		$this->assertSame( $join, $result, 'A clause that already defines the wc_product_meta_lookup alias must be left alone; a second definition is a duplicate-alias SQL error.' );
	}
}
