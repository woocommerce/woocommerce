<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Reports;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Reports\HposLegacyOrderReportQueryBuilder;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the HPOS-backed legacy order report query builder.
 */
class HposLegacyOrderReportQueryBuilderTest extends WC_Unit_Test_Case {

	/**
	 * Original HPOS usage state.
	 *
	 * @var bool
	 */
	private $original_hpos_usage;

	/**
	 * Original GMT offset.
	 *
	 * @var string
	 */
	private $original_gmt_offset;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_hpos_usage = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->original_gmt_offset = (string) get_option( 'gmt_offset' );
	}

	/**
	 * Restore test fixtures.
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot_feature_and_usage( $this->original_hpos_usage );
		update_option( 'gmt_offset', $this->original_gmt_offset );
		parent::tearDown();
	}

	/**
	 * @testdox Should build HPOS-backed clauses with the expected columns, joins and date bounds.
	 */
	public function test_build_query_uses_hpos_clauses(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );
		update_option( 'gmt_offset', 2 );

		$query = ( new HposLegacyOrderReportQueryBuilder() )->build_query(
			array(
				'data'                => array(
					'_order_total'    => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_sales',
					),
					'_order_shipping' => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'total_shipping',
					),
					'_refund_amount'  => array(
						'type'     => 'meta',
						'function' => 'SUM',
						'name'     => 'refund_amount',
					),
					'post_date'       => array(
						'type'     => 'post_data',
						'function' => null,
						'name'     => 'post_date',
					),
				),
				'where'               => array(
					array(
						'key'      => 'posts.ID',
						'value'    => array( '123' ),
						'operator' => 'IN',
					),
				),
				'where_meta'          => array(
					// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					array(
						'meta_key'   => '_customer_user',
						'meta_value' => '0',
						'operator'   => '>',
					),
					// phpcs:enable
				),
				'group_by'            => 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date), ID',
				'order_by'            => 'posts.post_date ASC',
				'limit'               => '',
				'filter_range'        => true,
				'order_types'         => array( 'shop_order' ),
				'order_status'        => array( OrderStatus::COMPLETED ),
				'parent_order_status' => false,
			),
			strtotime( '2026-06-01 00:00:00' ),
			strtotime( '2026-06-30 00:00:00' )
		);

		$local_date_expression = 'DATE_ADD(orders.date_created_gmt, INTERVAL 7200 SECOND)';

		$this->assertStringContainsString( 'wc_orders AS orders', $query['from'] );
		$this->assertStringContainsString( 'orders.total_amount', $query['select'] );
		$this->assertStringContainsString( 'op_data.shipping_total_amount', $query['select'] );
		$this->assertStringContainsString( 'meta__refund_amount.meta_value', $query['select'] );
		$this->assertStringContainsString( 'wc_order_operational_data AS op_data', $query['join'] );
		$this->assertStringContainsString( 'wc_orders_meta AS meta__refund_amount', $query['join'] );
		$this->assertStringNotContainsString( 'meta__order_total', $query['join'] );
		$this->assertStringNotContainsString( 'meta__customer_user', $query['join'] );
		$this->assertStringContainsString( 'orders.customer_id > \'0\'', $query['where'] );
		$this->assertStringContainsString( 'orders.id IN (\'123\')', $query['where'] );
		$this->assertStringContainsString( "orders.date_created_gmt >= '2026-05-31 22:00:00'", $query['where'] );
		$this->assertStringContainsString( "orders.date_created_gmt < '2026-06-30 22:00:00'", $query['where'] );
		$this->assertSame(
			"GROUP BY YEAR({$local_date_expression}), MONTH({$local_date_expression}), DAY({$local_date_expression}), orders.id",
			$query['group_by']
		);
		$this->assertSame( "ORDER BY {$local_date_expression} ASC", $query['order_by'] );
	}
}
