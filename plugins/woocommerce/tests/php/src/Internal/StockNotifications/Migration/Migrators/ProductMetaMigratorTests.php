<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Migrators;

use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Migrators\ProductMetaMigrator;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Report\Reporter;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers\Writer;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the product meta section: the polarity inversion between the legacy
 * "disabled" flag and Core's "enable signups" flag, and the write-once-never-revisit
 * candidate query.
 */
class ProductMetaMigratorTests extends WC_Unit_Test_Case {

	/**
	 * Legacy post meta key holding the "sign-ups disabled" flag.
	 *
	 * @var string
	 */
	private const LEGACY_META_KEY = '_wc_bis_disabled';

	/**
	 * Product meta key marking a product the section can never settle.
	 *
	 * @var string
	 */
	private const FAILED_META_KEY = '_wc_bis_migration_signups_failed';

	/**
	 * Set up a clean run state.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );
	}

	/**
	 * Clear the run state.
	 */
	public function tearDown(): void {
		delete_option( 'wc_bis_migration_state' );
		delete_option( 'wc_bis_migration_lock' );
		delete_option( 'wc_bis_migration_batch_lock' );

		parent::tearDown();
	}

	/**
	 * @testdox a legacy disabled flag should become a Core signups-disabled flag.
	 */
	public function test_legacy_disabled_becomes_core_signups_disabled(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );

		$this->migrate();

