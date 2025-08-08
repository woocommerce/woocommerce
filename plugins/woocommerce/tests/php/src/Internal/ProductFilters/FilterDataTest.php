<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;
use Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData;

/**
 * Tests related to Counts service.
 */
class FilterDataTest extends AbstractProductFiltersTest {
	/**
	 * The system under test.
	 *
	 * @var DataRegenerator
	 */
	private $sut;

	/**
	 * TaxonomyHierarchyData instance for clearing the cache.
	 *
	 * @var TaxonomyHierarchyData
	 */
	private $taxonomy_hierarchy_data;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();

		$this->sut                     = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) );
		$this->taxonomy_hierarchy_data = $container->get( TaxonomyHierarchyData::class );

		$this->fixture_data->add_product_review( $this->products[0]->get_id(), 5 );
		$this->fixture_data->add_product_review( $this->products[1]->get_id(), 3 );
		$this->fixture_data->add_product_review( $this->products[3]->get_id(), 5 );
	}

	/**
	 * @testdox Test price range without filter.
	 */
	public function test_get_filtered_price_with_default_query() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$this->test_get_filtered_price_with( $wp_query );
	}

	/**
	 * @testdox Test price range with stock filter set to instock.
	 */
	public function test_get_filtered_price_with_stock_filter() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$wp_query->set( 'filter_stock_status', 'instock' );
		$this->test_get_filtered_price_with(
			$wp_query,
			function ( $product_data ) {
				return 'instock' === $product_data['stock_status'];
			}
		);
	}

	/**
	 * @testdox Test price range with stock filter set to multiple options.
	 */
	public function test_get_filtered_price_with_stock_filter_multiple() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'filter_stock_status', 'outofstock,onbackorder' );
		$this->test_get_filtered_price_with(
			$wp_query,
			function ( $product_data ) {
				return 'outofstock' === $product_data['stock_status'] ||
				'onbackorder' === $product_data['stock_status'];
			}
		);
	}

	/**
	 * @testdox Test stock counts without filter.
	 */
	public function test_get_stock_status_counts_with_default_query() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );

		$this->test_get_stock_status_counts_with( $wp_query );
	}

	/**
	 * @testdox Test stock counts with min price.
	 */
	public function test_get_stock_status_counts_with_min_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'min_price', 20 );
		$this->test_get_stock_status_counts_with(
			$wp_query,
			function ( $product_data ) {
				if ( ! isset( $product_data['variations'] ) ) {
					return $product_data['regular_price'] >= 20;
				}

				foreach ( $product_data['variations'] as $variation_data ) {
					if ( $variation_data['props']['regular_price'] < 20 ) {
						return false;
					}
				}
				return true;
			}
		);
	}

	/**
	 * @testdox Test rating counts without filter.
	 */
	public function test_get_rating_counts_with_default_query() {
		$wp_query   = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_rating_counts   = $this->sut->get_rating_counts( $query_vars );
		$expected_rating_counts = array(
			3 => 1,
			5 => 2,
		);

		$this->assertEqualsCanonicalizing(
			$expected_rating_counts,
			$actual_rating_counts
		);
	}

	/**
	 * @testdox Test rating counts with min price.
	 */
	public function test_get_rating_counts_with_min_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'min_price', 20 );
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_rating_counts   = $this->sut->get_rating_counts( $query_vars );
		$expected_rating_counts = array(
			3 => 1,
			5 => 1,
		);

		$this->assertEqualsCanonicalizing(
			$expected_rating_counts,
			$actual_rating_counts
		);
	}

	/**
	 * @testdox Test attribute count without filter.
	 */
	public function test_get_attribute_counts_with_default_query() {
		$wp_query                  = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts( 'pa_color' );

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test attribute count with max price.
	 */
	public function test_get_attribute_counts_with_max_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'max_price', 55 );

		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts(
			'pa_color',
			function ( $product_data ) {
				if ( isset( $product_data['regular_price'] ) && $product_data['regular_price'] <= 55 ) {
					return true;
				}

				if ( isset( $product_data['variations'] ) ) {
					foreach ( $product_data['variations'] as $variation_data ) {
						if ( isset( $variation_data['props']['regular_price'] ) && $variation_data['props']['regular_price'] <= 55 ) {
							return true;
						}
					}
				}

				return false;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test attribute count with query_type set to `and`.
	 * @todo Remove this test once the issue with `and` query type is fixed in https://github.com/woocommerce/woocommerce/pull/44825.
	 */
	public function test_get_attribute_counts_with_query_type_and() {
		$this->markTestSkipped( 'Skipping tests with query_type `and` because there is an issue with Filterer::filter_by_attribute_post_clauses that generate wrong clauses for `and`. We can fix the same issue in FilterClausesGenerator::add_attribute_clauses but doing so will make the attribute counts data doesnt match with current query. A fix for both methods is pending. See https://github.com/woocommerce/woocommerce/pull/44825.' );
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'filter_color', 'blue-slug,green-slug' );
		$wp_query->set( 'query_type_color', 'and' );

		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts(
			'pa_color',
			function ( $product_data ) {
				$has_green = false;
				$has_blue  = false;

				if ( isset( $product_data['variations'] ) ) {
					foreach ( $product_data['variations'] as $variation_data ) {
						if ( empty( $variation_data['attributes']['pa_color'] ) ) {
							return false;
						}

						if ( 'blue' === $variation_data['attributes']['pa_color'] ) {
							$has_blue = true;
						}

						if ( 'green' === $variation_data['attributes']['pa_color'] ) {
							$has_green = true;
						}
					}
				}

				return $has_blue && $has_green;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test attribute count with query_type set to `or`.
	 */
	public function test_get_attribute_counts_with_query_type_or() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'query_type_color', 'or' );

		/**
		 * For query type `or`, the selected attributes are unset from
		 * $query_vars before passed to get_attribute_counts, so we don't set
		 * them here.
		 *
		 * $wp_query->set( 'filter_color', 'blue-slug,green-slug' );
		 */

		$query_vars                = array_filter( $wp_query->query_vars );
		$actual_attribute_counts   = $this->sut->get_attribute_counts( $query_vars, 'pa_color' );
		$expected_attribute_counts = $this->get_expected_attribute_counts(
			'pa_color',
			function ( $product_data ) {
				if ( isset( $product_data['variations'] ) ) {
					foreach ( $product_data['variations'] as $variation_data ) {
						if ( empty( $variation_data['attributes']['pa_color'] ) ) {
							return false;
						}

						if ( 'blue' === $variation_data['attributes']['pa_color'] ||
							'green' === $variation_data['attributes']['pa_color']
						) {
							return true;
						}
					}
				}

				return false;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_attribute_counts, $actual_attribute_counts );
	}

	/**
	 * @testdox Test taxonomy count without filter.
	 */
	public function test_get_taxonomy_counts_with_default_query() {
		$wp_query                 = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars               = array_filter( $wp_query->query_vars );
		$actual_taxonomy_counts   = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );
		$expected_taxonomy_counts = $this->get_expected_category_counts();

		$this->assertEqualsCanonicalizing( $expected_taxonomy_counts, $actual_taxonomy_counts );
	}

	/**
	 * @testdox Test taxonomy count with max price.
	 */
	public function test_get_taxonomy_counts_with_max_price() {
		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'max_price', 35 );

		$query_vars               = array_filter( $wp_query->query_vars );
		$actual_taxonomy_counts   = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );
		$expected_taxonomy_counts = $this->get_expected_category_counts(
			function ( $product_data ) {
				if ( ! isset( $product_data['regular_price'] ) ) {
					return false;
				}

				return $product_data['regular_price'] <= 35;
			}
		);

		$this->assertEqualsCanonicalizing( $expected_taxonomy_counts, $actual_taxonomy_counts );
	}

	/**
	 * @testdox Test taxonomy count with hierarchical categories.
	 *
	 * Note: We create test categories here rather than using the existing ones from setUp()
	 * because calculating expected counts for hierarchical categories would require complex
	 * filtering logic in the callback passed to get_expected_category_counts(). The callback
	 * would need to analyze the $product_data property to handle parent-child relationships.
	 * For these hierarchy-specific tests, we directly create a simple parent-child
	 * structure and assert the expected counts based on our test data.
	 */
	public function test_get_taxonomy_counts_with_hierarchical_categories() {
		// Create parent category.
		$parent_term = wp_insert_term( 'Electronics', 'product_cat' );
		$parent_id   = $parent_term['term_id'];

		// Create child category.
		$child_term = wp_insert_term( 'Phones', 'product_cat', array( 'parent' => $parent_id ) );
		$child_id   = $child_term['term_id'];

		wp_set_object_terms( $this->products[0]->get_id(), array( $parent_id ), 'product_cat' );
		wp_set_object_terms( $this->products[1]->get_id(), array( $child_id ), 'product_cat' );

		$this->taxonomy_hierarchy_data->clear_cache( 'product_cat' );

		$wp_query   = new \WP_Query( array( 'post_type' => 'product' ) );
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_taxonomy_counts = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );

		// Parent category should have count of 2, child category should have count of 1.
		$this->assertSame( 2, $actual_taxonomy_counts[ $parent_id ] );
		$this->assertSame( 1, $actual_taxonomy_counts[ $child_id ] );

		wp_delete_term( $child_id, 'product_cat' );
		wp_delete_term( $parent_id, 'product_cat' );
	}

	/**
	 * @testdox Test taxonomy count with hierarchical categories and max price.
	 *
	 * Note: We create test categories here rather than using the existing ones from setUp()
	 * because calculating expected counts for hierarchical categories would require complex
	 * filtering logic in the callback passed to get_expected_category_counts(). The callback
	 * would need to analyze the $product_data property to handle parent-child relationships.
	 * For these hierarchy-specific tests, we directly create a simple parent-child
	 * structure and assert the expected counts based on our test data.
	 */
	public function test_get_taxonomy_counts_with_hierarchical_categories_with_max_price() {
		// Create parent category.
		$parent_term = wp_insert_term( 'Electronics', 'product_cat' );
		$parent_id   = $parent_term['term_id'];

		// Create child category.
		$child_term = wp_insert_term( 'Phones', 'product_cat', array( 'parent' => $parent_id ) );
		$child_id   = $child_term['term_id'];

		wp_set_object_terms( $this->products[0]->get_id(), array( $parent_id ), 'product_cat' );
		wp_set_object_terms( $this->products[1]->get_id(), array( $child_id ), 'product_cat' );

		$this->taxonomy_hierarchy_data->clear_cache( 'product_cat' );

		$wp_query = new \WP_Query( array( 'post_type' => 'product' ) );
		$wp_query->set( 'max_price', 15 );

		$query_vars = array_filter( $wp_query->query_vars );

		$actual_taxonomy_counts = $this->sut->get_taxonomy_counts( $query_vars, 'product_cat' );

		// Parent category should have count of 1, child category should not be in results.
		$this->assertSame( 1, $actual_taxonomy_counts[ $parent_id ] );
		$this->assertArrayNotHasKey( $child_id, $actual_taxonomy_counts );

		wp_delete_term( $child_id, 'product_cat' );
		wp_delete_term( $parent_id, 'product_cat' );
	}

	/**
	 * Get expected attribute count from product data and map them with actual term IDs.
	 *
	 * @param string   $attribute_name  WP_Query instance.
	 * @param callable $filter_callback Callback passed to filter test products.
	 */
	private function get_expected_attribute_counts( $attribute_name, $filter_callback = null ) {
		$attribute_counts_by_term_id   = array();
		$attribute_counts_by_term_name = array();

		if ( $filter_callback ) {
			$filtered_products_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_products_data = $this->products_data;
		}

		foreach ( $filtered_products_data as $product_data ) {
			if ( empty( $product_data['variations'] ) ) {
				continue;
			}

			foreach ( $product_data['variations'] as $variation_data ) {
				if ( ! isset( $attribute_counts_by_term_name[ $variation_data['attributes'][ $attribute_name ] ] ) ) {
					$attribute_counts_by_term_name[ $variation_data['attributes'][ $attribute_name ] ] = 0;
				}
				$attribute_counts_by_term_name[ $variation_data['attributes'][ $attribute_name ] ] += 1;
			}
		}

		foreach ( get_terms( array( 'taxonomy' => 'pa_color' ) ) as $term ) {
			if ( isset( $attribute_counts_by_term_name[ $term->name ] ) ) {
				$attribute_counts_by_term_id[ $term->term_id ] = $attribute_counts_by_term_name[ $term->name ];
			}
		}

		return $attribute_counts_by_term_id;
	}

	/**
	 * Get expected category count from product data and map them with actual term IDs.
	 *
	 * @param callable $filter_callback Callback passed to filter test products.
	 */
	private function get_expected_category_counts( $filter_callback = null ) {
		$category_counts_by_term_id = array();

		if ( $filter_callback ) {
			$filtered_products_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_products_data = $this->products_data;
		}

		foreach ( $filtered_products_data as $product_data ) {
			if ( empty( $product_data['category_ids'] ) ) {
				continue;
			}

			foreach ( $product_data['category_ids'] as $product_category_id ) {
				if ( ! isset( $category_counts_by_term_id[ $product_category_id ] ) ) {
					$category_counts_by_term_id[ $product_category_id ] = 0;
				}
				$category_counts_by_term_id[ $product_category_id ] += 1;
			}
		}

		return $category_counts_by_term_id;
	}

	/**
	 * Test stock count.
	 *
	 * @param \WP_Query $wp_query        WP_Query instance.
	 * @param callable  $filter_callback Callback passed to filter test products.
	 */
	private function test_get_stock_status_counts_with( $wp_query, $filter_callback = null ) {
		$query_vars = array_filter( $wp_query->query_vars );

		$actual_stock_status_counts = $this->sut->get_stock_status_counts( $query_vars, array( 'instock', 'outofstock', 'onbackorder' ) );

		$expected_stock_status_counts = array(
			'instock'     => 0,
			'outofstock'  => 0,
			'onbackorder' => 0,
		);

		if ( $filter_callback ) {
			$filtered_product_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_product_data = $this->products_data;
		}

		foreach ( $filtered_product_data as $product_data ) {
			$expected_stock_status_counts[ $product_data['stock_status'] ] += 1;
		}

		$this->assertEqualsCanonicalizing( $expected_stock_status_counts, $actual_stock_status_counts );
	}

	/**
	 * Test filter price range.
	 *
	 * @param \WP_Query $wp_query        WP_Query instance.
	 * @param callable  $filter_callback Callback passed to filter test products.
	 */
	private function test_get_filtered_price_with( $wp_query, $filter_callback = null ) {
		$query_vars = array_filter( $wp_query->query_vars );

		$prices = array();

		if ( $filter_callback ) {
			$filtered_product_data = array_filter(
				$this->products_data,
				$filter_callback
			);
		} else {
			$filtered_product_data = $this->products_data;
		}

		foreach ( $filtered_product_data as $product_data ) {
			$prices[] = $product_data['regular_price'] ?? null;

			if ( isset( $product_data['variations'] ) ) {
				foreach ( $product_data['variations'] as $variation_data ) {
					$prices[] = $variation_data['props']['regular_price'] ?? null;
				}
			}
		}

		$prices = array_filter( $prices );
		$prices = array_map( 'intval', $prices );

		$expected_price_range = array(
			'min_price' => min( $prices ),
			'max_price' => max( $prices ),
		);

		$actual_price_range = (array) $this->sut->get_filtered_price( $query_vars );

		$this->assertEqualsCanonicalizing( $expected_price_range, $actual_price_range );
	}
}
