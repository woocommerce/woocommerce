<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Variations;

use Automattic\WooCommerce\Admin\API\Reports\Variations\Controller;
use WC_Unit_Test_Case;

/**
 * Tests for the Variations report export methods.
 */
class ControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Controller
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Controller();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'woocommerce_report_variations_export_columns' );
		remove_all_filters( 'woocommerce_report_variations_prepare_export_item' );
	}

	/**
	 * @testdox get_export_columns returns the default column set.
	 */
	public function test_get_export_columns_returns_defaults(): void {
		$columns = $this->sut->get_export_columns();

		$this->assertArrayHasKey( 'product_name', $columns );
		$this->assertArrayHasKey( 'sku', $columns );
		$this->assertArrayHasKey( 'items_sold', $columns );
		$this->assertArrayHasKey( 'net_revenue', $columns );
		$this->assertArrayHasKey( 'orders_count', $columns );
	}

	/**
	 * @testdox get_export_columns allows adding a column via filter.
	 */
	public function test_get_export_columns_filter_can_add_column(): void {
		add_filter(
			'woocommerce_report_variations_export_columns',
			function ( $columns ) {
				$columns['currency'] = 'Currency';
				return $columns;
			}
		);

		$columns = $this->sut->get_export_columns();

		$this->assertArrayHasKey( 'currency', $columns, 'Filter should be able to add a currency column' );
	}

	/**
	 * @testdox get_export_columns allows removing a column via filter.
	 */
	public function test_get_export_columns_filter_can_remove_column(): void {
		add_filter(
			'woocommerce_report_variations_export_columns',
			function ( $columns ) {
				unset( $columns['sku'] );
				return $columns;
			}
		);

		$columns = $this->sut->get_export_columns();

		$this->assertArrayNotHasKey( 'sku', $columns, 'Filter should be able to remove a column' );
	}

	/**
	 * @testdox prepare_item_for_export allows adding extra columns via filter.
	 */
	public function test_prepare_item_for_export_filter_can_add_column(): void {
		add_filter(
			'woocommerce_report_variations_prepare_export_item',
			function ( $export_item ) {
				$export_item['currency'] = 'CAD';
				return $export_item;
			},
			10
		);

		$item = array(
			'items_sold'    => 10,
			'net_revenue'   => 250.00,
			'orders_count'  => 8,
			'extended_info' => array(
				'name'           => 'Test Product - Blue',
				'sku'            => 'TEST-BLUE',
				'attributes'     => array(),
				'stock_status'   => 'instock',
				'stock_quantity' => 5,
				'manage_stock'   => true,
			),
		);

		$export_item = $this->sut->prepare_item_for_export( $item );

		$this->assertArrayHasKey( 'currency', $export_item, 'Filter should be able to add a currency column value' );
		$this->assertSame( 'CAD', $export_item['currency'] );
	}

	/**
	 * @testdox prepare_item_for_export passes the original item to the filter.
	 */
	public function test_prepare_item_for_export_filter_receives_original_item(): void {
		$received_item = null;

		add_filter(
			'woocommerce_report_variations_prepare_export_item',
			function ( $export_item, $item ) use ( &$received_item ) {
				$received_item = $item;
				return $export_item;
			},
			10,
			2
		);

		$item = array(
			'items_sold'    => 3,
			'net_revenue'   => 60.00,
			'orders_count'  => 2,
			'extended_info' => array(
				'name'           => 'Test Product - Red',
				'sku'            => 'TEST-RED',
				'attributes'     => array(),
				'stock_status'   => 'outofstock',
				'stock_quantity' => 0,
				'manage_stock'   => true,
			),
		);

		$this->sut->prepare_item_for_export( $item );

		$this->assertSame( $item, $received_item, 'Filter should receive the original report item as second argument' );
	}
}