		$this->assertSame( 'no', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ) );
	}

	/**
	 * A merchant can toggle "disable sign-ups" on an existing product while the legacy
	 * extension is still running — which is the whole window this migration runs in. If the
	 * batch query kept a keyset cursor, such a product would sit below it forever: never
	 * served, yet still counted, so the section would report outstanding work it could never
	 * do and the double-send notice would never clear.
	 *
	 * @testdox a product that becomes a candidate below an advanced cursor should still migrate.
	 */
	public function test_a_candidate_below_the_cursor_still_migrates(): void {
		$first  = WC_Helper_Product::create_simple_product()->get_id();
		$second = $this->create_product_with_legacy_flag( 'yes' );

		$this->assertGreaterThan( $first, $second, 'The fixture assumes ascending product ids.' );

		$migrator = $this->build_migrator();
		$writer   = wc_get_container()->get( Writer::class );

		// Drain the section, leaving any cursor a caller kept at the highest id seen.
		$this->migrate();
		$this->assertSame( 0, $migrator->count_remaining(), 'The section should be drained.' );

		// Now the merchant disables sign-ups on a product below that high-water mark, which
		// is the legacy extension's own flag and can happen at any point during the run.
		update_post_meta( $first, self::LEGACY_META_KEY, 'yes' );

		$this->assertSame( 1, $migrator->count_remaining(), 'The newly flagged product is outstanding.' );

		$batch = $migrator->get_batch( $second, 10 );

		$this->assertSame( array( $first ), $batch, 'A candidate below the cursor must still be served.' );

		$migrator->migrate_batch( $batch, $writer );

		$this->assertSame( 0, $migrator->count_remaining(), 'The section must be able to drain again.' );
		$this->assertSame( 'no', get_post_meta( $first, Config::get_product_signups_meta_key(), true ) );
	}

	/**
	 * Core only ever flips a value that is already there, so the target key exists solely
	 * because something deliberately wrote it. Treating its presence as "handled" is what
	 * lets a merchant re-enable sign-ups on a migrated product mid-run without the next
	 * pass overwriting them.
	 *
	 * @testdox a product whose Core flag already exists should be left alone.
	 */
	public function test_a_product_with_an_existing_core_flag_is_left_alone(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );
		update_post_meta( $product_id, Config::get_product_signups_meta_key(), 'yes' );

		$migrator = $this->build_migrator();

		$this->assertSame( 0, $migrator->count_remaining() );
		$this->assertSame( array(), $migrator->get_batch( 0, 10 ) );

		$this->migrate();

		$this->assertSame(
			'yes',
			get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ),
			'A deliberate Core value must not be overwritten by the migration.'
		);
	}

	/**
	 * Nothing revisits a drained section, so a trashed product left behind now would come
	 * back from the trash with sign-ups silently re-enabled.
	 *
	 * @testdox a trashed product should migrate like any other.
	 */
	public function test_a_trashed_product_migrates(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );
		wp_trash_post( $product_id );

		$this->migrate();

		$this->assertSame( 'no', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ) );
	}

	/**
	 * Neither side reads a variation's own flag, so there is nothing to write — but the row
	 * still has to leave the candidate set, or this cursorless section serves it forever.
	 *
	 * @testdox a variation carrying the legacy flag should be settled without being written.
	 */
	public function test_a_variation_is_settled_without_being_written(): void {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = current( $variable->get_children() );

		update_post_meta( $variation, self::LEGACY_META_KEY, 'yes' );

		$migrator = $this->build_migrator();
		$outcomes = $migrator->migrate_batch( $migrator->get_batch( 0, 10 ), wc_get_container()->get( Writer::class ) );

		$this->assertSame( array( Reporter::OUTCOME_VARIATION_SKIPPED => 1 ), $outcomes );
		$this->assertSame(
			'',
			get_post_meta( $variation, Config::get_product_signups_meta_key(), true ),
			'A variation must not be written to.'
		);
		$this->assertSame( array(), $migrator->get_batch( 0, 10 ), 'The row must still be settled.' );
	}

	/**
	 * @testdox duplicate legacy meta rows should serve one id, not one per row.
	 */
	public function test_duplicate_legacy_rows_serve_one_id(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );
		add_post_meta( $product_id, self::LEGACY_META_KEY, 'yes' );

		$migrator = $this->build_migrator();

		$this->assertSame( array( $product_id ), $migrator->get_batch( 0, 10 ) );
		$this->assertSame( 1, $migrator->count_remaining() );
	}

	/**
	 * @testdox count_remaining and get_batch should agree about what is left.
	 */
	public function test_count_remaining_and_get_batch_agree(): void {
		$this->create_product_with_legacy_flag( 'yes' );
		$this->create_product_with_legacy_flag( 'yes' );

		$migrator = $this->build_migrator();

		$this->assertSame(
			$migrator->count_remaining(),
			count( $migrator->get_batch( 0, 100 ) ),
			'A count the batch query cannot serve is a section that never drains.'
		);
	}

	/**
	 * @testdox a product with no legacy flag should have nothing written.
	 */
	public function test_absent_legacy_flag_writes_nothing(): void {
		$product = WC_Helper_Product::create_simple_product();

		$this->migrate();

		$this->assertSame( '', get_post_meta( $product->get_id(), Config::get_product_signups_meta_key(), true ) );
	}

	/**
	 * @testdox a legacy flag set to anything but yes should write nothing.
	 */
	public function test_legacy_flag_other_than_yes_writes_nothing(): void {
		$product_id = $this->create_product_with_legacy_flag( 'no' );

		$this->migrate();

		$this->assertSame( '', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ) );
	}

	/**
	 * @testdox a variation should resolve to its parent's flag with no rows of its own.
	 */
	public function test_variation_resolves_to_its_parent_without_fan_out(): void {
		$variable = WC_Helper_Product::create_variation_product();
		$variable->update_meta_data( self::LEGACY_META_KEY, 'yes' );
		$variable->save();

		$this->migrate();

		$variation_id = $variable->get_children()[0];

		$this->assertSame( 'no', get_post_meta( $variable->get_id(), Config::get_product_signups_meta_key(), true ) );
		$this->assertSame( '', get_post_meta( $variation_id, Config::get_product_signups_meta_key(), true ), 'No fan-out rows.' );

		$eligibility = new EligibilityService();
		$eligibility->init( new StockManagementHelper() );

		$this->assertFalse( $eligibility->product_allows_signups( wc_get_product( $variation_id ) ) );
	}

	/**
	 * @testdox a migrated product should not be visited again.
	 */
	public function test_migrated_product_leaves_the_candidate_set(): void {
		$this->create_product_with_legacy_flag( 'yes' );

		$this->migrate();

		$this->assertSame( array(), $this->build_migrator()->get_batch( 0, 10 ) );
		$this->assertSame( 0, $this->build_migrator()->count_remaining() );
	}

	/**
	 * @testdox a product that cannot be loaded should not be retried.
	 */
	public function test_a_product_that_cannot_load_is_never_retried(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );

		// Loading the product as a variation is how a real store's broken row behaves:
		// the post is typed as a product but the class refuses to read it.
		$as_variation = static function ( $classname, $product_type, $post_type, $id ) use ( $product_id ) {
			return $id === $product_id ? \WC_Product_Variation::class : $classname;
		};

		add_filter( 'woocommerce_product_class', $as_variation, 10, 4 );

		try {
			$this->migrate();
			$this->migrate();

			$this->assertSame( array(), $this->build_migrator()->get_batch( 0, 10 ), 'A row that can never settle must not be re-admitted.' );
		} finally {
			remove_filter( 'woocommerce_product_class', $as_variation, 10 );
		}

		$this->assertNotSame( '', get_post_meta( $product_id, '_wc_bis_migration_signups_failed', true ) );
	}

	/**
	 * @testdox migrating a product should save it once, not once per meta key.
	 */
	public function test_a_migrated_product_is_saved_once(): void {
		$this->create_product_with_legacy_flag( 'yes' );

		$saves   = 0;
		$counter = static function () use ( &$saves ) {
			++$saves;
		};

		add_action( 'woocommerce_update_product', $counter );
		$this->migrate();
		remove_action( 'woocommerce_update_product', $counter );

		$this->assertSame( 1, $saves, 'The migrated value is written in one save.' );
	}

	/**
	 * @testdox a dry run should write nothing.
	 */
	public function test_dry_run_writes_nothing(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );

		$migrator = $this->build_migrator();
		$migrator->migrate_batch( $migrator->get_batch( 0, 10 ), new Writer( true ) );

		$this->assertSame( '', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ) );
	}

	/**
	 * A `woocommerce_update_product` callback belonging to some other extension can throw,
	 * and the migrator's payload write goes through `$product->save()`, which fires it. The
	 * failure marker must still land: recovery writes it raw rather than through the CRUD
	 * layer, so it does not repeat the save that just threw.
	 *
	 * @testdox a throwing product save hook settles the row instead of failing the batch.
	 */
	public function test_a_throwing_save_hook_settles_the_row(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );
		$migrator   = $this->build_migrator();
		$batch      = $migrator->get_batch( 0, 10 );

		$fired   = 0;
		$thrower = static function () use ( &$fired ) {
			++$fired;
			throw new \RuntimeException( 'third-party callback' );
		};
		add_action( 'woocommerce_update_product', $thrower );

		try {
			$outcomes = $migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
		} finally {
			remove_action( 'woocommerce_update_product', $thrower );
		}

		$this->assertSame( array( Reporter::OUTCOME_FAILED => 1 ), $outcomes );
		$this->assertSame(
			1,
			$fired,
			'Recovery must not repeat the save that just threw: only the payload write may reach the CRUD layer.'
		);
		$this->assertNotSame(
			'',
			get_post_meta( $product_id, self::FAILED_META_KEY, true ),
			'The failure marker must land, or the row is served again on every pass.'
		);
		$this->assertSame(
			array(),
			$migrator->get_batch( 0, 10 ),
			'A settled row must leave the candidate set.'
		);
	}

	/**
	 * The marker write fires its own meta hooks, so a callback can throw from the recovery
	 * path too. There is nothing left to fall back to, but the batch must still not throw:
	 * migrate_batch() reports per-row failures rather than propagating them.
	 *
	 * @testdox a throw from the recovery write is still not propagated out of the batch.
	 */
	public function test_a_throw_from_the_recovery_write_does_not_fail_the_batch(): void {
		$this->create_product_with_legacy_flag( 'yes' );
		$migrator = $this->build_migrator();
		$batch    = $migrator->get_batch( 0, 10 );

		$save_thrower = static function () {
			throw new \RuntimeException( 'third-party callback' );
		};
		$meta_thrower = static function ( $meta_id, $object_id, $meta_key ) {
			if ( self::FAILED_META_KEY === $meta_key ) {
				throw new \RuntimeException( 'third-party meta callback' );
			}
		};

		add_action( 'woocommerce_update_product', $save_thrower );
		add_action( 'added_post_meta', $meta_thrower, 10, 3 );

		try {
			$outcomes = $migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
		} finally {
			remove_action( 'woocommerce_update_product', $save_thrower );
			remove_action( 'added_post_meta', $meta_thrower, 10 );
		}

		$this->assertSame( array( Reporter::OUTCOME_FAILED => 1 ), $outcomes );
	}

	/**
	 * A marker that never lands leaves the row a candidate, and this section has no cursor to
	 * move past it. The outcome has to say so, since that is what the processor parks on. The
	 * writer's own boolean cannot answer it — hence the read-back the outcome is taken from.
	 *
	 * @testdox a row whose marker cannot be written is reported as unsettled.
	 */
	public function test_a_row_whose_marker_cannot_be_written_is_reported_as_unsettled(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );
		$migrator   = $this->build_migrator();
		$batch      = $migrator->get_batch( 0, 10 );

		$save_thrower = static function () {
			throw new \RuntimeException( 'third-party callback' );
		};
		// Short-circuits the marker write without erroring, the way a third-party filter can.
		$blocker = static function ( $check, $object_id, $meta_key ) {
			return self::FAILED_META_KEY === $meta_key ? true : $check;
		};

		add_action( 'woocommerce_update_product', $save_thrower );
		add_filter( 'update_post_metadata', $blocker, 10, 3 );

		try {
			$outcomes = $migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
		} finally {
			remove_action( 'woocommerce_update_product', $save_thrower );
			remove_filter( 'update_post_metadata', $blocker, 10 );
		}

		$this->assertSame( array( Reporter::OUTCOME_UNSETTLED => 1 ), $outcomes );
		$this->assertSame(
			'',
			get_post_meta( $product_id, self::FAILED_META_KEY, true ),
			'The marker did not land, which is exactly what the unsettled outcome reports.'
		);
	}

	/**
	 * @testdox a dry run writes no failure marker.
	 */
	public function test_a_dry_run_writes_no_failure_marker(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );
		$migrator   = $this->build_migrator();

		wp_delete_post( $product_id, true );

		$migrator->migrate_batch( array( $product_id ), new Writer( true ) );

		$this->assertSame( '', get_post_meta( $product_id, self::FAILED_META_KEY, true ) );
	}

	/**
	 * A live run leans on its own writes to shrink the candidate set: a row leaves it as soon
	 * as it carries the target key or the failure marker. A dry run writes neither, so the
	 * same batch would be served on every pass and the rehearsal would never end. It pages by
	 * cursor instead, which is also what lets it report on every candidate rather than on the
	 * first batch it happened to be handed.
	 *
	 * @testdox a dry run should page through every candidate and terminate.
	 */
	public function test_a_dry_run_pages_through_every_candidate(): void {
		$expected = array(
			$this->create_product_with_legacy_flag( 'yes' ),
			$this->create_product_with_legacy_flag( 'yes' ),
			$this->create_product_with_legacy_flag( 'yes' ),
		);
		sort( $expected );

		$migrator = new ProductMetaMigrator( new Reporter(), true );
		$writer   = new Writer( true );
		$cursor   = 0;
		$batches  = 0;
		$visited  = array();

		while ( true ) {
			$batch = $migrator->get_batch( $cursor, 1 );

			if ( empty( $batch ) ) {
				break;
			}

			$migrator->migrate_batch( $batch, $writer );

			$visited = array_merge( $visited, $batch );
			$cursor  = (int) max( $batch );

			++$batches;
			$this->assertLessThan( 10, $batches, 'The dry run failed to terminate.' );
		}

		$this->assertSame( $expected, $visited, 'A dry run must walk every candidate exactly once.' );

		foreach ( $expected as $product_id ) {
			$this->assertSame( '', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ) );
			$this->assertSame( '', get_post_meta( $product_id, self::FAILED_META_KEY, true ) );
		}
	}

	/**
	 * The payload write goes through `$product->save()`, and a `woocommerce_update_product`
	 * callback runs after it. One that drops the meta again leaves a row the writer reported
	 * as written, so trusting that boolean would report the row as migrated while leaving it
	 * a candidate — served again on the very next pass, forever. The value is read back
	 * instead, and a row that did not land is settled with the failure marker.
	 *
	 * @testdox a save hook that drops the written meta should settle the row rather than re-serve it.
	 */
	public function test_a_save_that_drops_the_meta_settles_the_row(): void {
		$product_id = $this->create_product_with_legacy_flag( 'yes' );

		$dropper = static function ( $id ) {
			delete_post_meta( (int) $id, Config::get_product_signups_meta_key() );
		};

		add_action( 'woocommerce_update_product', $dropper );

		try {
			$this->migrate();
		} finally {
			remove_action( 'woocommerce_update_product', $dropper );
		}

		$this->assertSame( '', get_post_meta( $product_id, Config::get_product_signups_meta_key(), true ), 'The callback dropped the value.' );
		$this->assertNotSame( '', get_post_meta( $product_id, self::FAILED_META_KEY, true ), 'The row is settled with the failure marker.' );
		$this->assertSame( 0, $this->build_migrator()->count_remaining(), 'The section is drained.' );
	}

	/**
	 * Run the product meta section to completion.
	 *
	 * @return void
	 */
	private function migrate(): void {
		$migrator = $this->build_migrator();
		$cursor   = 0;
		$batches  = 0;

		while ( true ) {
			$batch = $migrator->get_batch( $cursor, 10 );

			if ( empty( $batch ) ) {
				break;
			}

			$migrator->migrate_batch( $batch, wc_get_container()->get( Writer::class ) );
			$cursor = (int) end( $batch );

			++$batches;
			$this->assertLessThan( 10, $batches, 'The product meta section failed to drain.' );
		}
	}

	/**
	 * Build a migrator.
	 *
	 * @return ProductMetaMigrator
	 */
	private function build_migrator(): ProductMetaMigrator {
		return new ProductMetaMigrator( new Reporter() );
	}

	/**
	 * Create a simple product carrying the legacy flag.
	 *
	 * @param string $value Legacy flag value.
	 * @return int The product id.
	 */
	private function create_product_with_legacy_flag( string $value ): int {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( self::LEGACY_META_KEY, $value );
		$product->save();

		return $product->get_id();
	}
}
