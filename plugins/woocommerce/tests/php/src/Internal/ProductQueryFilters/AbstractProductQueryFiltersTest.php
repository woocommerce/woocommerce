<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductQueryFilters;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use WC_Product;
use WC_Product_Variable;

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
	 * Test products data.
	 *
	 * @var Array
	 */
	protected $products_data;

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

		$this->fixture_data = new FixtureData();

		global $wpdb;
		$wpdb->query(
			"
			  CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wc_product_attributes_lookup (
			  product_id bigint(20) NOT NULL,
			  product_or_parent_id bigint(20) NOT NULL,
			  taxonomy varchar(32) NOT NULL,
			  term_id bigint(20) NOT NULL,
			  is_variation_attribute tinyint(1) NOT NULL,
			  in_stock tinyint(1) NOT NULL
			  );
			"
		);

		update_option( 'woocommerce_attribute_lookup_enabled', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		$this->remove_all_attributes();
		$this->remove_all_products();
		$this->empty_lookup_tables();

		$this->products_data = array(
			array(
				'name'          => 'Product 1',
				'regular_price' => 10,
				'stock_status'  => 'onbackorder',
			),
			array(
				'name'          => 'Product 2',
				'regular_price' => 20,
				'stock_status'  => 'instock',
			),
			array(
				'name'          => 'Product 3',
				'regular_price' => 30,
				'stock_status'  => 'outofstock',
			),
			array(
				'name'          => 'Product 4',
				'regular_price' => 40,
				'stock_status'  => 'instock',
			),
			array(
				'name'         => 'Product 5',
				'stock_status' => 'instock',
				'variations'   => array(
					array(
						'attributes' => array(
							'pa_color' => 'red',
						),
						'props'      => array(
							'regular_price' => 50,
							'stock_status'  => 'instock',
						),
					),
					array(
						'attributes' => array(
							'pa_color' => 'green',
						),
						'props'      => array(
							'regular_price' => 50,
							'stock_status'  => 'instock',
						),
					),
				),
			),
			array(
				'name'         => 'Product 6',
				'stock_status' => 'instock',
				'variations'   => array(
					array(
						'attributes' => array(
							'pa_color' => 'blue',
						),
						'props'      => array(
							'regular_price' => 60,
							'stock_status'  => 'instock',
						),
					),
					array(
						'attributes' => array(
							'pa_color' => 'green',
						),
						'props'      => array(
							'regular_price' => 60,
							'stock_status'  => 'instock',
						),
					),
				),
			),
		);

		$this->products = array_map(
			array( $this, 'create_test_product' ),
			$this->products_data
		);
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->remove_all_attributes();
		$this->remove_all_products();
		$this->empty_lookup_tables();
		wp_cache_flush();
	}

	/**
	 * Truncate the lookup table.
	 */
	private function empty_lookup_tables() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_product_meta_lookup" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_product_attributes_lookup" );
	}

	/**
	 * Remove all attributes and associated terms.
	 */
	private function remove_all_attributes() {
		$attribute_ids_by_name = wc_get_attribute_taxonomy_ids();
		foreach ( $attribute_ids_by_name as $attribute_name => $attribute_id ) {
			$attribute_name  = wc_sanitize_taxonomy_name( $attribute_name );
			$taxonomy_name   = wc_attribute_taxonomy_name( $attribute_name );
			$attribute_terms = get_terms( array( 'taxonomy' => $taxonomy_name ) );
			if ( ! is_wp_error( $attribute_terms ) ) {
				foreach ( $attribute_terms as $term ) {
					wp_delete_term( $term->term_id, $taxonomy_name );
				}
			}
			unregister_taxonomy( $taxonomy_name );

			wc_delete_attribute( $attribute_id );
		}
	}

	/**
	 * Remove all products.
	 */
	private function remove_all_products() {
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

	/**
	 * Get data from results of wc_get_product(), default to return the product name.
	 *
	 * @param \WC_Product[] $products Array of products.
	 * @param function      $callback The callback that passed to array map.
	 */
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

	/**
	 * Build and create attribute from variations data.
	 *
	 * @param array $variations_data Variation data.
	 */
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


	/**
	 * Manually insert the lookup data if it isn't automatically inserted.
	 *
	 * @param \WC_Product $product  WC_Product instance.
	 * @param string      $taxonomy Attribute taxonomy name.
	 * @param int         $term_id  Attribute term id.
	 */
	private function update_lookup_table( \WC_Product $product, $taxonomy, $term_id ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = %d AND product_or_parent_id = %d AND taxonomy = %s AND term_id = %d",
				$product->get_id(),
				$product->get_parent_id(),
				$taxonomy,
				$term_id
			)
		);

		if ( ! empty( $rows ) ) {
			return;
		}

		$wpdb->replace(
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


	/**
	 * Create test product from provided data.
	 *
	 * @param array $product_data Product data.
	 */
	private function create_test_product( $product_data ) {
		if ( isset( $product_data['variations'] ) ) {
			$attributes = $this->get_attributes_from_variations( $product_data['variations'] );

			$variable_product = $this->fixture_data->get_variable_product(
				$product_data,
				$attributes
			);

			foreach ( $product_data['variations'] as $variation_data ) {
				$variation_attributes = array_map(
					function( $item ) {
						return "$item-slug";
					},
					$variation_data['attributes']
				);

				$variation = $this->fixture_data->get_variation_product(
					$variable_product->get_id(),
					$variation_attributes,
					$variation_data['props']
				);

				foreach ( $variation_data['attributes'] as $taxonomy => $slug ) {
					$term = get_term_by( 'slug', "$slug-slug", $taxonomy );
					$this->update_lookup_table( $variation, $taxonomy, $term->term_id );
				}
			}
			WC_Product_Variable::sync( $variable_product );

			return $variable_product;
		}

		return $this->fixture_data->get_simple_product( $product_data );
	}
}
