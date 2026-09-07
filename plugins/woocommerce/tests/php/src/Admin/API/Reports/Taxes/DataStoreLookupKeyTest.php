<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Taxes;

use Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore;
use ReflectionProperty;
use WC_Unit_Test_Case;

/**
 * Tests how the Taxes reports read the lookup when its primary key does not include the tax order
 * item, the shape a failed re-key in `WC_Install::create_tables()` leaves behind.
 *
 * These tests stand apart from DataStoreTest because they change the lookup's primary key, and
 * `ALTER TABLE` commits the transaction the test framework rolls back after each test. Anything
 * this class wrote before that point would stand for the rest of the run, so it seeds no data and
 * touches no options.
 */
class DataStoreLookupKeyTest extends WC_Unit_Test_Case {

	/**
	 * Forget what `DataStore::lookup_is_keyed_by_order_item()` read, so that a change to the
	 * lookup's primary key is seen.
	 */
	private function reset_lookup_key_cache(): void {
		$property = new ReflectionProperty( DataStore::class, 'lookup_keyed_by_order_item' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * @testdox The reports drop the replaced-row check while the lookup is not keyed by tax order item.
	 */
	public function test_reports_drop_the_replaced_row_check_while_the_lookup_is_not_re_keyed(): void {
		global $wpdb;

		$table_name = DataStore::get_db_table_name();

		// A row left over from an earlier test would hold the key change up, and the reports read
		// the shape of the table rather than its rows.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is not user input.
		$wpdb->query( "DELETE FROM `{$table_name}`" );

		// The shape a failed re-key leaves behind: dbDelta added the column, the key change never
		// landed. Nothing writes a per-line row there, so no row can have been replaced.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is not user input.
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP PRIMARY KEY, ADD PRIMARY KEY (order_id, tax_rate_id)" );
		$this->reset_lookup_key_cache();

		try {
			$condition = DataStore::get_legacy_row_condition();

			$this->assertStringNotContainsString( 'NOT EXISTS', $condition, 'The reports should not look for a per-line row that cannot be there.' );
			$this->assertSame( "{$table_name}.order_item_id = 0", $condition, 'The reports should read a row at zero the way the released report did.' );
		} finally {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name is not user input.
			$wpdb->query( "ALTER TABLE `{$table_name}` DROP PRIMARY KEY, ADD PRIMARY KEY (order_id, tax_rate_id, order_item_id)" );
			$this->reset_lookup_key_cache();
		}

		$this->assertStringContainsString( 'NOT EXISTS', DataStore::get_legacy_row_condition(), 'The check should be back once the re-key has landed.' );
	}
}
