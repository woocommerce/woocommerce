<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the notification insert path: null handling, meta staying with its own row, and
 * the transaction that keeps a Core row from being left behind without its migration marker.
 */
class WriterInsertTests extends WC_Unit_Test_Case {

	/**
	 * Empty the Core tables before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		// Table names are $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notifications" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_stock_notificationmeta" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * @testdox meta must follow its own row when the generated ids are not consecutive.
	 *
	 * `wc_stock_notifications` takes live writes: a shopper can sign up for a restock alert
	 * while the migration runs, so the ids this call is given are not a block that can be
	 * walked by arithmetic. Each row is inserted on its own and reads its own
	 * `$wpdb->insert_id`, which holds however the ids come out; the session increment
	 * reproduces a non-consecutive layout from a single thread.
	 */
	public function test_meta_follows_its_own_row_when_ids_are_not_consecutive(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'wc_stock_notifications';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'SET SESSION auto_increment_increment = 3' );

		try {
			( new Writer() )->insert_notifications(
				array(
					$this->build_row( 'first@example.com', array( array( '_wc_bis_legacy_id', 101 ) ) ),
					$this->build_row( 'second@example.com', array( array( '_wc_bis_legacy_id', 102 ) ) ),
					$this->build_row( 'third@example.com', array( array( '_wc_bis_legacy_id', 103 ) ) ),
				)
			);
		} finally {
			$wpdb->query( 'SET SESSION auto_increment_increment = 1' );
		}

		$ids = $wpdb->get_col( "SELECT id FROM {$table} ORDER BY id ASC" );

		$this->assertCount( 3, $ids );
		$this->assertNotSame(
			array( (int) $ids[0], (int) $ids[0] + 1, (int) $ids[0] + 2 ),
			array_map( 'intval', $ids ),
			'This test is only meaningful while the generated ids are non-consecutive.'
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$expected = array(
			'first@example.com'  => '101',
			'second@example.com' => '102',
			'third@example.com'  => '103',
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( $expected as $email => $legacy_id ) {
			$stored = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT m.meta_value FROM {$wpdb->prefix}wc_stock_notificationmeta m
					 INNER JOIN {$table} n ON n.id = m.notification_id
					 WHERE n.user_email = %s AND m.meta_key = '_wc_bis_legacy_id'",
					$email
				)
			);

			$this->assertSame( $legacy_id, $stored, "The marker for {$email} landed on another subscriber's row." );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @testdox two legacy rows sharing a product and address must each keep their own marker.
	 */
	public function test_two_rows_sharing_a_product_and_address_each_keep_their_marker(): void {
		global $wpdb;

		( new Writer() )->insert_notifications(
			array(
				$this->build_row( 'twin@example.com', array( array( '_wc_bis_legacy_id', 201 ) ) ),
				$this->build_row( 'twin@example.com', array( array( '_wc_bis_legacy_id', 202 ) ) ),
			)
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$markers = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->prefix}wc_stock_notificationmeta WHERE meta_key = '_wc_bis_legacy_id' ORDER BY notification_id ASC" );

		$this->assertSame( array( '201', '202' ), $markers, 'Duplicate legacy subscriptions must not collapse onto one row.' );
	}

	/**
	 * @testdox a concurrent signup sharing a product and address must not disturb the write.
	 *
	 * The id each row was given comes from its own insert, so a row this call did not write
	 * is simply irrelevant, however close its key or its id.
	 */
	public function test_a_concurrent_row_sharing_a_key_is_irrelevant(): void {
		$writer = new Writer();

		// Stands in for the shopper signing up mid-run: same product, same address, no marker.
		$writer->insert_notifications( array( $this->build_row( 'shopper@example.com' ) ) );

		$writer->insert_notifications(
			array( $this->build_row( 'shopper@example.com', array( array( '_wc_bis_legacy_id', 301 ) ) ) )
		);

		$rows    = LegacyStore::get_core_rows();
		$markers = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );

		$this->assertCount( 2, $rows );
		$this->assertArrayNotHasKey( (int) $rows[0]['id'], $markers, "The shopper's own signup must stay unmarked." );
		$this->assertSame( array( '301' ), $markers[ (int) $rows[1]['id'] ] );
	}

	/**
	 * @testdox a null date column should be written as SQL NULL, not a zero date.
	 */
	public function test_null_dates_are_written_as_null(): void {
		( new Writer() )->insert_notifications( array( $this->build_row( 'shopper@example.com' ) ) );

		$row = LegacyStore::get_core_rows()[0];

		$this->assertNull( $row['date_last_attempt_gmt'], 'Core reads this column with IS NULL.' );
		$this->assertNull( $row['date_notified_gmt'] );
		$this->assertNull( $row['date_cancelled_gmt'] );
		$this->assertNull( $row['cancellation_source'] );
	}

