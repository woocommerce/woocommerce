<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use WC_Product;
use WC_Product_Variable;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Tests related to FilterClausesGenerator service.
 */
abstract class AbstractProductFiltersTest extends \WC_Unit_Test_Case {
	/**
	 * Class-owned product filter fixture state, keyed by test class.
	 *
	 * @var array
	 */
	private static $class_fixture_state = array();

	/**
	 * Option values to restore after class fixture setup and teardown.
	 *
	 * @var array
	 */
	private static $class_fixture_option_values = array();

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
	 * Product categories.
	 *
	 * @var array
	 */
	protected $product_categories;

	/**
	 * Product tags.
	 *
	 * @var array
	 */
	protected $product_tags;

	/**
	 * Ensure the lookup table exists before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
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

		if ( static::uses_class_product_filter_fixtures() ) {
			static::set_up_class_product_filter_fixtures();
		}
	}

	/**
	 * Remove custom-table rows not covered by WordPress's class cleanup.
	 */
	public static function wpTearDownAfterClass(): void {
		global $wpdb;

		if ( static::uses_class_product_filter_fixtures() ) {
			static::delete_class_product_filter_fixtures();
		}

		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_product_meta_lookup" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_product_attributes_lookup" );

		if ( static::uses_class_product_filter_fixtures() ) {
			static::restore_class_product_filter_fixture_options();
		}
	}

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		$this->set_up_test_case();

		if ( static::uses_class_product_filter_fixtures() ) {
			$this->use_class_product_filter_fixtures();
		} else {
			$this->set_up_product_filter_fixtures();
		}
	}

	/**
	 * Whether this test class owns one immutable catalog shared by all its methods.
	 */
	protected static function uses_class_product_filter_fixtures(): bool {
		return false;
	}

	/**
	 * Start the standard per-test transaction and reset WordPress state.
	 */
	protected function set_up_test_case(): void {
		parent::setUp();
	}

	/**
	 * Create the product filter catalog used by a test.
	 */
	protected function set_up_product_filter_fixtures(): void {
		$this->fixture_data = new FixtureData();

		update_option( 'woocommerce_attribute_lookup_enabled', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		$this->remove_all_attributes();
		$this->remove_all_products();
		$this->empty_lookup_tables();

		$this->product_categories = array(
			'cat-1' => $this->fixture_data->get_product_category( array( 'name' => 'Cat 1' ) ),
			'cat-2' => $this->fixture_data->get_product_category( array( 'name' => 'Cat 2' ) ),
			'cat-3' => $this->fixture_data->get_product_category( array( 'name' => 'Cat 3' ) ),
		);

		$this->product_tags = array(
			'tag-1' => $this->fixture_data->get_product_tag(
				array(
					'name' => 'Tag 1',
					'slug' => 'tag-1',
				)
			),
			'tag-2' => $this->fixture_data->get_product_tag(
				array(
					'name' => 'Tag 2',
					'slug' => 'tag-2',
				)
			),
			'tag-3' => $this->fixture_data->get_product_tag(
				array(
					'name' => 'Tag 3',
					'slug' => 'tag-3',
				)
			),
		);

		$this->products_data = array(
			array(
				'name'          => 'Product 1',
				'regular_price' => 10,
				'stock_status'  => ProductStockStatus::ON_BACKORDER,
				'category_ids'  => array( $this->product_categories['cat-1']['term_id'] ),
				'tag_ids'       => array( $this->product_tags['tag-1']['term_id'] ),
			),
			array(
				'name'          => 'Product 2',
				'regular_price' => 20,
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'category_ids'  => array( $this->product_categories['cat-2']['term_id'] ),
				'tag_ids'       => array( $this->product_tags['tag-2']['term_id'] ),
			),
			array(
				'name'          => 'Product 3',
				'regular_price' => 30,
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
				'category_ids'  => array(
					$this->product_categories['cat-2']['term_id'],
					$this->product_categories['cat-3']['term_id'],
				),
				'tag_ids'       => array( $this->product_tags['tag-3']['term_id'] ),
			),
			array(
				'name'          => 'Product 4',
				'regular_price' => 40,
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'category_ids'  => array( $this->product_categories['cat-1']['term_id'] ),
				'tag_ids'       => array( $this->product_tags['tag-1']['term_id'] ),
			),
			array(
				'name'         => 'Product 5',
				'stock_status' => ProductStockStatus::IN_STOCK,
				'category_ids' => array( $this->product_categories['cat-1']['term_id'] ),
				'tag_ids'      => array( $this->product_tags['tag-2']['term_id'] ),
				'variations'   => array(
					array(
						'attributes' => array(
							'pa_color' => 'red',
						),
						'props'      => array(
							'regular_price' => 50,
							'stock_status'  => ProductStockStatus::IN_STOCK,
						),
					),
					array(
						'attributes' => array(
							'pa_color' => 'green',
						),
						'props'      => array(
							'regular_price' => 50,
							'stock_status'  => ProductStockStatus::IN_STOCK,
						),
					),
				),
			),
			array(
				'name'         => 'Product 6',
				'stock_status' => ProductStockStatus::IN_STOCK,
				'category_ids' => array( $this->product_categories['cat-1']['term_id'] ),
				'tag_ids'      => array( $this->product_tags['tag-3']['term_id'] ),
				'variations'   => array(
					array(
						'attributes' => array(
							'pa_color' => 'blue',
						),
						'props'      => array(
							'regular_price' => 60,
							'stock_status'  => ProductStockStatus::IN_STOCK,
						),
					),
					array(
						'attributes' => array(
							'pa_color' => 'green',
						),
						'props'      => array(
							'regular_price' => 60,
							'stock_status'  => ProductStockStatus::IN_STOCK,
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
	 * Create an immutable product filter catalog before per-test transactions begin.
	 */
	protected static function set_up_class_product_filter_fixtures(): void {
		$class_name = static::class;

		self::$class_fixture_option_values[ $class_name ] = array(
			'woocommerce_attribute_lookup_enabled' => get_option( 'woocommerce_attribute_lookup_enabled', null ),
			'woocommerce_calc_taxes'               => get_option( 'woocommerce_calc_taxes', null ),
			'woocommerce_tax_display_shop'         => get_option( 'woocommerce_tax_display_shop', null ),
		);
		static::enable_direct_product_attribute_lookup_updates();

		try {
			$fixture_owner = new static();
			$fixture_owner->set_up_product_filter_fixtures();
			$fixture_owner->set_up_additional_class_product_filter_fixtures();

			self::$class_fixture_state[ $class_name ] = array(
				'product_ids'        => array_map(
					static function ( $product ) {
						return $product->get_id();
					},
					$fixture_owner->products
				),
				'products_data'      => $fixture_owner->products_data,
				'product_categories' => $fixture_owner->product_categories,
				'product_tags'       => $fixture_owner->product_tags,
			);
		} finally {
			static::restore_class_product_filter_fixture_options();
			static::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Add fixtures needed by a class after creating its shared catalog.
	 */
	protected function set_up_additional_class_product_filter_fixtures(): void {
	}

	/**
	 * Rehydrate the class-owned catalog inside a per-test transaction.
	 */
	protected function use_class_product_filter_fixtures(): void {
		$fixture_state = self::$class_fixture_state[ static::class ];

		update_option( 'woocommerce_attribute_lookup_enabled', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );
		\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		unregister_taxonomy( 'product_type' );
		\WC_Post_Types::register_taxonomies();
		foreach ( array_keys( wc_get_attribute_taxonomy_ids() ) as $attribute_name ) {
			clean_taxonomy_cache( wc_attribute_taxonomy_name( wc_sanitize_taxonomy_name( $attribute_name ) ) );
		}

		$this->fixture_data       = new FixtureData();
		$this->products_data      = $fixture_state['products_data'];
		$this->product_categories = $fixture_state['product_categories'];
		$this->product_tags       = $fixture_state['product_tags'];
		$this->products           = array_map( 'wc_get_product', $fixture_state['product_ids'] );
	}

	/**
	 * Delete a class-owned catalog through WooCommerce data stores.
	 */
	protected static function delete_class_product_filter_fixtures(): void {
		static::enable_direct_product_attribute_lookup_updates();

		try {
			$fixture_owner = new static();
			$fixture_owner->delete_product_filter_fixtures();
		} finally {
			static::disable_direct_product_attribute_lookup_updates();
		}

		unset( self::$class_fixture_state[ static::class ] );
	}

	/**
	 * Restore options changed while creating or deleting a class-owned catalog.
	 */
	protected static function restore_class_product_filter_fixture_options(): void {
		$class_name = static::class;

		foreach ( self::$class_fixture_option_values[ $class_name ] ?? array() as $option_name => $option_value ) {
			if ( null === $option_value ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $option_value );
			}
		}
	}

	/**
	 * Delete product filter fixtures through WooCommerce data stores.
	 */
	protected function delete_product_filter_fixtures(): void {
		$this->remove_all_attributes();
		$this->remove_all_products();
		$this->empty_lookup_tables();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		if ( ! static::uses_class_product_filter_fixtures() ) {
			$this->delete_product_filter_fixtures();
		}

		foreach ( array_keys( wc_get_attribute_taxonomy_ids() ) as $attribute_name ) {
			$taxonomy_name = wc_attribute_taxonomy_name( wc_sanitize_taxonomy_name( $attribute_name ) );
			unregister_taxonomy( $taxonomy_name );
		}

		\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		\WC_Query::reset_chosen_attributes();
		parent::tearDown();
	}

	/**
	 * Empty the lookup tables inside the current test transaction.
	 */
	private function empty_lookup_tables() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_product_meta_lookup" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_product_attributes_lookup" );
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
			$callback = function ( $product ) {
				return $product->get_name();
			};
		}

		return array_map(
			$callback,
			$products
		);
	}

	/**
	 * Build and create attributes from variations data.
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
			function ( $item ) use ( $attributes_data ) {
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
					function ( $item ) {
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
