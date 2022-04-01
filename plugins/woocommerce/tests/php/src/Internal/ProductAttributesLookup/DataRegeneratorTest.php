<?php
/**
 * DataRegeneratorTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup;

use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Testing\Tools\FakeQueue;

/**
 * Tests for the DataRegenerator class.
 * @package Automattic\WooCommerce\Tests\Internal\ProductAttributesLookup
 */
class DataRegeneratorTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var DataRegenerator
	 */
	private $sut;

	/**
	 * @var LookupDataStore
	 */
	private $lookup_data_store;

	/**
	 * @var string
	 */
	private $lookup_table_name;

	/**
	 * @var FakeQueue
	 */
	private $queue;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		global $wpdb;

		parent::setUp();

		$this->lookup_table_name = $wpdb->prefix . 'wc_product_attributes_lookup';

		// phpcs:disable Squiz.Commenting
		$this->lookup_data_store = new class() extends LookupDataStore {
			public $passed_products = array();

			public function create_data_for_product( $product ) {
				$this->passed_products[] = $product;
			}
		};
		// phpcs:enable Squiz.Commenting

		// This is needed to prevent the hook to act on the already registered LookupDataStore class.
		remove_all_actions( 'woocommerce_run_product_attribute_lookup_regeneration_callback' );

		$container = wc_get_container();
		$container->reset_all_resolved();
		$container->replace( LookupDataStore::class, $this->lookup_data_store );
		$this->sut = $container->get( DataRegenerator::class );

		$this->register_legacy_proxy_class_mocks(
			array(
				\WC_Queue::class => new FakeQueue(),
			)
		);
		$this->queue = $this->get_legacy_instance_of( \WC_Queue::class );
	}

	/**
	 * @testdox `initiate_regeneration` creates the lookup table, deleting it first if it already existed.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $previously_existing True to create a lookup table beforehand.
	 */
	public function test_initiate_regeneration_creates_looukp_table( $previously_existing ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $this->lookup_table_name );

		if ( $previously_existing ) {
			$wpdb->query( 'CREATE TABLE ' . $this->lookup_table_name . ' (foo int);' );
		}

		$this->sut->initiate_regeneration();

		// Try to insert a row to verify that the table exists.
		// We can't use the regular table existence detection mechanisms because PHPUnit creates all tables as temporary.
		$wpdb->query( 'INSERT INTO ' . $this->lookup_table_name . " VALUES (1, 1, 'taxonomy', 1, 1, 1 )" );
		$value = $wpdb->get_var( 'SELECT product_id FROM ' . $this->lookup_table_name . ' LIMIT 1' );
		$this->assertEquals( 1, $value );

		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * @testdox `initiate_regeneration` initializes the transient options, and enqueues the first step for time()+1.
	 */
	public function test_initiate_regeneration_initializes_temporary_options_and_enqueues_regeneration_step() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_products' => function( $args ) {
					return array( 100 );
				},
				'time'            => function() {
					return 1000;
				},
			)
		);

		$this->sut->initiate_regeneration();

		$this->assertEquals( 100, get_option( 'woocommerce_attribute_lookup_last_product_id_to_process' ) );
		$this->assertEquals( 0, get_option( 'woocommerce_attribute_lookup_last_products_page_processed' ) );
		$this->assertFalse( get_option( 'woocommerce_attribute_lookup_enabled' ) );

		$expected_enqueued = array(
			'method'    => 'schedule_single',
			'args'      => array(),
			'timestamp' => 1001,
			'hook'      => 'woocommerce_run_product_attribute_lookup_regeneration_callback',
			'group'     => 'woocommerce-db-updates',
		);
		$actual_enqueued   = current( $this->queue->get_methods_called() );

		$this->assertEquals( sort( $expected_enqueued ), sort( $actual_enqueued ) );
	}

	/**
	 * @testdox `initiate_regeneration` finalizes the regeneration process without enqueueing any step if the db is empty.
	 *
	 * @testWith [false]
	 *           [[]]
	 *
	 * @param mixed $get_products_result Result from wc_get_products.
	 */
	public function test_initiate_regeneration_does_not_enqueues_regeneration_step_when_no_products( $get_products_result ) {
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_products' => function( $args ) use ( $get_products_result ) {
					return $get_products_result;
				},
			)
		);

		$this->sut->initiate_regeneration();

		$this->assertFalse( get_option( 'woocommerce_attribute_lookup_last_product_id_to_process' ) );
		$this->assertFalse( get_option( 'woocommerce_attribute_lookup_last_products_page_processed' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_attribute_lookup_enabled' ) );
		$this->assertEmpty( $this->queue->get_methods_called() );
	}

	/**
	 * @testdox `initiate_regeneration` processes one chunk of products IDs and enqueues next step if there are more products available.
	 */
	public function test_initiate_regeneration_correctly_processes_ids_and_enqueues_next_step() {
		$requested_products_pages = array();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_products' => function( $args ) use ( &$requested_products_pages ) {
					if ( 'DESC' === current( $args['orderby'] ) ) {
						return array( 100 );
					} else {
						$requested_products_pages[] = $args['page'];
						return array( 1, 2, 3 );
					}
				},
				'time'            => function() {
					return 1000;
				},
			)
		);

		$this->sut->initiate_regeneration();
		$this->queue->clear_methods_called();

		update_option( 'woocommerce_attribute_lookup_last_products_page_processed', 7 );

		do_action( 'woocommerce_run_product_attribute_lookup_regeneration_callback' );

		$this->assertEquals( array( 1, 2, 3 ), $this->lookup_data_store->passed_products );
		$this->assertEquals( array( 8 ), $requested_products_pages );
		$this->assertEquals( 8, get_option( 'woocommerce_attribute_lookup_last_products_page_processed' ) );

		$expected_enqueued = array(
			'method'    => 'schedule_single',
			'args'      => array(),
			'timestamp' => 1001,
			'hook'      => 'woocommerce_run_product_attribute_lookup_regeneration_callback',
			'group'     => 'woocommerce-db-updates',
		);
		$actual_enqueued   = current( $this->queue->get_methods_called() );
		$this->assertEquals( sort( $expected_enqueued ), sort( $actual_enqueued ) );
	}

	/**
	 * @testdox `initiate_regeneration` finishes regeneration when the max product id is reached or no more products are returned.
	 *
	 * @testWith [[98,99,100]]
	 *           [[99,100,101]]
	 *           [[]]
	 *
	 * @param array $product_ids The products ids that wc_get_products will return.
	 */
	public function test_initiate_regeneration_finishes_when_no_more_products_available( $product_ids ) {
		$requested_products_pages = array();

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_products' => function( $args ) use ( &$requested_products_pages, $product_ids ) {
					if ( 'DESC' === current( $args['orderby'] ) ) {
						return array( 100 );
					} else {
						$requested_products_pages[] = $args['page'];
						return $product_ids;
					}
				},
			)
		);

		$this->sut->initiate_regeneration();
		$this->queue->clear_methods_called();

		do_action( 'woocommerce_run_product_attribute_lookup_regeneration_callback' );

		$this->assertEquals( $product_ids, $this->lookup_data_store->passed_products );
		$this->assertFalse( get_option( 'woocommerce_attribute_lookup_last_product_id_to_process' ) );
		$this->assertFalse( get_option( 'woocommerce_attribute_lookup_last_products_page_processed' ) );
		$this->assertEquals( 'no', get_option( 'woocommerce_attribute_lookup_enabled' ) );
		$this->assertEmpty( $this->queue->get_methods_called() );
	}
}
