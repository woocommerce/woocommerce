<?php
/**
 * LookupDataStoreTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Testing\Tools\FakeQueue;
use Automattic\WooCommerce\Enums\ProductStockStatus;

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
	 * Product attributes used for the tests.
	 *
	 * @var array
	 */
	private static $attributes;

	/**
	 * Runs before all the tests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		for ( $i = 1; $i <= 3; $i++ ) {
			$taxonomy_id   = wc_create_attribute(
				array(
					'name'         => 'tax' . $i,
					'has_archives' => true,
				)
			);
			$taxonomy_name = wc_get_attribute( $taxonomy_id )->slug;
			register_taxonomy( $taxonomy_name, array( 'product' ) );
			self::$attributes[] = array(
				'id'       => $taxonomy_id,
				'name'     => $taxonomy_name,
				'term_ids' => array(
					wp_create_term( "term_{$i}_1", $taxonomy_name )['term_id'],
					wp_create_term( "term_{$i}_2", $taxonomy_name )['term_id'],
					wp_create_term( "term_{$i}_3", $taxonomy_name )['term_id'],
				),
			);
		}
	}

	/**
	 * Runs after all the tests.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		foreach ( self::$attributes as $attribute ) {
			wc_delete_attribute( $attribute['id'] );
			unregister_taxonomy( $attribute['name'] );
		}
	}

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		global $wpdb;

		parent::setUp();

		$this->lookup_table_name = $wpdb->prefix . 'wc_product_attributes_lookup';
		$this->sut               = new LookupDataStore();

		$this->reset_legacy_proxy_mocks();
		$this->register_legacy_proxy_class_mocks(
			array(
				\WC_Queue::class => new FakeQueue(),
			)
		);

		$this->get_instance_of( DataRegenerator::class )->truncate_lookup_table();
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		delete_option( 'woocommerce_attribute_lookup_optimized_updates' );
	}

	/**
	 * @testdox `create_data_for_product` creates the appropriate entries for simple products, skipping custom product attributes.
	 *
	 * @testWith [true, true]
	 *           [false, true]
	 *           [true, false]
	 *           [false, false]
	 *
	 * @param bool $in_stock 'true' if the product is supposed to be in stock.
	 * @param bool $use_optimized_db_access 'true' to use optimized db access for the table update.
	 */
	public function test_create_data_for_simple_product( bool $in_stock, bool $use_optimized_db_access ) {
		$product = new \WC_Product_Simple();

		$this->set_product_attributes(
			$product,
			array(
				self::$attributes[0]['name'] => array(
					'id'      => self::$attributes[1]['id'],
					'options' => array( self::$attributes[0]['term_ids'][0], self::$attributes[0]['term_ids'][1] ),
				),
				self::$attributes[1]['name'] => array(
					'id'      => self::$attributes[2]['id'],
					'options' => array( self::$attributes[1]['term_ids'][0], self::$attributes[1]['term_ids'][1] ),
				),
				'pa_custom_attribute'        => array(
					'id'      => 0,
					'options' => array( 'foo', 'bar' ),
				),
			)
		);

		if ( $in_stock ) {
			$product->set_stock_status( ProductStockStatus::IN_STOCK );
			$expected_in_stock = 1;
		} else {
			$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
			$expected_in_stock = 0;
		}
		$this->save( $product );
		$product_id = $product->get_id();

		$this->sut->create_data_for_product( $product, $use_optimized_db_access );

		$expected = array(
			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => self::$attributes[0]['name'],
				'term_id'                => self::$attributes[0]['term_ids'][0],
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => self::$attributes[0]['name'],
				'term_id'                => self::$attributes[0]['term_ids'][1],
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => self::$attributes[1]['name'],
				'term_id'                => self::$attributes[1]['term_ids'][0],
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => self::$attributes[1]['name'],
				'term_id'                => self::$attributes[1]['term_ids'][1],
				'in_stock'               => $expected_in_stock,
				'is_variation_attribute' => 0,
			),
		);

		$actual = $this->get_lookup_table_data();

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox `create_data_for_product` creates the appropriate entries for variable products.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $use_optimized_db_access 'true' to use optimized db access for the table update.
	 */
	public function test_update_data_for_variable_product( bool $use_optimized_db_access ) {
		$non_variation_attribute = self::$attributes[0];
		$variation_attribute_1   = self::$attributes[1];
		$variation_attribute_2   = self::$attributes[2];

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
		$this->set_product_attributes(
			$product,
			array(
				$non_variation_attribute['name'] => array(
					'id'      => $non_variation_attribute['id'],
					'options' => $non_variation_attribute['term_ids'],
				),
				'pa_custom_attribute'            => array(
					'id'      => 0,
					'options' => array( 'foo', 'bar' ),
				),
				$variation_attribute_1['name']   => array(
					'id'        => $variation_attribute_1['id'],
					'options'   => $variation_attribute_1['term_ids'],
					'variation' => true,
				),
				$variation_attribute_2['name']   => array(
					'id'        => $variation_attribute_2['id'],
					'options'   => $variation_attribute_2['term_ids'],
					'variation' => true,
				),
			)
		);
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();
		$product_id = $product->get_id();

		$variation_1 = new \WC_Product_Variation();
		$variation_1->set_attributes(
			array(
				$variation_attribute_1['name'] => 'term_2_1',
				$variation_attribute_2['name'] => 'term_3_1',
			)
		);
		$variation_1->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation_1->set_parent_id( $product_id );
		$variation_1->save();
		$variation_1_id = $variation_1->get_id();

		$variation_2 = new \WC_Product_Variation();
		$variation_2->set_attributes(
			array(
				$variation_attribute_1['name'] => 'term_2_2',
			)
		);
		$variation_2->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$variation_2->set_parent_id( $product_id );
		$variation_2->save();
		$variation_2_id = $variation_2->get_id();

		$product->set_children( array( $variation_1_id, $variation_2_id ) );

		\WC_Product_Variable::sync( $product );

		$this->sut->create_data_for_product( $product, $use_optimized_db_access );

		$expected = array(
			// Main product: one entry for each of the regular attribute values,
			// excluding custom product attributes.

			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $non_variation_attribute['name'],
				'term_id'                => $non_variation_attribute['term_ids'][0],
				'is_variation_attribute' => 0,
				'in_stock'               => 1,
			),
			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $non_variation_attribute['name'],
				'term_id'                => $non_variation_attribute['term_ids'][1],
				'is_variation_attribute' => 0,
				'in_stock'               => 1,
			),
			array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $non_variation_attribute['name'],
				'term_id'                => $non_variation_attribute['term_ids'][2],
				'is_variation_attribute' => 0,
				'in_stock'               => 1,
			),

			// Variation 1: one entry for each of the defined variation attributes.

			array(
				'product_id'             => $variation_1_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute_1['name'],
				'term_id'                => $variation_attribute_1['term_ids'][0],
				'is_variation_attribute' => 1,
				'in_stock'               => 1,
			),
			array(
				'product_id'             => $variation_1_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute_2['name'],
				'term_id'                => $variation_attribute_2['term_ids'][0],
				'is_variation_attribute' => 1,
				'in_stock'               => 1,
			),

			// Variation 2: one entry for the defined value for variation-attribute-1,
			// then one for each of the possible values of variation-attribute-2
			// (the values defined in the parent product).

			array(
				'product_id'             => $variation_2_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute_1['name'],
				'term_id'                => $variation_attribute_1['term_ids'][1],
				'is_variation_attribute' => 1,
				'in_stock'               => 0,
			),
			array(
				'product_id'             => $variation_2_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute_2['name'],
				'term_id'                => $variation_attribute_2['term_ids'][0],
				'is_variation_attribute' => 1,
				'in_stock'               => 0,
			),
			array(
				'product_id'             => $variation_2_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute_2['name'],
				'term_id'                => $variation_attribute_2['term_ids'][1],
				'is_variation_attribute' => 1,
				'in_stock'               => 0,
			),
			array(
				'product_id'             => $variation_2_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute_2['name'],
				'term_id'                => $variation_attribute_2['term_ids'][2],
				'is_variation_attribute' => 1,
				'in_stock'               => 0,
			),
		);

		$actual = $this->get_lookup_table_data();

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
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
				'get_post_type'    => function ( $id ) use ( $product ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return 'product';
					} else {
						return get_post_type( $id );
					}
				},
				'time'             => function () {
					return 100;
				},
				'current_user_can' => function ( $capability, ...$args ) {
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
                // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
				do_action( 'wp_trash_post', $product );
				break;
			case 'delete_post':
                // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
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
				'get_post_type'    => function ( $id ) use ( $product, $variation ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return 'product';
					} elseif ( $id === $variation->get_id() || $id === $variation ) {
						return 'product_variation';
					} else {
						return get_post_type( $id );
					}
				},
				'time'             => function () {
					return 100;
				},
				'current_user_can' => function ( $capability, ...$args ) {
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
				'get_post_type'    => function ( $id ) use ( $product, $variation ) {
					if ( $id === $product->get_id() || $id === $product ) {
						return 'product';
					} elseif ( $id === $variation->get_id() || $id === $variation ) {
						return 'product_variation';
					} else {
						return get_post_type( $id );
					}
				},
				'time'             => function () {
					return 100;
				},
				'current_user_can' => function ( $capability, ...$args ) {
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
				'time' => function () {
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
				'time' => function () {
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
		$changeset_combinations = array(
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
				array( 'stock_status' => ProductStockStatus::IN_STOCK ),
				'update',
			),
			array(
				array( 'manage_stock' => true ),
				'update',
			),
			array(
				array( 'catalog_visibility' => CatalogVisibility::VISIBLE ),
				'creation',
			),
			array(
				array( 'catalog_visibility' => CatalogVisibility::CATALOG ),
				'creation',
			),
			array(
				array( 'catalog_visibility' => CatalogVisibility::SEARCH ),
				'deletion',
			),
			array(
				array( 'catalog_visibility' => CatalogVisibility::HIDDEN ),
				'deletion',
			),
			array(
				array( 'foo' => 'bar' ),
				'none',
			),
		);

		$data = array();
		foreach ( $changeset_combinations as $combination ) {
			$data[] = array( $combination[0], $combination[1], false );
			$data[] = array( $combination[0], $combination[1], true );
		}

		return $data;
	}

	/**
	 * @testdox 'on_product_changed' creates, updates deletes the data for a simple product depending on the changeset when the "direct updates" option is on.
	 *
	 * @dataProvider data_provider_for_test_on_product_changed_with_direct_updates
	 *
	 * @param array  $changeset The changeset to test.
	 * @param string $expected_action The expected performed action, one of 'none', 'creation', 'update' or 'deletion'.
	 * @param bool   $use_optimized_db_access 'true' to use optimized db access for the table update.
	 */
	public function test_on_product_changed_for_simple_product_with_direct_updates( $changeset, $expected_action, $use_optimized_db_access ) {
		if ( $use_optimized_db_access ) {
			update_option( 'woocommerce_attribute_lookup_optimized_updates', 'yes' );
			$this->sut = new LookupDataStore();
		}

		$this->set_direct_update_option( true );

		$attribute         = self::$attributes[0];
		$another_attribute = self::$attributes[1];

		$product = new \WC_Product_Simple();
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$this->set_product_attributes(
			$product,
			array(
				$attribute['name'] => array(
					'id'      => $attribute['id'],
					'options' => array( $attribute['term_ids'][0] ),
				),
			)
		);
		$product->save();
		$product_id         = $product->get_id();
		$another_product_id = $product_id + 100;

		// The product creation will have populated the table, but we want to start clean.
		$this->get_instance_of( DataRegenerator::class )->truncate_lookup_table();

		$this->insert_lookup_table_data( $another_product_id, $another_product_id, $another_attribute['name'], $another_attribute['term_ids'][0], false, true );
		if ( 'creation' !== $expected_action && 'deletion' !== $expected_action ) {
			$this->insert_lookup_table_data( $product_id, $product_id, $attribute['name'], $attribute['term_ids'][0], false, false );
		}

		$this->sut->on_product_changed( $product, $changeset );

		$actual = $this->get_lookup_table_data();

		$expected = array(
			array(
				'product_id'             => $another_product_id,
				'product_or_parent_id'   => $another_product_id,
				'taxonomy'               => $another_attribute['name'],
				'term_id'                => $another_attribute['term_ids'][0],
				'is_variation_attribute' => 0,
				'in_stock'               => 1,
			),
		);

		// Differences:
		// Creation or update: the product is stored as having stock.
		// None: the product remains stored as not having stock.
		if ( 'deletion' !== $expected_action ) {
			$expected[] = array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $attribute['name'],
				'term_id'                => $attribute['term_ids'][0],
				'is_variation_attribute' => 0,
				'in_stock'               => 'creation' === $expected_action || 'update' === $expected_action ? 1 : 0,
			);
		}

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox 'on_product_changed' creates, updates deletes the data for a variable product and if needed its variations depending on the changeset when the "direct updates" option is on.
	 *
	 * @dataProvider data_provider_for_test_on_product_changed_with_direct_updates
	 *
	 * @param array  $changeset The changeset to test.
	 * @param string $expected_action The expected performed action, one of 'none', 'creation', 'update' or 'deletion'.
	 * @param bool   $use_optimized_db_access 'true' to use optimized db access for the table update.
	 */
	public function test_on_variable_product_changed_for_variable_product_with_direct_updates( $changeset, $expected_action, $use_optimized_db_access ) {
		if ( $use_optimized_db_access ) {
			update_option( 'woocommerce_attribute_lookup_optimized_updates', 'yes' );
			$this->sut = new LookupDataStore();
		}

		$this->set_direct_update_option( true );

		$non_variation_attribute = self::$attributes[0];
		$variation_attribute     = self::$attributes[1];
		$another_attribute       = self::$attributes[2];

		$product = new \WC_Product_Variable();
		$this->set_product_attributes(
			$product,
			array(
				$non_variation_attribute['name'] => array(
					'id'      => $non_variation_attribute['id'],
					'options' => array( $non_variation_attribute['term_ids'][0] ),
				),
				$variation_attribute['name']     => array(
					'id'        => $variation_attribute['id'],
					'options'   => array( $variation_attribute['term_ids'][0] ),
					'variation' => true,
				),
			)
		);
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();
		$product_id = $product->get_id();

		$variation = new \WC_Product_Variation();
		$variation->set_attributes(
			array(
				$variation_attribute['name'] => 'term_2_1',
			)
		);
		$variation->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation->set_parent_id( $product_id );
		$variation->save();
		$variation_id = $variation->get_id();

		$product->set_children( array( $variation_id ) );
		\WC_Product_Variable::sync( $product );

		$another_product_id = $variation_id + 100;

		// The product creation will have populated the table, but we want to start clean.
		$this->get_instance_of( DataRegenerator::class )->truncate_lookup_table();

		$this->insert_lookup_table_data( $another_product_id, $another_product_id, $another_attribute['name'], $another_attribute['term_ids'][0], false, true );
		if ( 'creation' !== $expected_action && 'deletion' !== $expected_action ) {
			$this->insert_lookup_table_data( $product_id, $product_id, $non_variation_attribute['name'], $non_variation_attribute['term_ids'][0], false, false );
			$this->insert_lookup_table_data( $variation_id, $product_id, $variation_attribute['name'], $variation_attribute['term_ids'][0], true, false );
		}

		$this->sut->on_product_changed( $product, $changeset );

		$actual = $this->get_lookup_table_data();

		$expected = array(
			array(
				'product_id'             => $another_product_id,
				'product_or_parent_id'   => $another_product_id,
				'taxonomy'               => $another_attribute['name'],
				'term_id'                => $another_attribute['term_ids'][0],
				'is_variation_attribute' => 0,
				'in_stock'               => 1,
			),
		);

		// Differences:
		// Creation: both main product and variation are stored as having stock.
		// Update: main product only is updated as having stock (variation is supposed to get a separate update).
		// None: both main product and variation are still stored as not having stock.
		if ( 'deletion' !== $expected_action ) {
			$expected[] = array(
				'product_id'             => $product_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $non_variation_attribute['name'],
				'term_id'                => $non_variation_attribute['term_ids'][0],
				'is_variation_attribute' => 0,
				'in_stock'               => 'none' === $expected_action ? 0 : 1,
			);

			$expected[] = array(
				'product_id'             => $variation_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute['name'],
				'term_id'                => $variation_attribute['term_ids'][0],
				'is_variation_attribute' => 1,
				'in_stock'               => 'creation' === $expected_action ? 1 : 0,
			);
		}

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox 'on_product_changed' creates, updates deletes the data for a variation depending on the changeset when the "direct updates" option is on.
	 *
	 * @dataProvider data_provider_for_test_on_product_changed_with_direct_updates
	 *
	 * @param array  $changeset The changeset to test.
	 * @param string $expected_action The expected performed action, one of 'none', 'creation', 'update' or 'deletion'.
	 * @param bool   $use_optimized_db_access 'true' to use optimized db access for the table update.
	 */
	public function test_on_variation_changed_for_variable_product_with_direct_updates( $changeset, $expected_action, $use_optimized_db_access ) {
		if ( $use_optimized_db_access ) {
			update_option( 'woocommerce_attribute_lookup_optimized_updates', 'yes' );
			$this->sut = new LookupDataStore();
		}

		$non_variation_attribute = self::$attributes[0];
		$variation_attribute     = self::$attributes[1];
		$another_attribute       = self::$attributes[2];

		$this->set_direct_update_option( true );

		$product = new \WC_Product_Variable();
		$this->set_product_attributes(
			$product,
			array(
				$non_variation_attribute['name'] => array(
					'id'      => $non_variation_attribute['id'],
					'options' => array( $non_variation_attribute['term_ids'][0] ),
				),
				$variation_attribute['name']     => array(
					'id'        => $variation_attribute['id'],
					'options'   => array( $variation_attribute['term_ids'][0] ),
					'variation' => true,
				),
			)
		);
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();
		$product_id = $product->get_id();

		$variation = new \WC_Product_Variation();
		$variation->set_attributes(
			array(
				$variation_attribute['name'] => 'term_2_1',
			)
		);
		$variation->set_stock_status( ProductStockStatus::IN_STOCK );
		$variation->set_parent_id( $product_id );
		$variation->save();
		$variation_id = $variation->get_id();

		$product->set_children( array( $variation_id ) );
		\WC_Product_Variable::sync( $product );

		$another_product_id = $variation_id + 100;

		// The product creation will have populated the table, but we want to start clean.
		$this->get_instance_of( DataRegenerator::class )->truncate_lookup_table();

		$this->insert_lookup_table_data( $another_product_id, $another_product_id, $another_attribute['name'], $another_attribute['term_ids'][0], false, true );
		if ( 'creation' !== $expected_action && 'deletion' !== $expected_action ) {
			$this->insert_lookup_table_data( $variation_id, $product_id, $variation_attribute['name'], $variation_attribute['term_ids'][0], true, false );
		}

		$this->sut->on_product_changed( $variation, $changeset );

		$actual = $this->get_lookup_table_data();

		$expected = array(
			array(
				'product_id'             => $another_product_id,
				'product_or_parent_id'   => $another_product_id,
				'taxonomy'               => $another_attribute['name'],
				'term_id'                => $another_attribute['term_ids'][0],
				'is_variation_attribute' => 0,
				'in_stock'               => 1,
			),
		);

		// Differences:
		// Creation or update: the variation is stored as having stock.
		// None: the variation is still stored as not having stock.
		if ( 'deletion' !== $expected_action ) {
			$expected[] = array(
				'product_id'             => $variation_id,
				'product_or_parent_id'   => $product_id,
				'taxonomy'               => $variation_attribute['name'],
				'term_id'                => $variation_attribute['term_ids'][0],
				'is_variation_attribute' => 1,
				'in_stock'               => 'none' === $expected_action ? 0 : 1,
			);
		}

		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
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
			$attribute->set_variation( $attribute_data['variation'] ?? false );
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

		foreach ( $result as &$row ) {
			foreach ( $row as $column_name => $value ) {
				if ( 'taxonomy' !== $column_name ) {
					$row[ $column_name ] = intval( $value );
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
