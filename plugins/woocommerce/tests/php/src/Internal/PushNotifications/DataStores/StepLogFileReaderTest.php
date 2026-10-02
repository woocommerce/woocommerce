<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogFileReader;
use WC_Unit_Test_Case;

/**
 * Tests for the StepLogFileReader class.
 */
class StepLogFileReaderTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StepLogFileReader
	 */
	private $sut;

	/**
	 * The real file handler used to write fixtures.
	 *
	 * @var LogHandlerFileV2
	 */
	private $handler;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut     = new StepLogFileReader();
		$this->handler = new LogHandlerFileV2();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( glob( Settings::get_log_directory() . 'push-*.log' ) as $file ) {
			wp_delete_file( $file );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should read back lines written under a source, in time order, with their context decoded.
	 */
	public function test_read_returns_lines_for_the_source_in_time_order(): void {
		$now = time();
		$this->handler->handle( $now - 10, 'info', 'Recipients: resolved', $this->context( 'push-token-4412', 'recipients', 'resolved' ) );
		$this->handler->handle( $now - 20, 'info', 'Token excluded: notifications off', $this->context( 'push-token-4412', 'token_excluded', 'notifications_off' ) );
		$this->handler->handle( $now - 5, 'info', 'Triggered: queued', $this->context( 'push-token-9999', 'triggered', 'queued' ) );

		$rows = $this->sut->read( 'push-token-4412', $now - DAY_IN_SECONDS, $now )['rows'];

		$this->assertCount( 2, $rows );
		$this->assertSame( 'token_excluded', $rows[0]['context']['step'] );
		$this->assertSame( 'recipients', $rows[1]['context']['step'] );
		$this->assertSame( 'info', $rows[1]['level'] );
		$this->assertSame( 'Recipients: resolved', $rows[1]['message'] );
		$this->assertSame( $now - 10, $rows[1]['timestamp'] );
		$this->assertNull( $rows[1]['raw'] );
	}

	/**
	 * @testdox Should include the previous day's file when the range starts part way through that day.
	 */
	public function test_read_spans_days_from_a_mid_day_start(): void {
		$yesterday_evening = (int) strtotime( gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ) . 'T23:50:00+00:00' );
		$this->handler->handle( $yesterday_evening, 'info', 'Triggered: queued', $this->context( 'push-notifications-store-order', 'triggered', 'queued' ) );
		$this->handler->handle( $yesterday_evening + 900, 'info', 'Loopback started: ok', $this->context( 'push-notifications-store-order', 'loopback_started', 'ok' ) );

		$rows = $this->sut->read( 'push-notifications-store-order', $yesterday_evening - 60, $yesterday_evening + 3600 )['rows'];

		$this->assertSame( array( 'triggered', 'loopback_started' ), array_column( array_column( $rows, 'context' ), 'step' ) );
	}

	/**
	 * @testdox Should stop at the newest day that fills the page, leaving older days unread.
	 */
	public function test_read_stops_once_the_page_is_full(): void {
		$today     = (int) strtotime( gmdate( 'Y-m-d' ) . 'T10:00:00+00:00' );
		$yesterday = $today - DAY_IN_SECONDS;

		$this->handler->handle( $yesterday, 'info', 'Old one', $this->context( 'push-notifications-store-order', 'triggered', 'queued' ) );
		$this->handler->handle( $today, 'info', 'New one', $this->context( 'push-notifications-store-order', 'triggered', 'queued' ) );
		$this->handler->handle( $today + 1, 'info', 'New two', $this->context( 'push-notifications-store-order', 'triggered', 'queued' ) );

		$result = $this->sut->read( 'push-notifications-store-order', $yesterday - HOUR_IN_SECONDS, $today + HOUR_IN_SECONDS, 2 );

		$this->assertSame( array( 'New one', 'New two' ), array_column( $result['rows'], 'message' ) );
		$this->assertSame(
			(int) strtotime( gmdate( 'Y-m-d' ) . 'T00:00:00+00:00' ),
			$result['covered_from'],
			'Coverage should start at the newest day, since the older one was never opened.'
		);
	}

	/**
	 * @testdox Should turn each line into the caller's rows as it reads, rather than collecting them all first.
	 */
	public function test_read_expands_each_line_as_it_is_read(): void {
		$now = time();
		$this->handler->handle( $now - 60, 'info', 'Recipients: resolved', $this->context( 'push-notifications-store-order', 'recipients', 'resolved' ) );

		$result = $this->sut->read(
			'push-notifications-store-order',
			$now - HOUR_IN_SECONDS,
			$now,
			100,
			fn( array $row ) => array_fill( 0, 3, $row )
		);

		$this->assertCount( 3, $result['rows'] );
	}

	/**
	 * @testdox Should leave out lines outside the requested range.
	 */
	public function test_read_filters_by_timestamp(): void {
		$now = time();
		$this->handler->handle( $now - 7200, 'info', 'Old', $this->context( 'push-token-1', 'dispatched', 'accepted' ) );
		$this->handler->handle( $now - 60, 'info', 'Recent', $this->context( 'push-token-1', 'dispatched', 'accepted' ) );

		$rows = $this->sut->read( 'push-token-1', $now - 3600, $now )['rows'];

		$this->assertCount( 1, $rows );
		$this->assertSame( 'Recent', $rows[0]['message'] );
	}

	/**
	 * @testdox Should return nothing for a source with no file.
	 */
	public function test_read_returns_nothing_for_a_missing_file(): void {
		$this->assertSame( array(), $this->sut->read( 'push-token-404', time() - DAY_IN_SECONDS, time() )['rows'] );
	}

	/**
	 * @testdox Should parse a well-formed line and return the raw text when the context is not JSON.
	 * @dataProvider provider_lines
	 *
	 * @param string     $line     The line.
	 * @param array|null $expected The expected row, or null.
	 */
	public function test_parse_line( string $line, ?array $expected ): void {
		$this->assertSame( $expected, StepLogFileReader::parse_line( $line ) );
	}

	/**
	 * Lines and their parsed form.
	 *
	 * @return array
	 */
	public function provider_lines(): array {
		return array(
			'with context'     => array(
				'2026-09-15T10:00:00+00:00 INFO Dispatched: accepted CONTEXT: {"step":"dispatched","outcome":"accepted"}' . "\n",
				array(
					'timestamp' => 1789466400,
					'level'     => 'info',
					'message'   => 'Dispatched: accepted',
					'context'   => array(
						'step'    => 'dispatched',
						'outcome' => 'accepted',
					),
					'raw'       => null,
				),
			),
			'without context'  => array(
				'2026-09-15T10:00:00+00:00 ERROR Something broke',
				array(
					'timestamp' => 1789466400,
					'level'     => 'error',
					'message'   => 'Something broke',
					'context'   => null,
					'raw'       => null,
				),
			),
			'reshaped context' => array(
				'2026-09-15T10:00:00+00:00 INFO Send: accepted CONTEXT: step=send outcome=accepted',
				array(
					'timestamp' => 1789466400,
					'level'     => 'info',
					'message'   => 'Send: accepted',
					'context'   => null,
					'raw'       => '2026-09-15T10:00:00+00:00 INFO Send: accepted CONTEXT: step=send outcome=accepted',
				),
			),
			'no timestamp'     => array( 'just some text here', null ),
			'unknown level'    => array( '2026-09-15T10:00:00+00:00 LOUD Send: accepted', null ),
		);
	}

	/**
	 * Builds a step logger context for a fixture line.
	 *
	 * @param string $source  The log source.
	 * @param string $step    The step.
	 * @param string $outcome The outcome.
	 * @return array
	 */
	private function context( string $source, string $step, string $outcome ): array {
		return array(
			'source'      => $source,
			'identifier'  => '1_store_order_42',
			'type'        => 'store_order',
			'resource_id' => 42,
			'step'        => $step,
			'outcome'     => $outcome,
		);
	}
}
