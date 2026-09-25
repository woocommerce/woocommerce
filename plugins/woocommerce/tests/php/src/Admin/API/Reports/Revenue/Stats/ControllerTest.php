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
	 * @testdox get_export_columns does not add core schema fields that the export leaves out.
	 */
	public function test_get_export_columns_skips_unexported_core_fields(): void {
		$columns = $this->sut->get_export_columns();

		foreach ( array( 'num_items_sold', 'coupons_count', 'products', 'segments' ) as $key ) {
			$this->assertArrayNotHasKey( $key, $columns, "Core field {$key} must not be treated as an extension column" );
		}
	}

	/**
	 * @testdox Export includes scalar fields that extensions add to the report schema.
	 */
	public function test_export_includes_fields_added_to_schema(): void {
		$this->add_extension_schema_fields();

		$columns = $this->sut->get_export_columns();

		$this->assertSame( 'Cost of goods', $columns['cost_of_goods'] ?? null, 'The description should be the label, without its trailing period' );
		$this->assertSame( 'Profitable days', $columns['profitable_days'] ?? null, 'The title should be preferred over the description' );
		$this->assertSame( 'Margin', $columns['cogs_margin'] ?? null, 'Fields only declared on the interval subtotals should be exported too' );
		$this->assertArrayNotHasKey( 'cogs_breakdown', $columns, 'Object fields cannot be written to a CSV cell' );
		$this->assertSame( 'Date', $columns['date'], 'An extension field must not replace a core export column' );
		$this->assertSame( array( 'cost_of_goods', 'profitable_days', 'cogs_margin' ), array_slice( array_keys( $columns ), -3 ), 'Extension columns should follow the core columns' );

		$item                                 = $this->get_report_item();
		$item['subtotals']['cost_of_goods']   = '12.5';
		$item['subtotals']['profitable_days'] = 3;
		$item['subtotals']['cogs_margin']     = 0.25;
		$item['subtotals']['cogs_breakdown']  = array( 'a' => 1 );
		$item['subtotals']['date']            = 'not a date';

		$export_item = $this->sut->prepare_item_for_export( $item );

		$this->assertSame( '12.50', $export_item['cost_of_goods'], 'Currency fields should use the store precision' );
		$this->assertSame( 3, $export_item['profitable_days'], 'Other numeric fields should be exported as-is' );
		$this->assertSame( 0.25, $export_item['cogs_margin'] );
		$this->assertArrayNotHasKey( 'cogs_breakdown', $export_item );
		$this->assertSame( '2024-01-01', $export_item['date'] );
	}

	/**
	 * @testdox Export leaves an extension column empty when an interval has no usable value for it.
	 */
	public function test_prepare_item_for_export_leaves_unusable_extension_values_empty(): void {
		$this->add_extension_schema_fields();

		$item                                 = $this->get_report_item();
		$item['subtotals']['profitable_days'] = array( 3 );

		$export_item = $this->sut->prepare_item_for_export( $item );

		$this->assertArrayHasKey( 'cost_of_goods', $export_item );
		$this->assertNull( $export_item['cost_of_goods'], 'A missing value should leave the cell empty' );
		$this->assertNull( $export_item['profitable_days'], 'A non-scalar value should leave the cell empty' );
	}

	/**
	 * @testdox Export filters can still relabel and override extension columns.
	 */
	public function test_export_filters_override_extension_columns(): void {
		$this->add_extension_schema_fields();
		add_filter(
			'woocommerce_report_revenue_stats_export_columns',
			function ( $columns ) {
				$columns['cost_of_goods'] = 'COGS';
				return $columns;
			}
		);
		add_filter(
			'woocommerce_report_revenue_stats_prepare_export_item',
			function ( $export_item ) {
				$export_item['cost_of_goods'] = 'custom';
				return $export_item;
			}
		);

		$columns     = $this->sut->get_export_columns();
		$export_item = $this->sut->prepare_item_for_export( $this->get_report_item() );

		$this->assertSame( 'COGS', $columns['cost_of_goods'] );
		$this->assertSame( 'custom', $export_item['cost_of_goods'] );
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

	/**
	 * Add report fields to the totals schema the way the Cost of Goods extension does.
	 */
	private function add_extension_schema_fields(): void {
		add_filter(
			'woocommerce_rest_report_revenue_stats_schema',
			function ( $properties ) {
				$properties['totals']['properties'] += array(
					'cost_of_goods'   => array(
						'description' => 'Cost of goods.',
						'type'        => 'number',
						'format'      => 'currency',
					),
					'profitable_days' => array(
						'title'       => 'Profitable days',
						'description' => 'Number of days with a profit.',
						'type'        => 'integer',
					),
					'cogs_breakdown'  => array(
						'description' => 'Cost breakdown.',
						'type'        => 'object',
					),
					'date'            => array(
						'description' => 'Date the costs were last updated.',
						'type'        => 'string',
					),
				);
				$properties['intervals']['items']['properties']['subtotals']['properties']['cogs_margin'] = array(
					'title' => 'Margin',
					'type'  => 'number',
				);
				return $properties;
			}
		);
	}

	/**
	 * Get a report interval with only the core subtotals.
	 *
	 * @return array
	 */
	private function get_report_item(): array {
		return array(
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
	}
}
