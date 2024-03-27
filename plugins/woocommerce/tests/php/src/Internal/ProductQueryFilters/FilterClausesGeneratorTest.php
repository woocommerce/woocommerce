<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductQueryFilters;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClausesGenerator;

/**
 * Tests related to FilterClausesGenerator service.
 */
class FilterClausesGeneratorTest extends AbstractProductQueryFiltersTest {
	/**
	 * The system under test.
	 *
	 * @var DataRegenerator
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();
		$this->sut = $container->get( FilterClausesGenerator::class );
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
		$price_range     = wp_parse_args(
			$price_range,
			array(
				'min_price' => 0,
				'min_price' => 0,
			)
		);
		$filter_callback = function( $args ) use ( $price_range ) {
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
				function( \WC_Product $product ) use ( $price_range ) {
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
		$filter_callback = function( $args ) use ( $stock_statuses ) {
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
				function( \WC_Product $product ) use ( $stock_statuses ) {
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
	 * Skipping tests with query_type `and` because there is an issue with
	 * Filterer::filter_by_attribute_post_clauses that generate wrong clauses
	 * for `and`. We can fix the same issue in
	 * FilterClausesGenerator::add_attribute_clauses but doing so will make the
	 * attribute counts data doesnt match with current query. A fix for both
	 * methods is pending.
	 *
	 * ["pa_color",["red-slug","green-slug"],"and"]
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
		$filter_callback   = function( $args ) use ( $chosen_attributes ) {
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
				function( \WC_Product $product ) use ( $chosen_attributes ) {
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
}
