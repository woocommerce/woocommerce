<?php

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\AttributesHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Tests related to filtering for WC_Query.
 */
class FiltererTest extends \WC_Unit_Test_Case {

	/**
	 * Counter to insert unique SKU for concurrent tests.
	 * The starting value ensures no conflicts between existing generators.
	 *
	 * @var int $sku_counter
	 */
	private static $sku_counter = 200000;

	/**
	 * Runs before all the tests in the class.
	 */
	public static function setUpBeforeClass(): void {
		global $wpdb, $wp_post_types;

		parent::setUpBeforeClass();

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

		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_product_attributes_lookup" );

		// This is required too for WC_Query to act on the main query.
		$wp_post_types['product']->has_archive = true;
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		global $wpdb;

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
					$this->save( $child );
				}
			}

			$product->delete( true );
		}

		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_product_attributes_lookup" );

		\WC_Query::reset_chosen_attributes();
	}

	/**
	 * Save a product and delete any lookup table data that may have been automatically inserted
	 * (for the purposes of unit testing we want to insert this data manually)
	 *
	 * @param \WC_Product $product The product to save and delete lookup table data for.
	 */
	private function save( \WC_Product $product ) {
		global $wpdb;

		$product->save();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = %d",
				$product->get_id()
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Core function to create a product.
	 *
	 * Format of $product_attributes is:
	 *
	 * [
	 *   'non_variation_defining' => [
	 *     'Name' => ['Value','Value'],...
	 *   ]
	 *  'variation_defining' => [
	 *     'Name' => ['Value','Value'],...
	 *   ]
	 * ]
	 *
	 * @param string $class_name The name of the product class that will be instantiated to create the product.
	 * @param array  $product_attributes The product attributes.
	 * @return mixed An instance of the class passed in $class_name representing the created product.
	 */
	private function create_product_core( $class_name, $product_attributes ) {
		$attributes            = array();
		$attribute_ids_by_name = wc_get_attribute_taxonomy_ids();

		$product_attributes = array(
			false => ArrayUtil::get_value_or_default( $product_attributes, 'non_variation_defining', array() ),
			true  => ArrayUtil::get_value_or_default( $product_attributes, 'variation_defining', array() ),
		);
		foreach ( $product_attributes as $defines_variation => $attribute_terms_by_name ) {
			foreach ( $attribute_terms_by_name as $attribute_name => $attribute_terms ) {
				if ( ! is_array( $attribute_terms ) ) {
					$attribute_terms = array( $attribute_terms );
				}
				$sanitized_attribute_name = wc_sanitize_taxonomy_name( $attribute_name );

				$term_ids = array();
				foreach ( $attribute_terms as $term ) {
					$term_ids[] = (int) term_exists( $term, 'pa_' . $sanitized_attribute_name )['term_id'];
				}

				$attribute = new \WC_Product_Attribute();
				$attribute->set_id( $attribute_ids_by_name );
				$attribute->set_name( 'pa_' . $sanitized_attribute_name );
				$attribute->set_options( $term_ids );
				$attribute->set_visible( true );
				$attribute->set_variation( $defines_variation );
				$attributes[] = $attribute;
			}
		}

		$product = new $class_name();
		$product->set_props(
			array(
				'name'          => 'Product',
				'regular_price' => 1,
				'price'         => 1,
				'sku'           => 'DUMMY SKU' . self::$sku_counter,
				'manage_stock'  => false,
				'tax_status'    => 'taxable',
				'downloadable'  => false,
				'virtual'       => false,
			)
		);

		++self::$sku_counter;

		$product->set_attributes( $attributes );

		return $product;
	}

	/**
	 * Creates a simple product.
	 *
	 * @param array $attribute_terms_by_name An array of product attributes, keys are attribute names, values are arrays of attribute term names.
	 * @param bool  $in_stock True if the product is in stock, false otherwise.
	 * @return array The product data, as generated by the REST API product creation entry point.
	 */
	private function create_simple_product( $attribute_terms_by_name, $in_stock ) {
		$product = $this->create_product_core( \WC_Product_Simple::class, array( 'non_variation_defining' => $attribute_terms_by_name ) );

		$product->set_stock_status( $in_stock ? 'instock' : 'outofstock' );

		$this->save( $product );

		if ( empty( $attribute_terms_by_name ) ) {
			return $product;
		}

		$lookup_insert_clauses = array();
		$lookup_insert_values  = array();

		foreach ( $attribute_terms_by_name as $name => $terms ) {
			$id = $product->get_id();
			$this->compose_lookup_table_insert( $id, $id, $name, $terms, $lookup_insert_clauses, $lookup_insert_values, $in_stock, false );
		}

		$this->run_lookup_table_insert( $lookup_insert_clauses, $lookup_insert_values );

		return $product;
	}

	/**
	 * Creates a variable product.
	 * Format for the supplied data:
	 *
	 * variation_attributes => [
	 *     Color => [Red, Blue, Green],
	 *     Size  => [Big, Medium, Small]
	 * ],
	 * non_variation_attributes => [
	 *     Features => [Washable, Ironable]
	 * ],
	 * variations => [
	 *     [
	 *       defining_attributes => [
	 *         Color => Red,
	 *         Size  => Small
	 *       ],
	 *       in_stock => true
	 *     ],
	 *     [
	 *       defining_attributes => [
	 *         Color => Red,
	 *         Size  => null  //Means "Any"
	 *       ],
	 *       in_stock => false
	 *     ],
	 * ]
	 *
	 * Format for the returned data:
	 *
	 * [
	 *   id => 1,
	 *   variation_ids => [2,3]
	 * ]
	 *
	 * @param array $data The data for creating the product.
	 * @returns array The product and variation ids.
	 */
	private function create_variable_product( $data ) {

		// * First create the main product.

		$product = $this->create_product_core(
			\WC_Product_Variable::class,
			array(
				'non_variation_defining' => $data['non_variation_attributes'],
				'variation_defining'     => $data['variation_attributes'],
			)
		);

		$this->save( $product );

		$product_id = $product->get_id();

		// * Now create the variations.

		$variation_ids = array();

		foreach ( $data['variations'] as $variation_data ) {
			$variation = new \WC_Product_Variation();
			$variation->set_props(
				array(
					'parent_id'     => $product->get_id(),
					'regular_price' => 10,
				)
			);
			$attributes = array();
			foreach ( $variation_data['defining_attributes'] as $attribute_name => $attribute_value ) {
				$attribute_name                = wc_attribute_taxonomy_name( $attribute_name );
				$attribute_value               = wc_sanitize_taxonomy_name( $attribute_value );
				$attributes[ $attribute_name ] = $attribute_value;

			}
			$variation->set_attributes( $attributes );
			$variation->set_stock_status( $variation_data['in_stock'] ? 'instock' : 'outofstock' );
			$this->save( $variation );

			$variation_ids[] = $variation->get_id();
		}

		// This is needed because it's not done by the REST API.
		\WC_Product_Variable::sync_stock_status( $product_id );

		// * And finally, insert the data in the lookup table.

		$lookup_insert_clauses = array();
		$lookup_insert_values  = array();

		if ( ! empty( $data['non_variation_attributes'] ) ) {
			$main_product_in_stock = ! empty(
				array_filter(
					$data['variations'],
					function( $variation ) {
						return $variation['in_stock'];
					}
				)
			);

			foreach ( $data['non_variation_attributes'] as $name => $terms ) {
				$this->compose_lookup_table_insert( $product->get_id(), $product->get_id(), $name, $terms, $lookup_insert_clauses, $lookup_insert_values, $main_product_in_stock, false );
			}
		}

		reset( $variation_ids );
		foreach ( $data['variations'] as $variation_data ) {
			$variation_id = current( $variation_ids );

			foreach ( $variation_data['defining_attributes'] as $attribute_name => $attribute_value ) {
				if ( is_null( $attribute_value ) ) {
					$attribute_values = $data['variation_attributes'][ $attribute_name ];
				} else {
					$attribute_values = array( $attribute_value );
				}
				$this->compose_lookup_table_insert( $variation_id, $product->get_id(), $attribute_name, $attribute_values, $lookup_insert_clauses, $lookup_insert_values, $variation_data['in_stock'], true );
			}

			next( $variation_ids );
		}

		$this->run_lookup_table_insert( $lookup_insert_clauses, $lookup_insert_values );

		return array(
			'id'            => $product_id,
			'variation_ids' => $variation_ids,
		);
	}

	/**
	 * Compose the values part of a query to insert data in the lookup table.
	 *
	 * @param int    $product_id Value for the "product_id" column.
	 * @param int    $product_or_parent_id Value for the "product_or_parent_id" column.
	 * @param string $attribute_name Taxonomy name of the attribute.
	 * @param array  $terms Term names to insert for the attribute.
	 * @param array  $insert_query_parts Array of strings to add the new query parts to.
	 * @param array  $insert_query_values Array of values to add the new query values to.
	 * @param bool   $in_stock True if the product/variation is in stock, false otherwise.
	 * @param bool   $is_variation True if it's an attribute that defines a variation, false otherwise.
	 */
	private function compose_lookup_table_insert( $product_id, $product_or_parent_id, $attribute_name, $terms, &$insert_query_parts, &$insert_query_values, $in_stock, $is_variation ) {
		$taxonomy_name     = wc_attribute_taxonomy_name( $attribute_name );
		$term_objects      = get_terms( $taxonomy_name, array( 'hide_empty' => false ) );
		$term_ids_by_names = wp_list_pluck( $term_objects, 'term_id', 'name' );

		foreach ( $terms as $term ) {
			$insert_query_parts[]  = '(%d, %d, %s, %d, %d, %d )';
			$insert_query_values[] = $product_id;
			$insert_query_values[] = $product_or_parent_id;
			$insert_query_values[] = wc_attribute_taxonomy_name( $attribute_name );
			$insert_query_values[] = $term_ids_by_names[ $term ];
			$insert_query_values[] = $is_variation ? 1 : 0;
			$insert_query_values[] = $in_stock ? 1 : 0;
		}
	}

	/**
	 * Runs an insert clause in the lookup table.
	 * The clauses and values are to be generated with compose_lookup_table_insert.
	 *
	 * @param array $insert_query_parts Array of strings with query parts.
	 * @param array $insert_values Array of values for the query.
	 */
	private function run_lookup_table_insert( $insert_query_parts, $insert_values ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		$insert_query =
			"INSERT INTO {$wpdb->prefix}wc_product_attributes_lookup ( product_id, product_or_parent_id, taxonomy, term_id, is_variation_attribute, in_stock ) VALUES "
			. join( ',', $insert_query_parts );

		$prepared_insert = $wpdb->prepare( $insert_query, $insert_values );

		$wpdb->query( $prepared_insert );

		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Create a product attribute.
	 *
	 * @param string $name The attribute name.
	 * @param array  $terms The terms that will be created for the attribute.
	 */
	private function create_product_attribute( $name, $terms ) {
		return ProductHelper::create_attribute( $name, $terms );
	}

	/**
	 * Set the "hide out of stock products" option.
	 *
	 * @param bool $hide The value to set the option to.
	 */
	private function set_hide_out_of_stock_items( $hide ) {
		update_option( 'woocommerce_hide_out_of_stock_items', $hide ? 'yes' : 'no' );
	}

	/**
	 * Set the "hide out of stock products" option.
	 *
	 * @param bool $use The value to set the option to.
	 */
	private function set_use_lookup_table( $use ) {
		update_option( 'woocommerce_attribute_lookup_enabled', $use ? 'yes' : 'no' );
	}

	/**
	 * Simulate a product query.
	 *
	 * @param array $filters The attribute filters as an array of attribute name => attribute terms.
	 * @param array $query_types The query types for each attribute as an array of attribute name => "or"/"and".
	 * @return mixed
	 */
	private function do_product_request( $filters, $query_types = array() ) {
		global $wp_the_query;

		foreach ( $filters as $name => $values ) {
			if ( ! empty( $values ) ) {
				$_GET[ 'filter_' . wc_sanitize_taxonomy_name( $name ) ] = join( ',', array_map( 'wc_sanitize_taxonomy_name', $values ) );
			}
		}

		foreach ( $query_types as $name => $value ) {
			$_GET[ 'query_type_' . wc_sanitize_taxonomy_name( $name ) ] = $value;
		}

		return $wp_the_query->query(
			array(
				'post_type' => 'product',
				'fields'    => 'ids',
			)
		);
	}

	/**
	 * Assert that the filter by attribute widget lists a given set of terms for an attribute
	 * (with a count of 1 each)
	 *
	 * @param string $attribute_name The attribute name the terms belong to.
	 * @param array  $expected_terms The labelss of the terms that are expected to be listed.
	 * @param string $filter_type The filter type in use, "and" or "or".
	 */
	private function assert_counters( $attribute_name, $expected_terms, $filter_type = 'and' ) {
		$widget = new class() extends \WC_Widget_Layered_Nav {
			// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod, Squiz.Commenting.FunctionComment
			public function get_filtered_term_product_counts( $term_ids, $taxonomy, $query_type ) {
				return parent::get_filtered_term_product_counts( $term_ids, $taxonomy, $query_type );
			}
			// phpcs:enable Generic.CodeAnalysis.UselessOverridingMethod, Squiz.Commenting.FunctionComment
		};

		$taxonomy         = wc_attribute_taxonomy_name( $attribute_name );
		$term_ids_by_name = wp_list_pluck( get_terms( $taxonomy, array( 'hide_empty' => '1' ) ), 'term_id', 'name' );

		$expected = array();
		foreach ( $expected_terms as $term ) {
			$expected[ $term_ids_by_name[ $term ] ] = 1;
		}

		$term_counts = $widget->get_filtered_term_product_counts( $term_ids_by_name, $taxonomy, $filter_type );
		$this->assertEqualsCanonicalizing( $expected, $term_counts );
	}

	/**
	 * Data provider for the test_filtering_simple_product_in_stock tests
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_simple_product_in_stock() {
		return array(
			array( array(), 'and', true ),
			array( array(), 'or', true ),
			array( array( 'Blue' ), 'and', true ),
			array( array( 'Blue' ), 'or', true ),
			array( array( 'Blue', 'Red' ), 'and', true ),
			array( array( 'Blue', 'Red' ), 'or', true ),
			array( array( 'Green' ), 'and', false ),
			array( array( 'Green' ), 'or', false ),
			array( array( 'Blue', 'Green' ), 'and', false ),
			array( array( 'Blue', 'Green' ), 'or', true ),
		);
	}

	/**
	 * @testdox The product query shows a simple product only if it's not filtered out by the specified attribute filters (using lookup table).
	 *
	 * @dataProvider data_provider_for_test_filtering_simple_product_in_stock
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_simple_product_in_stock_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_simple_product_in_stock( $attributes, $filter_type, $expected_to_be_visible, true );
	}

	/**
	 * @testdox The product query shows a simple product only if it's not filtered out by the specified attribute filters (not using lookup table).
	 *
	 * @dataProvider data_provider_for_test_filtering_simple_product_in_stock
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_simple_product_in_stock_not_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_simple_product_in_stock( $attributes, $filter_type, $expected_to_be_visible, false );
	}

	/**
	 * Main code for the test_filtering_simple_product_in_stock tests.
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 * @param bool   $using_lookup_table Are we using the lookup table?.
	 */
	private function base_test_filtering_simple_product_in_stock( $attributes, $filter_type, $expected_to_be_visible, $using_lookup_table ) {
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red', 'Green' ) );

		$product = $this->create_simple_product(
			array(
				'Color' => array(
					'Blue',
					'Red',
				),
			),
			true
		);

		$filtered_product_ids = $this->do_product_request( array( 'Color' => $attributes ), array( 'Color' => $filter_type ) );

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product->get_id() ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$expected_to_be_included_in_count = 'or' === $filter_type || $expected_to_be_visible;

		$this->assert_counters( 'Color', $expected_to_be_included_in_count ? array( 'Blue', 'Red' ) : array(), $filter_type );
	}

	/**
	 * Data provider for the test_filtering_simple_product_out_of_stock tests.
	 *
	 * @return array
	 */
	public function data_provider_for_test_filtering_simple_product_out_of_stock() {
		return array(
			array( false, true, true ),
			array( false, false, true ),
			array( true, true, true ),
			array( true, false, false ),
		);
	}

	/**
	 * @testdox The product query shows a simple product only if it's in stock OR we don't have "hide out of stock items" set.
	 *
	 * @xtestWith [false, true, true]
	 *           [false, false, true]
	 *           [true, true, true]
	 *           [true, false, false]
	 *
	 * @dataProvider data_provider_for_test_filtering_simple_product_out_of_stock
	 *
	 * @param bool $hide_out_of_stock The value of the "hide out of stock products" option.
	 * @param bool $is_in_stock True if the product is in stock, false otherwise.
	 * @param bool $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_simple_product_out_of_stock_using_lookup_table( $hide_out_of_stock, $is_in_stock, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_simple_product_out_of_stock( $hide_out_of_stock, $is_in_stock, $expected_to_be_visible );
	}

	/**
	 * @testdox The product query shows a simple product only if it's in stock OR we don't have "hide out of stock items" set.
	 *
	 * @xtestWith [false, true, true]
	 *           [false, false, true]
	 *           [true, true, true]
	 *           [true, false, false]
	 *
	 * @dataProvider data_provider_for_test_filtering_simple_product_out_of_stock
	 *
	 * @param bool $hide_out_of_stock The value of the "hide out of stock products" option.
	 * @param bool $is_in_stock True if the product is in stock, false otherwise.
	 * @param bool $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_simple_product_out_of_stock_not_using_lookup_table( $hide_out_of_stock, $is_in_stock, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_simple_product_out_of_stock( $hide_out_of_stock, $is_in_stock, $expected_to_be_visible );
	}

	/**
	 * Main code for the test_filtering_simple_product_out_of_stock tests.
	 *
	 * @param bool $hide_out_of_stock The value of the "hide out of stock products" option.
	 * @param bool $is_in_stock True if the product is in stock, false otherwise.
	 * @param bool $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	private function base_test_filtering_simple_product_out_of_stock( $hide_out_of_stock, $is_in_stock, $expected_to_be_visible ) {
		$this->create_product_attribute( 'Features', array( 'Washable', 'Ironable', 'Elastic' ) );

		$product = $this->create_simple_product(
			array( 'Features' => array( 'Washable', 'Ironable' ) ),
			$is_in_stock
		);

		$this->set_hide_out_of_stock_items( $hide_out_of_stock );

		$filtered_product_ids = $this->do_product_request( array() );

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product->get_id() ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$this->assert_counters( 'Features', $expected_to_be_visible ? array( 'Washable', 'Ironable' ) : array() );
	}

	/**
	 * Data provider for the test_filtering_simple_product_by_multiple_attributes tests.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_simple_product_by_multiple_attributes() {
		return array(
			array( array(), array(), true ),
			array( array( 'Blue' ), array(), true ),
			array( array(), array( 'Ironable' ), true ),
			array( array( 'Blue' ), array( 'Ironable' ), true ),
			array( array( 'Red' ), array(), false ),
			array( array(), array( 'Washable' ), false ),
			array( array( 'Blue' ), array( 'Washable' ), false ),
			array( array( 'Red' ), array( 'Ironable' ), false ),
		);
	}

	/**
	 * @testdox The product query shows a simple product only if it's not filtered out by the specified attribute filters (when filtering by multiple attributes, using the lookup table).
	 *
	 * Worth noting that multiple attributes are always combined in an AND fashion for filtering.
	 *
	 * @dataProvider data_provider_for_test_filtering_simple_product_by_multiple_attributes
	 *
	 * @param array $attributes_1 The color attribute names that will be included in the query.
	 * @param array $attributes_2 The features attribute names that will be included in the query.
	 * @param bool  $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_simple_product_by_multiple_attributes_using_lookup_table( $attributes_1, $attributes_2, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_simple_product_by_multiple_attributes( $attributes_1, $attributes_2, $expected_to_be_visible );
	}

	/**
	 * @testdox The product query shows a simple product only if it's not filtered out by the specified attribute filters (when filtering by multiple attributes, not using the lookup table).
	 *
	 * Worth noting that multiple attributes are always combined in an AND fashion for filtering.
	 *
	 * @dataProvider data_provider_for_test_filtering_simple_product_by_multiple_attributes
	 *
	 * @param array $attributes_1 The color attribute names that will be included in the query.
	 * @param array $attributes_2 The features attribute names that will be included in the query.
	 * @param bool  $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_simple_product_by_multiple_attributes_not_using_using_lookup_table( $attributes_1, $attributes_2, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_simple_product_by_multiple_attributes( $attributes_1, $attributes_2, $expected_to_be_visible );
	}

	/**
	 * Main code for the test_filtering_simple_product_by_multiple_attributes tests.
	 *
	 * @param array $attributes_1 The color attribute names that will be included in the query.
	 * @param array $attributes_2 The features attribute names that will be included in the query.
	 * @param bool  $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	private function base_test_filtering_simple_product_by_multiple_attributes( $attributes_1, $attributes_2, $expected_to_be_visible ) {
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red' ) );
		$this->create_product_attribute( 'Features', array( 'Ironable', 'Washable' ) );

		$product = $this->create_simple_product(
			array(
				'Color'    => array(
					'Blue',
				),
				'Features' => array(
					'Ironable',
				),
			),
			true
		);

		$filtered_product_ids = $this->do_product_request(
			array(
				'Color'    => $attributes_1,
				'Features' => $attributes_2,
			)
		);

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product->get_id() ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$this->assert_counters( 'Color', $expected_to_be_visible ? array( 'Blue' ) : array() );
		$this->assert_counters( 'Features', $expected_to_be_visible ? array( 'Ironable' ) : array() );
	}

	/**
	 * Data provider for the test_filtering_variable_product_in_stock_for_non_variation_defining_attributes tests.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_in_stock_for_non_variation_defining_attributes() {
		return array(
			array( array(), 'and', true ),
			array( array(), 'or', true ),
			array( array( 'Washable' ), 'and', true ),
			array( array( 'Washable' ), 'or', true ),
			array( array( 'Washable', 'Ironable' ), 'and', true ),
			array( array( 'Washable', 'Ironable' ), 'or', true ),
			array( array( 'Elastic' ), 'and', false ),
			array( array( 'Elastic' ), 'or', false ),
			array( array( 'Washable', 'Elastic' ), 'and', false ),
			array( array( 'Washable', 'Elastic' ), 'or', true ),
		);
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (for non-variation-defining attributes), using the lookup table.
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_in_stock_for_non_variation_defining_attributes
	 *
	 * @param array  $attributes The feature attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_in_stock_for_non_variation_defining_attributes_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_variable_product_in_stock_for_non_variation_defining_attributes( $attributes, $filter_type, $expected_to_be_visible, true );
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (for non-variation-defining attributes), not using the lookup table.
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_in_stock_for_non_variation_defining_attributes
	 *
	 * @param array  $attributes The feature attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_in_stock_for_non_variation_defining_attributes_not_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_variable_product_in_stock_for_non_variation_defining_attributes( $attributes, $filter_type, $expected_to_be_visible, false );
	}

	/**
	 * Main code for the test_filtering_variable_product_in_stock_for_non_variation_defining_attributes tests.
	 *
	 * @param array  $attributes The feature attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 * @param bool   $using_lookup_table Are we using the lookup table?.
	 */
	private function base_test_filtering_variable_product_in_stock_for_non_variation_defining_attributes( $attributes, $filter_type, $expected_to_be_visible, $using_lookup_table ) {
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red' ) );
		$this->create_product_attribute( 'Features', array( 'Washable', 'Ironable', 'Elastic' ) );

		$product = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(
					'Features' => array( 'Washable', 'Ironable' ),
				),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Blue',
						),
					),
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Red',
						),
					),
				),
			)
		);

		$filtered_product_ids = $this->do_product_request( array( 'Features' => $attributes ), array( 'Features' => $filter_type ) );

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product['id'] ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$expected_to_be_included_in_count = 'or' === $filter_type || $expected_to_be_visible;
		$this->assert_counters( 'Features', $expected_to_be_included_in_count ? array( 'Washable', 'Ironable' ) : array(), $filter_type );
	}

	/**
	 * Data provider for the test_filtering_variable_product_out_of_stock tests.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_out_of_stock() {
		return array(
			array( false, true, true, true ),
			array( false, true, false, true ),
			array( false, false, true, true ),
			array( false, false, false, true ),
			array( true, true, true, true ),
			array( true, true, false, true ),
			array( true, false, true, true ),
			array( true, false, false, false ),
		);
	}

	/**
	 * @testdox The product query shows a variable product only if at least one of the variations is in stock OR we don't have "hide out of stock items" set (using the lookup table).
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_out_of_stock
	 *
	 * @param bool $hide_out_of_stock The value of the "hide out of stock products" option.
	 * @param bool $variation_1_is_in_stock True if the first variation is in stock, false otherwise.
	 * @param bool $variation_2_is_in_stock True if the second variation is in stock, false otherwise.
	 * @param bool $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_out_of_stock_using_lookup_table( $hide_out_of_stock, $variation_1_is_in_stock, $variation_2_is_in_stock, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_variable_product_out_of_stock( $hide_out_of_stock, $variation_1_is_in_stock, $variation_2_is_in_stock, $expected_to_be_visible, true );
	}

	/**
	 * @testdox The product query shows a variable product only if at least one of the variations is in stock OR we don't have "hide out of stock items" set (using the lookup table).
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_out_of_stock
	 *
	 * @param bool $hide_out_of_stock The value of the "hide out of stock products" option.
	 * @param bool $variation_1_is_in_stock True if the first variation is in stock, false otherwise.
	 * @param bool $variation_2_is_in_stock True if the second variation is in stock, false otherwise.
	 * @param bool $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_out_of_stock_not_using_lookup_table( $hide_out_of_stock, $variation_1_is_in_stock, $variation_2_is_in_stock, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_variable_product_out_of_stock( $hide_out_of_stock, $variation_1_is_in_stock, $variation_2_is_in_stock, $expected_to_be_visible, false );
	}

	/**
	 * Main code for the test_filtering_variable_product_out_of_stock tests.
	 *
	 * @param bool $hide_out_of_stock The value of the "hide out of stock products" option.
	 * @param bool $variation_1_is_in_stock True if the first variation is in stock, false otherwise.
	 * @param bool $variation_2_is_in_stock True if the second variation is in stock, false otherwise.
	 * @param bool $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 * @param bool $using_lookup_table Are we using the lookup table?.
	 */
	private function base_test_filtering_variable_product_out_of_stock( $hide_out_of_stock, $variation_1_is_in_stock, $variation_2_is_in_stock, $expected_to_be_visible, $using_lookup_table ) {
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red' ) );

		$product = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => $variation_1_is_in_stock,
						'defining_attributes' => array(
							'Color' => 'Blue',
						),
					),
					array(
						'in_stock'            => $variation_2_is_in_stock,
						'defining_attributes' => array(
							'Color' => 'Red',
						),
					),
				),
			)
		);

		$this->set_hide_out_of_stock_items( $hide_out_of_stock );

		$filtered_product_ids = $this->do_product_request( array() );

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product['id'] ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		/**
		 * When using the lookup table, attribute counters only take in account in stock variations.
		 * When not using it, all variations are accounted if at least one of them has stock.
		 */

		$expected_visible_attributes = array();
		if ( $using_lookup_table ) {
			if ( ! $hide_out_of_stock || $variation_1_is_in_stock ) {
				$expected_visible_attributes[] = 'Blue';
			}
			if ( ! $hide_out_of_stock || $variation_2_is_in_stock ) {
				$expected_visible_attributes[] = 'Red';
			}
		} elseif ( ! $hide_out_of_stock || $variation_1_is_in_stock || $variation_2_is_in_stock ) {
			$expected_visible_attributes = array( 'Blue', 'Red' );
		}

		$this->assert_counters( 'Color', $expected_visible_attributes );
	}

	/**
	 * Base data provider for the test_filtering_variable_product_in_stock_for_variation_defining_attributes tests.
	 *
	 * @return array[]
	 */
	private function data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_core() {
		return array(
			array( array(), 'and', true ),
			array( array(), 'or', true ),
			array( array( 'Blue' ), 'and', true ),
			array( array( 'Blue' ), 'or', true ),
			array( array( 'Blue', 'Red' ), 'and', true ),
			array( array( 'Blue', 'Red' ), 'or', true ),
			array( array( 'Green' ), 'and', false ),
			array( array( 'Green' ), 'or', false ),

		);
	}

	/**
	 * Data provider for test_filtering_variable_product_in_stock_for_variation_defining_attributes_using_lookup_table.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_using_lookup_table() {
		$data = $this->data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_core();

		/**
		 * When filtering by an attribute having a variation AND another one not having it:
		 * The product shows, since when dealing with variation attributes we're effectively doing OR.
		 */

		$data[] = array( array( 'Blue', 'Green' ), 'and', true );
		return $data;
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (for variation-defining attributes), using the lookup table.
	 *
	 * Note that the difference with the simple product or the non-variation attributes case is that "and" is equivalent to "or".
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_using_lookup_table
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_in_stock_for_variation_defining_attributes_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_variable_product_in_stock_for_variation_defining_attributes( $attributes, $filter_type, $expected_to_be_visible, true );
	}

	/**
	 * Data provider for test_filtering_variable_product_in_stock_for_variation_defining_attributes_not_using_lookup_table.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_not_using_lookup_table() {
		$data = $this->data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_core();

		/**
		 * When filtering by an attribute having a variation AND another one not having it:
		 * The product doesn't show because variation attributes are treated as non-variation ones.
		 */

		$data[] = array( array( 'Blue', 'Green' ), 'and', false );
		return $data;
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (for variation-defining attributes), not using the lookup table.
	 *
	 * Note that the difference with the simple product or the non-variation attributes case is that "and" is equivalent to "or".
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_not_using_lookup_table
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_in_stock_for_variation_defining_attributes_not_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_variable_product_in_stock_for_variation_defining_attributes( $attributes, $filter_type, $expected_to_be_visible, false );
	}

	/**
	 * Main code for the test_filtering_variable_product_in_stock_for_variation_defining_attributes tests.
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 * @param bool   $using_lookup_table Are we using the lookup table?.
	 */
	private function base_test_filtering_variable_product_in_stock_for_variation_defining_attributes( $attributes, $filter_type, $expected_to_be_visible, $using_lookup_table ) {
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red', 'Green' ) );

		$product = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Blue',
						),
					),
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Red',
						),
					),
				),
			)
		);

		$filtered_product_ids = $this->do_product_request( array( 'Color' => $attributes ), array( 'Color' => $filter_type ) );

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product['id'] ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$expected_counted_attributes = 'or' === $filter_type || $expected_to_be_visible ? array( 'Blue', 'Red' ) : array();

		$this->assert_counters( 'Color', $expected_counted_attributes, $filter_type );
	}


	/**
	 * Base data provider for the test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value tests.
	 *
	 * @return array[]
	 */
	private function data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_core() {
		return array(
			array( array(), 'and', true ),
			array( array(), 'or', true ),
			array( array( 'Blue' ), 'and', true ),
			array( array( 'Blue' ), 'or', true ),
			array( array( 'Red' ), 'and', true ),
			array( array( 'Red' ), 'or', true ),
			array( array( 'Green' ), 'and', true ),
			array( array( 'Green' ), 'or', true ),
			array( array( 'White' ), 'and', false ),
			array( array( 'White' ), 'or', false ),
		);
	}

	/**
	 * Data provider for test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_using_lookup_table.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_using_lookup_table() {
		$data = $this->data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_core();

		/**
		 * When filtering by attributes having a variation AND others not having it:
		 * The product shows, since when dealing with variation attributes we're effectively doing OR.
		 */
		$data[] = array( array( 'Blue', 'Red', 'Green', 'White' ), 'and', true );

		return $data;
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (for variation-defining attributes, with "Any" values), using the lookup table.
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_using_lookup_table
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value( $attributes, $filter_type, $expected_to_be_visible, true );
	}

	/**
	 * Data provider for test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_not_using_lookup_table.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_not_using_lookup_table() {
		$data = $this->data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_core();

		/**
		 * When filtering by attributes having a variation AND others not having it:
		 * The product doesn't show because variation attributes are treated as non-variation ones.
		 */
		$data[] = array( array( 'Blue', 'Red', 'Green', 'White' ), 'and', false );

		return $data;
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (for variation-defining attributes, with "Any" values), not using the lookup table.
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_not_using_lookup_table
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value_not_using_lookup_table( $attributes, $filter_type, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value( $attributes, $filter_type, $expected_to_be_visible, false );
	}

	/**
	 * Main code for the test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value tests.
	 *
	 * @param array  $attributes The color attribute names that will be included in the query.
	 * @param string $filter_type The filtering type, "or" or "and".
	 * @param bool   $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 * @param bool   $using_lookup_table Are we using the lookup table?.
	 */
	private function base_test_filtering_variable_product_in_stock_for_variation_defining_attributes_with_any_value( $attributes, $filter_type, $expected_to_be_visible, $using_lookup_table ) {
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red', 'Green', 'White' ) );

		$product = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red', 'Green' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => null,
						),
					),
				),
			)
		);

		$filtered_product_ids = $this->do_product_request( array( 'Color' => $attributes ), array( 'Color' => $filter_type ) );

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product['id'] ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$expected_to_be_included_in_count = 'or' === $filter_type || $expected_to_be_visible;

		$this->assert_counters( 'Color', $expected_to_be_included_in_count ? array( 'Blue', 'Red', 'Green' ) : array(), $filter_type );
	}

	/**
	 * @testdox Products not in "publish" state aren't shown.
	 *
	 * @testWith [true, ["Red"]]
	 *           [false, ["Blue", "Red"]]
	 *
	 * @param bool  $using_lookup_table Use the lookup table?.
	 * @param array $expected_colors_included_in_counters Expected colors to be included in the widget counters.
	 */
	public function test_filtering_excludes_non_published_products( $using_lookup_table, $expected_colors_included_in_counters ) {
		$this->set_use_lookup_table( $using_lookup_table );
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red' ) );
		$this->create_product_attribute( 'Features', array( 'Washable', 'Ironable' ) );

		$product_simple_1 = $this->create_simple_product(
			array( 'Features' => array( 'Washable' ) ),
			true
		);

		$product_simple_2 = $this->create_simple_product(
			array( 'Features' => array( 'Ironable' ) ),
			true
		);

		$product_variable_1 = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Blue',
						),
					),
				),
			)
		);

		$product_variable_2 = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Red',
						),
					),
				),
			)
		);

		$post_data       = array( 'post_status' => 'draft' );
		$post_data['ID'] = $product_simple_1->get_id();
		wp_update_post( $post_data );
		$post_data['ID'] = $product_variable_1['id'];
		wp_update_post( $post_data );

		$filtered_product_ids = $this->do_product_request( array() );

		$this->assertEqualsCanonicalizing( array( $product_simple_2->get_id(), $product_variable_2['id'] ), $filtered_product_ids );

		$this->assert_counters( 'Color', $expected_colors_included_in_counters );
		$this->assert_counters( 'Features', array( 'Ironable' ) );
	}

	/**
	 * @testdox Hidden products aren't shown.
	 *
	 * @testWith [true, ["Red"]]
	 *           [false, ["Blue", "Red"]]
	 *
	 * @param bool  $using_lookup_table Use the lookup table?.
	 * @param array $expected_colors_included_in_counters Expected colors to be included in the widget counters.
	 */
	public function test_filtering_excludes_hidden_products( $using_lookup_table, $expected_colors_included_in_counters ) {
		$this->set_use_lookup_table( $using_lookup_table );
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red' ) );
		$this->create_product_attribute( 'Features', array( 'Washable', 'Ironable' ) );

		$product_simple_1 = $this->create_simple_product(
			array( 'Features' => array( 'Washable' ) ),
			true
		);

		$product_simple_2 = $this->create_simple_product(
			array( 'Features' => array( 'Ironable' ) ),
			true
		);

		$product_variable_1 = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Blue',
						),
					),
				),
			)
		);

		$product_variable_2 = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue', 'Red' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Red',
						),
					),
				),
			)
		);

		$terms = array( 'exclude-from-catalog' );
		wp_set_object_terms( $product_simple_1->get_id(), $terms, 'product_visibility' );
		wp_set_object_terms( $product_variable_1['id'], $terms, 'product_visibility' );

		$actual_filtered_product_ids   = $this->do_product_request( array() );
		$expected_filtered_product_ids = array( $product_simple_2->get_id(), $product_variable_2['id'] );
		sort( $actual_filtered_product_ids );
		sort( $expected_filtered_product_ids );

		$this->assertEquals( $expected_filtered_product_ids, $actual_filtered_product_ids );

		$this->assert_counters( 'Color', $expected_colors_included_in_counters );
		$this->assert_counters( 'Features', array( 'Ironable' ) );
	}

	/**
	 * Data provider for the test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes tests.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes() {
		return array(
			array( array(), array(), true ),
			array( array( 'Blue' ), array(), true ),
			array( array(), array( 'Medium' ), true ),
			array( array( 'Blue' ), array( 'Medium' ), true ),
			array( array( 'Red' ), array(), false ),
			array( array(), array( 'Large' ), false ),
			array( array( 'Blue' ), array( 'Large' ), false ),
			array( array( 'Red' ), array( 'Medium' ), false ),
		);
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (when filtering by multiple attributes), using the lookup table.
	 *
	 * Worth noting that multiple attributes are always combined in an AND fashion for filtering.
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes
	 *
	 * @param array $attributes_1 The color attribute names that will be included in the query.
	 * @param array $attributes_2 The size attribute names that will be included in the query.
	 * @param bool  $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes_using_lookup_table( $attributes_1, $attributes_2, $expected_to_be_visible ) {
		$this->set_use_lookup_table( true );
		$this->base_test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes( $attributes_1, $attributes_2, $expected_to_be_visible );
	}

	/**
	 * @testdox The product query shows a variable product only if it's not filtered out by the specified attribute filters (when filtering by multiple attributes), not using the lookup table.
	 *
	 * Worth noting that multiple attributes are always combined in an AND fashion for filtering.
	 *
	 * @dataProvider data_provider_for_test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes
	 *
	 * @param array $attributes_1 The color attribute names that will be included in the query.
	 * @param array $attributes_2 The size attribute names that will be included in the query.
	 * @param bool  $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	public function test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes_not_using_lookup_table( $attributes_1, $attributes_2, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->base_test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes( $attributes_1, $attributes_2, $expected_to_be_visible );
	}

	/**
	 * Main code for the test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes tests.
	 *
	 * @param array $attributes_1 The color attribute names that will be included in the query.
	 * @param array $attributes_2 The size attribute names that will be included in the query.
	 * @param bool  $expected_to_be_visible True if the product is expected to be returned by the query, false otherwise.
	 */
	private function base_test_filtering_variable_product_for_variation_defining_attributes_by_multiple_attributes( $attributes_1, $attributes_2, $expected_to_be_visible ) {
		$this->set_use_lookup_table( false );
		$this->create_product_attribute( 'Color', array( 'Blue', 'Red' ) );
		$this->create_product_attribute( 'Size', array( 'Large', 'Medium' ) );

		$product = $this->create_variable_product(
			array(
				'variation_attributes'     => array(
					'Color' => array( 'Blue' ),
					'Size'  => array( 'Medium' ),
				),
				'non_variation_attributes' => array(),
				'variations'               => array(
					array(
						'in_stock'            => true,
						'defining_attributes' => array(
							'Color' => 'Blue',
							'Size'  => 'Medium',
						),
					),
				),
			)
		);

		$filtered_product_ids = $this->do_product_request(
			array(
				'Color' => $attributes_1,
				'Size'  => $attributes_2,
			)
		);

		if ( $expected_to_be_visible ) {
			$this->assertEquals( array( $product['id'] ), $filtered_product_ids );
		} else {
			$this->assertEmpty( $filtered_product_ids );
		}

		$this->assert_counters( 'Color', $expected_to_be_visible ? array( 'Blue' ) : array() );
		$this->assert_counters( 'Size', $expected_to_be_visible ? array( 'Medium' ) : array() );
	}
}
