<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\StockNotifications\Stats;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Tests\Admin\API\Reports\StockNotifications\ReportTestTrait;
use WC_REST_Unit_Test_Case;

/**
 * Stock notifications stats report API controller test.
 */
class ControllerTest extends WC_REST_Unit_Test_Case {

	use ReportTestTrait;

	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-analytics/reports/stock-notifications/stats';

	/**
	 * Period covering 2026-03-01 to 2026-03-07.
	 *
	 * @var array
	 */
	const PERIOD = array(
		'after'  => '2026-03-01T00:00:00',
		'before' => '2026-03-07T23:59:59',
	);

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->set_up_stock_notifications_report();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		try {
			$this->tear_down_stock_notifications_report();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should bucket a sign-up on the site-time day it was made, not the next UTC day.
	 */
	public function test_intervals_use_site_timezone(): void {
		update_option( 'timezone_string', 'America/Los_Angeles' );
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-10 06:30:00' );

		$stats    = $this->get_stats(
			array(
				'after'  => '2026-03-09T00:00:00',
				'before' => '2026-03-10T23:59:59',
				'order'  => 'asc',
			)
		);
		$next_day = $this->get_stats(
			array(
				'after'  => '2026-03-10T00:00:00',
				'before' => '2026-03-10T23:59:59',
			)
		);

		$this->assertSame(
			array(
				'2026-03-09' => 1,
				'2026-03-10' => 0,
			),
			array_column( array_map( array( $this, 'flatten_interval' ), $stats['intervals'] ), 'signups', 'interval' )
		);
		$this->assertSame( 0, $next_day['totals']['signups'], 'A sign-up at 23:30 local time does not belong to the next UTC day.' );
	}

	/**
	 * @testdox Should bucket each sign-up and send with the DST offset in force on its date, not the current one.
	 */
	public function test_intervals_honour_dst_per_row(): void {
		update_option( 'timezone_string', 'America/Los_Angeles' );
		// 07:30 UTC is 23:30 the day before in winter (PST, UTC-8) and 00:30 the same day in summer
		// (PDT, UTC-7). Shifting both by the current offset misplaces one of them in either season.
		$winter = '2026-01-16 07:30:00';
		$summer = '2026-07-16 07:30:00';
		$this->insert_row( 1, NotificationStatus::SENT, $winter, 'winter@example.com', $winter );
		$this->insert_row( 1, NotificationStatus::SENT, $summer, 'summer@example.com', $summer );
		// Either side of the 2026-11-01 09:00 UTC fall-back: 00:30 PDT and 23:30 PST, both on 2026-11-01.
		foreach ( array( '2026-11-01 07:30:00', '2026-11-02 07:30:00' ) as $i => $date ) {
			$this->insert_row( 1, NotificationStatus::SENT, $date, "autumn-{$i}@example.com", $date );
		}

		$cases = array(
			'2026-01-15' => array(
				'2026-01-15' => 1,
				'2026-01-16' => 0,
			),
			'2026-07-15' => array(
				'2026-07-15' => 0,
				'2026-07-16' => 1,
			),
			// A range containing the transition: one interval per local day, none stray.
			'2026-10-31' => array(
				'2026-10-31' => 0,
				'2026-11-01' => 2,
				'2026-11-02' => 0,
			),
		);
		foreach ( $cases as $day => $expected ) {
			$stats     = $this->get_stats(
				array(
					'after'  => $day . 'T00:00:00',
					'before' => array_key_last( $expected ) . 'T23:59:59',
					'order'  => 'asc',
				)
			);
			$intervals = array_map( array( $this, 'flatten_interval' ), $stats['intervals'] );

			$this->assertSame( $expected, array_column( $intervals, 'signups', 'interval' ), "Sign-ups around {$day} land on their local day." );
			$this->assertSame( $expected, array_column( $intervals, 'notifications_sent', 'interval' ), "Sends around {$day} land on their local day." );
		}
	}

	/**
	 * @testdox Should compute totals from non-pending sign-ups and sends in the period.
	 */
	public function test_totals(): void {
		$this->insert_row( 1, NotificationStatus::PENDING, '2026-03-02 10:00:00', 'pending@example.com' );
		$this->insert_row( 1, NotificationStatus::CANCELLED, '2026-03-02 10:00:00', 'a@example.com' );
		$this->insert_row( 1, NotificationStatus::SENT, '2026-03-03 10:00:00', 'a@example.com', '2026-03-04 10:00:00' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-03-05 10:00:00', 'b@example.com' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-01-01 10:00:00', 'c@example.com', '2026-03-06 10:00:00' );
		$this->insert_row( 3, NotificationStatus::SENT, '2026-01-01 10:00:00', 'd@example.com', '2026-02-01 10:00:00' );
		$this->insert_row( 4, NotificationStatus::CANCELLED, '2026-01-01 10:00:00', 'e@example.com', null, '2026-03-05 10:00:00', NotificationCancellationSource::USER );
		$this->insert_row( 5, NotificationStatus::CANCELLED, '2026-01-01 10:00:00', 'f@example.com', null, '2026-03-05 10:00:00', NotificationCancellationSource::ADMIN );

		$stats = $this->get_stats( self::PERIOD );

		$this->assertSame(
			array(
				'signups'            => 3,
				'notifications_sent' => 2,
				'unsubscribed'       => 1,
				'products_count'     => 3,
			),
			$stats['totals'],
			'Products 1, 2 and 4 have activity in the period; 3 was sent before it and 5 was cancelled by an admin.'
		);
		$sent = array_column( array_map( array( $this, 'flatten_interval' ), $stats['intervals'] ), 'notifications_sent', 'interval' );
		$this->assertSame( 1, $sent['2026-03-04'] );
		$this->assertSame( 1, $sent['2026-03-06'] );
	}

	/**
	 * @testdox Should zero-fill every interval of a period without rows.
	 */
	public function test_intervals_are_zero_filled(): void {
		$stats = $this->get_stats( self::PERIOD );

		$this->assertCount( 7, $stats['intervals'] );
		foreach ( $stats['intervals'] as $interval ) {
			$this->assertSame(
				array(
					'signups'            => 0,
					'notifications_sent' => 0,
					'unsubscribed'       => 0,
				),
				$interval['subtotals'],
				"Interval {$interval['interval']} should be zero-filled."
			);
		}
	}

	/**
	 * @testdox Should return only the requested fields.
	 */
	public function test_fields_param(): void {
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-02 10:00:00' );

		$stats = $this->get_stats( array_merge( self::PERIOD, array( 'fields' => array( 'signups' ) ) ) );

		$this->assertSame( array( 'signups' => 1 ), $stats['totals'] );
		foreach ( $stats['intervals'] as $interval ) {
			$this->assertSame( array( 'signups' ), array_keys( $interval['subtotals'] ), "Interval {$interval['interval']} has unrequested fields." );
		}
	}

	/**
	 * @testdox Should page date-ordered intervals on site-time days when rows are sparse.
	 */
	public function test_date_ordered_paging_uses_site_timezone(): void {
		update_option( 'timezone_string', 'America/Los_Angeles' );
		// Local times (UTC-8): 03-02 20:00, 03-03 00:30, 03-03 23:30, 03-04 20:00. 03-01 and 03-05 stay empty.
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-03 04:00:00', 'b@example.com' );
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-03 08:30:00', 'c@example.com' );
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-04 07:30:00', 'd@example.com' );
		$this->insert_row( 1, NotificationStatus::ACTIVE, '2026-03-05 04:00:00', 'e@example.com' );

		$stats = $this->get_stats(
			array(
				'after'    => '2026-03-01T00:00:00',
				'before'   => '2026-03-05T23:59:59',
				'interval' => 'day',
				'orderby'  => 'date',
				'order'    => 'asc',
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertSame(
			array(
				'2026-03-03' => 2,
				'2026-03-04' => 1,
			),
			array_column( array_map( array( $this, 'flatten_interval' ), $stats['intervals'] ), 'signups', 'interval' )
		);
	}

	/**
	 * Get the stats response data.
	 *
	 * @param array $params Query parameters.
	 * @return array
	 */
	private function get_stats( array $params ): array {
		$response = $this->request( self::ENDPOINT, $params );
		$this->assertSame( 200, $response->get_status(), 'The stats request failed.' );

		return json_decode( (string) wp_json_encode( $response->get_data() ), true );
	}

	/**
	 * Flatten an interval to its id and subtotals.
	 *
	 * @param array $interval Interval.
	 * @return array
	 */
	private function flatten_interval( array $interval ): array {
		return array_merge( array( 'interval' => $interval['interval'] ), $interval['subtotals'] );
	}
}
