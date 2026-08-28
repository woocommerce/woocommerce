<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\SettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\NullWriter;
use WC_Unit_Test_Case;

/**
 * Tests for the general settings section, where nothing carries a per-row marker and the
 * `MigrationState` write-once record is the only thing standing between a re-run and an
 * infinite rewrite loop.
 */
class SettingsMigratorMigrationTests extends WC_Unit_Test_Case {

	/**
	 * Legacy option holding the signup toggle.
	 *
	 * @var string
	 */
	private const LEGACY_ALLOW_SIGNUPS = 'wc_bis_allow_signups';

	/**
	 * Core option the signup toggle migrates to.
	 *
	 * @var string
	 */
	private const CORE_ALLOW_SIGNUPS = 'woocommerce_customer_stock_notifications_allow_signups';

	/**
	 * Legacy option holding the unverified-deletion threshold, an integer.
	 *
	 * @var string
	 */
	private const LEGACY_THRESHOLD = 'wc_bis_delete_unverified_days_threshold';

	/**
	 * Core option the threshold migrates to.
	 *
	 * @var string
	 */
	private const CORE_THRESHOLD = 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold';

	/**
	 * Run state.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Set up a clean state and legacy options.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_options();
		$this->state = new MigrationState();
	}

	/**
	 * Clear everything this section reads or writes.
	 */
	public function tearDown(): void {
		$this->clear_options();

		parent::tearDown();
	}

	/**
	 * @testdox an absent Core option should be written.
	 */
	public function test_absent_option_is_written(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );

		$this->migrate();

		$this->assertSame( 'no', get_option( self::CORE_ALLOW_SIGNUPS ) );
	}

	/**
	 * @testdox a second run must not re-migrate or rewrite anything already migrated.
	 */
	public function test_second_run_finds_nothing_outstanding_and_writes_nothing(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );
		$this->migrate();

		// Simulate a merchant edit after the first run to prove it is left alone.
		update_option( self::CORE_ALLOW_SIGNUPS, 'yes' );

		$migrator = $this->build_migrator();

		$this->assertSame( array(), $migrator->get_batch( 0, 10 ), 'A migrated key must never come back as outstanding.' );

		$counts = $migrator->migrate_batch( array( self::LEGACY_ALLOW_SIGNUPS ), wc_get_container()->get( DbWriter::class ) );

		$this->assertSame( array(), $counts, 'migrate_batch() was only exercised here to prove get_batch() already returned nothing.' );
		$this->assertSame( 'yes', get_option( self::CORE_ALLOW_SIGNUPS ), 'A second run must never overwrite a value it already migrated once.' );
	}

	/**
	 * @testdox an integer option should round-trip and settle after one migration.
	 */
	public function test_integer_option_round_trips_and_settles(): void {
		update_option( self::LEGACY_THRESHOLD, 30 );

		$this->migrate();

		// Loose comparison: within one request the option cache hands back the int that was
		// written, while a later request reads the string the database stored.
		$this->assertEquals( 30, get_option( self::CORE_THRESHOLD ) );
		$this->assertSame(
			array(),
			$this->build_migrator()->get_batch( 0, 10 ),
			'A key that has been migrated once must not stay outstanding.'
		);
	}

	/**
	 * @testdox a dry run should write nothing and still drain the section.
	 */
	public function test_dry_run_writes_nothing_and_drains(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );

		$migrator = $this->build_migrator();
		$writer   = new NullWriter();

		$batches = 0;
		while ( true ) {
			$batch = $migrator->get_batch( 0, 10 );

			if ( empty( $batch ) ) {
				break;
			}

			$migrator->migrate_batch( $batch, $writer );
			++$batches;

			$this->assertLessThan( 5, $batches, 'A dry run must terminate.' );
		}

		$this->assertFalse( get_option( self::CORE_ALLOW_SIGNUPS ) );
		$this->assertFalse( $this->state->is_option_migrated( self::CORE_ALLOW_SIGNUPS ) );
	}

	/**
	 * Run the settings section to completion.
	 *
	 * @return array<string,int> Outcome counts from the last batch.
	 */
	private function migrate(): array {
		$migrator = $this->build_migrator();
		$counts   = array();
		$batches  = 0;

		while ( true ) {
			$batch = $migrator->get_batch( 0, 10 );

			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $migrator->migrate_batch( $batch, wc_get_container()->get( DbWriter::class ) ) as $outcome => $count ) {
				$counts[ $outcome ] = ( $counts[ $outcome ] ?? 0 ) + $count;
			}

			++$batches;
			$this->assertLessThan( 10, $batches, 'The settings section failed to drain.' );
		}

		return $counts;
	}

	/**
	 * Build a migrator sharing this test's state.
	 *
	 * @return SettingsMigrator
	 */
	private function build_migrator(): SettingsMigrator {
		return new SettingsMigrator( $this->state, new Reporter() );
	}

	/**
	 * Delete every option this section touches, plus the run state.
	 *
	 * @return void
	 */
	private function clear_options(): void {
		delete_option( 'wc_bis_migration_state' );

		foreach (
			array(
				self::LEGACY_ALLOW_SIGNUPS,
				self::CORE_ALLOW_SIGNUPS,
				self::LEGACY_THRESHOLD,
				self::CORE_THRESHOLD,
				'wc_bis_double_opt_in_required',
				'woocommerce_customer_stock_notifications_require_double_opt_in',
				'wc_bis_account_required',
				'woocommerce_customer_stock_notifications_require_account',
				'wc_bis_create_new_account_on_registration',
				'woocommerce_customer_stock_notifications_create_account_on_signup',
			) as $option
		) {
			delete_option( $option );
		}
	}
}
