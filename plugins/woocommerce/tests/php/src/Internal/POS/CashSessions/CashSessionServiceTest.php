<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionException;
use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionService;
use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionsDataStore;
use Automattic\WooCommerce\Internal\POS\CashSessions\CashSourceResolver;
use Automattic\WooCommerce\Internal\POS\CashSessions\DrawerName;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use WC_Unit_Test_Case;

/**
 * Tests for the CashSessionService class, with a stubbed data store so that the conflict, replay and
 * rollback branches a concurrent request would hit can run in a sequential test.
 */
class CashSessionServiceTest extends WC_Unit_Test_Case {

	private const SESSION_ID = 7;

	private const REQUEST_ID = 'aaaaaaaa-0000-4000-8000-000000000001';

	/**
	 * The System Under Test.
	 *
	 * @var CashSessionService
	 */
	private $sut;

	/**
	 * Stubbed storage.
	 *
	 * @var CashSessionsDataStore&MockObject
	 */
	private $data_store;

	/**
	 * Stubbed order and refund validation.
	 *
	 * @var CashSourceResolver&MockObject
	 */
	private $source_resolver;

	/**
	 * Transaction double.
	 *
	 * @var RecordingTransaction
	 */
	private $transaction;

	/**
	 * Current user.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_currency', 'EUR' );
		update_option( 'woocommerce_price_num_decimals', '2' );

		$this->user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $this->user_id );

		$this->source_resolver = $this->createMock( CashSourceResolver::class );
		$this->reset_sut();
	}

	/**
	 * @testdox Should reject trailing newlines in UUIDs before writing to storage.
	 *
	 * @testWith ["open", "request_id"]
	 *           ["close", "request_id"]
	 *           ["movement", "request_id"]
	 *           ["event", "request_id"]
	 *           ["event", "correlation_id"]
	 *
	 * @param string $operation Operation to call.
	 * @param string $field     Invalid UUID field.
	 */
	public function test_rejects_uuid_trailing_newline( string $operation, string $field ): void {
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->expects( $this->never() )->method( 'insert_session' );
		$this->data_store->expects( $this->never() )->method( 'insert_movement' );
		$this->data_store->expects( $this->never() )->method( 'insert_drawer_event' );
		$params           = array(
			'open'     => $this->open_params(),
			'close'    => $this->close_params(),
			'movement' => $this->paid_in_params(),
			'event'    => $this->drawer_event_params(),
		)[ $operation ];
		$params[ $field ] = self::REQUEST_ID . "\n";
		$calls            = array(
			'open'     => fn() => $this->sut->open_session( $params ),
			'close'    => fn() => $this->sut->close_session( self::SESSION_ID, $params ),
			'movement' => fn() => $this->sut->record_movement( self::SESSION_ID, $params ),
			'event'    => fn() => $this->sut->record_drawer_event( self::SESSION_ID, $params ),
		);

		$this->assert_error( $this->catch_error( $calls[ $operation ] ), 400, 'rest_invalid_param' );
		$this->assertSame( array(), $this->transaction->calls );
	}

	/**
	 * @testdox Should translate amount and drawer validation errors before returning REST error data.
	 *
	 * @testWith ["opening_amount", "bad", "woocommerce_rest_cash_invalid_amount"]
	 *           ["opening_amount", "1.001", "woocommerce_rest_cash_invalid_amount"]
	 *           ["opening_amount", "999999999999999999999", "woocommerce_rest_cash_invalid_amount"]
	 *           ["drawer_id", "", "woocommerce_rest_cash_invalid_drawer"]
	 *
	 * @param string $field Invalid field.
	 * @param string $value Invalid value.
	 * @param string $code  Expected error code.
	 */
	public function test_translates_value_validation_errors( string $field, string $value, string $code ): void {
		add_filter( 'gettext_woocommerce', static fn( $translation ) => 'Translated: ' . $translation );
		$params           = $this->open_params();
		$params[ $field ] = $value;

		$error = $this->catch_error( fn() => $this->sut->open_session( $params ) );

		$this->assert_error( $error, 400, $code );
		$this->assertStringStartsWith( 'Translated: ', $error->to_wp_error()->get_error_message() );
	}

