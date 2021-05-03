<?php
/**
 * LookupDataStoreTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Testing\Tools\FakeQueue;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Tests for the LookupDataStore class.
 * @package Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup
 */
class LookupDataStoreTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var LookupDataStore
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		global $wpdb;

		$this->sut = new LookupDataStore();

		// Initiating regeneration with a fake queue will just create the lookup table in the database.
		add_filter(
			'woocommerce_queue_class',
			function() {
				return FakeQueue::class;
			}
		);
		$this->get_instance_of( DataRegenerator::class )->initiate_regeneration();
	}

	/**
	 * @testdox `test_update_data_for_product` throws an exception if a variation is passed.
	 */
	public function test_update_data_for_product_throws_if_variation_is_passed() {
		$product = new \WC_Product_Variation();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "LookupDataStore::update_data_for_product can't be called for variations." );

		$this->sut->update_data_for_product( $product );
	}

	/**
	 * @testdox `test_update_data_for_product` creates the appropriate entries for simple products, skipping custom product attributes.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $in_stock 'true' if the product is supposed to be in stock.
	 */
	public function test_update_data_for_simple_product( $in_stock ) {
		$product = new \WC_Product_Simple();
		$product->set_id( 10 );
		$this->set_product_attributes(
			$product,
			array(
				'pa_attribute_1'      => array(
					'id'      => 100,
					'options' => array( 51, 52 ),
				),
				'pa_attribute_2'      => array(
					'id'      => 200,
					'options' => array( 73, 74 ),
				),
				'pa_custom_attribute' => array(
					'id'      => 0,
					'options' => array( 'foo', 'bar' ),
				),
			)
		);

		if ( $in_stock ) {
			$product->set_stock_status( 'instock' );
			$expected_in_stock = 1;
		} else {
			$product->set_stock_status( 'outofstock' );
			$expected_in_stock = 0;
		}

		$this->sut->update_data_for_product( $product );

		$expected = array(
			array(
				'product_id'             => 10,
				'product_or_parent_id'   => 10,
				'taxonomy'               => 'pa_attribute_1',
				'term_id'                => 51,
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
			array(
				'product_id'             => 10,
				'product_or_parent_id'   => 10,
				'taxonomy'               => 'pa_attribute_1',
				'term_id'                => 52,
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
			array(
				'product_id'             => 10,
				'product_or_parent_id'   => 10,
				'taxonomy'               => 'pa_attribute_2',
				'term_id'                => 73,
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
			array(
				'product_id'             => 10,
				'product_or_parent_id'   => 10,
				'taxonomy'               => 'pa_attribute_2',
				'term_id'                => 74,
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
		);

		$actual = $this->get_lookup_table_data();

		$this->assertEquals( sort( $expected ), sort( $actual ) );
	}

	/**
	 * @testdox `test_update_data_for_product` creates the appropriate entries for variable products.
	 */
	public function test_update_data_for_variable_product() {
		$products = array();

		/**
		 * Create one normal attribute and two attributes used to define variations,
		 * with 4 terms each.
		 */

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_terms'      => function( $args ) use ( &$invokations_of_get_terms ) {
					switch ( $args['taxonomy'] ) {
						case 'non-variation-attribute':
							return array(
								10 => 'term_10',
								20 => 'term_20',
								30 => 'term_30',
								40 => 'term_40',
							);
						case 'variation-attribute-1':
							return array(
								50 => 'term_50',
								60 => 'term_60',
								70 => 'term_70',
								80 => 'term_80',
							);
						case 'variation-attribute-2':
							return array(
								90  => 'term_90',
								100 => 'term_100',
								110 => 'term_110',
								120 => 'term_120',
							);
						default:
							throw new \Exception( "Unexpected call to 'get_terms'" );
					}
				},
				'wc_get_product' => function( $id ) use ( &$products ) {
					return $products[ $id ];
				},
			)
		);

		/**
		 * Create a variable product with:
		 * - 3 of the 4 values of the regular attribute.
		 * - A custom product attribute.
		 * - The two variation attributes, with 3 of the 4 terms for each one.
		 * - Variation 1 having one value for each of the variation attributes.
		 * - Variation 2 having one value for variation-attribute-1
		 *   but none for variation-attribute-2 (so the value for that one is "Any").
		 */

		$product = new \WC_Product_Variable();
		$product->set_id( 1000 );
		$this->set_product_attributes(
			$product,
			array(
				'non-variation-attribute' => array(
					'id'      => 100,
					'options' => array( 10, 20, 30 ),
				),
				'pa_custom_attribute'     => array(
					'id'      => 0,
					'options' => array( 'foo', 'bar' ),
				),
				'variation-attribute-1'   => array(
					'id'        => 200,
					'options'   => array( 50, 60, 70 ),
					'variation' => true,
				),
				'variation-attribute-2'   => array(
					'id'        => 300,
					'options'   => array( 90, 100, 110 ),
					'variation' => true,
				),
			)
		);
		$product->set_stock_status( 'instock' );

		$variation_1 = new \WC_Product_Variation();
		$variation_1->set_id( 1001 );
		$variation_1->set_attributes(
			array(
				'variation-attribute-1' => 'term_50',
				'variation-attribute-2' => 'term_90',
			)
		);
		$variation_1->set_stock_status( 'instock' );

		$variation_2 = new \WC_Product_Variation();
		$variation_2->set_id( 1002 );
		$variation_2->set_attributes(
			array(
				'variation-attribute-1' => 'term_60',
			)
		);
		$variation_2->set_stock_status( 'outofstock' );

		$product->set_children( array( 1001, 1002 ) );
		$products[1000] = $product;
		$products[1001] = $variation_1;
		$products[1002] = $variation_2;

		$this->sut->update_data_for_product( $product );

		$expected = array(
			// Main product: one entry for each of the regular attribute values,
				// excluding custom product attributes.

				array(
					'product_id'             => '1000',
					'product_or_parent_id'   => '1000',
					'taxonomy'               => 'non-variation-attribute',
					'term_id'                => '10',
					'is_variation_attribute' => '0',
					'in_stock'               => '1',
				),
			array(
				'product_id'             => '1000',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'non-variation-attribute',
				'term_id'                => '20',
				'is_variation_attribute' => '0',
				'in_stock'               => '1',
			),
			array(
				'product_id'             => '1000',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'non-variation-attribute',
				'term_id'                => '30',
				'is_variation_attribute' => '0',
				'in_stock'               => '1',
			),

			// Variation 1: one entry for each of the defined variation attributes.

			array(
				'product_id'             => '1001',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'variation-attribute-1',
				'term_id'                => '50',
				'is_variation_attribute' => '1',
				'in_stock'               => '1',
			),
			array(
				'product_id'             => '1001',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'variation-attribute-2',
				'term_id'                => '90',
				'is_variation_attribute' => '1',
				'in_stock'               => '1',
			),

			// Variation 2: one entry for the defined value for variation-attribute-1,
				// then one for each of the possible values of variation-attribute-2
				// (the values defined in the parent product).

				array(
					'product_id'             => '1002',
					'product_or_parent_id'   => '1000',
					'taxonomy'               => 'variation-attribute-1',
					'term_id'                => '60',
					'is_variation_attribute' => '1',
					'in_stock'               => '0',
				),
			array(
				'product_id'             => '1002',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'variation-attribute-2',
				'term_id'                => '90',
				'is_variation_attribute' => '1',
				'in_stock'               => '0',
			),
			array(
				'product_id'             => '1002',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'variation-attribute-2',
				'term_id'                => '100',
				'is_variation_attribute' => '1',
				'in_stock'               => '0',
			),
			array(
				'product_id'             => '1002',
				'product_or_parent_id'   => '1000',
				'taxonomy'               => 'variation-attribute-2',
				'term_id'                => '110',
				'is_variation_attribute' => '1',
				'in_stock'               => '0',
			),
		);

		$actual = $this->get_lookup_table_data();
		$this->assertEquals( sort( $expected ), sort( $actual ) );
	}

	/**
	 * Set the product attributes from an array with this format:
	 *
	 * [
	 *   'taxonomy_or_custom_attribute_name' =>
	 *      [
	 *        'id' => attribute id (0 for custom product attribute),
	 *        'options' => [term_id, term_id...] (for custom product attributes: ['term', 'term'...]
	 *        'variation' => 1|0 (optional, default 0)
	 *      ], ...
	 * ]
	 *
	 * @param WC_Product $product The product to set the attributes.
	 * @param array      $attributes_data The attributes to set.
	 */
	private function set_product_attributes( $product, $attributes_data ) {
		$attributes = array();
		foreach ( $attributes_data as $taxonomy => $attribute_data ) {
			$attribute = new \WC_Product_Attribute();
			$attribute->set_id( $attribute_data['id'] );
			$attribute->set_name( $taxonomy );
			$attribute->set_options( $attribute_data['options'] );
			$attribute->set_variation( ArrayUtil::get_value_or_default( $attribute_data, 'variation', false ) );
			$attributes[] = $attribute;
		}

		$product->set_attributes( $attributes );
	}

	/**
	 * Get all the data in the lookup table as an array of associative arrays.
	 *
	 * @return array All the rows in the lookup table as an array of associative arrays.
	 */
	private function get_lookup_table_data() {
		global $wpdb;

		$result = $wpdb->get_results( 'select * from ' . $wpdb->prefix . 'wc_product_attributes_lookup', ARRAY_A );

		foreach ( $result as $row ) {
			foreach ( $row as $column_name => $value ) {
				if ( 'taxonomy' !== $column_name ) {
					$row[ $column_name ] = (int) $value;
				}
			}
		}

		return $result;
	}
}
