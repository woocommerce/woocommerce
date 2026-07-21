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
	 * Original timezone string.
	 *
	 * @var string
	 */
	private $original_timezone_string;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_hpos_usage      = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->original_gmt_offset      = (string) get_option( 'gmt_offset' );
		$this->original_timezone_string = (string) get_option( 'timezone_string' );
	}

	/**
	 * Restore test fixtures.
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot_feature_and_usage( $this->original_hpos_usage );
		update_option( 'gmt_offset', $this->original_gmt_offset );
		update_option( 'timezone_string', $this->original_timezone_string );
		parent::tearDown();
	}

	/**
	 * @testdox Should build HPOS-backed clauses with the expected columns, joins and date bounds.
	 */
	public function test_build_query_uses_hpos_clauses(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );
		update_option( 'timezone_string', '' );
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

	/**
	 * @testdox Should resolve date bounds and row buckets with the DST offset for the report dates, not the current offset.
	 */
	public function test_build_query_is_dst_aware_for_named_timezones(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );
		// Europe/Berlin is UTC+1 in winter and UTC+2 in summer, so a single cached offset is wrong
		// for at least one of the two ranges below.
		update_option( 'timezone_string', 'Europe/Berlin' );

		$builder = new HposLegacyOrderReportQueryBuilder();

		$base_args = array(
			'data'         => array(
				'post_date' => array(
					'type'     => 'post_data',
					'function' => null,
					'name'     => 'post_date',
				),
			),
			'group_by'     => 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)',
			'filter_range' => true,
			'order_types'  => array( 'shop_order' ),
		);

		// Winter range: Berlin is UTC+1, so local midnight maps back one hour.
		$winter = $builder->build_query(
			$base_args,
			strtotime( '2026-01-01 00:00:00' ),
			strtotime( '2026-01-31 00:00:00' )
		);
		$this->assertStringContainsString( "orders.date_created_gmt >= '2025-12-31 23:00:00'", $winter['where'] );
		$this->assertStringContainsString( "orders.date_created_gmt < '2026-01-31 23:00:00'", $winter['where'] );

		// Summer range: Berlin is UTC+2, so the same local midnight maps back two hours.
		$summer = $builder->build_query(
			$base_args,
			strtotime( '2026-07-01 00:00:00' ),
			strtotime( '2026-07-31 00:00:00' )
		);
		$this->assertStringContainsString( "orders.date_created_gmt >= '2026-06-30 22:00:00'", $summer['where'] );
		$this->assertStringContainsString( "orders.date_created_gmt < '2026-07-31 22:00:00'", $summer['where'] );

		// Row bucketing converts per row with CONVERT_TZ so DST is honoured (with a fixed-offset
		// fallback for servers without timezone tables), rather than a single fixed shift.
		$this->assertStringContainsString( "IFNULL(CONVERT_TZ(orders.date_created_gmt, '+00:00', 'Europe/Berlin')", $summer['group_by'] );
	}

	/**
	 * @testdox Should build order-item joins and predicates for the taxes-report shape (order_item_meta data plus array meta_key where_meta).
	 */
	public function test_build_query_translates_order_item_meta_joins_and_predicates(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );

		$query = ( new HposLegacyOrderReportQueryBuilder() )->build_query(
			array(
				'data'         => array(
					'tax_amount' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'tax',
						'function'        => 'SUM',
						'name'            => 'tax_amount',
					),
				),
				'where_meta'   => array(
					// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					array(
						'type'       => 'order_item_meta',
						'meta_key'   => array( '_line_tax', '_line_subtotal' ),
						'meta_value' => '0',
						'operator'   => '!=',
					),
					// phpcs:enable
				),
				'filter_range' => false,
				'order_types'  => array( 'shop_order' ),
			),
			0,
			0
		);

		$this->assertStringContainsString( 'woocommerce_order_items AS order_items', $query['join'] );
		$this->assertStringContainsString( 'woocommerce_order_itemmeta AS order_item_meta_tax_amount', $query['join'] );
		$this->assertStringContainsString( 'orders.id = order_items.order_id', $query['join'] );
		$this->assertStringContainsString( "order_item_meta__line_tax_array.meta_key   IN ('_line_tax','_line_subtotal')", $query['where'] );
		$this->assertStringContainsString( "order_item_meta__line_tax_array.meta_value != '0'", $query['where'] );
	}

	/**
	 * @testdox Should build the coupon-usage shape: order_item data entry with a bare order_item_type where key passed through untranslated.
	 */
	public function test_build_query_translates_coupon_usage_shape(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );

		$query = ( new HposLegacyOrderReportQueryBuilder() )->build_query(
			array(
				'data'         => array(
					'order_item_id' => array(
						'type'            => 'order_item',
						'order_item_type' => 'coupon',
						'function'        => 'COUNT',
						'name'            => 'order_coupon_count',
					),
				),
				'where'        => array(
					array(
						'key'      => 'order_item_type',
						'value'    => 'coupon',
						'operator' => '=',
					),
				),
				'filter_range' => false,
				'order_types'  => array( 'shop_order' ),
			),
			0,
			0
		);

		$this->assertStringContainsString( 'COUNT( order_items.order_item_id) as order_coupon_count', $query['select'] );
		$this->assertStringContainsString( 'woocommerce_order_items AS order_items', $query['join'] );
		$this->assertStringContainsString( "AND order_item_type = 'coupon'", $query['where'] );
	}

	/**
	 * @testdox Should map parent_meta order totals to the parent orders table and honour parent_order_status.
	 */
	public function test_build_query_translates_refund_lines_shape(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );

		$builder = new HposLegacyOrderReportQueryBuilder();

		$args = array(
			'data'                => array(
				'_order_total' => array(
					'type'     => 'parent_meta',
					'function' => '',
					'name'     => 'parent_total',
				),
				'ID'           => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_refunds',
					'distinct' => true,
				),
			),
			'filter_range'        => false,
			'order_types'         => array( 'shop_order_refund' ),
			'order_status'        => false,
			'parent_order_status' => array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ),
		);

		$query = $builder->build_query( $args, 0, 0 );

		$this->assertStringContainsString( 'parent_orders.total_amount as parent_total', $query['select'] );
		$this->assertStringContainsString( 'COUNT(DISTINCT orders.id) as total_refunds', $query['select'] );
		$this->assertStringContainsString( 'AS parent_orders ON orders.parent_order_id = parent_orders.id', $query['join'] );
		$this->assertStringContainsString( "orders.type 	IN ( 'shop_order_refund' )", $query['where'] );
		// With no own-status filter the parent status must match strictly (no IS NULL escape).
		$this->assertStringContainsString( "parent_orders.status IN ( 'wc-completed','wc-processing' )", $query['where'] );
		$this->assertStringNotContainsString( 'parent_orders.id IS NULL', $query['where'] );

		// With an own-status filter, parentless orders are allowed through.
		$args['order_status'] = array( OrderStatus::COMPLETED );
		$query                = $builder->build_query( $args, 0, 0 );
		$this->assertStringContainsString( 'OR parent_orders.id IS NULL', $query['where'] );
	}

	/**
	 * @testdox Should build joins required by typed where rows even without a matching data entry, like the CPT path.
	 */
	public function test_build_query_builds_joins_from_where_rows(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );

		$query = ( new HposLegacyOrderReportQueryBuilder() )->build_query(
			array(
				'data'         => array(
					'ID' => array(
						'type'     => 'post_data',
						'function' => 'COUNT',
						'name'     => 'total_orders',
					),
				),
				'where'        => array(
					array(
						'type'     => 'order_item',
						'key'      => 'order_items.order_item_type',
						'value'    => 'coupon',
						'operator' => '=',
					),
				),
				'filter_range' => false,
				'order_types'  => array( 'shop_order' ),
			),
			0,
			0
		);

		$this->assertStringContainsString( 'woocommerce_order_items AS order_items ON orders.id = order_items.order_id', $query['join'] );
		$this->assertStringContainsString( "AND order_items.order_item_type = 'coupon'", $query['where'] );
		$this->assertStringNotContainsString( 'order_items', $query['select'] );
	}

	/**
	 * @testdox Should compare plain post_date where predicates against the GMT column so the date index stays usable.
	 */
	public function test_build_query_where_post_date_predicate_compares_gmt_column(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );
		update_option( 'timezone_string', 'Europe/Berlin' );

		$builder = new HposLegacyOrderReportQueryBuilder();

		// Sparkline shape: no filter_range, `post_date >` is the only date bound.
		$sparkline_args = array(
			'data'         => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'sparkline_value',
				),
				'post_date'    => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date',
				),
			),
			'where'        => array(
				array(
					'key'      => 'post_date',
					'value'    => '2026-07-14',
					'operator' => '>',
				),
			),
			'group_by'     => 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)',
			'filter_range' => false,
			'order_types'  => array( 'shop_order' ),
		);

		$query = $builder->build_query( $sparkline_args, 0, 0 );

		// Berlin is UTC+2 on that date, so local midnight maps back two hours.
		$this->assertStringContainsString( "orders.date_created_gmt > '2026-07-13 22:00:00'", $query['where'] );
		$this->assertStringNotContainsString( 'CONVERT_TZ', $query['where'] );
		$this->assertStringNotContainsString( 'DATE_ADD', $query['where'] );
		// Row bucketing still converts per row.
		$this->assertStringContainsString( 'CONVERT_TZ', $query['group_by'] );

		// Values that are not plain date/datetime strings keep the translated per-row expression.
		$sparkline_args['where'][0]['value'] = 'abc';

		$query = $builder->build_query( $sparkline_args, 0, 0 );

		$this->assertStringContainsString( 'CONVERT_TZ', $query['where'] );
		$this->assertStringContainsString( "> 'abc'", $query['where'] );
	}

	/**
	 * @testdox Should skip where predicates with an empty IN list instead of emitting broken SQL.
	 */
	public function test_build_query_skips_empty_in_predicates(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );

		$query = ( new HposLegacyOrderReportQueryBuilder() )->build_query(
			array(
				'data'         => array(
					'ID' => array(
						'type'     => 'post_data',
						'function' => 'COUNT',
						'name'     => 'total_orders',
					),
				),
				'where'        => array(
					array(
						'key'      => 'posts.ID',
						'value'    => array(),
						'operator' => 'IN',
					),
				),
				'where_meta'   => array(
					// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					array(
						'meta_key'   => '_some_meta',
						'meta_value' => array(),
						'operator'   => 'NOT IN',
					),
					// phpcs:enable
				),
				'filter_range' => false,
				'order_types'  => array( 'shop_order' ),
			),
			0,
			0
		);

		$this->assertStringNotContainsString( 'IN ()', $query['where'] );
		$this->assertStringNotContainsString( 'orders.id IN', $query['where'] );
		$this->assertStringNotContainsString( '_some_meta', $query['where'] );
	}

	/**
	 * @testdox Should translate only word-bounded ID and post_date tokens, leaving longer identifiers untouched.
	 */
	public function test_translate_legacy_sql_fragment_respects_word_boundaries(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 0 );

		$query = ( new HposLegacyOrderReportQueryBuilder() )->build_query(
			array(
				'data'         => array(
					'ID' => array(
						'type'     => 'post_data',
						'function' => null,
						'name'     => 'id',
					),
				),
				'group_by'     => 'posts.post_date_gmt, product_ID, ID, YEAR(posts.post_date)',
				'order_by'     => 'posts.ID DESC',
				'filter_range' => false,
				'order_types'  => array( 'shop_order' ),
			),
			0,
			0
		);

		$local_date_expression = 'DATE_ADD(orders.date_created_gmt, INTERVAL 0 SECOND)';

		// Tokens embedded in longer identifiers survive untranslated.
		$this->assertStringContainsString( 'posts.post_date_gmt', $query['group_by'] );
		$this->assertStringContainsString( 'product_ID', $query['group_by'] );
		// Word-bounded tokens translate as before.
		$this->assertStringContainsString( "YEAR({$local_date_expression})", $query['group_by'] );
		$this->assertStringContainsString( ' orders.id,', $query['group_by'] );
		$this->assertSame( 'ORDER BY orders.id DESC', $query['order_by'] );
	}
}