	/**
	 * @testdox Should translate timestamp validation errors before returning REST error data.
	 *
	 * @testWith ["2026-09-25T12:00:00"]
	 *           ["2026-02-30T12:00:00Z"]
	 *
	 * @param string $timestamp Invalid timestamp.
	 */
	public function test_translates_timestamp_validation_errors( string $timestamp ): void {
		add_filter( 'gettext_woocommerce', static fn( $translation ) => 'Translated: ' . $translation );
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$params                = $this->drawer_event_params();
		$params['occurred_at'] = $timestamp;

		$error = $this->catch_error( fn() => $this->sut->record_drawer_event( self::SESSION_ID, $params ) );

		$this->assert_error( $error, 400, 'woocommerce_rest_cash_invalid_timestamp' );
		$this->assertStringStartsWith( 'Translated: ', $error->to_wp_error()->get_error_message() );
	}

	/**
	 * @testdox Should replay an open whose insert lost the race to the same request.
	 */
	public function test_open_insert_conflict_replays_same_request(): void {
		$stored = null;
		$this->data_store->method( 'insert_session' )->willReturnCallback(
			function ( array $row ) use ( &$stored ) {
				$stored = array_merge( $this->session_row(), $row );
				return null;
			}
		);
		$this->data_store->method( 'find_session_by_open_request' )->willReturnCallback(
			function () use ( &$stored ) {
				return $stored;
			}
		);

		$result = $this->sut->open_session( $this->open_params() );

		$this->assertFalse( $result['created'] );
		$this->assertSame( self::SESSION_ID, (int) $result['session']['row']['id'] );
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should report the open session when an open insert lost the race to another request on the device.
	 */
	public function test_open_insert_conflict_reports_open_device(): void {
		$this->data_store->method( 'insert_session' )->willReturn( null );
		$this->data_store->method( 'find_open_session_by_device' )->willReturnOnConsecutiveCalls( null, $this->session_row( array( 'id' => 99 ) ) );

		$error = $this->catch_error( fn() => $this->sut->open_session( $this->open_params() ) );

		$this->assert_error( $error, 409, 'woocommerce_rest_cash_session_already_open' );
		$this->assertSame( 99, $error->to_wp_error()->get_error_data()['session_id'] );
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should ask for a retry when an open insert conflicts with a row it cannot see yet.
	 */
	public function test_open_insert_conflict_in_progress(): void {
		$this->data_store->method( 'insert_session' )->willReturn( null );

		$this->assert_error( $this->catch_error( fn() => $this->sut->open_session( $this->open_params() ) ), 409, 'woocommerce_rest_cash_request_in_progress' );
	}

	/**
	 * @testdox Should roll back the session when the opening float cannot be stored.
	 */
	public function test_open_rolls_back_when_opening_float_fails(): void {
		$this->data_store->method( 'insert_session' )->willReturn( self::SESSION_ID );
		$this->data_store->method( 'insert_movement' )->willReturn( null );

		$this->expectException( RuntimeException::class );
		try {
			$this->sut->open_session( $this->open_params() );
		} finally {
			$this->assert_rolled_back();
		}
	}

	/**
	 * @testdox Should resolve a movement whose revision bump failed by replaying, reporting the close, or asking for a retry.
	 *
	 * @testWith ["replay", ""]
	 *           ["closed", "woocommerce_rest_cash_session_closed"]
	 *           ["open", "woocommerce_rest_cash_request_in_progress"]
	 *
	 * @param string $state         What the re-read finds.
	 * @param string $expected_code Expected error code, or empty for a replay.
	 */
	public function test_movement_bump_failure_is_resolved( string $state, string $expected_code ): void {
		$params = $this->paid_in_params();
		$hash   = $this->movement_hash( $params );

		$this->data_store->method( 'get_session' )->willReturnOnConsecutiveCalls(
			$this->session_row(),
			$this->session_row( array( 'status' => 'closed' === $state ? 'closed' : 'open' ) )
		);
		$this->data_store->method( 'bump_revision' )->willReturn( false );
		$this->data_store->method( 'find_movement_by_request' )->willReturnOnConsecutiveCalls(
			null,
			'replay' === $state ? $this->movement_row( array( 'request_hash' => $hash ) ) : null
		);

		if ( '' === $expected_code ) {
			$this->assertFalse( $this->sut->record_movement( self::SESSION_ID, $params )['created'] );
		} else {
			$this->assert_error( $this->catch_error( fn() => $this->sut->record_movement( self::SESSION_ID, $params ) ), 409, $expected_code );
		}
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should report the recorded source when a cash sale insert loses the race to another request.
	 */
	public function test_movement_insert_conflict_reports_source(): void {
		$this->source_resolver->method( 'resolve_sale' )->willReturn(
			array(
				'amount'          => 1000,
				'occurred_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'source_key'      => 'order:5',
			)
		);
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'bump_revision' )->willReturn( true );
		$this->data_store->method( 'get_movement_sums' )->willReturn( array() );
		$this->data_store->method( 'insert_movement' )->willReturn( null );
		$this->data_store->method( 'find_movement_by_source' )->willReturnOnConsecutiveCalls(
			null,
			$this->movement_row(
				array(
					'id'         => 41,
					'session_id' => 3,
				)
			)
		);

		$error = $this->catch_error(
			fn() => $this->sut->record_movement(
				self::SESSION_ID,
				array(
					'request_id' => self::REQUEST_ID,
					'type'       => 'cash_sale',
					'order_id'   => 5,
				)
			)
		);

		$this->assert_error( $error, 409, 'woocommerce_rest_cash_source_already_recorded' );
		$this->assertSame( 41, $error->to_wp_error()->get_error_data()['movement_id'] );
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should roll back a movement when a statement inside the transaction throws.
	 */
	public function test_movement_rolls_back_on_error(): void {
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'bump_revision' )->willReturn( true );
		$this->data_store->method( 'get_movement_sums' )->willThrowException( new RuntimeException( 'Lock wait timeout' ) );

		$this->expectException( RuntimeException::class );
		try {
			$this->sut->record_movement( self::SESSION_ID, $this->paid_in_params() );
		} finally {
			$this->assert_rolled_back();
		}
	}

	/**
	 * @testdox Should resolve a drawer event insert conflict by replaying, rejecting other data, or asking for a retry.
	 *
	 * @testWith ["same", ""]
	 *           ["different", "woocommerce_rest_cash_request_conflict"]
	 *           ["missing", "woocommerce_rest_cash_request_in_progress"]
	 *
	 * @param string $winner        The row the re-read finds.
	 * @param string $expected_code Expected error code, or empty for a replay.
	 */
	public function test_drawer_event_insert_conflict_is_resolved( string $winner, string $expected_code ): void {
		$hash = null;
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'lock_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'insert_drawer_event' )->willReturnCallback(
			function ( array $row ) use ( &$hash ) {
				$hash = $row['request_hash'];
				return null;
			}
		);
		$this->data_store->method( 'find_drawer_event_by_request' )->willReturnCallback(
			function () use ( &$hash, $winner ) {
				if ( null === $hash || 'missing' === $winner ) {
					return null;
				}
				return $this->drawer_event_row( array( 'request_hash' => 'same' === $winner ? $hash : str_repeat( '0', 64 ) ) );
			}
		);

		if ( '' === $expected_code ) {
			$this->assertFalse( $this->sut->record_drawer_event( self::SESSION_ID, $this->drawer_event_params() )['created'] );
		} else {
			$this->assert_error( $this->catch_error( fn() => $this->sut->record_drawer_event( self::SESSION_ID, $this->drawer_event_params() ) ), 409, $expected_code );
		}
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should report a revision conflict or a close when the close update matches no row.
	 *
	 * @testWith ["open", "woocommerce_rest_cash_session_revision_conflict"]
	 *           ["closed", "woocommerce_rest_cash_session_closed"]
	 *
	 * @param string $status        Status the re-read finds.
	 * @param string $expected_code Expected error code.
	 */
	public function test_close_update_failure_is_resolved( string $status, string $expected_code ): void {
		$this->data_store->method( 'get_session' )->willReturnOnConsecutiveCalls(
			$this->session_row(),
			$this->session_row(
				array(
					'status'   => $status,
					'revision' => 2,
				)
			)
		);
		$this->data_store->method( 'lock_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'get_movement_sums' )->willReturn( array() );
		$this->data_store->method( 'close_session' )->willReturn( false );

		$error = $this->catch_error( fn() => $this->sut->close_session( self::SESSION_ID, $this->close_params() ) );

		$this->assert_error( $error, 409, $expected_code );
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should replay an open retry that finds the device busy with the session its original just opened.
	 */
	public function test_open_retry_racing_original_replays(): void {
		$hash = $this->open_hash();
		$this->data_store->method( 'find_session_by_open_request' )->willReturnOnConsecutiveCalls( null, $this->session_row( array( 'open_request_hash' => $hash ) ) );
		$this->data_store->method( 'find_open_session_by_device' )->willReturn( $this->session_row() );

		$result = $this->sut->open_session( $this->open_params() );

		$this->assertFalse( $result['created'] );
		$this->assertSame( array(), $this->transaction->calls );
	}

	/**
	 * @testdox Should replay a cash sale retry that finds the order already recorded by its original.
	 */
	public function test_movement_retry_racing_original_replays(): void {
		$this->source_resolver->method( 'resolve_sale' )->willReturn(
			array(
				'amount'          => 1000,
				'occurred_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'source_key'      => 'order:5',
			)
		);
		$params = array(
			'request_id' => self::REQUEST_ID,
			'type'       => 'cash_sale',
			'order_id'   => 5,
		);
		$stored = $this->movement_row(
			array(
				'type'         => 'cash_sale',
				'order_id'     => 5,
				'source_key'   => 'order:5',
				'request_hash' => $this->movement_hash( $params ),
			)
		);
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'find_movement_by_request' )->willReturnOnConsecutiveCalls( null, $stored );
		$this->data_store->method( 'find_movement_by_source' )->willReturn( $stored );

		$result = $this->sut->record_movement( self::SESSION_ID, $params );

		$this->assertFalse( $result['created'] );
		$this->assertSame( 40, $result['movement']['id'] );
	}

	/**
	 * @testdox Should replay a paid out retry that raced its original instead of rejecting it for insufficient cash.
	 */
	public function test_paid_out_retry_racing_original_replays_before_cash_check(): void {
		$params = array(
			'request_id' => self::REQUEST_ID,
			'type'       => 'paid_out',
			'amount'     => '8.00',
			'reason'     => 'Supplier',
		);
		// A 10.00 float, so the hash helper's own paid out of 8.00 passes the cash check.
		$this->data_store->method( 'get_movement_sums' )->willReturn( array( self::SESSION_ID => array( 'opening_float' => 1000 ) ) );
		$stored = $this->movement_row(
			array(
				'type'         => 'paid_out',
				'amount'       => 800,
				'reason'       => 'Supplier',
				'request_hash' => $this->movement_hash( $params ),
			)
		);
		// The retry misses the first lookup, then finds the original once it holds the session lock. The totals
		// already include the original 8.00, leaving 2.00, so a cash check before the replay would reject it.
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'bump_revision' )->willReturn( true );
		$this->data_store->method( 'get_movement_sums' )->willReturn(
			array(
				self::SESSION_ID => array(
					'opening_float' => 1000,
					'paid_out'      => 800,
				),
			)
		);
		$this->data_store->method( 'find_movement_by_request' )->willReturnOnConsecutiveCalls( null, $stored );
		$this->data_store->expects( $this->never() )->method( 'insert_movement' );

		$result = $this->sut->record_movement( self::SESSION_ID, $params );

		$this->assertFalse( $result['created'] );
		$this->assertSame( 40, $result['movement']['id'] );
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should replay a drawer event retry that finds the session closed after its original was stored.
	 */
	public function test_drawer_event_retry_racing_original_replays(): void {
		$params = $this->drawer_event_params();
		$stored = $this->drawer_event_row( array( 'request_hash' => $this->drawer_event_hash( $params ) ) );
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'lock_session' )->willReturn( $this->session_row( array( 'status' => 'closed' ) ) );
		$this->data_store->method( 'find_drawer_event_by_request' )->willReturnOnConsecutiveCalls( null, $stored );

		$result = $this->sut->record_drawer_event( self::SESSION_ID, $params );

		$this->assertFalse( $result['created'] );
		$this->assertSame( 60, $result['event']['id'] );
		$this->assert_rolled_back();
	}

	/**
	 * @testdox Should re-read a session whose revision changed while its totals were read, so both match.
	 */
	public function test_get_session_pairs_revision_with_totals(): void {
		$this->data_store->method( 'get_session' )->willReturnOnConsecutiveCalls(
			$this->session_row(),
			$this->session_row( array( 'revision' => 2 ) )
		);
		$this->data_store->method( 'get_movement_sums' )->willReturn( array( self::SESSION_ID => array( 'paid_in' => 500 ) ) );
		$this->data_store->method( 'get_session_revisions' )->willReturn( array( self::SESSION_ID => 2 ) );

		$session = $this->sut->get_session( self::SESSION_ID );

		$this->assertSame( 2, $session['row']['revision'], 'The revision should be the one the totals were read at' );
		$this->assertSame( 500, $session['totals']['paid_in'] );
	}

	/**
	 * @testdox Should ask for a retry when a session keeps changing while its totals are read.
	 */
	public function test_get_session_gives_up_when_revision_keeps_changing(): void {
		$revision = 1;
		$this->data_store->method( 'get_session' )->willReturnCallback(
			function () use ( &$revision ) {
				return $this->session_row( array( 'revision' => $revision ) );
			}
		);
		$this->data_store->method( 'get_movement_sums' )->willReturn( array() );
		$this->data_store->method( 'get_session_revisions' )->willReturnCallback(
			function () use ( &$revision ) {
				++$revision;
				return array( self::SESSION_ID => $revision );
			}
		);

		$error = $this->catch_error( fn() => $this->sut->get_session( self::SESSION_ID ) );

		$this->assert_error( $error, 409, 'woocommerce_rest_cash_session_busy' );
	}

	/**
	 * Build a new SUT with a fresh data store stub and transaction double.
	 */
	private function reset_sut(): void {
		$this->data_store  = $this->createMock( CashSessionsDataStore::class );
		$this->transaction = new RecordingTransaction();
		$this->sut         = new CashSessionService();
		$this->sut->init( $this->data_store, $this->source_resolver, $this->transaction );
	}

	/**
	 * Payload hash the service stores for a paid in request, captured from a successful write.
	 *
	 * @param array<string, mixed> $params Movement request.
	 * @return string
	 */
	private function movement_hash( array $params ): string {
		$hash = '';
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'bump_revision' )->willReturn( true );
		$this->data_store->method( 'get_movement_sums' )->willReturn( array() );
		$this->data_store->method( 'get_movement' )->willReturn( $this->movement_row() );
		$this->data_store->method( 'insert_movement' )->willReturnCallback(
			function ( array $row ) use ( &$hash ) {
				$hash = $row['request_hash'];
				return 1;
			}
		);
		$this->sut->record_movement( self::SESSION_ID, $params );

		$this->reset_sut();
		return $hash;
	}

	/**
	 * Payload hash the service stores for the open request, captured from a successful write.
	 *
	 * @return string
	 */
	private function open_hash(): string {
		$hash = '';
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'insert_movement' )->willReturn( 1 );
		$this->data_store->method( 'insert_session' )->willReturnCallback(
			function ( array $row ) use ( &$hash ) {
				$hash = $row['open_request_hash'];
				return self::SESSION_ID;
			}
		);
		$this->sut->open_session( $this->open_params() );

		$this->reset_sut();
		return $hash;
	}

