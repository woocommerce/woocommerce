<?php
/**
 * MigrationStateTests class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\MigrationState;
use WC_Unit_Test_Case;

/**
 * Tests that the migration run state survives a malformed option.
 *
 * `wc_bis_migration_state` is an ordinary option, so anything on the site can overwrite it
 * with the wrong shape. Every read has to come back usable rather than fatal.
 */
class MigrationStateTests extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var MigrationState
	 */
	private MigrationState $sut;

	/**
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new MigrationState();
	}

	/**
	 * Tear down the test case.
	 */
	public function tearDown(): void {
		delete_option( Constants::STATE_OPTION );
		delete_option( Constants::LOCK_OPTION );
		delete_option( Constants::BATCH_LOCK_OPTION );

		parent::tearDown();
	}

	/**
	 * @testdox a non-array option should read back as the default state.
	 */
	public function test_a_scalar_option_reads_back_as_the_default_state(): void {
		update_option( Constants::STATE_OPTION, 'corrupt', false );

		$state = $this->sut->get_state();

		$this->assertSame( array(), $state['cursor'] );
		$this->assertSame( array(), $state['counts'] );
		$this->assertSame( array(), $state['totals'] );
		$this->assertNull( $state['losses'] );
	}

	/**
	 * @testdox an unreadable lock row should read as no lock, not fatal or block a run.
	 */
	public function test_an_unreadable_lock_row_does_not_block_a_run(): void {
		update_option( Constants::LOCK_OPTION, 'held', false );

		$this->assertFalse( $this->sut->is_lock_held() );
		$this->assertNull( $this->sut->get_lock() );
		$this->assertTrue( $this->sut->acquire_lock( 'this run' ), 'An unreadable lock must be reclaimable.' );
	}

	/**
	 * @testdox scalar nested fields should not break the accessors declared as ?array.
	 */
	public function test_scalar_nested_fields_do_not_break_the_array_accessors(): void {
		update_option(
			Constants::STATE_OPTION,
			array(
				'counts' => 'not-an-array',
				'losses' => 12,
			),
			false
		);

		$this->assertNull( $this->sut->get_count( 'notifications' ) );
		$this->assertNull( $this->sut->get_losses() );
	}

	/**
	 * @testdox a lock timestamped in the future should be reclaimable, not permanent.
	 */
	public function test_a_future_lock_does_not_block_every_run_forever(): void {
		$this->write_lock( PHP_INT_MAX, 'a run with a bad clock' );

		$this->assertFalse( $this->sut->is_lock_held() );
		$this->assertTrue( $this->sut->acquire_lock( 'this run' ), 'A future lock must be reclaimable.' );
		$this->assertSame( 'this run', $this->sut->get_lock()['owner'] );
	}

	/**
	 * @testdox a lock older than the stale threshold should be taken over by the next run.
	 */
	public function test_a_stale_lock_is_taken_over(): void {
		$this->write_lock( time() - ( HOUR_IN_SECONDS + 60 ), 'an abandoned run' );

		$this->assertFalse( $this->sut->is_lock_held() );
		$this->assertTrue( $this->sut->acquire_lock( 'this run' ) );
		$this->assertSame( 'this run', $this->sut->get_lock()['owner'] );
	}

	/**
	 * @testdox refreshing a lock another run has taken over should not stamp it.
	 */
	public function test_refreshing_a_stolen_lock_leaves_the_new_holder_alone(): void {
		$this->write_lock( time() - ( HOUR_IN_SECONDS + 60 ), 'an abandoned run' );

		// The abandoned run's own instance, still going, and unaware it lost the lock.
		$abandoned = new MigrationState();

		$this->assertTrue( $this->sut->acquire_lock( 'the new run' ) );

		$abandoned->refresh_lock();

		$this->assertSame( 'the new run', $this->sut->get_lock()['owner'] );
	}

	/**
	 * @testdox releasing by owner should leave a lock a later run took over in place.
	 */
	public function test_releasing_by_owner_leaves_a_reclaimed_lock_alone(): void {
		$this->write_lock( time() - ( HOUR_IN_SECONDS + 60 ), 'an abandoned run' );

		$this->assertTrue( $this->sut->acquire_lock( 'the new run' ) );
		$this->assertFalse( $this->sut->release_lock_owned_by( 'an abandoned run' ) );
		$this->assertTrue( $this->sut->is_lock_held() );
	}

	/**
	 * @testdox the lock should live in its own option row, not in the run state.
	 */
	public function test_the_lock_is_not_stored_in_the_run_state(): void {
		$this->assertTrue( $this->sut->acquire_lock( 'this run' ) );

		$this->assertArrayNotHasKey( 'lock', $this->sut->get_state() );
		$this->assertNotFalse( get_option( Constants::LOCK_OPTION ) );

		$this->sut->release_lock();

		$this->assertFalse( get_option( Constants::LOCK_OPTION ) );
	}

	/**
	 * @testdox a lock acquired just now should still be held.
	 */
	public function test_a_fresh_lock_is_still_held(): void {
		$this->assertTrue( $this->sut->acquire_lock( 'this run' ) );
		$this->assertTrue( $this->sut->is_lock_held() );
		$this->assertFalse( $this->sut->acquire_lock( 'another run' ), 'A held lock must refuse a second run.' );
	}

	/**
	 * @testdox a scalar entry inside counts should read back as no cached count.
	 */
	public function test_a_scalar_count_entry_reads_back_as_null(): void {
		update_option(
			Constants::STATE_OPTION,
			array( 'counts' => array( 'notifications' => 7 ) ),
			false
		);

		$this->assertNull( $this->sut->get_count( 'notifications' ) );
	}

	/**
	 * @testdox a parked section should stay parked until it is unparked.
	 */
	public function test_a_parked_section_stays_parked_until_unparked(): void {
		$this->sut->park_section( 'product-meta', 'cannot settle its rows' );

		$this->assertTrue( $this->sut->is_section_parked( 'product-meta' ) );
		$this->assertFalse( $this->sut->is_section_parked( 'notifications' ) );
		$this->assertSame( 'cannot settle its rows', $this->sut->get_parked_sections()['product-meta']['reason'] );

		$this->sut->unpark_all();

		$this->assertSame( array(), $this->sut->get_parked_sections() );
	}

	/**
	 * The reason and timestamp are what the Tools screen and the CLI report, so a re-park
	 * must not overwrite them with a later one and lose when the section first stopped.
	 *
	 * @testdox parking a section twice should keep the first reason and timestamp.
	 */
	public function test_parking_a_section_twice_keeps_the_first_record(): void {
		$this->sut->park_section( 'product-meta', 'first reason' );
		$first = $this->sut->get_parked_sections()['product-meta'];

		$this->sut->park_section( 'product-meta', 'second reason' );

		$this->assertSame( $first, $this->sut->get_parked_sections()['product-meta'] );
	}

	/**
	 * @testdox a scalar parked field should read back as nothing parked.
	 */
	public function test_a_scalar_parked_field_reads_back_as_empty(): void {
		update_option( Constants::STATE_OPTION, array( 'parked' => 'yes' ), false );

		$this->assertSame( array(), $this->sut->get_parked_sections() );
		$this->assertFalse( $this->sut->is_section_parked( 'product-meta' ) );
	}

	/**
	 * @testdox a settled option should stay settled until it is reset.
	 */
	public function test_a_settled_option_stays_settled_until_reset(): void {
		$this->sut->settle_option( 'woocommerce_customer_stock_notifications_allow_signups' );

		$this->assertTrue( $this->sut->is_option_settled( 'woocommerce_customer_stock_notifications_allow_signups' ) );
		$this->assertFalse( $this->sut->is_option_settled( 'woocommerce_customer_stock_notifications_require_account' ) );
		$this->assertSame( array( 'woocommerce_customer_stock_notifications_allow_signups' ), $this->sut->get_settled_options() );

		$this->sut->reset_settled_options();

		$this->assertFalse( $this->sut->is_option_settled( 'woocommerce_customer_stock_notifications_allow_signups' ) );
		$this->assertSame( array(), $this->sut->get_settled_options() );
	}

	/**
	 * @testdox settle_options should settle several markers in a single save.
	 */
	public function test_settle_options_settles_several_markers_at_once(): void {
		$this->sut->settle_options( array( 'marker_one', 'marker_two' ) );

		$this->assertTrue( $this->sut->is_option_settled( 'marker_one' ) );
		$this->assertTrue( $this->sut->is_option_settled( 'marker_two' ) );
	}

	/**
	 * @testdox reset_all_cursors should leave settled options alone.
	 *
	 * `--retry-failed` calls reset_all_cursors() too, and it must not re-overwrite a
	 * merchant's post-migration edit to a setting - only `--force` does that.
	 */
	public function test_reset_all_cursors_leaves_settled_options_alone(): void {
		$this->sut->settle_option( 'woocommerce_customer_stock_notifications_allow_signups' );

		$this->sut->reset_all_cursors();

		$this->assertTrue( $this->sut->is_option_settled( 'woocommerce_customer_stock_notifications_allow_signups' ) );
	}

	/**
	 * @testdox a non-array options key should read back as no settled markers.
	 */
	public function test_a_scalar_options_field_reads_back_as_empty(): void {
		update_option( Constants::STATE_OPTION, array( 'options' => 'yes' ), false );

		$this->assertSame( array(), $this->sut->get_settled_options() );
		$this->assertFalse( $this->sut->is_option_settled( 'woocommerce_customer_stock_notifications_allow_signups' ) );
	}

	/**
	 * @testdox the batch lock should refuse a second holder until it is released.
	 */
	public function test_the_batch_lock_admits_one_holder_at_a_time(): void {
		$other = new MigrationState();

		$this->assertTrue( $this->sut->acquire_batch_lock() );
		$this->assertFalse( $other->acquire_batch_lock(), 'A held batch lock must refuse a second batch.' );

		$this->sut->release_batch_lock();

		$this->assertTrue( $other->acquire_batch_lock(), 'A released batch lock must be available again.' );

		$other->release_batch_lock();
	}

	/**
	 * @testdox releasing a batch lock a later worker took over should leave it in place.
	 */
	public function test_releasing_a_taken_over_batch_lock_leaves_it_alone(): void {
		$overrunning = new MigrationState();
		$this->assertTrue( $overrunning->acquire_batch_lock() );

		// The overrunning worker's batch outlives the stale threshold, so the next one reclaims
		// the lock while that worker is still going.
		update_option( Constants::BATCH_LOCK_OPTION, sprintf( '%010d|%s', time() - ( 6 * MINUTE_IN_SECONDS ), 'the overrunning batch' ), false );
		$this->assertTrue( $this->sut->acquire_batch_lock() );

		$overrunning->release_batch_lock();

		$this->assertNotFalse( get_option( Constants::BATCH_LOCK_OPTION ), "The new holder's batch lock must survive." );

		$this->sut->release_batch_lock();

		$this->assertFalse( get_option( Constants::BATCH_LOCK_OPTION ) );
	}

	/**
	 * Write a lock row directly, in the stored format.
	 *
	 * @param int    $acquired_at Acquisition time, as a Unix timestamp.
	 * @param string $owner       Identifier of the process holding the lock.
	 * @return void
	 */
	private function write_lock( int $acquired_at, string $owner ): void {
		update_option( Constants::LOCK_OPTION, sprintf( '%010d|%s', $acquired_at, $owner ), false );
	}
}
