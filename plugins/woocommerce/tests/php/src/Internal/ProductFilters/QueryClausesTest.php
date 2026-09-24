<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

require_once WC_ABSPATH . '/includes/class-wc-brands.php';

/**
 * Tests related to QueryClauses service.
 */
class QueryClausesTest extends AbstractProductFiltersTest {
	/**
	 * This class only reads its product catalog inside per-test transactions.
	 */
	protected static function uses_class_product_filter_fixtures(): bool {
		return true;
	}

	/**
	 * The system under test.
	 *
	 * @var QueryClauses
	 */
	private $sut;

	/**
	 * Callback added to the woocommerce_product_filter_taxonomy_params filter during a test.
	 *
	 * @var callable|null
	 */
	private $taxonomy_params_filter;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure brands taxonomy is registered for testing.
		\WC_Brands::init_taxonomy();

		$this->sut = wc_get_container()->get( QueryClauses::class );

		// The static map may have been warmed by another test class before product_brand existed.
		$this->clear_params_cache();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->taxonomy_params_filter ) {
				remove_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );
				$this->taxonomy_params_filter = null;
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Test the product query with post clauses containing price clauses.
	 *
	 * @testWith [{"min_price": 20}]
	 *           [{"max_price": 50}]
	 *           [{"min_price": 20,"max_price": 50}]
	 *
	 * @param array $price_range {
	 *     Price range array.
	 *
	 *     @type int|string $min_price Optional. Min price.
	 *     @type int|string $max_price Optional. Max Price.
	 * }
	 */
	public function test_price_clauses_with( $price_range ) {
		$price_range     = array_filter(
			wp_parse_args(
				$price_range,
				array(
					'min_price' => 0,
					'max_price' => 0,
				)
			)
		);
		$filter_callback = function ( $args ) use ( $price_range ) {
			return $this->sut->add_price_clauses( $args, $price_range );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $price_range ) {
					if ( $product->is_type( 'variable' ) ) {
						$product_price = $product->get_variation_price();
					} else {
						$product_price = $product->get_regular_price();
					}
					if ( ! empty( $price_range['min_price'] ) && ! empty( $price_range['max_price'] ) ) {
						return $product_price >= $price_range['min_price'] &&
							$product_price <= $price_range['max_price'];
					}
					if ( ! empty( $price_range['max_price'] ) ) {
						return $product_price <= $price_range['max_price'];
					}
					if ( ! empty( $price_range['min_price'] ) ) {
						return $product_price >= $price_range['min_price'];
					}
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * @testdox Test the product query with post clauses containing stock clauses.
	 *
	 * @testWith [["instock"]]
	 *           [["outofstock"]]
	 *           [["onbackorder"]]
	 *           [["instock","onbackorder"]]
	 *
	 * @param array $stock_statuses Stock statuses to be queried.
	 */
	public function test_stock_clauses_with( $stock_statuses ) {
		$filter_callback = function ( $args ) use ( $stock_statuses ) {
			return $this->sut->add_stock_clauses( $args, $stock_statuses );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $stock_statuses ) {
					return in_array( $product->get_stock_status(), $stock_statuses, true );
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * Test the product query with post clauses containing attribute clauses.
	 *
	 * @testWith ["pa_color",["not-exist-slug"],"or"]
	 *           ["pa_color",["red-slug"],"or"]
	 *           ["pa_color",["red-slug","not-exist-slug"],"or"]
	 *           ["pa_color",["red-slug","green-slug"],"or"]
	 *
	 * @todo Add tests for `and` query type once https://github.com/woocommerce/woocommerce/pull/44825 is merged.
	 *
	 * @param string   $taxonomy   Attribute taxonomy name.
	 * @param string[] $terms      Chosen terms' slug.
	 * @param string   $query_type Query type. Accepts 'and' or 'or'.
	 */
	public function test_attribute_clauses_with( $taxonomy, $terms, $query_type ) {
		$chosen_attributes = array(
			$taxonomy => array(
				'terms'      => $terms,
				'query_type' => $query_type,
			),
		);
		$filter_callback   = function ( $args ) use ( $chosen_attributes ) {
			return $this->sut->add_attribute_clauses( $args, $chosen_attributes );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $chosen_attributes ) {
					$product_attributes = $product->get_attributes();

					foreach ( $chosen_attributes as $taxonomy => $data ) {
						if ( ! in_array(
							$taxonomy,
							array_keys( $product_attributes ),
							true
						) ) {
							return false;
						}

						$slugs = $product_attributes[ $taxonomy ]->get_slugs();

						if ( 'or' === $data['query_type'] && empty( array_intersect( $data['terms'], $slugs ) ) ) {
							return false;
						}

						if ( 'and' === $data['query_type'] && array_diff( $data['terms'], $slugs ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * Test the product query with post clauses containing taxonomy clauses.
	 *
	 * @testWith ["product_cat", ["cat-1"]]
	 *           ["product_cat", ["cat-2"]]
	 *           ["product_cat", ["cat-1", "cat-2"]]
	 *           ["product_tag", ["tag-1"]]
	 *           ["product_tag", ["tag-2", "tag-3"]]
	 *
	 * @param string   $taxonomy Taxonomy name.
	 * @param string[] $terms    Chosen terms' slug.
	 */
	public function test_taxonomy_clauses_with( $taxonomy, $terms ) {
		$chosen_taxonomies = array(
			$taxonomy => $terms,
		);
		$filter_callback   = function ( $args ) use ( $chosen_taxonomies ) {
			return $this->sut->add_taxonomy_clauses( $args, $chosen_taxonomies );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_products_array(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_data_from_products_array(
			array_filter(
				$this->products,
				function ( \WC_Product $product ) use ( $chosen_taxonomies ) {
					foreach ( $chosen_taxonomies as $taxonomy => $terms ) {
						$product_terms = wp_get_post_terms( $product->get_id(), $taxonomy, array( 'fields' => 'slugs' ) );

						if ( is_wp_error( $product_terms ) ) {
							return false;
						}

						// Check if product has any of the requested terms (OR logic).
						if ( empty( array_intersect( $terms, $product_terms ) ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	/**
	 * @testdox Non-string taxonomy filter values are ignored.
	 */
	public function test_non_string_taxonomy_filter_values_are_ignored(): void {
		$query                           = new \WP_Query();
		$query->query_vars['categories'] = array( 'cat-1' );
		$clauses                         = array( 'where' => '' );

		$this->assertSame( $clauses, $this->sut->add_query_clauses( $clauses, $query ) );
	}

	/**
	 * @testdox A hierarchical category filter must match products in descendant categories without a child_of query per chosen term.
	 *
	 * @testWith [["hcat-parent"], ["In Parent", "In Child", "In Grandchild"]]
	 *           [["hcat-child"], ["In Child", "In Grandchild"]]
	 *           [["hcat-grandchild"], ["In Grandchild"]]
	 *           [["hcat-parent", "hcat-sibling"], ["In Parent", "In Child", "In Grandchild", "In Sibling"]]
	 *
	 * @param string[] $terms             Chosen category slugs.
	 * @param string[] $expected_products Product names expected to match.
	 */
	public function test_taxonomy_clauses_expand_descendants_without_per_term_queries( array $terms, array $expected_products ): void {
		$parent     = $this->fixture_data->get_product_category( array( 'name' => 'HCat Parent' ) );
		$child      = $this->fixture_data->get_product_category( array( 'name' => 'HCat Child' ) );
		$grandchild = $this->fixture_data->get_product_category( array( 'name' => 'HCat Grandchild' ) );
		$sibling    = $this->fixture_data->get_product_category( array( 'name' => 'HCat Sibling' ) );
		// parent > child > grandchild; sibling stays top-level (own branch).
		wp_update_term( $child['term_id'], 'product_cat', array( 'parent' => $parent['term_id'] ) );
		wp_update_term( $grandchild['term_id'], 'product_cat', array( 'parent' => $child['term_id'] ) );

		$make_product = function ( $name, $term ) {
			return $this->fixture_data->get_simple_product(
				array(
					'name'         => $name,
					'category_ids' => array( $term['term_id'] ),
				)
			);
		};
		$products     = array(
			$make_product( 'In Parent', $parent ),
			$make_product( 'In Child', $child ),
			$make_product( 'In Grandchild', $grandchild ),
			$make_product( 'In Sibling', $sibling ),
		);

		// Fail if anything still resolves children one term at a time.
		$child_of_queries = 0;
		$counter          = function ( $args ) use ( &$child_of_queries ) {
			if ( ! empty( $args['child_of'] ) ) {
				++$child_of_queries;
			}
			return $args;
		};
		add_filter( 'get_terms_args', $counter );

		$chosen          = array( 'product_cat' => $terms );
		$filter_callback = function ( $args ) use ( $chosen ) {
			return $this->sut->add_taxonomy_clauses( $args, $chosen );
		};
		add_filter( 'posts_clauses', $filter_callback );
		$received = $this->get_data_from_products_array( wc_get_products( array() ) );
		remove_filter( 'posts_clauses', $filter_callback );
		remove_filter( 'get_terms_args', $counter );

		$this->assertEqualsCanonicalizing( $expected_products, $received );
		$this->assertSame( 0, $child_of_queries, 'Hierarchy must be resolved in batch, not via a child_of query per chosen term.' );

		foreach ( $products as $product ) {
			$product->delete( true );
		}
		foreach ( array( $grandchild, $sibling, $child, $parent ) as $term ) {
			wp_delete_term( $term['term_id'], 'product_cat' );
		}
	}

	/**
	 * @testdox Omitting a taxonomy from the filtered map does not disable its main-query filter.
	 */
	public function test_omitted_taxonomy_param_still_filters_main_query(): void {
		$this->taxonomy_params_filter = function ( array $taxonomy_params ): array {
			unset( $taxonomy_params['product_brand'] );
			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		list( $where, $posts ) = $this->query_main_products( $this->cat_and_unknown_brand_query_vars() );

		$this->assertStringContainsString( 'AND 1=0', $where, 'The default brands param must still fail-close an unmatched brand.' );
		$this->assertCount( 0, $posts, 'Omitting the mapping must not disable the brand filter.' );
	}

	/**
	 * @testdox Renaming a taxonomy filter param releases the old param and filters on the new one.
	 */
	public function test_renamed_taxonomy_param_is_used_on_main_query(): void {
		$brand_owner = $this->products[0];
		$brand_slug  = $this->assign_brand_to_product( $brand_owner, 'Acme' );

		$this->taxonomy_params_filter = function ( array $taxonomy_params ): array {
			$taxonomy_params['product_brand'] = 'wc_brands';
			return $taxonomy_params;
		};
		add_filter( 'woocommerce_product_filter_taxonomy_params', $this->taxonomy_params_filter );

		list( $where, $posts ) = $this->query_main_products( array( 'brands' => $brand_slug ) );

		$this->assertStringNotContainsString( 'AND 1=0', $where, 'The released param must no longer reach the taxonomy clauses.' );
		$this->assertEqualsCanonicalizing(
			$this->get_data_from_products_array( $this->products ),
			$this->get_data_from_products_array( array_map( 'wc_get_product', $posts ) ),
			'Once renamed, the old param must be ignored rather than filtering the query.'
		);

		list( $where, $posts ) = $this->query_main_products( array( 'wc_brands' => $brand_slug ) );

		$this->assertStringNotContainsString( 'AND 1=0', $where, 'The renamed param matches a real term, so the query must not fail closed.' );
		$this->assertSame(
			array( $brand_owner->get_name() ),
			$this->get_data_from_products_array( array_map( 'wc_get_product', $posts ) ),
			'The renamed param should filter on product_brand exactly as the original param did.'
		);
	}

	/**
	 * @testdox Without the filter, a taxonomy param with no matching term still fails the main query closed.
	 */
	public function test_taxonomy_param_still_fails_closed_without_filter(): void {
		list( $where, $posts ) = $this->query_main_products( $this->cat_and_unknown_brand_query_vars() );

		$this->assertStringContainsString( 'AND 1=0', $where, 'An unmatched taxonomy filter param should still fail the query closed by default.' );
		$this->assertCount( 0, $posts, 'No products should be returned when the taxonomy filter fails closed.' );
	}

	/**
	 * Query vars pairing a category that exists with a brand slug that does not.
	 *
	 * @return array
	 */
	private function cat_and_unknown_brand_query_vars(): array {
		return array(
			'product_cat' => 'cat-1',
			'brands'      => 'no-such-brand',
		);
	}

	/**
	 * Create a product brand and assign it to a single product.
	 *
	 * @param \WC_Product $product    Product to assign the brand to.
	 * @param string      $brand_name Brand name.
	 * @return string The brand slug.
	 */
	private function assign_brand_to_product( \WC_Product $product, string $brand_name ): string {
		$term = wp_insert_term( $brand_name, 'product_brand' );
		$this->assertIsArray( $term, 'The product brand fixture should be created.' );

		wp_set_object_terms( $product->get_id(), array( (int) $term['term_id'] ), 'product_brand' );

		return get_term( (int) $term['term_id'], 'product_brand' )->slug;
	}

	/**
	 * Run a main product query and capture the resulting WHERE clause alongside the returned posts.
	 *
	 * @param array $query_vars Query vars to add to the product query.
	 * @return array {
	 *     @type string     $0 The final WHERE clause.
	 *     @type \WP_Post[] $1 The posts returned by the query.
	 * }
	 */
	private function query_main_products( array $query_vars ): array {
		$where         = '';
		$capture_where = function ( array $clauses ) use ( &$where ): array {
			$where = $clauses['where'];
			return $clauses;
		};
		add_filter( 'posts_clauses', $capture_where, 20 );

		global $wp_the_query;
		$previous_wp_the_query = $wp_the_query;

		try {
			$query        = new \WP_Query();
			$wp_the_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$posts        = $query->query(
				array_merge(
					array(
						'post_type' => 'product',
						// Stands in for what WC_Query::pre_get_posts() sets on a product archive.
						'wc_query'  => 'product_query',
					),
					$query_vars
				)
			);
		} finally {
			$wp_the_query = $previous_wp_the_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			remove_filter( 'posts_clauses', $capture_where, 20 );
		}

		return array( $where, $posts );
	}
}
