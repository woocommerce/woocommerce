<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogFileReader;
use Automattic\WooCommerce\Internal\PushNotifications\DataStores\StepLogTableReader;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationProcessor;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use Automattic\WooCommerce\Internal\PushNotifications\Services\StepLogQuery;
use WC_Unit_Test_Case;

/**
 * Tests for the StepLogQuery class.
 */
class StepLogQueryTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StepLogQuery
	 */
	private $sut;

	/**
	 * Mock file reader.
	 *
	 * @var StepLogFileReader|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $file_reader;

	/**
	 * Mock table reader.
	 *
	 * @var StepLogTableReader|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $table_reader;

	/**
	 * Mock data store.
	 *
	 * @var PushTokensDataStore|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $data_store;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->file_reader  = $this->createMock( StepLogFileReader::class );
		$this->table_reader = $this->createMock( StepLogTableReader::class );
		$this->data_store   = $this->createMock( PushTokensDataStore::class );
		$this->data_store->method( 'count_tokens' )->willReturn( 3 );
		$this->data_store->method( 'has_ever_had_tokens' )->willReturn( true );

		$step_logger = new NotificationStepLogger();
		$step_logger->init( $this->data_store );

		$this->sut = new StepLogQuery();
		$this->sut->init( $this->file_reader, $this->table_reader, $this->data_store, $step_logger );
	}

	/**
	 * @testdox Should read the type's journey file and the module's error source, keeping only the resource's rows.
	 */
	public function test_for_notification_reads_both_sources_and_filters_by_resource(): void {
		$this->file_reader->method( 'read' )->willReturnMap(
			array(
				array(
					'push-notifications-store-order',
					100,
					200,
					array(
						$this->row( 150, 'store_order', 42, 'triggered', 'queued' ),
						$this->row( 151, 'store_order', 43, 'triggered', 'queued' ),
					),
				),
				array(
					'push_notifications',
					100,
					200,
					array(
						$this->row( 160, 'store_order', 42, 'send', 'failed', 'error' ),
					),
				),
			)
		);

		$result = $this->sut->for_notification( 'store_order', 42, 100, 200 );

		$this->assertCount( 2, $result['rows'] );
		$this->assertSame( 'triggered', $result['rows'][0]['context']['step'] );
		$this->assertSame( 'error', $result['rows'][1]['level'] );
		$this->assertSame( gmdate( 'c', 160 ), $result['rows'][1]['timestamp'] );
		$this->assertSame(
			array(
				'triggered:queued' => 1,
				'send:failed'      => 1,
			),
			$result['counts']
		);
		$this->assertSame( 100, $result['covered_from'] );
	}

	/**
	 * @testdox Should read one device's source and return every row.
	 */
	public function test_for_token_reads_the_token_source(): void {
		$this->file_reader->expects( $this->once() )
			->method( 'read' )
			->with( 'push-token-4412', 100, 200 )
			->willReturn( array( $this->row( 150, 'store_order', 42, 'held_back', 'type_disabled' ) ) );

		$result = $this->sut->for_token( 4412, 100, 200 );

		$this->assertCount( 1, $result['rows'] );
		$this->assertSame( array( 'held_back:type_disabled' => 1 ), $result['counts'] );
	}

	/**
	 * @testdox Should read every device the user owns and merge the rows in time order.
	 */
	public function test_for_user_reads_every_token_of_the_user(): void {
		$this->data_store->method( 'get_token_ids_for_user' )->with( 7 )->willReturn( array( 11, 12 ) );
		$this->file_reader->method( 'read' )->willReturnMap(
			array(
				array( 'push-token-11', 100, 200, array( $this->row( 180, 'store_order', 42, 'send', 'accepted' ) ) ),
				array( 'push-token-12', 100, 200, array( $this->row( 150, 'store_order', 42, 'send', 'accepted' ) ) ),
			)
		);

		$result = $this->sut->for_user( 7, 100, 200 );

		$this->assertSame( array( gmdate( 'c', 150 ), gmdate( 'c', 180 ) ), array_column( $result['rows'], 'timestamp' ) );
		$this->assertSame( array( 'send:accepted' => 2 ), $result['counts'] );
	}

	/**
	 * @testdox Should read the table instead of files, and report its coverage, when the store uses the database handler.
	 */
	public function test_uses_the_table_reader_for_the_database_handler(): void {
		add_filter( 'pre_option_woocommerce_logs_default_handler', fn() => 'WC_Log_Handler_DB' );

		$this->file_reader->expects( $this->never() )->method( 'read' );
		$this->table_reader->expects( $this->once() )
			->method( 'read' )
			->with( 'push-token-4412', 100, 200 )
			->willReturn(
				array(
					'rows'         => array( $this->row( 150, 'store_order', 42, 'send', 'accepted' ) ),
					'covered_from' => 140,
				)
			);

		$result = $this->sut->for_token( 4412, 100, 200 );

		$this->assertCount( 1, $result['rows'] );
		$this->assertSame( 140, $result['covered_from'] );
	}

	/**
	 * @testdox Should return the store's logging state with every answer.
	 */
	public function test_returns_the_logging_state(): void {
		$this->file_reader->method( 'read' )->willReturn( array() );

		$state = $this->sut->for_token( 4412, 100, 200 )['logging'];

		$this->assertTrue( $state['logging_enabled'] );
		$this->assertTrue( $state['push_logging_enabled'] );
		$this->assertSame( 'none', $state['level_threshold'] );
		$this->assertSame( 30, $state['retention_period_days'] );
		$this->assertSame( 3, $state['token_count'] );
		$this->assertSame( NotificationProcessor::TOKEN_LINE_CAP, $state['token_line_cap'] );
		$this->assertArrayHasKey( 'default_handler', $state );
		$this->assertArrayHasKey( 'log_directory_writable', $state );
	}

	/**
	 * Builds a row as a reader would return it.
	 *
	 * @param int    $timestamp   The timestamp.
	 * @param string $type        The notification type.
	 * @param int    $resource_id The resource ID.
	 * @param string $step        The step.
	 * @param string $outcome     The outcome.
	 * @param string $level       The level.
	 * @return array
	 */
	private function row( int $timestamp, string $type, int $resource_id, string $step, string $outcome, string $level = 'info' ): array {
		return array(
			'timestamp' => $timestamp,
			'level'     => $level,
			'message'   => $step . ': ' . $outcome,
			'context'   => array(
				'identifier'  => '1_' . $type . '_' . $resource_id,
				'type'        => $type,
				'resource_id' => $resource_id,
				'step'        => $step,
				'outcome'     => $outcome,
			),
			'raw'       => null,
		);
	}
}
