<?php
/**
 * LookupDataStoreTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Testing\Tools\FakeQueue;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

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
	 * The lookup table name.
	 *
	 * @var string
	 */
	private $lookup_table_name;

	/**
	 * Runs after all the tests in the class.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		wc_get_container()->get( DataRegenerator::class )->delete_all_attributes_lookup_data();
	}

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		global $wpdb;

		$this->lookup_table_name = $wpdb->prefix . 'wc_product_attributes_lookup';
		$this->sut               = new LookupDataStore();

		$this->reset_legacy_proxy_mocks();
		$this->register_legacy_proxy_class_mocks(
			array(
				\WC_Queue::class => new FakeQueue(),
			)
		);

		// Initiating regeneration with a fake queue will just create the lookup table in the database.
		$this->get_instance_of( DataRegenerator::class )->initiate_regeneration();
	}

	/**
	 * @testdox `create_data_for_product` throws an exception if a variation is passed.
	 */
	public function test_create_data_for_product_throws_if_variation_is_passed() {
		$product = new \WC_Product_Variation();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( "LookupDataStore::create_data_for_product can't be called for variations." );

		$this->sut->create_data_for_product( $product );
	}

	/**
	 * @testdox `create_data_for_product` creates the appropriate entries for simple products, skipping custom product attributes.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $in_stock 'true' if the product is supposed to be in stock.
	 */
	public function test_create_data_for_simple_product( $in_stock ) {
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

		$this->sut->create_data_for_product( $product );

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
	 * @testdox `create_data_for_product` creates the appropriate entries for variable products.
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

		$this->sut->create_data_for_product( $product );

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
	 * @testdox Deleting a simple product schedules deletion of lookup table entries when the "direct updates" option is off.
	 *
	 * @testWith ["wp_trash_post"]
	 *           ["delete_post"]
	 *           ["delete_method_in_product"]
	 *           ["force_delete_method_in_product"]
	 *
	 * @param string $deletion_mechanism The mechanism used for deletion, one of: 'wp_trash_post', 'delete_post', 'delete_method_in_product', 'force_delete_method_in_product'.
	 */
	public function test_deleting_simple_product_schedules_deletion( $deletion_mechanism ) {
		$this->set_direct_update_option( false );

		$product    = new \WC_Product_Simple();
		$product_id = 10;
		$product->set_id( $product_id );
		$this->save( $product );

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_post_type'    => function( $id ) use ( $product ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return 'product';
					} else {
						return get_post_type( $id );
					}
				},
				'time'             => function() {
					return 100;
				},
				'current_user_can' => function( $capability, ...$args ) {
					if ( 'delete_posts' === $capability ) {
						return true;
					} else {
						return current_user_can( $capability, $args );
					}
				},
			)
		);

		$this->delete_product( $product, $deletion_mechanism );

		$queue_calls = WC()->get_instance_of( \WC_Queue::class )->get_methods_called();

		$this->assertEquals( 1, count( $queue_calls ) );

		$expected = array(
			'method'    => 'schedule_single',
			'args'      =>
				array(
					$product_id,
					LookupDataStore::ACTION_DELETE,
				),
			'group'     => 'woocommerce-db-updates',
			'timestamp' => 101,
			'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
		);
		$this->assertEquals( $expected, $queue_calls[0] );
	}

	/**
	 * Delete a product or variation.
	 *
	 * @param \WC_Product $product The product to delete.
	 * @param string      $deletion_mechanism The mechanism used for deletion, one of: 'wp_trash_post', 'delete_post', 'delete_method_in_product', 'force_delete_method_in_product'.
	 */
	private function delete_product( \WC_Product $product, string $deletion_mechanism ) {
		// We can't use the 'wp_trash_post' and 'delete_post' functions directly
		// because these invoke 'get_post', which fails because tests runs within an
		// uncommitted database transaction. Being WP core functions they can't be mocked or hacked.
		// So instead, we trigger the actions that the tested functionality captures.

		switch ( $deletion_mechanism ) {
			case 'wp_trash_post':
				do_action( 'wp_trash_post', $product );
				break;
			case 'delete_post':
				do_action( 'delete_post', $product->get_id() );
				break;
			case 'delete_method_in_product':
				$product->delete( false );
				break;
			case 'force_delete_method_in_product':
				$product->delete( true );
				break;
		}
	}

	/**
	 * @testdox Deleting a variable product schedules deletion of lookup table entries when the "direct updates" option is off.
	 *
	 * @testWith ["wp_trash_post"]
	 *           ["delete_post"]
	 *           ["delete_method_in_product"]
	 *           ["force_delete_method_in_product"]
	 *
	 * @param string $deletion_mechanism The mechanism used for deletion, one of: 'wp_trash_post', 'delete_post', 'delete_method_in_product', 'force_delete_method_in_product'.
	 */
	public function test_deleting_variable_product_schedules_deletion( $deletion_mechanism ) {
		$this->set_direct_update_option( false );

		$product = new \WC_Product_Variable();
		$product->set_id( 1000 );

		$variation = new \WC_Product_Variation();
		$variation->set_id( 1001 );

		$product->set_children( array( 1001 ) );
		$this->save( $product );

		$product_id = $product->get_id();

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_post_type'    => function( $id ) use ( $product, $variation ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return 'product';
					} elseif ( $id === $variation->get_id() || $id === $variation ) {
						return 'product_variation';
					} else {
						return get_post_type( $id );
					}
				},
				'time'             => function() {
					return 100;
				},
				'current_user_can' => function( $capability, ...$args ) {
					if ( 'delete_posts' === $capability ) {
						return true;
					} else {
						return current_user_can( $capability, $args );
					}
				},
			)
		);

		$this->delete_product( $product, $deletion_mechanism );

		$queue_calls = WC()->get_instance_of( \WC_Queue::class )->get_methods_called();

		$this->assertEquals( 1, count( $queue_calls ) );

		$expected = array(
			'method'    => 'schedule_single',
			'args'      =>
				array(
					$product_id,
					LookupDataStore::ACTION_DELETE,
				),
			'group'     => 'woocommerce-db-updates',
			'timestamp' => 101,
			'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
		);

		$this->assertEquals( $expected, $queue_calls[0] );
	}

	/**
	 * @testdox Deleting a variation schedules deletion of lookup table entries when the "direct updates" option is off.
	 *
	 * @testWith ["wp_trash_post"]
	 *           ["delete_post"]
	 *           ["delete_method_in_product"]
	 *           ["force_delete_method_in_product"]
	 *
	 * @param string $deletion_mechanism The mechanism used for deletion, one of: 'wp_trash_post', 'delete_post', 'delete_method_in_product', 'force_delete_method_in_product'.
	 */
	public function test_deleting_variation_schedules_deletion( $deletion_mechanism ) {
		$this->set_direct_update_option( false );

		$product = new \WC_Product_Variable();
		$product->set_id( 1000 );

		$variation = new \WC_Product_Variation();
		$variation->set_id( 1001 );

		$product->set_children( array( 1001 ) );
		$this->save( $product );

		$variation_id = $product->get_id();

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_post_type'    => function( $id ) use ( $product, $variation ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return 'product';
					} elseif ( $id === $variation->get_id() || $id === $variation ) {
						return 'product_variation';
					} else {
						return get_post_type( $id );
					}
				},
				'time'             => function() {
					return 100;
				},
				'current_user_can' => function( $capability, ...$args ) {
					if ( 'delete_posts' === $capability ) {
						return true;
					} else {
						return current_user_can( $capability, $args );
					}
				},
			)
		);

		$this->delete_product( $product, $deletion_mechanism );

		$queue_calls = WC()->get_instance_of( \WC_Queue::class )->get_methods_called();

		$this->assertEquals( 1, count( $queue_calls ) );

		$expected = array(
			'method'    => 'schedule_single',
			'args'      =>
				array(
					$variation_id,
					LookupDataStore::ACTION_DELETE,
				),
			'group'     => 'woocommerce-db-updates',
			'timestamp' => 101,
			'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
		);

		$this->assertEquals( $expected, $queue_calls[0] );
	}

	/**
	 * @testdox 'on_product_deleted' doesn't schedule duplicate deletions (for the same product).
	 */
	public function test_no_duplicate_deletions_are_scheduled() {
		$this->set_direct_update_option( false );

		$this->register_legacy_proxy_function_mocks(
			array(
				'time' => function() {
					return 100;
				},
			)
		);

		$this->sut->on_product_deleted( 1 );
		$this->sut->on_product_deleted( 1 );
		$this->sut->on_product_deleted( 2 );

		$queue_calls = WC()->get_instance_of( \WC_Queue::class )->get_methods_called();

		$this->assertEquals( 2, count( $queue_calls ) );

		$expected = array(
			array(
				'method'    => 'schedule_single',
				'args'      =>
					array(
						1,
						LookupDataStore::ACTION_DELETE,
					),
				'group'     => 'woocommerce-db-updates',
				'timestamp' => 101,
				'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
			),
			array(
				'method'    => 'schedule_single',
				'args'      =>
					array(
						2,
						LookupDataStore::ACTION_DELETE,
					),
				'group'     => 'woocommerce-db-updates',
				'timestamp' => 101,
				'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
			),
		);

		$this->assertEquals( $expected, $queue_calls );
	}

	/**
	 * @testdox 'on_product_deleted' deletes the data for a variation when the "direct updates" option is on.
	 */
	public function test_direct_deletion_of_variation() {
		global $wpdb;

		$this->set_direct_update_option( true );

		$variation = new \WC_Product_Variation();
		$variation->set_id( 2 );
		$this->save( $variation );

		$this->insert_lookup_table_data( 1, 1, 'pa_foo', 10, false, true );
		$this->insert_lookup_table_data( 2, 1, 'pa_bar', 20, true, true );

		$this->sut->on_product_deleted( $variation );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT DISTINCT product_id FROM ' . $this->lookup_table_name, ARRAY_N );

		$this->assertEquals( array( 1 ), $rows[0] );
	}

	/**
	 * @testdox 'on_product_deleted' deletes the data for a product and its variations when the "direct updates" option is on.
	 */
	public function test_direct_deletion_of_product() {
		global $wpdb;

		$this->set_direct_update_option( true );

		$product = new \WC_Product();
		$product->set_id( 1 );
		$this->save( $product );

		$this->insert_lookup_table_data( 1, 1, 'pa_foo', 10, false, true );
		$this->insert_lookup_table_data( 2, 1, 'pa_bar', 20, true, true );
		$this->insert_lookup_table_data( 3, 3, 'pa_foo', 10, false, true );

		$this->sut->on_product_deleted( $product );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT DISTINCT product_id FROM ' . $this->lookup_table_name, ARRAY_N );

		$this->assertEquals( array( 3 ), $rows[0] );
	}

	/**
	 * @testdox Changing the stock status of a simple product schedules update of lookup table entries when the "direct updates" option is off.
	 *
	 * @testWith ["instock", "outofstock"]
	 *           ["outofstock", "instock"]
	 *
	 * @param string $old_status Original status of the product.
	 * @param string $new_status New status of the product.
	 */
	public function test_changing_simple_product_stock_schedules_update( string $old_status, string $new_status ) {
		$this->set_direct_update_option( false );

		$product    = new \WC_Product_Simple();
		$product_id = 10;
		$product->set_id( $product_id );
		$product->set_stock_status( $old_status );
		$this->save( $product );

		$this->register_legacy_proxy_function_mocks(
			array(
				'time' => function() {
					return 100;
				},
			)
		);

		$product->set_stock_status( $new_status );
		$product->save();

		$queue_calls = WC()->get_instance_of( \WC_Queue::class )->get_methods_called();

		$this->assertEquals( 1, count( $queue_calls ) );

		$expected = array(
			'method'    => 'schedule_single',
			'args'      =>
				array(
					$product_id,
					LookupDataStore::ACTION_UPDATE_STOCK,
				),
			'group'     => 'woocommerce-db-updates',
			'timestamp' => 101,
			'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
		);
		$this->assertEquals( $expected, $queue_calls[0] );
	}

	/**
	 * @testdox Changing the stock status of a variable product or a variation schedules update of lookup table entries when the "direct updates" option is off.
	 *
	 * @testWith ["instock", "outofstock", true]
	 *           ["outofstock", "instock", true]
	 *           ["instock", "outofstock", false]
	 *           ["outofstock", "instock", false]
	 *
	 * @param string $old_status Original status of the product.
	 * @param string $new_status New status of the product.
	 * @param bool   $change_variation_stock True if the stock of the variation changes.
	 */
	public function test_changing_variable_product_or_variation_stock_schedules_update( string $old_status, string $new_status, bool $change_variation_stock ) {
		$this->set_direct_update_option( false );

		$product    = new \WC_Product_Variable();
		$product_id = 1000;
		$product->set_id( $product_id );

		$variation    = new \WC_Product_Variation();
		$variation_id = 1001;
		$variation->set_id( $variation_id );
		$variation->set_stock_status( $old_status );
		$variation->save();

		$product->set_children( array( 1001 ) );
		$product->set_stock_status( $old_status );
		$this->save( $product );

		$this->register_legacy_proxy_function_mocks(
			array(
				'time' => function () {
					return 100;
				},
			)
		);

		if ( $change_variation_stock ) {
			$variation->set_stock_status( $new_status );
			$variation->save();
		} else {
			$product->set_stock_status( $new_status );
			$product->save();
		}

		$queue_calls = WC()->get_instance_of( \WC_Queue::class )->get_methods_called();

		$this->assertEquals( 1, count( $queue_calls ) );

		$expected = array(
			'method'    => 'schedule_single',
			'args'      =>
				array(
					$change_variation_stock ? $variation_id : $product_id,
					LookupDataStore::ACTION_UPDATE_STOCK,
				),
			'group'     => 'woocommerce-db-updates',
			'timestamp' => 101,
			'hook'      => 'woocommerce_run_product_attribute_lookup_update_callback',
		);

		$this->assertEquals( $expected, $queue_calls[0] );
	}

	/**
	 * Data provider for on_product_changed tests with direct update option set.
	 *
	 * @return array[]
	 */
	public function data_provider_for_test_on_product_changed_with_direct_updates() {
		return array(
			array(
				null,
				'creation',
			),
			array(
				array( 'attributes' => array() ),
				'creation',
			),
			array(
				array( 'stock_quantity' => 1 ),
				'update',
			),
			array(
				array( 'stock_status' => 'instock' ),
				'update',
			),
			array(
				array( 'manage_stock' => true ),
				'update',
			),
			array(
				array( 'catalog_visibility' => 'visible' ),
				'creation',
			),
			array(
				array( 'catalog_visibility' => 'catalog' ),
				'creation',
			),
			array(
				array( 'catalog_visibility' => 'search' ),
				'deletion',
			),
			array(
				array( 'catalog_visibility' => 'hidden' ),
				'deletion',
			),
			array(
				array( 'foo' => 'bar' ),
				'none',
			),
		);
	}

	/**
	 * @testdox 'on_product_changed' creates, updates deletes the data for a simple product depending on the changeset when the "direct updates" option is on.
	 *
	 * @dataProvider data_provider_for_test_on_product_changed_with_direct_updates
	 *
	 * @param array  $changeset The changeset to test.
	 * @param string $expected_action The expected performed action, one of 'none', 'creation', 'update' or 'deletion'.
	 */
	public function test_on_product_changed_for_simple_product_with_direct_updates( $changeset, $expected_action ) {
		global $wpdb;

		$this->set_direct_update_option( true );

		$product = new \WC_Product_Simple();
		$product->set_id( 2 );
		$product->set_stock_status( 'instock' );
		$this->set_product_attributes(
			$product,
			array(
				'pa_bar' => array(
					'id'      => 100,
					'options' => array( 20 ),
				),
			)
		);
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_product' => function( $id ) use ( $product ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return $product;
					} else {
						return wc_get_product( $id );
					}
				},
			)
		);

		$this->insert_lookup_table_data( 1, 1, 'pa_foo', 10, false, true );
		if ( 'creation' !== $expected_action ) {
			$this->insert_lookup_table_data( 2, 2, 'pa_bar', 20, false, false );
		}

		$this->sut->on_product_changed( $product, $changeset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT * FROM ' . $this->lookup_table_name, ARRAY_N );

		$expected = array( array( '1', '1', 'pa_foo', '10', '0', '1' ) );

		// Differences:
		// Creation or update: the product is stored as having stock.
		// None: the product remains stored as not having stock.
		if ( 'creation' === $expected_action || 'update' === $expected_action ) {
			$expected[] = array( '2', '2', 'pa_bar', '20', '0', '1' );
		} elseif ( 'none' === $expected_action ) {
			$expected[] = array( '2', '2', 'pa_bar', '20', '0', '0' );
		}

		$this->assertEquals( $expected, $rows );
	}

	/**
	 * @testdox 'on_product_changed' creates, updates deletes the data for a variable product and if needed its variations depending on the changeset when the "direct updates" option is on.
	 *
	 * @dataProvider data_provider_for_test_on_product_changed_with_direct_updates
	 *
	 * @param array  $changeset The changeset to test.
	 * @param string $expected_action The expected performed action, one of 'none', 'creation', 'update' or 'deletion'.
	 */
	public function test_on_variable_product_changed_for_variable_product_with_direct_updates( $changeset, $expected_action ) {
		global $wpdb;

		$this->set_direct_update_option( true );

		$product = new \WC_Product_Variable();
		$product->set_id( 2 );
		$this->set_product_attributes(
			$product,
			array(
				'non-variation-attribute' => array(
					'id'      => 100,
					'options' => array( 10 ),
				),
				'variation-attribute'     => array(
					'id'        => 200,
					'options'   => array( 20 ),
					'variation' => true,
				),
			)
		);
		$product->set_stock_status( 'instock' );

		$variation = new \WC_Product_Variation();
		$variation->set_id( 3 );
		$variation->set_attributes(
			array(
				'variation-attribute' => 'term_20',
			)
		);
		$variation->set_stock_status( 'instock' );
		$variation->set_parent_id( 2 );

		$product->set_children( array( 3 ) );

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_terms'      => function( $args ) {
					switch ( $args['taxonomy'] ) {
						case 'non-variation-attribute':
							return array(
								10 => 'term_10',
							);
						case 'variation-attribute':
							return array(
								20 => 'term_20',
							);
						default:
							throw new \Exception( "Unexpected call to 'get_terms'" );
					}
				},
				'wc_get_product' => function( $id ) use ( $product, $variation ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return $product;
					} elseif ( $id === $variation->get_id() || $id === $variation ) {
						return $variation;
					} else {
						return wc_get_product( $id );
					}
				},
			)
		);

		$this->insert_lookup_table_data( 1, 1, 'pa_foo', 10, false, true );
		if ( 'creation' !== $expected_action ) {
			$this->insert_lookup_table_data( 2, 2, 'non-variation-attribute', 10, false, false );
			$this->insert_lookup_table_data( 3, 2, 'variation-attribute', 20, true, false );
		}

		$this->sut->on_product_changed( $product, $changeset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT * FROM ' . $this->lookup_table_name, ARRAY_N );

		$expected = array( array( '1', '1', 'pa_foo', '10', '0', '1' ) );

		// Differences:
		// Creation: both main product and variation are stored as having stock.
		// Update: main product only is updated as having stock (variation is supposed to get a separate update).
		// None: both main product and variation are still stored as not having stock.
		if ( 'creation' === $expected_action ) {
			$expected[] = array( '2', '2', 'non-variation-attribute', '10', '0', '1' );
			$expected[] = array( '3', '2', 'variation-attribute', '20', '1', '1' );
		} elseif ( 'update' === $expected_action ) {
			$expected[] = array( '2', '2', 'non-variation-attribute', '10', '0', '1' );
			$expected[] = array( '3', '2', 'variation-attribute', '20', '1', '0' );
		} elseif ( 'none' === $expected_action ) {
			$expected[] = array( '2', '2', 'non-variation-attribute', '10', '0', '0' );
			$expected[] = array( '3', '2', 'variation-attribute', '20', '1', '0' );
		}

		$this->assertEquals( $expected, $rows );
	}

	/**
	 * @testdox 'on_product_changed' creates, updates deletes the data for a variation depending on the changeset when the "direct updates" option is on.
	 *
	 * @dataProvider data_provider_for_test_on_product_changed_with_direct_updates
	 *
	 * @param array  $changeset The changeset to test.
	 * @param string $expected_action The expected performed action, one of 'none', 'creation', 'update' or 'deletion'.
	 */
	public function test_on_variation_changed_for_variable_product_with_direct_updates( $changeset, $expected_action ) {
		global $wpdb;

		$this->set_direct_update_option( true );

		$product = new \WC_Product_Variable();
		$product->set_id( 2 );
		$this->set_product_attributes(
			$product,
			array(
				'non-variation-attribute' => array(
					'id'      => 100,
					'options' => array( 10 ),
				),
				'variation-attribute'     => array(
					'id'        => 200,
					'options'   => array( 20 ),
					'variation' => true,
				),
			)
		);
		$product->set_stock_status( 'instock' );

		$variation = new \WC_Product_Variation();
		$variation->set_id( 3 );
		$variation->set_attributes(
			array(
				'variation-attribute' => 'term_20',
			)
		);
		$variation->set_stock_status( 'instock' );
		$variation->set_parent_id( 2 );

		$product->set_children( array( 3 ) );

		$this->register_legacy_proxy_function_mocks(
			array(
				'get_terms'      => function( $args ) {
					switch ( $args['taxonomy'] ) {
						case 'non-variation-attribute':
							return array(
								10 => 'term_10',
							);
						case 'variation-attribute':
							return array(
								20 => 'term_20',
							);
						default:
							throw new \Exception( "Unexpected call to 'get_terms'" );
					}
				},
				'wc_get_product' => function( $id ) use ( $product, $variation ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return $product;
					} elseif ( $id === $variation->get_id() || $id === $variation ) {
						return $variation;
					} else {
						return wc_get_product( $id );
					}
				},
			)
		);

		$this->insert_lookup_table_data( 1, 1, 'pa_foo', 10, false, true );
		if ( 'creation' !== $expected_action ) {
			$this->insert_lookup_table_data( 3, 2, 'variation-attribute', 20, true, false );
		}

		$this->sut->on_product_changed( $variation, $changeset );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( 'SELECT * FROM ' . $this->lookup_table_name, ARRAY_N );

		$expected = array( array( '1', '1', 'pa_foo', '10', '0', '1' ) );

		// Differences:
		// Creation or update: the variation is stored as having stock.
		// None: the variation is still stored as not having stock.
		if ( 'creation' === $expected_action || 'update' === $expected_action ) {
			$expected[] = array( '3', '2', 'variation-attribute', '20', '1', '1' );
		} elseif ( 'none' === $expected_action ) {
			$expected[] = array( '3', '2', 'variation-attribute', '20', '1', '0' );
		}

		$this->assertEquals( $expected, $rows );
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

	/**
	 * Set the value of the option for direct lookup table updates.
	 *
	 * @param bool $value True to set the option to 'yes', false for 'no'.
	 */
	private function set_direct_update_option( bool $value ) {
		update_option( 'woocommerce_attribute_lookup_direct_updates', $value ? 'yes' : 'no' );
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

		$queue = WC()->get_instance_of( \WC_Queue::class );
		$queue->clear_methods_called();
	}

	/**
	 * Insert one entry in the lookup table.
	 *
	 * @param int    $product_id The product id.
	 * @param int    $product_or_parent_id The product id for non-variable products, the main/parent product id for variations.
	 * @param string $taxonomy Taxonomy name.
	 * @param int    $term_id Term id.
	 * @param bool   $is_variation_attribute True if the taxonomy corresponds to an attribute used to define variations.
	 * @param bool   $has_stock True if the product is in stock.
	 */
	private function insert_lookup_table_data( int $product_id, int $product_or_parent_id, string $taxonomy, int $term_id, bool $is_variation_attribute, bool $has_stock ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . $this->lookup_table_name . ' (
					  product_id,
					  product_or_parent_id,
					  taxonomy,
					  term_id,
					  is_variation_attribute,
					  in_stock)
					VALUES
					  ( %d, %d, %s, %d, %d, %d )',
				$product_id,
				$product_or_parent_id,
				$taxonomy,
				$term_id,
				$is_variation_attribute ? 1 : 0,
				$has_stock ? 1 : 0
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}
}
