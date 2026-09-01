<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\StockNotifications\DataRetentionController;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\NotificationsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Tests that the migration and Core's unverified-row retention sweep do not interfere.
 *
 * The retention threshold is deliberately absent from the candidate path: reading it there
 * would make the population being migrated depend on a setting a merchant can change
 * mid-run.
 */
class RetentionInteractionTests extends WC_Unit_Test_Case {

	/**
	 * Core option holding the unverified-deletion threshold, in days.
	 *
	 * @var string
	 */
	private const THRESHOLD_OPTION = 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold';

	/**
	 * A published simple product every seeded row points at.
	 *
	 * @var int
	 */
	private int $product_id;

	/**
	 * Set up the legacy tables and a product to subscribe to.
	 */
	public function setUp(): void {
		parent::setUp();

		LegacyStore::create_tables();
		LegacyStore::truncate_all();
		delete_option( self::THRESHOLD_OPTION );
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		$product = new \WC_Product_Simple();
		$product->save();
		$this->product_id = $product->get_id();
	}

	/**
	 * Clean up the tables, the threshold and the daily task.
	 */
	public function tearDown(): void {
		( new DataRetentionController() )->clear_daily_task();

		LegacyStore::drop_tables();
		delete_option( self::THRESHOLD_OPTION );
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );

		parent::tearDown();
	}

	/**
	 * @testdox the candidate set should be identical whatever the retention threshold is.
	 */
	public function test_candidate_set_ignores_the_retention_threshold(): void {
		$this->seed_rows( 3 );

		update_option( self::THRESHOLD_OPTION, 1 );
		$one_day = $this->candidates();

		update_option( self::THRESHOLD_OPTION, 365 );
		$one_year = $this->candidates();

		$this->assertNotEmpty( $one_day );
		$this->assertSame( $one_day, $one_year, 'The threshold must not reach the candidate query.' );
	}

	/**
	 * @testdox the retention sweep should delete nothing the migration wrote.
	 */
	public function test_retention_sweep_leaves_migrated_rows_alone(): void {
		$this->seed_rows( 3, 1500000000 );

		$this->migrate();
		$before = LegacyStore::get_core_rows();
		$this->assertCount( 3, $before );

		update_option( self::THRESHOLD_OPTION, 1 );
		( new DataRetentionController() )->do_wc_customer_stock_notifications_daily();

		$this->assertSame( $before, LegacyStore::get_core_rows(), 'No migrated row is pending, so none is swept.' );

		// And a re-run adds nothing back, since every row still carries its marker.
		$this->migrate();
		$this->assertSame( $before, LegacyStore::get_core_rows() );
	}

	/**
	 * @testdox writing the threshold should schedule the daily task once.
	 */
	public function test_writing_the_threshold_schedules_the_daily_task_once(): void {
		$controller = new DataRetentionController();

		$this->assertFalse( wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );

		update_option( self::THRESHOLD_OPTION, 30 );
		$this->assertSame( 'daily', wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK ) );

		$first_run = wp_next_scheduled( DataRetentionController::DAILY_TASK_HOOK );

		update_option( self::THRESHOLD_OPTION, 30 );
		$this->assertSame( $first_run, wp_next_scheduled( DataRetentionController::DAILY_TASK_HOOK ), 'Rewriting the same value must not reschedule.' );

		$controller->clear_daily_task();
	}

	/**
	 * Seed eligible legacy rows.
	 *
	 * @param int $count       How many rows to seed.
	 * @param int $create_date Legacy create date, as a Unix timestamp.
	 * @return void
	 */
	private function seed_rows( int $count, int $create_date = 1600000000 ): void {
		for ( $i = 0; $i < $count; $i++ ) {
			LegacyStore::add_notification(
				array(
					'product_id'  => $this->product_id,
					'user_email'  => "shopper{$i}@example.com",
					'create_date' => $create_date,
				)
			);
		}
	}

	/**
	 * The current candidate legacy ids.
	 *
	 * @return int[]
	 */
	private function candidates(): array {
		return ( new NotificationsMigrator( new Reporter() ) )->get_batch( 0, 100 );
	}

	/**
	 * Migrate every outstanding legacy row.
	 *
	 * @return void
	 */
	private function migrate(): void {
		$migrator = new NotificationsMigrator( new Reporter() );
		$migrator->migrate_batch( $migrator->get_batch( 0, 100 ), wc_get_container()->get( Writer::class ) );
	}
}
