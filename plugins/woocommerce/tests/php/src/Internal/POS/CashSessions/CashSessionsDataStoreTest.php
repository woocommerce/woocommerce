<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\CashSessions\CashSessionsDataStore;
use WC_Install;
use WC_Unit_Test_Case;

/**
 * Tests for the CashSessionsDataStore class.
 */
class CashSessionsDataStoreTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CashSessionsDataStore
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( CashSessionsDataStore::class );
	}

	/**
	 * @testdox Should create the cash session tables on install and list them as WooCommerce tables.
	 */
	public function test_tables_are_installed_and_listed(): void {
		global $wpdb;

		foreach ( array( $this->sut->get_sessions_table(), $this->sut->get_movements_table(), $this->sut->get_drawer_events_table() ) as $table ) {
			$this->assertSame( $table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ), "$table should exist" );
			$this->assertContains( $table, WC_Install::get_tables(), "$table should be listed for uninstall" );
		}
	}

	/**
	 * @testdox Should allow only one open session per device at the database level.
	 */
	public function test_open_device_is_unique(): void {
		$first = $this->sut->insert_session( $this->session_row( 'device-1', 'aaaaaaaa-0000-4000-8000-000000000001' ) );
		$this->assertGreaterThan( 0, $first );

		$this->assertNull(
			$this->sut->insert_session( $this->session_row( 'device-1', 'aaaaaaaa-0000-4000-8000-000000000002' ) ),
			'A second open session for the same device should be rejected'
		);
		$this->assertGreaterThan(
			0,
			$this->sut->insert_session( $this->session_row( 'device-2', 'aaaaaaaa-0000-4000-8000-000000000003' ) ),
			'Another device can open a session'
		);
		$this->assertSame( $first, (int) $this->sut->find_open_session_by_device( 'device-1' )['id'] );
		$this->assertNull( $this->sut->find_open_session_by_device( 'DEVICE-1' ), 'Device IDs match exactly' );
	}

	/**
	 * @testdox Should reject a second movement for the same source or the same request in one session.
	 */
	public function test_movement_source_and_request_are_unique(): void {
		$session_id = $this->sut->insert_session( $this->session_row( 'device-1', 'aaaaaaaa-0000-4000-8000-000000000001' ) );
		$other_id   = $this->sut->insert_session( $this->session_row( 'device-2', 'aaaaaaaa-0000-4000-8000-000000000002' ) );

		$this->assertGreaterThan( 0, $this->sut->insert_movement( $this->movement_row( $session_id, 'bbbbbbbb-0000-4000-8000-000000000001', 'order:10' ) ) );
		$this->assertNull(
			$this->sut->insert_movement( $this->movement_row( $other_id, 'bbbbbbbb-0000-4000-8000-000000000002', 'order:10' ) ),
			'A source can be recorded once per site'
		);
		$this->assertNull(
			$this->sut->insert_movement( $this->movement_row( $session_id, 'bbbbbbbb-0000-4000-8000-000000000001', null ) ),
			'A request ID can be used once per session'
		);
		$this->assertGreaterThan(
			0,
			$this->sut->insert_movement( $this->movement_row( $other_id, 'bbbbbbbb-0000-4000-8000-000000000001', null ) ),
			'Request IDs are scoped to the session'
		);
		$this->assertSame( $session_id, (int) $this->sut->find_movement_by_source( 'order:10' )['session_id'] );
	}

	/**
	 * @testdox Should sum movements per session and type with exact integers.
	 */
	public function test_get_movement_sums(): void {
		$session_id = $this->sut->insert_session( $this->session_row( 'device-1', 'aaaaaaaa-0000-4000-8000-000000000001' ) );
		$this->sut->insert_movement(
			array_merge(
				$this->movement_row( $session_id, null, null ),
				array(
					'type'   => 'paid_in',
					'amount' => 999999999999999,
				)
			)
		);
		$this->sut->insert_movement(
			array_merge(
				$this->movement_row( $session_id, null, null ),
				array(
					'type'   => 'paid_in',
					'amount' => 1,
				)
			)
		);
		$this->sut->insert_movement(
			array_merge(
				$this->movement_row( $session_id, null, null ),
				array(
					'type'   => 'paid_out',
					'amount' => 5,
				)
			)
		);

		$sums = $this->sut->get_movement_sums( array( $session_id ) );

		$this->assertSame( 1000000000000000, $sums[ $session_id ]['paid_in'] );
		$this->assertSame( 5, $sums[ $session_id ]['paid_out'] );
	}

	/**
	 * @testdox Should close only an open session at the expected revision and release the device.
	 */
	public function test_close_session_checks_status_and_revision(): void {
		$session_id = $this->sut->insert_session( $this->session_row( 'device-1', 'aaaaaaaa-0000-4000-8000-000000000001' ) );
		$this->assertTrue( $this->sut->bump_revision( $session_id ) );

		$fields = array(
			'counted_amount'     => 0,
			'expected_amount'    => 0,
			'variance'           => 0,
			'note'               => '',
			'closed_by'          => 1,
			'closed_by_name'     => 'admin',
			'date_closed_gmt'    => '2026-09-25 12:00:00',
			'close_request_id'   => 'cccccccc-0000-4000-8000-000000000001',
			'close_request_hash' => str_repeat( 'a', 64 ),
		);
		$this->assertFalse( $this->sut->close_session( $session_id, 1, $fields ), 'A stale revision should not close' );
		$this->assertTrue( $this->sut->close_session( $session_id, 2, $fields ) );
		$this->assertFalse( $this->sut->close_session( $session_id, 2, $fields ), 'A closed session cannot close again' );
		$this->assertFalse( $this->sut->bump_revision( $session_id ), 'A closed session revision cannot change' );
		$this->assertNull( $this->sut->find_open_session_by_device( 'device-1' ) );
	}

	/**
	 * Build a session row.
	 *
	 * @param string $device_id  Device ID.
	 * @param string $request_id Open request ID.
	 * @return array<string, mixed>
	 */
	private function session_row( string $device_id, string $request_id ): array {
		return array(
			'device_id'          => $device_id,
			'drawer_name'        => null,
			'drawer_key'         => null,
			'currency'           => 'EUR',
			'currency_precision' => 2,
			'opened_by'          => 1,
			'opened_by_name'     => 'admin',
			'date_created_gmt'   => '2026-09-25 10:00:00',
			'open_request_id'    => $request_id,
			'open_request_hash'  => str_repeat( 'f', 64 ),
		);
	}

	/**
	 * Build a movement row.
	 *
	 * @param int         $session_id Session ID.
	 * @param string|null $request_id Request ID.
	 * @param string|null $source_key Source key.
	 * @return array<string, mixed>
	 */
	private function movement_row( int $session_id, ?string $request_id, ?string $source_key ): array {
		return array(
			'session_id'       => $session_id,
			'type'             => 'cash_sale',
			'amount'           => 1000,
			'reason'           => '',
			'order_id'         => 10,
			'refund_id'        => null,
			'source_key'       => $source_key,
			'created_by'       => 1,
			'created_by_name'  => 'admin',
			'occurred_at_gmt'  => '2026-09-25 10:00:00',
			'date_created_gmt' => '2026-09-25 10:00:00',
			'request_id'       => $request_id,
			'request_hash'     => null === $request_id ? null : str_repeat( 'e', 64 ),
		);
	}
}
