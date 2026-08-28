<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\BulkNotificationWriter;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests for the bulk writer: null handling, chunking, and the transaction that keeps a Core
 * row from being left behind without its migration marker.
 */
class BulkNotificationWriterTests extends WC_Unit_Test_Case {

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
	 * while the migration runs. Under `innodb_autoinc_lock_mode = 2` — the default on MySQL 8
	 * and MariaDB — that insert can take an id from inside the block this chunk is being
	 * given, so ids derived by arithmetic from `$wpdb->insert_id` shift and stamp the legacy
	 * marker onto the wrong subscriber. A single statement cannot be interleaved from one
	 * thread, so the same non-consecutive layout is produced with the session increment.
	 */
	public function test_meta_follows_its_own_row_when_ids_are_not_consecutive(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'wc_stock_notifications';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'SET SESSION auto_increment_increment = 3' );

		try {
			( new BulkNotificationWriter() )->insert_notifications(
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

		foreach ( $expected as $email => $legacy_id ) {
			$stored = $wpdb->get_var( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are $wpdb->prefix-based, never user input.
					"SELECT m.meta_value FROM {$wpdb->prefix}wc_stock_notificationmeta m INNER JOIN {$table} n ON n.id = m.notification_id WHERE n.user_email = %s AND m.meta_key = '_wc_bis_legacy_id'",
					$email
				)
			);

			$this->assertSame( $legacy_id, $stored, "The marker for {$email} must sit on that subscriber's own row." );
		}
	}

	/**
	 * @testdox a null date column should be written as SQL NULL, not a zero date.
	 */
	public function test_null_dates_are_written_as_null(): void {
		( new BulkNotificationWriter() )->insert_notifications( array( $this->build_row( 'shopper@example.com' ) ) );

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
		( new BulkNotificationWriter() )->insert_notifications(
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
	 * @testdox a chunked write should keep every row's meta with its own row.
	 */
	public function test_chunked_write_keeps_meta_aligned(): void {
		$rows = array();

		for ( $i = 1; $i <= 5; $i++ ) {
			$rows[] = $this->build_row( "shopper{$i}@example.com", array( array( '_wc_bis_legacy_id', $i ) ) );
		}

		// A chunk size below the row count forces several statements, each with its own id block.
		( new BulkNotificationWriter( 2 ) )->insert_notifications( $rows );

		$core_rows = LegacyStore::get_core_rows();
		$markers   = LegacyStore::get_core_meta( '_wc_bis_legacy_id' );

		$this->assertCount( 5, $core_rows );

		foreach ( $core_rows as $index => $core_row ) {
			$this->assertSame( array( (string) ( $index + 1 ) ), $markers[ (int) $core_row['id'] ] );
		}
	}

	/**
	 * @testdox a failure between the two inserts should roll the whole chunk back.
	 */
	public function test_failed_meta_insert_rolls_the_chunk_back(): void {
		$writer = new BulkNotificationWriter();
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
