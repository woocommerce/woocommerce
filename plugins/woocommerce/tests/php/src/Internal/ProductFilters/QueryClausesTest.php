<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Tests related to QueryClauses service.
 */
class QueryClausesTest extends AbstractProductFiltersTest {
	/**
	 * The system under test.
	 *
	 * @var QueryClauses
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();
		$this->sut = $container->get( QueryClauses::class );
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
	 * @testdox A private variation is excluded from attribute filtering and republishing it restores the parent product.
	 */
	public function test_attribute_clauses_exclude_private_variations_until_republished(): void {
		$green_variation = $this->get_variation_by_attribute( $this->products[4], 'pa_color', 'green-slug' );
		$green_term      = get_term_by( 'slug', 'green-slug', 'pa_color' );

		$this->assertInstanceOf( \WP_Term::class, $green_term );

		try {
			$green_variation->set_status( 'private' );
			$green_variation->save();
			$this->assert_private_variation_lookup_row_is_retained( $green_variation, $this->products[4], 'pa_color', $green_term->term_id );

			$green_products_while_private = $this->get_product_names_filtered_by_attribute( 'pa_color', array( 'green-slug' ), 'or' );
			$red_products_while_private   = $this->get_product_names_filtered_by_attribute( 'pa_color', array( 'red-slug' ), 'or' );
		} finally {
			$green_variation->set_status( 'publish' );
			$green_variation->save();
		}

		$green_products_after_republishing = $this->get_product_names_filtered_by_attribute( 'pa_color', array( 'green-slug' ), 'or' );

		$this->assertEqualsCanonicalizing(
			array( 'Product 5' ),
			$red_products_while_private,
			'Product 5 should still match its published red variation while its green variation is private.'
		);
		$this->assertEqualsCanonicalizing(
			array( 'Product 6' ),
			$green_products_while_private,
			'Product 5 should not match green while its green variation is private.'
		);
		$this->assertEqualsCanonicalizing(
			array( 'Product 5', 'Product 6' ),
			$green_products_after_republishing,
			'Republishing Product 5\'s green variation should restore the green match.'
		);
	}

	/**
	 * Get product names filtered by an attribute.
	 *
	 * @param string   $taxonomy   Attribute taxonomy name.
	 * @param string[] $terms      Attribute term slugs.
	 * @param string   $query_type Query type.
	 * @return string[]
	 */
	private function get_product_names_filtered_by_attribute( string $taxonomy, array $terms, string $query_type ): array {
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

		try {
			return $this->get_data_from_products_array( wc_get_products( array() ) );
		} finally {
			remove_filter( 'posts_clauses', $filter_callback );
		}
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
}
