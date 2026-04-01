<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Revenue\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Revenue\Stats\Controller;
use WC_Unit_Test_Case;

/**
 * Tests for the Revenue Stats report export methods.
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
		remove_all_filters( 'woocommerce_report_revenue_stats_export_columns' );
		remove_all_filters( 'woocommerce_report_revenue_stats_prepare_export_item' );
	}

	/**
	 * @testdox get_export_columns returns the default column set.
	 */
	public function test_get_export_columns_returns_defaults(): void {
		$columns = $this->sut->get_export_columns();

		$this->assertArrayHasKey( 'date', $columns );
		$this->assertArrayHasKey( 'orders_count', $columns );
		$this->assertArrayHasKey( 'gross_sales', $columns );
		$this->assertArrayHasKey( 'refunds', $columns );
		$this->assertArrayHasKey( 'coupons', $columns );
		$this->assertArrayHasKey( 'net_revenue', $columns );
		$this->assertArrayHasKey( 'taxes', $columns );
		$this->assertArrayHasKey( 'shipping', $columns );
		$this->assertArrayHasKey( 'total_sales', $columns );
	}

	/**
	 * @testdox get_export_columns allows adding a column via filter.
	 */
	public function test_get_export_columns_filter_can_add_column(): void {
		add_filter(
			'woocommerce_report_revenue_stats_export_columns',
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
			'woocommerce_report_revenue_stats_export_columns',
			function ( $columns ) {
				unset( $columns['coupons'] );
				return $columns;
			}
		);

		$columns = $this->sut->get_export_columns();

		$this->assertArrayNotHasKey( 'coupons', $columns, 'Filter should be able to remove a column' );
	}

	/**
	 * @testdox prepare_item_for_export returns the default export row.
	 */
	public function test_prepare_item_for_export_returns_defaults(): void {
		$item = array(
			'date_start' => '2024-01-01',
			'subtotals'  => array(
				'orders_count' => 5,
				'gross_sales'  => 100.00,
				'refunds'      => 10.00,
				'coupons'      => 5.00,
				'net_revenue'  => 85.00,
				'taxes'        => 8.50,
				'shipping'     => 10.00,
				'total_sales'  => 95.00,
			),
		);

		$export_item = $this->sut->prepare_item_for_export( $item );

		$this->assertSame( '2024-01-01', $export_item['date'] );
		$this->assertSame( 5, $export_item['orders_count'] );
		$this->assertSame( '100.00', $export_item['gross_sales'] );
		$this->assertSame( '85.00', $export_item['net_revenue'] );
	}

	/**
	 * @testdox prepare_item_for_export allows adding extra columns via filter.
	 */
	public function test_prepare_item_for_export_filter_can_add_column(): void {
		add_filter(
			'woocommerce_report_revenue_stats_prepare_export_item',
			function ( $export_item ) {
				$export_item['currency'] = 'USD';
				return $export_item;
			},
			10
		);

		$item = array(
			'date_start' => '2024-01-01',
			'subtotals'  => array(
				'orders_count' => 1,
				'gross_sales'  => 50.00,
				'refunds'      => 0.00,
				'coupons'      => 0.00,
				'net_revenue'  => 50.00,
				'taxes'        => 5.00,
				'shipping'     => 5.00,
				'total_sales'  => 50.00,
			),
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
			'woocommerce_report_revenue_stats_prepare_export_item',
			function ( $export_item, $item ) use ( &$received_item ) {
				$received_item = $item;
				return $export_item;
			},
			10,
			2
		);

		$item = array(
			'date_start' => '2024-06-15',
			'subtotals'  => array(
				'orders_count' => 3,
				'gross_sales'  => 75.00,
				'refunds'      => 0.00,
				'coupons'      => 0.00,
				'net_revenue'  => 75.00,
				'taxes'        => 7.50,
				'shipping'     => 7.50,
				'total_sales'  => 75.00,
			),
		);

		$this->sut->prepare_item_for_export( $item );

		$this->assertSame( $item, $received_item, 'Filter should receive the original report item as second argument' );
	}
}
