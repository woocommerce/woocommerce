<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogTableReader;
use WC_Log_Handler_DB;
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
		$this->handler->handle( $now - 20, 'info', 'Held back: type disabled', $this->context( 'push-token-4412', 'held_back', 'type_disabled' ) );
		$this->handler->handle( $now - 15, 'info', 'Other source', $this->context( 'push-token-1', 'send', 'accepted' ) );
		$this->handler->handle( $now - 10, 'error', 'Send: failed', $this->context( 'push-token-4412', 'send', 'failed' ) );

		$result = $this->sut->read( 'push-token-4412', $now - DAY_IN_SECONDS, $now );

		$this->assertCount( 2, $result['rows'] );
		$this->assertSame( 'held_back', $result['rows'][0]['context']['step'] );
		$this->assertSame( 'error', $result['rows'][1]['level'] );
		$this->assertSame( 'Send: failed', $result['rows'][1]['message'] );
		$this->assertSame( $now - 10, $result['rows'][1]['timestamp'] );
		$this->assertSame( $now - DAY_IN_SECONDS, $result['covered_from'], 'A table the scan fully covers reports the requested start.' );
	}

	/**
	 * @testdox Should leave out rows outside the requested range.
	 */
	public function test_read_filters_by_timestamp(): void {
		$now = time();
		$this->handler->handle( $now - 7200, 'info', 'Old', $this->context( 'push-token-1', 'send', 'accepted' ) );
		$this->handler->handle( $now - 60, 'info', 'Recent', $this->context( 'push-token-1', 'send', 'accepted' ) );

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
