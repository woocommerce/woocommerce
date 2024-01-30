<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClausesGenerator;

/**
 * Tests related to FilterClausesGenerator service.
 */
class FilterClausesGeneratorTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var DataRegenerator
	 */
	private $sut;

	/**
	 * The test products data
	 *
	 * @var Array
	 */
	private $test_products_data = array(
		array(
			'name' => 'Product 1',
			'regular_price' => 20,
			'stock_status' => 'instock',
		),
		array(
			'name' => 'Product 2',
			'regular_price' => 30,
			'stock_status' => 'instock',
		),
		array(
			'name' => 'Product 3',
			'regular_price' => 40,
			'stock_status' => 'outofstock',
		),
		array(
			'name' => 'Product 4',
			'regular_price' => 50,
			'stock_status' => 'onbackorder',
		),
	);

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$container = wc_get_container();
		$this->sut = $container->get( FilterClausesGenerator::class );

		foreach ( $this->test_products_data as $product_data ) {
			$product = new \WC_Product_Simple();
			$product->set_name($product_data['name']);
			$product->set_regular_price($product_data['regular_price']);
			$product->set_stock_status($product_data['stock_status']);
			$product->save();
		}
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Unregister all product attributes.

		$attribute_ids_by_name = wc_get_attribute_taxonomy_ids();
		foreach ( $attribute_ids_by_name as $attribute_name => $attribute_id ) {
			$attribute_name = wc_sanitize_taxonomy_name( $attribute_name );
			$taxonomy_name  = wc_attribute_taxonomy_name( $attribute_name );
			unregister_taxonomy( $taxonomy_name );

			wc_delete_attribute( $attribute_id );
		}

		// Remove all products.

		$product_ids = wc_get_products( array( 'return' => 'ids' ) );
		foreach ( $product_ids as $product_id ) {
			$product     = wc_get_product( $product_id );
			$is_variable = $product->is_type( 'variable' );

			foreach ( $product->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				if ( empty( $child ) ) {
					continue;
				}

				if ( $is_variable ) {
					$child->delete( true );
				} else {
					$child->set_parent_id( 0 );
					$child->save();
				}
			}

			$product->delete( true );
		}
	}

	public function test_price_clauses() {
		for( $x = 0; $x <= 10; $x++ ) {
			$min = rand( 10, 60 );
			$max = rand( 50, 100 );
			$this->test_price_clauses_with( array( 'min_price' => $min ) );
			$this->test_price_clauses_with( array( 'max_price' => $max ) );
			$this->test_price_clauses_with( array( 'min_price' => $min, 'max_price' => $max ) );
		}
	}

	public function test_stock_clauses() {
		$this->test_stock_clauses_with( array( 'instock' ) );
		$this->test_stock_clauses_with( array( 'onbackorder' ) );
		$this->test_stock_clauses_with( array( 'outofstock' ) );
		$this->test_stock_clauses_with( array( 'instock', 'onbackorder' ) );
		$this->test_stock_clauses_with( array( 'outofstock', 'onbackorder' ) );
	}

	private function test_price_clauses_with( $price_range ) {
		$price_range = wp_parse_args(
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
		$received_products_name = $this->get_data_from_query_results(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_expected_products_data(
			function( $product ) use ( $price_range ) {
				if ( $price_range['min_price'] && $price_range['max_price'] ) {
					return $product['regular_price'] >= $price_range['min_price'] &&
						$product['regular_price'] <= $price_range['max_price'];
				}
				if ( $price_range['max_price'] ) {
					return $product['regular_price'] <= $price_range['max_price'];
				}
				if ( $price_range['min_price'] ) {
					return $product['regular_price'] >= $price_range['min_price'];
				}
			},
			'name'
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	private function test_stock_clauses_with( $stock_statuses ) {
		$filter_callback = function( $args ) use ( $stock_statuses ) {
			return $this->sut->add_stock_clauses( $args, $stock_statuses );
		};

		add_filter( 'posts_clauses', $filter_callback );
		$received_products_name = $this->get_data_from_query_results(
			wc_get_products( array() )
		);
		remove_filter( 'posts_clauses', $filter_callback );

		$expected_products_name = $this->get_expected_products_data(
			function( $product ) use ( $stock_statuses ) {
				return in_array( $product['stock_status'], $stock_statuses, true );
			}
		);

		$this->assertEqualsCanonicalizing( $expected_products_name, $received_products_name );
	}

	private function get_data_from_query_results( $products, $callback = null ) {
		if ( ! $callback ) {
			$callback = function( $product ) {
				return $product->get_name();
			};
		}

		return array_map(
			$callback,
			$products
		);

	}

	private function get_expected_products_data( $callback, $field = 'name' ) {
		$expected_products = array_filter(
			$this->test_products_data,
			$callback
		);

		if ( ! $field ) {
			return $expected_products;
		}

		return array_column( $expected_products, $field );
	}
}
