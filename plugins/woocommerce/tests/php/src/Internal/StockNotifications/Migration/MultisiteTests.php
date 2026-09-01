<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Requirements;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\MigrationBatchProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Runners\ToolsRegistrar;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers\LegacyStore;
use WC_Unit_Test_Case;

/**
 * Multisite coverage for the BIS-to-Core stock notifications migration.
 *
 * Everything the migration touches - the legacy and Core tables, the `wc_bis_migration_state`
 * option, the `wc_bis_migration_has_legacy_links` flag, and the CLI lock they carry - is
 * `$wpdb->prefix`-scoped, so a run on one site of a network must never be visible to, or
 * blocked by, another site.
 *
 * @group ms-required
 */
class MultisiteTests extends WC_Unit_Test_Case {

	/**
	 * A second site on the network, created fresh for each test.
	 *
	 * @var int|null
	 */
	private ?int $other_site_id = null;

	/**
	 * Skip outside a network install, then seed the legacy tables and the feature toggle on
	 * both the main site and the second site.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite coverage only applies on a network install.' );
		}

		$this->login_as_role( 'administrator' );

		$this->other_site_id = (int) self::factory()->blog->create();

		$this->seed_site();

		// Roles are per-site: without this the administrator created above has no
		// capabilities on the second site, which would hide its Tools entry for reasons
		// that have nothing to do with the migration.
		add_user_to_blog( $this->other_site_id, get_current_user_id(), 'administrator' );

		switch_to_blog( $this->other_site_id );
		$this->seed_site();
		restore_current_blog();
	}

	/**
	 * Restore the current blog even when a test failed mid-switch, then drop the legacy
	 * tables and clear the migration options on both sites.
	 */
	public function tearDown(): void {
		if ( null !== $this->other_site_id ) {
			while ( function_exists( 'ms_is_switched' ) && ms_is_switched() ) {
				restore_current_blog();
			}

			switch_to_blog( $this->other_site_id );
			$this->unseed_site();
			restore_current_blog();

			$this->unseed_site();
		}

		parent::tearDown();
	}

