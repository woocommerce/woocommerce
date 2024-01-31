<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductQueryFilters;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Tests related to FilterClausesGenerator service.
 */
abstract class AbstractProductQueryFiltersTest extends \WC_Unit_Test_Case {
	/**
	 * FixtureData instance.
	 *
	 * @var FixtureData
	 */
	protected $fixture_data;

	/**
	 * Test products.
	 *
	 * @var \WC_Product[]
	 */
	protected $products;

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_attribute_lookup_enabled', 'yes' );

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

	protected function get_data_from_products_array( $products, $callback = null ) {
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
