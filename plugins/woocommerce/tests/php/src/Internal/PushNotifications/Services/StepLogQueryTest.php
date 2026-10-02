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

		$step_logger = new NotificationStepLogger();

		$this->sut = new StepLogQuery();
		$this->sut->init( $this->file_reader, $this->table_reader, $this->data_store, $step_logger );
	}

	/**
	 * @testdox Should read the type's journey file and the module's error source, keeping only the resource's rows.
	 */
	public function test_for_notification_reads_both_sources_and_filters_by_resource(): void {
		$this->file_reader_returns(
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
					'push-suppressed',
					100,
					200,
					array(
						$this->row( 155, 'store_order', 42, 'token_excluded', 'notifications_off', 'info', 4412, 7 ),
						$this->row( 156, 'store_order', 43, 'token_excluded', 'notifications_off', 'info', 4413, 8 ),
					),
				),
				array(
					'push_notifications',
					100,
					200,
					array(
						$this->row( 160, 'store_order', 42, 'dispatched', 'failed', 'error' ),
					),
				),
			)
		);

		$result = $this->sut->for_notification( 'store_order', 42, 100, 200, 100 );

		$this->assertCount( 3, $result['rows'] );
		$this->assertSame( 'error', $result['rows'][0]['level'] );
		$this->assertSame( gmdate( 'c', 160 ), $result['rows'][0]['timestamp'] );
		$this->assertSame( 'token_excluded', $result['rows'][1]['context']['step'] );
		$this->assertSame( 'triggered', $result['rows'][2]['context']['step'] );
		$this->assertNull( $result['next_to'] );
		$this->assertEquals(
			array(
				'triggered:queued'                 => 1,
				'token_excluded:notifications_off' => 1,
				'dispatched:failed'                => 1,
			),
			$result['counts']
		);
		$this->assertSame( 100, $result['covered_from'] );
	}

	/**
	 * @testdox Should read every notification type, the suppressed source and the error source, and no source per token.
	 */
	public function test_for_site_reads_every_source_the_store_holds(): void {
		$read = array();
		$this->file_reader->method( 'read' )->willReturnCallback(
			function ( string $source, int $from ) use ( &$read ): array {
				$read[] = $source;

				return array(
					'rows'         => array(),
					'covered_from' => $from,
				);
			}
		);

		$this->sut->for_site( 100, 200, 100 );

		$this->assertEqualsCanonicalizing(
			array(
				'push-notifications-store-order',
				'push-notifications-store-review',
				'push-notifications-store-stock',
				'push-suppressed',
				'push_notifications',
			),
			$read
		);
	}

	/**
	 * @testdox Should find one token named inside the notification lines and return a row for it.
	 */
	public function test_for_token_finds_the_token_named_in_the_aggregate_lines(): void {
		$this->reader_rows_from(
			function ( string $source ) {
				if ( 'push-notifications-store-order' === $source ) {
					return array(
						$this->aggregate_row( 180, 'store_order', 42, 'recipients', 'resolved', array( 'token_ids' => array( 4412, 9999 ) ) ),
						$this->aggregate_row( 170, 'store_order', 42, 'dispatched', 'invalid_token', array( 'invalid_token_ids' => array( 9999 ) ) ),
					);
				}

				if ( 'push-suppressed' === $source ) {
					return array(
						$this->aggregate_row(
							150,
							'store_order',
							41,
							'token_excluded',
							'notifications_off',
							array(
								'excluded_tokens' => array(
									'notifications_off' => array( 4412 ),
									'order_missing'     => array( 9999 ),
								),
							)
						),
					);
				}

				return array();
			}
		);

		$result = $this->sut->for_token( 4412, 100, 200, 100 );

		$this->assertSame( array( gmdate( 'c', 180 ), gmdate( 'c', 150 ) ), array_column( $result['rows'], 'timestamp' ) );
		$this->assertSame( 'token_included', $result['rows'][0]['context']['step'] );
		$this->assertSame( 4412, $result['rows'][0]['context']['token_id'] );
		$this->assertArrayNotHasKey( 'token_ids', $result['rows'][0]['context'] );
		$this->assertSame( 'token_excluded', $result['rows'][1]['context']['step'] );
		$this->assertSame( 'notifications_off', $result['rows'][1]['context']['outcome'] );
	}

	/**
	 * @testdox Should return a row per token the user owns when one line names several of them.
	 */
	public function test_for_user_returns_a_row_per_token_named(): void {
		$this->data_store->method( 'get_token_ids_for_user' )->with( 7 )->willReturn( array( 11, 12 ) );
		$this->reader_rows_from(
			function ( string $source ) {
				if ( 'push-notifications-store-order' === $source ) {
					return array(
						$this->aggregate_row( 180, 'store_order', 42, 'recipients', 'resolved', array( 'token_ids' => array( 11, 12, 99 ) ) ),
					);
				}

				return array();
			}
		);

		$result = $this->sut->for_user( 7, 100, 200, 100 );

		$this->assertCount( 2, $result['rows'] );
		$this->assertSame( array( 11, 12 ), array_column( array_column( $result['rows'], 'context' ), 'token_id' ) );
	}

	/**
	 * @testdox Should include the batch loopback line covering this notification, which carries no identity of its own.
	 */
	public function test_for_notification_includes_the_batch_line_covering_it(): void {
		$batch_line = array(
			'timestamp' => 150,
			'level'     => 'info',
			'message'   => 'Loopback requested: ok',
			'context'   => array(
				'step'          => 'loopback_requested',
				'outcome'       => 'ok',
				'batch_size'    => 2,
				'notifications' => array(
					array(
						'type'        => 'store_order',
						'resource_id' => 42,
					),
					array(
						'type'        => 'store_order',
						'resource_id' => 43,
					),
				),
			),
			'raw'       => null,
		);

		$this->reader_rows_from( fn( string $source ) => 'push_notifications' === $source ? array( $batch_line ) : array() );

		$this->assertCount( 1, $this->sut->for_notification( 'store_order', 42, 100, 200, 100 )['rows'] );
		$this->assertCount( 0, $this->sut->for_notification( 'store_order', 99, 100, 200, 100 )['rows'] );
	}

	/**
	 * @testdox Should order lines sharing a second by the lifecycle, not by the order they were written.
	 */
	public function test_lines_sharing_a_second_are_ordered_by_the_lifecycle(): void {
		$this->file_reader_returns(
			array(
				array(
					'push-notifications-store-order',
					100,
					200,
					array(
						$this->row( 150, 'store_order', 42, 'loopback_started', 'ok' ),
						$this->row( 150, 'store_order', 42, 'dispatched', 'accepted' ),
						$this->row( 150, 'store_order', 42, 'recipients', 'resolved' ),
						$this->row( 150, 'store_order', 42, 'loopback_requested', 'ok' ),
					),
				),
				array( 'push-suppressed', 100, 200, array() ),
				array( 'push_notifications', 100, 200, array() ),
			)
		);

		$result = $this->sut->for_notification( 'store_order', 42, 100, 200, 100 );

		$this->assertSame(
			array( 'dispatched', 'recipients', 'loopback_started', 'loopback_requested' ),
			array_column( array_column( $result['rows'], 'context' ), 'step' )
		);
	}

	/**
	 * @testdox Should return one page newest first, with a cursor one second before the oldest row kept.
	 */
	public function test_a_page_is_capped_and_carries_a_cursor(): void {
		$this->file_reader_returns(
			array(
				array(
					'push-notifications-store-order',
					100,
					200,
					array(
						$this->row( 150, 'store_order', 42, 'triggered', 'queued' ),
						$this->row( 160, 'store_order', 42, 'loopback_requested', 'ok' ),
						$this->row( 170, 'store_order', 42, 'dispatched', 'accepted' ),
					),
				),
				array( 'push-suppressed', 100, 200, array() ),
				array( 'push_notifications', 100, 200, array() ),
			)
		);

		$result = $this->sut->for_notification( 'store_order', 42, 100, 200, 2 );

		$this->assertSame( array( gmdate( 'c', 170 ), gmdate( 'c', 160 ) ), array_column( $result['rows'], 'timestamp' ) );
		$this->assertSame( 159, $result['next_to'] );
		$this->assertEquals(
			array(
				'dispatched:accepted'   => 1,
				'loopback_requested:ok' => 1,
			),
			$result['counts']
		);
	}

	/**
	 * @testdox Should carry every row of the page's last second, even past the limit.
	 */
	public function test_a_page_never_ends_partway_through_a_second(): void {
		$this->file_reader_returns(
			array(
				array(
					'push-notifications-store-order',
					100,
					200,
					array(
						$this->row( 150, 'store_order', 42, 'triggered', 'queued' ),
						$this->row( 160, 'store_order', 42, 'loopback_requested', 'ok' ),
						$this->row( 160, 'store_order', 42, 'loopback_started', 'ok' ),
						$this->row( 160, 'store_order', 42, 'recipients', 'resolved' ),
					),
				),
				array( 'push-suppressed', 100, 200, array() ),
				array( 'push_notifications', 100, 200, array() ),
			)
		);

		$result = $this->sut->for_notification( 'store_order', 42, 100, 200, 2 );

		$this->assertCount( 3, $result['rows'] );
		$this->assertSame( array( 160, 160, 160 ), array_map( fn( array $row ) => (int) strtotime( $row['timestamp'] ), $result['rows'] ) );
		$this->assertSame( 159, $result['next_to'] );
	}

	/**
	 * @testdox Should report no cursor when the range holds nothing older.
	 */
	public function test_a_page_holding_everything_has_no_cursor(): void {
		$this->file_reader_returns(
			array(
				array(
					'push-notifications-store-order',
					100,
					200,
					array( $this->aggregate_row( 150, 'store_order', 42, 'recipients', 'resolved', array( 'token_ids' => array( 4412 ) ) ) ),
				),
				array( 'push-notifications-store-review', 100, 200, array() ),
				array( 'push-notifications-store-stock', 100, 200, array() ),
				array( 'push-suppressed', 100, 200, array() ),
			)
		);

		$result = $this->sut->for_token( 4412, 100, 200, 100 );

		$this->assertCount( 1, $result['rows'] );
		$this->assertNull( $result['next_to'] );
	}

	/**
	 * @testdox Should read the table instead of files, and report its coverage, when the store uses the database handler.
	 */
	public function test_uses_the_table_reader_for_the_database_handler(): void {
		add_filter( 'pre_option_woocommerce_logs_default_handler', fn() => 'WC_Log_Handler_DB' );

		$read = 0;

		$this->file_reader->expects( $this->never() )->method( 'read' );
		$this->reader_rows_from(
			function ( string $source ) use ( &$read ): array {
				++$read;

				return 'push-notifications-store-order' === $source
					? array( $this->aggregate_row( 150, 'store_order', 42, 'recipients', 'resolved', array( 'token_ids' => array( 4412 ) ) ) )
					: array();
			},
			$this->table_reader
		);

		$result = $this->sut->for_token( 4412, 100, 200, 100 );

		$this->assertCount( 1, $result['rows'] );
		$this->assertSame( 4, $read, 'Every source a token read covers should be read from the table.' );
	}

	/**
	 * @testdox Should return the store's logging state with every answer.
	 */
	public function test_returns_the_logging_state(): void {
		$this->file_reader->method( 'read' )->willReturn(
			array(
				'rows'         => array(),
				'covered_from' => 0,
			)
		);

		$state = $this->sut->for_token( 4412, 100, 200, 100 )['logging'];

		$this->assertTrue( $state['logging_enabled'] );
		$this->assertTrue( $state['push_logging_enabled'] );
		$this->assertSame( 'none', $state['level_threshold'] );
		$this->assertSame( 30, $state['retention_period_days'] );
		$this->assertSame( 3, $state['token_count'] );
		$this->assertSame( NotificationProcessor::RECIPIENT_ID_CAP, $state['recipient_id_cap'] );
		$this->assertArrayHasKey( 'default_handler', $state );
		$this->assertArrayHasKey( 'log_directory_writable', $state );
	}

	/**
	 * Makes a reader answer from a function of source to rows, applying the
	 * expansion and returning the coverage a real reader now returns.
	 *
	 * @param callable        $rows_for_source Called with a source, returning its rows.
	 * @param MockObject|null $reader          The reader to stub, defaulting to the file reader.
	 * @return void
	 */
	private function reader_rows_from( callable $rows_for_source, $reader = null ): void {
		$reader = $reader ?? $this->file_reader;

		$reader->method( 'read' )->willReturnCallback(
			function ( string $source, int $from, int $to, int $limit = PHP_INT_MAX, ?callable $expand = null ) use ( $rows_for_source ): array {
				$rows = array();

				foreach ( $rows_for_source( $source ) as $row ) {
					if ( null === $expand ) {
						$rows[] = $row;
						continue;
					}

					foreach ( $expand( $row ) as $expanded ) {
						$rows[] = $expanded;
					}
				}

				return array(
					'rows'         => $rows,
					'covered_from' => $from,
				);
			}
		);
	}

	/**
	 * Makes the file reader answer from the same shape willReturnMap took,
	 * applying the expansion and returning the coverage the real reader now
	 * returns, since the filtering happens as each line is read.
	 *
	 * @param array<int, array{0: string, 1: int, 2: int, 3: array}> $map Source, from, to and the rows to return.
	 * @return void
	 */
	private function file_reader_returns( array $map ): void {
		$by_source = array();

		foreach ( $map as $entry ) {
			$by_source[ $entry[0] ] = $entry[3];
		}

		$this->file_reader->method( 'read' )->willReturnCallback(
			function ( string $source, int $from, int $to, int $limit = PHP_INT_MAX, ?callable $expand = null ) use ( $by_source ): array {
				$rows = array();

				foreach ( $by_source[ $source ] ?? array() as $row ) {
					if ( null === $expand ) {
						$rows[] = $row;
						continue;
					}

					foreach ( $expand( $row ) as $expanded ) {
						$rows[] = $expanded;
					}
				}

				return array(
					'rows'         => $rows,
					'covered_from' => $from,
				);
			}
		);
	}

	/**
	 * Builds a row whose context names tokens in arrays, as the aggregated
	 * lines do.
	 *
	 * @param int    $timestamp   The line's timestamp.
	 * @param string $type        The notification type.
	 * @param int    $resource_id The order, comment or product ID.
	 * @param string $step        The step name.
	 * @param string $outcome     The outcome.
	 * @param array  $extra       Context fields naming the tokens.
	 * @return array
	 */
	private function aggregate_row( int $timestamp, string $type, int $resource_id, string $step, string $outcome, array $extra ): array {
		$row            = $this->row( $timestamp, $type, $resource_id, $step, $outcome );
		$row['context'] = array_merge( $row['context'], $extra );

		return $row;
	}

	/**
	 * Builds a parsed row as the readers return it.
	 *
	 * @param int      $timestamp   The line's timestamp.
	 * @param string   $type        The notification type.
	 * @param int      $resource_id The order, comment or product ID.
	 * @param string   $step        The step name.
	 * @param string   $outcome     The outcome.
	 * @param string   $level       The log level.
	 * @param int|null $token_id    The push token post ID, for a per-token row.
	 * @param int|null $user_id     The user who owns the token.
	 * @return array
	 */
	private function row( int $timestamp, string $type, int $resource_id, string $step, string $outcome, string $level = 'info', ?int $token_id = null, ?int $user_id = null ): array {
		$context = array(
			'identifier'  => '1_' . $type . '_' . $resource_id,
			'type'        => $type,
			'resource_id' => $resource_id,
			'step'        => $step,
			'outcome'     => $outcome,
		);

		if ( null !== $token_id ) {
			$context['token_id'] = $token_id;
			$context['user_id']  = $user_id;
		}

		return array(
			'timestamp' => $timestamp,
			'level'     => $level,
			'message'   => $step . ': ' . $outcome,
			'context'   => $context,
			'raw'       => null,
		);
	}
}
