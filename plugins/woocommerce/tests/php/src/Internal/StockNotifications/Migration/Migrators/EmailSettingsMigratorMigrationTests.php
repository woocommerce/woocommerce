<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use WC_Unit_Test_Case;

/**
 * Tests for the email settings section, which merges per sub-key rather than writing whole
 * settings arrays, and for the legacy-to-Core email pairing read back from what landed.
 */
class EmailSettingsMigratorMigrationTests extends WC_Unit_Test_Case {

	/**
	 * Legacy option to Core option, as the migrator maps them.
	 *
	 * @var array<string,string>
	 */
	private const OPTION_MAP = array(
		'woocommerce_bis_notification_received_settings' => 'woocommerce_customer_stock_notification_settings',
		'woocommerce_bis_notification_verify_settings'   => 'woocommerce_customer_stock_notification_verify_settings',
		'woocommerce_bis_notification_confirm_settings'  => 'woocommerce_customer_stock_notification_verified_settings',
	);

	/**
	 * Run state.
	 *
	 * @var MigrationState
	 */
	private MigrationState $state;

	/**
	 * Set up a clean state.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_options();
		$this->state = new MigrationState();
	}

	/**
	 * Clear the options this section reads and writes.
	 */
	public function tearDown(): void {
		$this->clear_options();

		parent::tearDown();
	}

	/**
	 * @testdox migrating one sub-key should leave a hand-edited sibling alone.
	 */
	public function test_sub_key_write_does_not_clobber_a_sibling(): void {
		update_option(
			'woocommerce_bis_notification_received_settings',
			array(
				'subject' => 'Legacy subject',
				'heading' => 'Legacy heading',
			)
		);

		$this->migrate();

		$core            = (array) get_option( 'woocommerce_customer_stock_notification_settings' );
		$core['heading'] = 'Merchant heading';
		update_option( 'woocommerce_customer_stock_notification_settings', $core );

		$this->migrate();

		$core = (array) get_option( 'woocommerce_customer_stock_notification_settings' );

		$this->assertSame( 'Legacy subject', $core['subject'], 'A migrated sub-key must keep the value it was given.' );
		$this->assertSame( 'Merchant heading', $core['heading'], 'A hand-edited sibling must survive.' );
	}

	/**
	 * @testdox the copy of each legacy email should land in its paired Core email.
	 */
	public function test_each_legacy_email_lands_in_its_paired_core_option(): void {
		foreach ( array_keys( self::OPTION_MAP ) as $legacy_key ) {
			update_option( $legacy_key, array( 'subject' => "Subject from {$legacy_key}" ) );
		}

		$this->migrate();

		foreach ( self::OPTION_MAP as $legacy_key => $core_key ) {
			$core = (array) get_option( $core_key );

			$this->assertSame( "Subject from {$legacy_key}", $core['subject'] ?? '', "Copy landed in the wrong Core email for {$legacy_key}." );
		}
	}

	/**
	 * @testdox a write that does not land should be reported and retried, not marked migrated.
	 */
	public function test_a_write_that_did_not_land_is_retried(): void {
		update_option(
			'woocommerce_bis_notification_received_settings',
			array( 'subject' => 'Legacy subject' )
		);

		// A writer that reports success without writing, the way a filtered-away
		// update_option() looks from here.
		$silent = new class() extends Writer {
			/**
			 * Discard the write and report success.
			 *
			 * @param string $option Option name.
			 * @param mixed  $value  Option value.
			 * @return bool
			 */
			public function write_option( string $option, $value ): bool {
				return true;
			}
		};

		$migrator = $this->build_migrator();
		$batch    = $migrator->get_batch( 0, 50 );
		$counts   = $migrator->migrate_batch( $batch, $silent );

		$this->assertGreaterThan( 0, $counts[ Reporter::OUTCOME_FAILED ] ?? 0, 'A write that did not land is a failure.' );

		// Nothing was marked migrated, so the sub-key is still outstanding.
		$this->assertContains(
			'woocommerce_bis_notification_received_settings::woocommerce_customer_stock_notification_settings::subject',
			$this->build_migrator()->get_batch( 0, 50 ),
			'A write that did not land must stay outstanding.'
		);

		// The next run writes again rather than settling on the value that never landed.
		$this->migrate();

		$stored = (array) get_option( 'woocommerce_customer_stock_notification_settings', array() );
		$this->assertSame( 'Legacy subject', $stored['subject'] ?? '' );
	}

	/**
	 * @testdox a completed run should leave nothing outstanding and write nothing on a second pass.
	 */
	public function test_a_completed_run_does_not_repeat_writes(): void {
		update_option(
			'woocommerce_bis_notification_received_settings',
			array( 'subject' => 'Legacy subject' )
		);

		$this->migrate();

		$this->assertSame( array(), $this->build_migrator()->get_batch( 0, 50 ), 'Nothing should remain outstanding after a completed run.' );

		$writer = $this->getMockBuilder( Writer::class )->onlyMethods( array( 'write_option' ) )->getMock();
		$writer->expects( $this->never() )->method( 'write_option' );

		$counts = $this->build_migrator()->migrate_batch( $this->build_migrator()->get_batch( 0, 50 ), $writer );

		$this->assertSame( array(), $counts, 'A second run should report nothing.' );
	}

	/**
	 * Run the email section to completion.
	 *
	 * @return array<string,int> Accumulated outcome counts.
	 */
	private function migrate(): array {
		$migrator = $this->build_migrator();
		$counts   = array();
		$batches  = 0;

		while ( true ) {
			$batch = $migrator->get_batch( 0, 50 );

			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) ) as $outcome => $count ) {
				$counts[ $outcome ] = ( $counts[ $outcome ] ?? 0 ) + $count;
			}

			++$batches;
			$this->assertLessThan( 10, $batches, 'The emails section failed to drain.' );
		}

		return $counts;
	}

	/**
	 * Build a migrator sharing this test's state.
	 *
	 * @return EmailSettingsMigrator
	 */
	private function build_migrator(): EmailSettingsMigrator {
		return new EmailSettingsMigrator( $this->state, new Reporter() );
	}

	/**
	 * Delete the legacy and Core email options, plus the run state.
	 *
	 * @return void
	 */
	private function clear_options(): void {
		delete_option( 'wc_bis_migration_state' );

		foreach ( self::OPTION_MAP as $legacy_key => $core_key ) {
			delete_option( $legacy_key );
			delete_option( $core_key );
		}
	}
}
