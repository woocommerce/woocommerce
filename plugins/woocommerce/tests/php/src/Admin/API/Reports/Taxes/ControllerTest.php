<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Taxes;

use Automattic\WooCommerce\Admin\API\Reports\Taxes\Controller;
use WC_Unit_Test_Case;

/**
 * Tests for the Taxes report export methods.
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
		remove_all_filters( 'woocommerce_report_taxes_export_columns' );
		remove_all_filters( 'woocommerce_report_taxes_prepare_export_item' );
	}

	/**
	 * @testdox get_export_columns returns the default column set.
	 */
	public function test_get_export_columns_returns_defaults(): void {
		$columns = $this->sut->get_export_columns();

		$this->assertArrayHasKey( 'tax_code', $columns );
		$this->assertArrayHasKey( 'rate', $columns );
		$this->assertArrayHasKey( 'total_tax', $columns );
		$this->assertArrayHasKey( 'order_tax', $columns );
		$this->assertArrayHasKey( 'shipping_tax', $columns );
		$this->assertArrayHasKey( 'orders_count', $columns );
	}

	/**
	 * @testdox get_export_columns allows adding a column via filter.
	 */
	public function test_get_export_columns_filter_can_add_column(): void {
		add_filter(
			'woocommerce_report_taxes_export_columns',
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
			'woocommerce_report_taxes_export_columns',
			function ( $columns ) {
				unset( $columns['rate'] );
				return $columns;
			}
		);

		$columns = $this->sut->get_export_columns();

		$this->assertArrayNotHasKey( 'rate', $columns, 'Filter should be able to remove a column' );
	}

	/**
	 * @testdox prepare_item_for_export allows adding extra columns via filter.
	 */
	public function test_prepare_item_for_export_filter_can_add_column(): void {
		add_filter(
			'woocommerce_report_taxes_prepare_export_item',
			function ( $export_item ) {
				$export_item['currency'] = 'USD';
				return $export_item;
			},
			10
		);

		$item = array(
			'tax_rate_id'  => 1,
			'country'      => 'US',
			'state'        => 'CA',
			'name'         => 'State Tax',
			'priority'     => 1,
			'tax_rate'     => '8.25',
			'total_tax'    => 82.50,
			'order_tax'    => 75.00,
			'shipping_tax' => 7.50,
			'orders_count' => 10,
		);

		$export_item = $this->sut->prepare_item_for_export( $item );

		$this->assertArrayHasKey( 'currency', $export_item, 'Filter should be able to add a currency column value' );
		$this->assertSame( 'USD', $export_item['currency'] );
	}

	/**
	 * @testdox prepare_item_for_export passes the original item to the filter.
	 */
	public function test_prepare_item_for_export_filter_receives_original_item(): void {
		$received_item = null;

		add_filter(
			'woocommerce_report_taxes_prepare_export_item',
			function ( $export_item, $item ) use ( &$received_item ) {
				$received_item = $item;
				return $export_item;
			},
			10,
			2
		);

		$item = array(
			'tax_rate_id'  => 2,
			'country'      => 'GB',
			'state'        => '',
			'name'         => 'VAT',
			'priority'     => 1,
			'tax_rate'     => '20.00',
			'total_tax'    => 200.00,
			'order_tax'    => 200.00,
			'shipping_tax' => 0.00,
			'orders_count' => 5,
		);

		$this->sut->prepare_item_for_export( $item );

		$this->assertSame( $item, $received_item, 'Filter should receive the original report item as second argument' );
	}
}
