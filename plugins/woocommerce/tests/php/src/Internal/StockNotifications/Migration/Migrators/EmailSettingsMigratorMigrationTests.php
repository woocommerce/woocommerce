<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\EmailSettingsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\DbWriter;
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

		$legacy            = (array) get_option( 'woocommerce_bis_notification_received_settings' );
		$legacy['subject'] = 'New legacy subject';
		update_option( 'woocommerce_bis_notification_received_settings', $legacy );

		$this->migrate();

		$core = (array) get_option( 'woocommerce_customer_stock_notification_settings' );

		$this->assertSame( 'New legacy subject', $core['subject'], 'A changed source must still move.' );
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
	 * @testdox a merchant-edited sub-key should be reported once and then stop being outstanding.
	 */
	public function test_merchant_edited_sub_key_is_reported_once(): void {
		update_option( 'woocommerce_bis_notification_verify_settings', array( 'subject' => 'Legacy subject' ) );
		$this->migrate();

		$core            = (array) get_option( 'woocommerce_customer_stock_notification_verify_settings' );
		$core['subject'] = 'Merchant subject';
		update_option( 'woocommerce_customer_stock_notification_verify_settings', $core );

		$counts = $this->migrate();

		$this->assertGreaterThanOrEqual( 1, $counts[ Reporter::OUTCOME_SKIPPED_USER_MODIFIED ] ?? 0 );
		$this->assertSame(
			'Merchant subject',
			( (array) get_option( 'woocommerce_customer_stock_notification_verify_settings' ) )['subject']
		);

		$this->assertSame( array(), $this->build_migrator()->get_batch( 0, 50 ), 'The skip is recorded, so nothing stays outstanding.' );
	}

	/**
	 * Run the email section to completion.
	 *
	 * @param bool $force Whether to overwrite merchant-edited values.
	 * @return array<string,int> Accumulated outcome counts.
	 */
	private function migrate( bool $force = false ): array {
		$migrator = $this->build_migrator( $force );
		$counts   = array();
		$batches  = 0;

		while ( true ) {
			$batch = $migrator->get_batch( 0, 50 );

			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $migrator->migrate_batch( $batch, new DbWriter() ) as $outcome => $count ) {
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
	 * @param bool $force Whether to overwrite merchant-edited values.
	 * @return EmailSettingsMigrator
	 */
	private function build_migrator( bool $force = false ): EmailSettingsMigrator {
		return new EmailSettingsMigrator( $this->state, new Reporter(), $force );
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