	/**
	 * Payload hash the service stores for a drawer event request, captured from a successful write.
	 *
	 * @param array<string, mixed> $params Drawer event request.
	 * @return string
	 */
	private function drawer_event_hash( array $params ): string {
		$hash = '';
		$this->data_store->method( 'get_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'lock_session' )->willReturn( $this->session_row() );
		$this->data_store->method( 'get_drawer_event' )->willReturn( $this->drawer_event_row() );
		$this->data_store->method( 'insert_drawer_event' )->willReturnCallback(
			function ( array $row ) use ( &$hash ) {
				$hash = $row['request_hash'];
				return 60;
			}
		);
		$this->sut->record_drawer_event( self::SESSION_ID, $params );

		$this->reset_sut();
		return $hash;
	}

	/**
	 * Open request.
	 *
	 * @return array<string, mixed>
	 */
	private function open_params(): array {
		return array(
			'request_id'     => self::REQUEST_ID,
			'device_id'      => 'ipad-1',
			'opening_amount' => '10.00',
		);
	}

	/**
	 * Paid in request.
	 *
	 * @return array<string, mixed>
	 */
	private function paid_in_params(): array {
		return array(
			'request_id' => self::REQUEST_ID,
			'type'       => 'paid_in',
			'amount'     => '5.00',
			'reason'     => 'Change',
		);
	}

	/**
	 * Drawer event request.
	 *
	 * @return array<string, mixed>
	 */
	private function drawer_event_params(): array {
		return array(
			'request_id'  => self::REQUEST_ID,
			'type'        => 'opened',
			'reason'      => 'no_sale',
			'occurred_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	/**
	 * Close request.
	 *
	 * @return array<string, mixed>
	 */
	private function close_params(): array {
		return array(
			'request_id'        => self::REQUEST_ID,
			'expected_revision' => 1,
			'counted_amount'    => '10.00',
		);
	}

	/**
	 * Session row.
	 *
	 * @param array<string, mixed> $overrides Column values.
	 * @return array<string, mixed>
	 */
	private function session_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                 => self::SESSION_ID,
				'device_id'          => 'ipad-1',
				'drawer_name'        => 'Till',
				'drawer_key'         => DrawerName::key( 'Till' ),
				'status'             => 'open',
				'revision'           => 1,
				'currency'           => 'EUR',
				'currency_precision' => 2,
				'expected_amount'    => null,
				'counted_amount'     => null,
				'variance'           => null,
				'note'               => null,
				'opened_by'          => $this->user_id,
				'opened_by_name'     => 'Cashier',
				'closed_by'          => null,
				'closed_by_name'     => null,
				'date_created_gmt'   => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
				'date_closed_gmt'    => null,
				'open_request_id'    => 'bbbbbbbb-0000-4000-8000-000000000001',
				'open_request_hash'  => str_repeat( 'f', 64 ),
				'close_request_id'   => null,
				'close_request_hash' => null,
			),
			$overrides
		);
	}

