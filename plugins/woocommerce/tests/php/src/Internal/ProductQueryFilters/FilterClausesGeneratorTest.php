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

	public function test_price_clauses() {
		for ( $x = 0; $x <= 10; $x++ ) {
			$min = rand( 0, 50 );
			$max = rand( 40, 100 );
			$this->test_price_clauses_with( array( 'min_price' => $min ) );
			$this->test_price_clauses_with( array( 'max_price' => $max ) );
			$this->test_price_clauses_with(
				array(
					'min_price' => $min,
					'max_price' => $max,
				)
			);
		}
	}

	public function test_stock_clauses() {
		$this->test_stock_clauses_with( array( 'instock' ) );
		$this->test_stock_clauses_with( array( 'onbackorder' ) );
		$this->test_stock_clauses_with( array( 'outofstock' ) );
		$this->test_stock_clauses_with( array( 'instock', 'onbackorder' ) );
		$this->test_stock_clauses_with( array( 'outofstock', 'onbackorder' ) );
	}

	public function test_attribute_clauses() {
		$this->test_attribute_clauses_with(
			array(
				'pa_color' => array(
					'terms'      => array( 'not-exist-slug' ),
					'query_type' => 'or',
				),
			)
		);

		$this->test_attribute_clauses_with(
			array(
				'pa_color' => array(
					'terms'      => array( 'red-slug' ),
					'query_type' => 'or',
				),
			)
		);

		$this->test_attribute_clauses_with(
			array(
				'pa_color' => array(
					'terms'      => array( 'red-slug', 'green-slug' ),
					'query_type' => 'and',
				),
			)
		);

		$this->test_attribute_clauses_with(
			array(
				'pa_color' => array(
					'terms'      => array( 'red-slug', 'green-slug' ),
					'query_type' => 'or',
				),
			)
		);

		$this->test_attribute_clauses_with(
			array(
				'pa_color' => array(
					'terms'      => array( 'red-slug', 'not-exist-slug' ),
					'query_type' => 'or',
				),
			)
		);
	}

	private function test_price_clauses_with( $price_range ) {
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
					if ( $price_range['min_price'] && $price_range['max_price'] ) {
						return $product->get_regular_price() >= $price_range['min_price'] &&
							$product->get_regular_price() <= $price_range['max_price'];
					}
					if ( $price_range['max_price'] ) {
						return $product->get_regular_price() <= $price_range['max_price'];
					}
					if ( $price_range['min_price'] ) {
						return $product->get_regular_price() >= $price_range['min_price'];
					}
				}
			)
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	private function test_stock_clauses_with( $stock_statuses ) {
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

	private function test_attribute_clauses_with( $chosen_attributes ) {
		$filter_callback = function( $args ) use ( $chosen_attributes ) {
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
							array_keys( $product_attributes )
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
