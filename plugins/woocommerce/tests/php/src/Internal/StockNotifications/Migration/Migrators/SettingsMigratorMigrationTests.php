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
 * fingerprint is the only thing standing between a re-run and a merchant's own edits.
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
	 * @testdox an option this migration wrote should be rewritten only when the legacy source changed.
	 */
	public function test_unchanged_option_is_rewritten_only_when_the_source_changes(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );
		$this->migrate();

		$migrator = $this->build_migrator();
		$this->assertSame( array(), $migrator->get_batch( 0, 10 ), 'Nothing changed, so nothing is outstanding.' );

		update_option( self::LEGACY_ALLOW_SIGNUPS, 'yes' );

		$this->assertContains( self::LEGACY_ALLOW_SIGNUPS, $this->build_migrator()->get_batch( 0, 10 ) );

		$this->migrate();
		$this->assertSame( 'yes', get_option( self::CORE_ALLOW_SIGNUPS ) );
	}

	/**
	 * @testdox a merchant-edited option should be left alone and reported once.
	 */
	public function test_merchant_edited_option_is_skipped_and_reported_once(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );
		$this->migrate();

		update_option( self::CORE_ALLOW_SIGNUPS, 'yes' );

		$counts = $this->migrate();

		$this->assertSame( 1, $counts[ Reporter::OUTCOME_SKIPPED_USER_MODIFIED ] ?? 0 );
		$this->assertSame( 'yes', get_option( self::CORE_ALLOW_SIGNUPS ), 'The merchant\'s value must survive.' );

		// Reported once: the skip is recorded, so the option stops being outstanding.
		$this->assertSame( array(), $this->build_migrator()->get_batch( 0, 10 ) );
	}

	/**
	 * @testdox force should overwrite only a merchant-edited option.
	 */
	public function test_force_overwrites_only_the_merchant_edited_case(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );
		$this->migrate();

		update_option( self::CORE_ALLOW_SIGNUPS, 'yes' );
		$this->migrate();

		$counts = $this->migrate( true );

		$this->assertSame( 1, $counts[ Reporter::OUTCOME_MIGRATED ] ?? 0 );
		$this->assertSame( 'no', get_option( self::CORE_ALLOW_SIGNUPS ), 'Force writes the legacy value over the edit.' );
	}

	/**
	 * @testdox an integer option should round-trip without reporting itself as edited.
	 */
	public function test_integer_option_round_trips_without_a_false_edit(): void {
		update_option( self::LEGACY_THRESHOLD, 30 );

		$this->migrate();

		// Loose comparison: within one request the option cache hands back the int that was
		// written, while a later request reads the string the database stored.
		$this->assertEquals( 30, get_option( self::CORE_THRESHOLD ) );
		$this->assertSame(
			array(),
			$this->build_migrator()->get_batch( 0, 10 ),
			'A value that reads back as a string must not look like a merchant edit.'
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
		$this->assertNull( $this->state->get_option_fingerprint( self::CORE_ALLOW_SIGNUPS ) );
	}

	/**
	 * Run the settings section to completion.
	 *
	 * @param bool $force Whether to overwrite merchant-edited options.
	 * @return array<string,int> Outcome counts from the last batch.
	 */
	private function migrate( bool $force = false ): array {
		$migrator = $this->build_migrator( $force );
		$counts   = array();
		$batches  = 0;

		while ( true ) {
			$batch = $migrator->get_batch( 0, 10 );

			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $migrator->migrate_batch( $batch, new DbWriter() ) as $outcome => $count ) {
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
	 * @param bool $force Whether to overwrite merchant-edited options.
	 * @return SettingsMigrator
	 */
	private function build_migrator( bool $force = false ): SettingsMigrator {
		return new SettingsMigrator( $this->state, new Reporter(), $force );
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
