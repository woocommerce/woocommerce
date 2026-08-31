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
	 * Set up a clean run state.
	 */
	public function setUp(): void {
		parent::setUp();

		delete_option( 'wc_bis_migration_state' );
	}

	/**
	 * Clear the run state.
	 */
	public function tearDown(): void {
		delete_option( 'wc_bis_migration_state' );

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
		$first  = $this->create_product_with_legacy_flag( 'yes' );
		$second = $this->create_product_with_legacy_flag( 'yes' );

		$this->assertGreaterThan( $first, $second, 'The fixture assumes ascending product ids.' );

		$migrator = $this->build_migrator();
		$writer   = wc_get_container()->get( Writer::class );

		// Drain the section, leaving any cursor a caller kept at the highest id seen.
		$this->migrate();
		$this->assertSame( 0, $migrator->count_remaining(), 'The section should be drained.' );

		// Now a product below that high-water mark becomes a candidate.
		update_post_meta( $first, Config::get_product_signups_meta_key(), 'yes' );

		$this->assertSame( 1, $migrator->count_remaining(), 'The re-flagged product is outstanding again.' );

		$batch = $migrator->get_batch( $second, 10 );

		$this->assertSame( array( $first ), $batch, 'A candidate below the cursor must still be served.' );

		$migrator->migrate_batch( $batch, $writer );

		$this->assertSame( 0, $migrator->count_remaining(), 'The section must be able to drain again.' );
		$this->assertSame( 'no', get_post_meta( $first, Config::get_product_signups_meta_key(), true ) );
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