	/**
	 * @testdox migrating on one site of a network should leave another site's legacy rows unmigrated.
	 */
	public function test_migration_on_one_site_leaves_other_sites_legacy_rows_unmigrated(): void {
		$product_id = $this->create_product();
		LegacyStore::add_notification(
			array(
				'product_id' => $product_id,
				'user_email' => 'main-site-shopper@example.com',
			)
		);

		switch_to_blog( $this->other_site_id );
		$other_product_id = $this->create_product();
		LegacyStore::add_notification(
			array(
				'product_id' => $other_product_id,
				'user_email' => 'other-site-shopper@example.com',
			)
		);
		restore_current_blog();

		$migrated = $this->run_migration_to_completion();
		$this->assertNotEmpty( $migrated, 'Sanity: the run on the main site actually migrated something.' );

		switch_to_blog( $this->other_site_id );

		try {
			$this->assertSame(
				array(),
				LegacyStore::get_core_rows(),
				"The other site's Core notifications table must stay empty."
			);
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @testdox migrating on one site of a network should leave another site's migration state option absent.
	 */
	public function test_migration_on_one_site_leaves_other_sites_state_option_absent(): void {
		$this->create_product();
		LegacyStore::add_notification( array( 'user_email' => 'main-site-shopper@example.com' ) );

		$this->run_migration_to_completion();

		$this->assertNotFalse( get_option( 'wc_bis_migration_state' ), 'Sanity: the main site wrote its own state option.' );

		switch_to_blog( $this->other_site_id );

		try {
			$this->assertFalse(
				get_option( 'wc_bis_migration_state' ),
				"The other site's migration state option must not exist."
			);
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @testdox migrating on one site of a network should leave another site's Tools entry still offering to start.
	 */
	public function test_migration_on_one_site_leaves_other_sites_tools_entry_offering_to_start(): void {
		$this->create_product();
		LegacyStore::add_notification( array( 'user_email' => 'main-site-shopper@example.com' ) );

		$this->run_migration_to_completion();

		switch_to_blog( $this->other_site_id );

		// Capabilities are cached per blog on the current user object, so they have to be
		// re-read after the switch or the Tools entry is hidden for the wrong reason.
		wp_set_current_user( get_current_user_id() );

		try {
			$tools = ( new ToolsRegistrar() )->handle_woocommerce_debug_tools( array() );

			$this->assertArrayHasKey( 'start_bis_migration', $tools, "The other site's Tools entry must still offer to start." );
			$this->assertArrayNotHasKey( 'stop_bis_migration', $tools, "The other site's Tools entry must not offer to stop." );
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @testdox the migration state option should be site-scoped, not shared across the network.
	 */
	public function test_state_option_is_site_scoped(): void {
		( new MigrationState() )->set_cursor( 'notifications', 42 );

		switch_to_blog( $this->other_site_id );

		try {
			$this->assertSame(
				0,
				( new MigrationState() )->get_cursor( 'notifications' ),
				"The other site's cursor must not see the main site's value."
			);
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @testdox the has-legacy-links flag should be site-scoped, not shared across the network.
	 */
	public function test_has_legacy_links_flag_is_site_scoped(): void {
		update_option( 'wc_bis_migration_has_legacy_links', 1 );

		switch_to_blog( $this->other_site_id );

		try {
			$this->assertFalse(
				get_option( 'wc_bis_migration_has_legacy_links' ),
				"The other site's has-legacy-links flag must not be set."
			);
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @testdox the CLI lock should be site-scoped: holding it on one site must not block another site from acquiring its own.
	 */
	public function test_cli_lock_is_site_scoped(): void {
		$main_site_state = new MigrationState();
		$this->assertTrue( $main_site_state->acquire_lock( 'main-site-owner' ) );

		switch_to_blog( $this->other_site_id );

		try {
			$other_site_state = new MigrationState();

			$this->assertFalse(
				$other_site_state->is_lock_held(),
				"The other site must not see the main site's lock as held."
			);
			$this->assertTrue(
				$other_site_state->acquire_lock( 'other-site-owner' ),
				'The other site must be able to acquire its own lock.'
			);
		} finally {
			restore_current_blog();
		}

		$this->assertTrue( $main_site_state->is_lock_held(), "The main site's own lock must be unaffected." );
	}

	/**
	 * Run the migration to completion on the current site, via the same batch processor
	 * the background run uses.
	 *
	 * @return int Number of batches processed.
	 */
	private function run_migration_to_completion(): int {
		$requirements = new Requirements();
		$requirements->init( wc_get_container()->get( StockNotificationsDataStore::class ) );

		$processor = new MigrationBatchProcessor();
		$processor->init( $requirements, wc_get_container()->get( Writer::class ) );

		$batches = 0;

		while ( true ) {
			$batch = $processor->get_next_batch_to_process( 50 );

			if ( empty( $batch ) ) {
				break;
			}

			$processor->process_batch( $batch );
			++$batches;

			$this->assertLessThan( 60, $batches, 'The run failed to terminate.' );
		}

		return $batches;
	}

	/**
	 * Set up the current site: the feature toggle and fresh, empty legacy tables.
	 *
	 * @return void
	 */
	private function seed_site(): void {
		update_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'yes' );

		// A blog created mid-test never ran WooCommerce's installer, so its administrator
		// role has core capabilities only. Roles are cached per site, hence the refresh.
		wp_roles()->for_site( get_current_blog_id() );
		$administrator = get_role( 'administrator' );

		if ( $administrator && ! $administrator->has_cap( 'manage_woocommerce' ) ) {
			$administrator->add_cap( 'manage_woocommerce' );
		}

		LegacyStore::create_tables();
		LegacyStore::create_core_tables();
		LegacyStore::truncate_all();

		$this->clear_migration_options();
	}

	/**
	 * Tear down the current site: drop the legacy tables and clear everything the migration
	 * persists, including the autoloaded flags.
	 *
	 * @return void
	 */
	private function unseed_site(): void {
		LegacyStore::drop_tables();
		$this->clear_migration_options();
		delete_option( 'woocommerce_feature_customer_stock_notifications_enabled' );
	}

	/**
	 * Clear every option the migration persists on the current site.
	 *
	 * @return void
	 */
	private function clear_migration_options(): void {
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );
		delete_option( 'wc_bis_migration_has_legacy_links' );
		delete_option( 'wc_bis_migration_has_migrated_rows' );
	}

	/**
	 * Create a published simple product on the current site.
	 *
	 * @return int
	 */
	private function create_product(): int {
		$product = new \WC_Product_Simple();
		$product->set_name( 'Multisite migration test product' );
		$product->save();

		return $product->get_id();
	}
}