	/**
	 * @testdox each row's meta should land against that row.
	 */
	public function test_meta_lands_against_its_own_row(): void {
		( new Writer() )->insert_notifications(
			array(
				$this->build_row( 'first@example.com', array( array( '_wc_bis_legacy_id', 11 ) ) ),
				$this->build_row( 'second@example.com', array( array( '_wc_bis_legacy_id', 22 ) ) ),
			)
		);

		$rows    = LegacyStore::get_core_rows();
		$markers = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );

		$this->assertSame( array( '11' ), $markers[ (int) $rows[0]['id'] ] );
		$this->assertSame( array( '22' ), $markers[ (int) $rows[1]['id'] ] );
	}

	/**
	 * @testdox a failure between a row and its meta should roll the whole call back.
	 */
	public function test_a_failed_meta_insert_rolls_the_call_back(): void {
		$writer = new Writer();
		$rows   = array( $this->build_row( 'shopper@example.com', array( array( '_wc_bis_legacy_id', 11 ) ) ) );

		$thrower = static function ( $query ) {
			if ( false !== stripos( $query, 'wc_stock_notificationmeta' ) && 0 === stripos( ltrim( $query ), 'INSERT' ) ) {
				throw new \RuntimeException( 'meta insert failed' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );

		try {
			$writer->insert_notifications( $rows );
			$this->fail( 'The writer should have propagated the failure.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'meta insert failed', $exception->getMessage() );
		} finally {
			remove_filter( 'query', $thrower );
		}

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'An unmarked Core row must never be left behind.' );
	}

	/**
	 * @testdox a failure on a later row must roll back the rows already inserted by the call.
	 */
	public function test_a_late_failure_rolls_back_the_earlier_rows(): void {
		$writer = new Writer();

		$thrower = static function ( $query ) {
			if ( false !== stripos( $query, 'late@example.com' ) ) {
				throw new \RuntimeException( 'row insert failed' );
			}

			return $query;
		};

		add_filter( 'query', $thrower );

		try {
			$writer->insert_notifications(
				array(
					$this->build_row( 'early@example.com', array( array( '_wc_bis_legacy_id', 41 ) ) ),
					$this->build_row( 'late@example.com', array( array( '_wc_bis_legacy_id', 42 ) ) ),
				)
			);
			$this->fail( 'The writer should have propagated the failure.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertSame( 'row insert failed', $exception->getMessage() );
		} finally {
			remove_filter( 'query', $thrower );
		}

		$this->assertSame( array(), LegacyStore::get_core_rows(), 'The call is one transaction; a late failure takes the earlier rows with it.' );
	}

	/**
	 * @testdox a malformed row must still roll the call back.
	 *
	 * A row whose `columns` is not an array reaches the typed helpers as a `\TypeError`,
	 * which is an `\Error` rather than an `\Exception`. Catching only `\Exception` would let
	 * it escape past the ROLLBACK and leave the connection mid-transaction for the rest of
	 * the request, with the earlier rows of the call still pending.
	 */
	public function test_a_malformed_row_rolls_the_call_back(): void {
		global $wpdb;

		$writer = new Writer();

		try {
			$writer->insert_notifications(
				array(
					$this->build_row( 'early@example.com', array( array( '_wc_bis_legacy_id', 51 ) ) ),
					array(
						'columns' => 'not-an-array',
						'meta'    => array(),
					),
				)
			);
			$this->fail( 'The writer should have propagated the failure.' );
		} catch ( \TypeError $error ) {
			// Read before any cleanup: an unrolled-back transaction still shows its own
			// pending row to this connection, so an empty table is the proof it rolled back.
			$this->assertSame( array(), LegacyStore::get_core_rows(), 'The earlier row of the call must not still be pending.' );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
		}
	}

	/**
	 * @testdox an empty row list should write nothing.
	 */
	public function test_an_empty_row_list_writes_nothing(): void {
		$this->assertSame( 0, ( new Writer() )->insert_notifications( array() ) );
		$this->assertSame( array(), LegacyStore::get_core_rows() );
	}

	/**
	 * Build one writer row.
	 *
	 * @param string $email Subscriber email.
	 * @param array  $meta  Meta rows in writer shape.
	 * @return array{columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}>}
	 */
	private function build_row( string $email, array $meta = array() ): array {
		return array(
			'columns' => array(
				'product_id'        => 1,
				'user_id'           => 0,
				'user_email'        => $email,
				'status'            => NotificationStatus::ACTIVE,
				'date_created_gmt'  => gmdate( 'Y-m-d H:i:s', 1600000000 ),
				'date_modified_gmt' => gmdate( 'Y-m-d H:i:s', 1600000000 ),
			),
			'meta'    => $meta,
		);
	}
}