	/**
	 * Movement row.
	 *
	 * @param array<string, mixed> $overrides Column values.
	 * @return array<string, mixed>
	 */
	private function movement_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'               => 40,
				'session_id'       => self::SESSION_ID,
				'type'             => 'paid_in',
				'amount'           => 500,
				'reason'           => 'Change',
				'order_id'         => null,
				'refund_id'        => null,
				'source_key'       => null,
				'created_by'       => $this->user_id,
				'created_by_name'  => 'Cashier',
				'occurred_at_gmt'  => gmdate( 'Y-m-d H:i:s' ),
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'request_id'       => self::REQUEST_ID,
				'request_hash'     => str_repeat( 'e', 64 ),
			),
			$overrides
		);
	}

	/**
	 * Drawer event row.
	 *
	 * @param array<string, mixed> $overrides Column values.
	 * @return array<string, mixed>
	 */
	private function drawer_event_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'               => 60,
				'session_id'       => self::SESSION_ID,
				'type'             => 'opened',
				'reason'           => 'no_sale',
				'drawer_name'      => 'Till',
				'order_id'         => null,
				'refund_id'        => null,
				'movement_id'      => null,
				'correlation_id'   => null,
				'occurred_at_gmt'  => gmdate( 'Y-m-d H:i:s' ),
				'created_by'       => $this->user_id,
				'created_by_name'  => 'Cashier',
				'date_created_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'request_id'       => self::REQUEST_ID,
				'request_hash'     => str_repeat( 'd', 64 ),
			),
			$overrides
		);
	}

	/**
	 * Run a call that must fail with a cash session error.
	 *
	 * @param callable $call Call.
	 * @return CashSessionException
	 */
	private function catch_error( callable $call ): CashSessionException {
		try {
			$call();
		} catch ( CashSessionException $e ) {
			return $e;
		}
		$this->fail( 'A CashSessionException was expected.' );
	}

	/**
	 * Assert that the write started a transaction, rolled it back and never committed.
	 */
	private function assert_rolled_back(): void {
		$this->assertSame( 'start', $this->transaction->calls[0] ?? null, 'The write should start a transaction' );
		$this->assertContains( 'rollback', $this->transaction->calls );
		$this->assertNotContains( 'commit', $this->transaction->calls );
	}

	/**
	 * Assert a cash session error.
	 *
	 * @param CashSessionException $error  Error.
	 * @param int                  $status Expected HTTP status.
	 * @param string               $code   Expected error code.
	 */
	private function assert_error( CashSessionException $error, int $status, string $code ): void {
		$wp_error = $error->to_wp_error();
		$this->assertSame( $code, $wp_error->get_error_code() );
		$this->assertSame( $status, $wp_error->get_error_data()['status'] );
	}
}
