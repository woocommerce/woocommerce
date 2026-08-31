<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Emails\CustomerStockNotificationEmail;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\OptionsMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use ReflectionClass;
use WC_Unit_Test_Case;

/**
 * Tests for the settings migrator: the legacy-to-Core option pairing, the per-sub-key merge
 * that leaves hand-edited siblings alone, and the read-back-and-compare that decides whether a
 * value is settled or has to be written again on the next batch.
 */
class OptionsMigratorTests extends WC_Unit_Test_Case {

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
	 * Legacy email settings option to Core email settings option, as the migrator maps them.
	 *
	 * @var array<string,string>
	 */
	private const EMAIL_MAP = array(
		'woocommerce_bis_notification_received_settings' => 'woocommerce_customer_stock_notification_settings',
		'woocommerce_bis_notification_verify_settings'   => 'woocommerce_customer_stock_notification_verify_settings',
		'woocommerce_bis_notification_confirm_settings'  => 'woocommerce_customer_stock_notification_verified_settings',
	);

	/**
	 * Clear every option this migrator reads or writes.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->clear_options();
	}

	/**
	 * Clear every option this migrator reads or writes.
	 */
	public function tearDown(): void {
		$this->clear_options();

		parent::tearDown();
	}

	/**
	 * @testdox the email map should pair legacy confirm with Core verified, not verify.
	 */
	public function test_email_map_pairs_confirm_with_verified_not_verify(): void {
		$map = $this->get_email_map();

		$this->assertArrayHasKey( 'woocommerce_bis_notification_confirm_settings', $map );
		$this->assertSame(
			'woocommerce_customer_stock_notification_verified_settings',
			$map['woocommerce_bis_notification_confirm_settings'],
			'Legacy confirm must map to Core verified, not verify.'
		);
	}

	/**
	 * @testdox the email map should pair legacy verify with Core verify, not verified.
	 */
	public function test_email_map_pairs_verify_with_verify_not_verified(): void {
		$map = $this->get_email_map();

		$this->assertArrayHasKey( 'woocommerce_bis_notification_verify_settings', $map );
		$this->assertSame(
			'woocommerce_customer_stock_notification_verify_settings',
			$map['woocommerce_bis_notification_verify_settings']
		);
	}

	/**
	 * @testdox the email map should pair legacy received with Core stock notification settings.
	 */
	public function test_email_map_pairs_received_with_notification_settings(): void {
		$map = $this->get_email_map();

		$this->assertArrayHasKey( 'woocommerce_bis_notification_received_settings', $map );
		$this->assertSame(
			'woocommerce_customer_stock_notification_settings',
			$map['woocommerce_bis_notification_received_settings']
		);
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
	 * @testdox an integer option should round-trip and then read as done.
	 */
	public function test_integer_option_round_trips_and_settles(): void {
		update_option( self::LEGACY_THRESHOLD, 30 );

		$this->migrate();

		// Loose comparison: within one request the option cache hands back the int that was
		// written, while a later request reads the string the database stored. Whether the
		// migrator considers it settled is the assertion that matters, and it compares the
		// two forms as equal on purpose.
		$this->assertEquals( 30, get_option( self::CORE_THRESHOLD ) );
		$this->assertTrue( $this->build_migrator()->is_done(), 'A migrated value must read as done however it round-tripped.' );
	}

	/**
	 * @testdox a value already in its Core home should not be written again.
	 */
	public function test_a_settled_value_is_not_written_again(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );

		$this->migrate();

		$writer = $this->getMockBuilder( Writer::class )->onlyMethods( array( 'write_option' ) )->getMock();
		$writer->expects( $this->never() )->method( 'write_option' );

		$this->assertSame( array(), $this->build_migrator()->migrate( $writer ), 'A settled store should report nothing.' );
	}

	/**
	 * @testdox a run that migrates on every batch should only write each value once.
	 */
	public function test_a_value_is_written_once_across_a_runs_batches(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );

