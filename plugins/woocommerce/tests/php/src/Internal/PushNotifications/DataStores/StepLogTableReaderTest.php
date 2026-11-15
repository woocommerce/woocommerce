<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogTableReader;
use WC_Log_Handler_DB;
use WC_Log_Levels;
use WC_Unit_Test_Case;

/**
 * Tests for the StepLogTableReader class.
 */
class StepLogTableReaderTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StepLogTableReader
	 */
	private $sut;

	/**
	 * The real database handler used to write fixtures.
	 *
	 * @var WC_Log_Handler_DB
	 */
	private $handler;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut     = new StepLogTableReader();
		$this->handler = new WC_Log_Handler_DB();
	}

	/**
	 * @testdox Should read back rows written under a source, in time order, with their context decoded.
	 */
	public function test_read_returns_rows_for_the_source(): void {
		$now = time();
		$this->handler->handle( $now - 20, 'info', 'Token excluded: notifications off', $this->context( 'push-token-4412', 'token_excluded', 'notifications_off' ) );
		$this->handler->handle( $now - 15, 'info', 'Other source', $this->context( 'push-token-1', 'dispatched', 'accepted' ) );
		$this->handler->handle( $now - 10, 'error', 'Dispatched: failed', $this->context( 'push-token-4412', 'dispatched', 'failed' ) );

		$result = $this->sut->read( 'push-token-4412', $now - DAY_IN_SECONDS, $now );

		$this->assertCount( 2, $result['rows'] );
		$this->assertSame( 'token_excluded', $result['rows'][0]['context']['step'] );
		$this->assertSame( 'error', $result['rows'][1]['level'] );
		$this->assertSame( 'Dispatched: failed', $result['rows'][1]['message'] );
		$this->assertSame( $now - 10, $result['rows'][1]['timestamp'] );
		$this->assertSame( $now - DAY_IN_SECONDS, $result['covered_from'], 'A table the scan fully covers reports the requested start.' );
	}

	/**
	 * @testdox Should leave out rows outside the requested range.
	 */
	public function test_read_filters_by_timestamp(): void {
		$now = time();
		$this->handler->handle( $now - 7200, 'info', 'Old', $this->context( 'push-token-1', 'dispatched', 'accepted' ) );
		$this->handler->handle( $now - 60, 'info', 'Recent', $this->context( 'push-token-1', 'dispatched', 'accepted' ) );

		$result = $this->sut->read( 'push-token-1', $now - 3600, $now );

		$this->assertCount( 1, $result['rows'] );
		$this->assertSame( 'Recent', $result['rows'][0]['message'] );
	}

	/**
	 * @testdox Should return nothing when the table is empty.
	 */
	public function test_read_returns_nothing_for_an_empty_table(): void {
		$result = $this->sut->read( 'push-token-404', time() - DAY_IN_SECONDS, time() );

		$this->assertSame( array(), $result['rows'] );
	}

	/**
	 * @testdox Should read on into the next window when the page's last second carries on there.
	 */
	public function test_read_does_not_stop_partway_through_a_second(): void {
		$now  = time();
		$edge = $this->next_free_log_id() + 10;
		$top  = $edge + StepLogTableReader::WINDOW_SIZE;

		// Three newer lines, then five lines of one second on both sides of the edge between two windows.
		foreach ( array( $top, $top - 1, $top - 2 ) as $offset => $log_id ) {
			$this->insert_row( $log_id, $now - 1 - $offset, 'Newer ' . $offset );
		}

		foreach ( range( $edge - 2, $edge + 2 ) as $log_id ) {
			$this->insert_row( $log_id, $now - 10, 'Split ' . $log_id );
		}

		$rows = $this->sut->read( 'push-token-1', $now - HOUR_IN_SECONDS, $now, 5 )['rows'];

		$this->assertCount( 5, array_filter( $rows, fn( array $row ) => $now - 10 === $row['timestamp'] ), 'Every line of the second on the window edge should be read.' );
	}

	/**
	 * Returns an ID above every row in the log table, for rows placed by ID.
	 *
	 * @return int
	 */
	private function next_free_log_id(): int {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COALESCE( MAX(log_id), 0 ) FROM {$wpdb->prefix}woocommerce_log" ) + 1; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Writes one `push-token-1` row at a chosen ID, as the database handler would.
	 *
	 * @param int    $log_id    The row ID.
	 * @param int    $timestamp The row's timestamp.
	 * @param string $message   The message.
	 * @return void
	 */
	private function insert_row( int $log_id, int $timestamp, string $message ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_log',
			array(
				'log_id'    => $log_id,
				'timestamp' => gmdate( 'Y-m-d H:i:s', $timestamp ),
				'level'     => WC_Log_Levels::get_level_severity( 'info' ),
				'source'    => 'push-token-1',
				'message'   => $message,
			),
			array( '%d', '%s', '%d', '%s', '%s' )
		);
	}

	/**
	 * Builds a step logger context for a fixture row.
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
