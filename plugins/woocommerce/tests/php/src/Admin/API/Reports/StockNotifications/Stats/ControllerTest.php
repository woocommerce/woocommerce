<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\StockNotifications\Stats;

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
	 * @testdox Should compute totals from non-pending sign-ups, sends in the period and current active sign-ups.
	 */
	public function test_totals(): void {
		$this->insert_row( 1, NotificationStatus::PENDING, '2026-03-02 10:00:00', 'pending@example.com' );
		$this->insert_row( 1, NotificationStatus::CANCELLED, '2026-03-02 10:00:00', 'a@example.com' );
		$this->insert_row( 1, NotificationStatus::SENT, '2026-03-03 10:00:00', 'a@example.com', '2026-03-04 10:00:00' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-03-05 10:00:00', 'b@example.com' );
		$this->insert_row( 2, NotificationStatus::ACTIVE, '2026-01-01 10:00:00', 'c@example.com', '2026-03-06 10:00:00' );
		$this->insert_row( 3, NotificationStatus::SENT, '2026-01-01 10:00:00', 'd@example.com', '2026-02-01 10:00:00' );

		$stats = $this->get_stats( self::PERIOD );

		$this->assertSame(
			array(
				'signups'            => 3,
				'customers'          => 2,
				'notifications_sent' => 2,
				'active_signups'     => 2,
			),
			$stats['totals']
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
					'customers'          => 0,
					'notifications_sent' => 0,
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