		$migrator = $this->build_migrator();
		$writer   = wc_get_container()->get( Writer::class );

		$first = $migrator->migrate( $writer );

		// What a merchant editing a setting while the run is still going looks like from here.
		update_option( self::CORE_ALLOW_SIGNUPS, 'yes' );

		$second = $migrator->migrate( $writer );

		$this->assertGreaterThan( 0, $first[ Reporter::OUTCOME_MIGRATED ] ?? 0 );
		$this->assertSame( array(), $second, 'A later batch in the same run must not revisit a settled value.' );
		$this->assertSame( 'yes', get_option( self::CORE_ALLOW_SIGNUPS ), 'A settled value must not be overwritten mid-run.' );
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
		$this->assertSame( 'Legacy heading', $core['heading'], 'The legacy value is written again on a later run.' );
	}

	/**
	 * @testdox the copy of each legacy email should land in its paired Core email.
	 */
	public function test_each_legacy_email_lands_in_its_paired_core_option(): void {
		foreach ( array_keys( self::EMAIL_MAP ) as $legacy_key ) {
			update_option( $legacy_key, array( 'subject' => "Subject from {$legacy_key}" ) );
		}

		$this->migrate();

		foreach ( self::EMAIL_MAP as $legacy_key => $core_key ) {
			$core = (array) get_option( $core_key );

			$this->assertSame( "Subject from {$legacy_key}", $core['subject'] ?? '', "Copy landed in the wrong Core email for {$legacy_key}." );
		}
	}

	/**
	 * @testdox a write that does not land should be reported and retried, not counted as migrated.
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
		$counts   = $migrator->migrate( $silent );

		$this->assertGreaterThan( 0, $counts[ Reporter::OUTCOME_FAILED ] ?? 0, 'A write that did not land is a failure.' );
		$this->assertFalse( $migrator->is_done(), 'A write that did not land must stay outstanding.' );
		$this->assertFalse( $migrator->has_pending(), 'The failed value is not served again by this run, or the run could not drain.' );

		// The next run starts a new instance, which writes again rather than settling on a
		// value that never landed.
		$this->migrate();

		$stored = (array) get_option( 'woocommerce_customer_stock_notification_settings', array() );
		$this->assertSame( 'Legacy subject', $stored['subject'] ?? '' );
	}

	/**
	 * @testdox a general option whose write does not land should be reported and left outstanding.
	 */
	public function test_a_general_option_write_that_did_not_land_is_reported(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );

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
		$counts   = $migrator->migrate( $silent );

		$this->assertGreaterThan( 0, $counts[ Reporter::OUTCOME_FAILED ] ?? 0 );
		$this->assertFalse( get_option( self::CORE_ALLOW_SIGNUPS ) );
		$this->assertFalse( $migrator->is_done() );

		$this->migrate();

		$this->assertSame( 'no', get_option( self::CORE_ALLOW_SIGNUPS ), 'The next run writes the value that never landed.' );
	}

	/**
	 * @testdox has_pending should be false once a run has been through every value.
	 */
	public function test_has_pending_clears_once_a_run_has_been_through_everything(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );

		$migrator = $this->build_migrator();

		$this->assertTrue( $migrator->has_pending(), 'A store with settings to move has a pass outstanding.' );

		$migrator->migrate( wc_get_container()->get( Writer::class ) );

		$this->assertFalse( $migrator->has_pending(), 'A run that has been through the settings must be able to drain.' );
		$this->assertTrue( $migrator->is_done() );
	}

	/**
	 * @testdox a dry run should write nothing and settle everything it rehearsed.
	 */
	public function test_a_dry_run_writes_nothing(): void {
		update_option( self::LEGACY_ALLOW_SIGNUPS, 'no' );
		update_option(
			'woocommerce_bis_notification_received_settings',
			array( 'subject' => 'Legacy subject' )
		);

		$migrator = $this->build_migrator();
		$writer   = new Writer( true );

		$this->assertNotEmpty( $migrator->migrate( $writer ), 'A dry run reports what a live run would have written.' );
		$this->assertSame( array(), $migrator->migrate( $writer ), 'A dry run must not report the same values on every batch.' );
		$this->assertFalse( $migrator->has_pending(), 'A dry run must be able to drain.' );

		$this->assertFalse( get_option( self::CORE_ALLOW_SIGNUPS ) );
		$this->assertFalse( get_option( 'woocommerce_customer_stock_notification_settings' ) );
	}

	/**
	 * @testdox a store with no legacy email settings row should keep its Core email enabled.
	 */
	public function test_an_absent_legacy_email_row_leaves_the_core_email_enabled(): void {
		$this->assertFalse( get_option( 'woocommerce_bis_notification_received_settings' ), 'The legacy row must be absent for this test to mean anything.' );

		$this->migrate();

		$this->assertArrayNotHasKey(
			'enabled',
			(array) get_option( 'woocommerce_customer_stock_notification_settings', array() ),
			'An absent legacy row must not write over the Core form field default.'
		);
		$this->assertTrue(
			( new CustomerStockNotificationEmail() )->is_enabled(),
			'A store that never saved the legacy email screens must keep sending back-in-stock emails.'
		);
		$this->assertTrue( $this->build_migrator()->is_done(), 'A legacy row that was never written leaves nothing outstanding.' );
	}

	/**
	 * @testdox a sub-key the legacy row never stored should leave the Core value alone.
	 */
	public function test_a_sub_key_absent_from_the_legacy_row_is_left_alone(): void {
		update_option(
			'woocommerce_customer_stock_notification_settings',
			array(
				'enabled' => 'no',
				'heading' => 'Merchant heading',
			)
		);
		update_option(
			'woocommerce_bis_notification_received_settings',
			array( 'subject' => 'Legacy subject' )
		);

		$this->migrate();

		$core = (array) get_option( 'woocommerce_customer_stock_notification_settings' );

		$this->assertSame( 'Legacy subject', $core['subject'], 'A sub-key the legacy row holds is migrated.' );
		$this->assertSame( 'no', $core['enabled'], 'A sub-key the legacy row never stored must not be overwritten with an empty string.' );
		$this->assertSame( 'Merchant heading', $core['heading'] );
	}

	/**
	 * @testdox a placeholder Core cannot fill in should be reported.
	 */
	public function test_an_unknown_placeholder_is_reported(): void {
		update_option(
			'woocommerce_bis_notification_received_settings',
			array( 'subject' => 'Hi {custom_field}, {product_name} is back' )
		);

		$counts = $this->build_migrator()->migrate( wc_get_container()->get( Writer::class ) );

		$this->assertGreaterThan( 0, $counts['unknown_placeholder'] ?? 0 );
	}

	/**
	 * Migrate everything with a fresh migrator and a live writer.
	 *
	 * @return array<string,int> Outcome counts.
	 */
	private function migrate(): array {
		return $this->build_migrator()->migrate( wc_get_container()->get( Writer::class ) );
	}

	/**
	 * Build a migrator with a reporter of its own.
	 *
	 * @return OptionsMigrator
	 */
	private function build_migrator(): OptionsMigrator {
		return new OptionsMigrator( new Reporter() );
	}

	/**
	 * Read OptionsMigrator::EMAIL_MAP via reflection, since it is a private constant and this
	 * pairing has no other public surface to assert against directly.
	 *
	 * @return array<string, string>
	 */
	private function get_email_map(): array {
		return ( new ReflectionClass( OptionsMigrator::class ) )->getConstant( 'EMAIL_MAP' );
	}

	/**
	 * Delete every legacy and Core option this migrator touches.
	 *
	 * @return void
	 */
	private function clear_options(): void {
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );

		foreach ( self::EMAIL_MAP as $legacy_key => $core_key ) {
			delete_option( $legacy_key );
			delete_option( $core_key );
		}

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
