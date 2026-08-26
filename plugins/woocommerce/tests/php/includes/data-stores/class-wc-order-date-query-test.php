<?php
/**
 * Tests that day-precision date queries behave identically on both order storage backends.
 *
 * Parity is asserted for ordinary 24-hour days only. HPOS derives its upper bound as
 * `$date1 + DAY_IN_SECONDS` in `OrdersTableQuery::local_time_to_gmt_date_query()`, so on a day
 * that runs 23 or 25 hours across a DST transition the two backends still disagree by an hour.
 * Post storage is the correct side there; closing the gap means fixing `OrdersTableQuery`.
 *
 * @package WooCommerce\Tests\DataStores
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Legacy class name.

/**
 * Class WC_Order_Date_Query_Test.
 *
 * @group order-query-tests
 */
class WC_Order_Date_Query_Test extends WC_Unit_Test_Case {

	/**
	 * Timestamp of 2026-07-20 21:00:00 -04:00, which falls on 2026-07-21 in UTC. This is the case a
	 * UTC-anchored day boundary gets wrong: the order belongs to the 20th in the site's timezone.
	 */
	private const PAID_AT = 1784595600;

	/**
	 * The order storage state before the test.
	 *
	 * @var bool
	 */
	private $previous_cot_state;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->previous_cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		OrderHelper::create_order_custom_table_if_not_exist();

		update_option( 'timezone_string', 'America/New_York' );
	}

	/**
	 * Restore the order storage and timezone settings.
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot_feature_and_usage( $this->previous_cot_state );
		update_option( 'timezone_string', '' );

		parent::tearDown();
	}

	/**
	 * Day-precision `date_paid` queries and whether an order paid at {@see self::PAID_AT} should match.
	 *
	 * @return array
	 */
	public function date_paid_query_provider(): array {
		return array(
			'the local day the order was paid'     => array( '2026-07-20', true ),
			'the following local day'              => array( '2026-07-21', false ),
			'the preceding local day'              => array( '2026-07-19', false ),
			'on or after the local day'            => array( '>=2026-07-20', true ),
			'strictly after the local day'         => array( '>2026-07-20', false ),
			'on or before the local day'           => array( '<=2026-07-20', true ),
			'strictly before the local day'        => array( '<2026-07-20', false ),
			'a range ending on the local day'      => array( '2026-07-19...2026-07-20', true ),
			'a range ending before the local day'  => array( '2026-07-18...2026-07-19', false ),
			'a range starting on the local day'    => array( '2026-07-20...2026-07-21', true ),
			'a range starting after the local day' => array( '2026-07-21...2026-07-22', false ),
			'a single-day range on the local day'  => array( '2026-07-20...2026-07-20', true ),
			'an explicit UTC instant on the day'   => array( '2026-07-20T02:00:00Z', true ),
		);
	}

	/**
	 * @testdox Day-precision date_paid queries should match on the local day the order was paid, and agree across order storage backends on an ordinary 24-hour day.
	 *
	 * @dataProvider date_paid_query_provider
	 *
	 * @param string $date_paid_query The date_paid query var.
	 * @param bool   $should_match    Whether the order is expected to match.
	 */
	public function test_date_paid_day_queries_match_the_local_day( string $date_paid_query, bool $should_match ): void {
		$results = array();

		$storage_backends = array(
			'posts' => false,
			'hpos'  => true,
		);

		foreach ( $storage_backends as $storage => $use_hpos ) {
			OrderHelper::toggle_cot_feature_and_usage( $use_hpos );

			$order = new WC_Order();
			$order->set_date_paid( self::PAID_AT );
			$order->save();

			$matched_ids = wc_get_orders(
				array(
					'date_paid' => $date_paid_query,
					'limit'     => -1,
					'return'    => 'ids',
				)
			);

			$results[ $storage ] = in_array( $order->get_id(), $matched_ids, true );

			$order->delete( true );
		}

		$this->assertSame(
			$results['posts'],
			$results['hpos'],
			"Post and HPOS storage disagree on 'date_paid' => '{$date_paid_query}'"
		);
		$this->assertSame(
			$should_match,
			$results['posts'],
			"Wrong result for 'date_paid' => '{$date_paid_query}' on an order paid at 2026-07-20 21:00 local time"
		);
	}
}
