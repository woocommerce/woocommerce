<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductQueryFilters\FilterClausesGenerator;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use WP_Query;

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
	 * FixtureData instance.
	 *
	 * @var FixtureData
	 */
	private $fixture_data;

	/**
	 * Test products.
	 *
	 * @var \WC_Product[]
	 */
	private $products;

	public function test_price_clauses() {
		for ( $x = 0; $x <= 10; $x++ ) {
			$min = rand( 10, 60 );
			$max = rand( 50, 100 );
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

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_attribute_lookup_enabled', 'yes' );

		$container = wc_get_container();
		$this->sut = $container->get( FilterClausesGenerator::class );

		$this->fixture_data = new FixtureData();

		$this->products = array_map(
			array( $this, 'create_test_product' ),
			array(
				array(
					'name'          => 'Product 1',
					'regular_price' => 20,
					'stock_status'  => 'instock',
				),
				array(
					'name'          => 'Product 2',
					'regular_price' => 30,
					'stock_status'  => 'instock',
				),
				array(
					'name'          => 'Product 3',
					'regular_price' => 40,
					'stock_status'  => 'outofstock',
				),
				array(
					'name'          => 'Product 4',
					'regular_price' => 50,
					'stock_status'  => 'onbackorder',
				),
				array(
					'name'       => 'Product 5',
					'variations' => array(
						array(
							'attributes' => array(
								'pa_color' => 'red',
								'pa_size'  => 'small',
							),
							'props'      => array(
								'regular_price' => 50,
								'stock_status'  => 'instock',
							),
						),
						array(
							'attributes' => array(
								'pa_color' => 'green',
								'pa_size'  => 'medium',
							),
							'props'      => array(
								'regular_price' => 50,
								'stock_status'  => 'instock',
							),
						),
					),
				),
			)
		);
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
					$product_attributes =
					$product->get_attributes(); foreach ( $chosen_attributes as
					$taxonomy => $data ) {
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

	private function get_data_from_products_array( $products, $callback = null ) {
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

	private function get_attributes_from_variations( $variations_data ) {
		$attributes_data = array();
		foreach ( $variations_data as $variation_data ) {
			foreach ( $variation_data['attributes'] as $taxonomy => $slug ) {
				$attributes_data[ str_replace( 'pa_', '', $taxonomy ) ][] = $slug;
			}
		}
		return array_map(
			function( $item ) use ( $attributes_data ) {
				return $this->fixture_data->get_product_attribute( $item, $attributes_data[ $item ] );

			},
			array_keys( $attributes_data )
		);
	}

	private function update_lookup_table( \WC_Product $product, $taxonomy, $term_id ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'wc_product_attributes_lookup',
			array(
				'product_id'             => $product->get_id(),
				'product_or_parent_id'   => $product->get_parent_id(),
				'taxonomy'               => $taxonomy,
				'term_id'                => $term_id,
				'is_variation_attribute' => true,
				'in_stock'               => $product->is_in_stock(),
			),
			array( '%d', '%d', '%s', '%d', '%d', '%d' )
		);
	}

	private function create_test_product( $product_data ) {
		if ( isset( $product_data['variations'] ) ) {
			$attributes       = $this->get_attributes_from_variations( $product_data['variations'] );
			$variable_product = $this->fixture_data->get_variable_product(
				$product_data,
				$attributes
			);
			foreach ( $product_data['variations'] as $variation_data ) {
				$variation = $this->fixture_data->get_variation_product(
					$variable_product->get_id(),
					$variation_data['attributes'],
					$variation_data['props']
				);
				foreach ( $variation_data['attributes'] as $taxonomy => $slug ) {
					$term = get_term_by( 'slug', "$slug-slug", $taxonomy );
					$this->update_lookup_table( $variation, $taxonomy, $term->term_id );
				}
			}
			return $variable_product;
		}

		return $this->fixture_data->get_simple_product( $product_data );
	}
}
