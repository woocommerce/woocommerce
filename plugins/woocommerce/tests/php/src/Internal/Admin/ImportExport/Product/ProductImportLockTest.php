<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport\Product;

use Automattic\WooCommerce\Internal\Admin\ImportExport\Product\ProductImportLock;
use WC_Unit_Test_Case;

/**
 * Tests for the ProductImportLock class.
 */
class ProductImportLockTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ProductImportLock
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new ProductImportLock();
	}

	/**
	 * @testdox Acquiring the import lock should be exclusive: a second attempt should fail while the first holds it.
	 */
	public function test_acquire_is_exclusive(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );
		$this->assertFalse( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );
	}

	/**
	 * @testdox Acquiring the import lock should take over a lock older than 10 minutes.
	 */
	public function test_acquire_takes_over_a_stale_lock(): void {
		global $wpdb;

		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => ( time() - ( MINUTE_IN_SECONDS * 10 ) - 1 ) . ':' . ProductImportLock::HOLDER_CLI ),
			array( 'option_name' => 'wc_product_importing' )
		);

		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_CLI ), 'A lock older than 10 minutes should be taken over' );
	}

	/**
	 * @testdox Acquiring the import lock should not take over a lock that is not yet stale.
	 */
	public function test_acquire_does_not_take_over_a_fresh_lock(): void {
		global $wpdb;

		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => ( time() - ( MINUTE_IN_SECONDS * 10 ) + 5 ) . ':' . ProductImportLock::HOLDER_CLI ),
			array( 'option_name' => 'wc_product_importing' )
		);

		$this->assertFalse( $this->sut->acquire( ProductImportLock::HOLDER_CLI ), 'A lock younger than 10 minutes should not be taken over' );
	}

	/**
	 * @testdox Releasing the import lock should free it for a new acquisition.
	 */
	public function test_release_frees_the_lock(): void {
		$this->sut->acquire( ProductImportLock::HOLDER_CLI );

		$this->sut->release();

		$this->assertNull( $this->sut->get_age() );
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );
	}

	/**
	 * @testdox Getting the import lock age should report null when no import is running.
	 */
	public function test_get_age_is_null_when_the_lock_is_free(): void {
		$this->assertNull( $this->sut->get_age() );
	}

	/**
	 * @testdox An admin import should not take over a fresh lock held by another admin import, so two runs cannot overlap.
	 */
	public function test_admin_does_not_take_over_a_fresh_admin_lock(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
		$this->assertFalse( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
	}

	/**
	 * @testdox An admin import should take over another admin import's lock after 2 minutes, so an abandoned run can be retried.
	 */
	public function test_admin_takes_over_an_admin_lock_after_two_minutes(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
		$this->age_lock( 2 * MINUTE_IN_SECONDS + 1 );

		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
	}

	/**
	 * @testdox A lock older than 2 minutes should still be honored when the holders are not both admin imports.
	 * @testWith ["admin", "cli"]
	 *           ["cli", "admin"]
	 *           ["cli", "cli"]
	 *
	 * @param string $holder     The holder of the existing lock.
	 * @param string $challenger The holder trying to take the lock.
	 */
	public function test_two_minute_takeover_only_applies_between_admin_imports( string $holder, string $challenger ): void {
		$this->assertTrue( $this->sut->acquire( $holder ) );
		$this->age_lock( 2 * MINUTE_IN_SECONDS + 1 );

		$this->assertFalse( $this->sut->acquire( $challenger ) );
	}

	/**
	 * @testdox An admin import should not take over a fresh lock held by a CLI import.
	 */
	public function test_admin_does_not_take_over_a_fresh_cli_lock(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );
		$this->assertFalse( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
	}

	/**
	 * @testdox A CLI import should not take over a fresh lock held by an admin import.
	 */
	public function test_cli_does_not_take_over_a_fresh_admin_lock(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
		$this->assertFalse( $this->sut->acquire( ProductImportLock::HOLDER_CLI ) );
	}

	/**
	 * @testdox Refreshing the lock should keep its holder, so a running CLI import still refuses an admin takeover.
	 */
	public function test_refresh_keeps_the_holder(): void {
		$this->sut->acquire( ProductImportLock::HOLDER_CLI );

		$this->sut->refresh();

		$this->assertFalse( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
		$this->assertLessThan( MINUTE_IN_SECONDS, $this->sut->get_age() );
	}

	/**
	 * @testdox A run that lost the lock to a takeover should find out on refresh and leave the new run's lock alone on release.
	 */
	public function test_run_that_lost_the_lock_cannot_refresh_or_release_it(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
		$this->age_lock( 2 * MINUTE_IN_SECONDS + 1 );

		$other_run = new ProductImportLock();
		$this->assertTrue( $other_run->acquire( ProductImportLock::HOLDER_ADMIN ) );

		$this->assertFalse( $this->sut->refresh() );
		$this->sut->release();
		$this->assertTrue( $other_run->refresh(), 'The new run should still hold the lock' );
	}

	/**
	 * @testdox Resuming the lock should succeed only with the token of the run that holds it.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $use_own_token Whether to resume with the token the run got when it took the lock.
	 */
	public function test_resume_requires_the_holding_runs_token( bool $use_own_token ): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );

		$next_request = new ProductImportLock();

		$this->assertSame( $use_own_token, $next_request->resume( $use_own_token ? (string) $this->sut->get_token() : 'another-token' ) );
	}

	/**
	 * @testdox A due refresh should only write once the lock is a minute old, so importing rows does not write on every row.
	 * @testWith [50, 50]
	 *           [60, 0]
	 *
	 * @param int $age          How long ago the lock was last refreshed.
	 * @param int $expected_age The age expected afterwards.
	 */
	public function test_refresh_if_due_only_refreshes_an_old_enough_lock( int $age, int $expected_age ): void {
		$this->sut->acquire( ProductImportLock::HOLDER_ADMIN );
		$this->age_lock( $age );

		$this->sut->refresh_if_due();

		$this->assertEqualsWithDelta( $expected_age, $this->sut->get_age(), 5 );
	}

	/**
	 * @testdox The time until takeover should follow the same stale rule as acquiring, per holder pairing.
	 * @testWith ["admin", 90, 30]
	 *           ["cli", 90, 510]
	 *           ["admin", 200, 0]
	 *
	 * @param string $challenger The holder that wants the admin-held lock.
	 * @param int    $age        How long ago the lock was last refreshed.
	 * @param int    $expected   The seconds expected until takeover.
	 */
	public function test_get_seconds_until_takeover( string $challenger, int $age, int $expected ): void {
		$this->sut->acquire( ProductImportLock::HOLDER_ADMIN );
		$this->age_lock( $age );

		$this->assertEqualsWithDelta( $expected, $this->sut->get_seconds_until_takeover( $challenger ), 5 );
	}

	/**
	 * @testdox The time until takeover should be null when no import is running.
	 */
	public function test_get_seconds_until_takeover_is_null_when_the_lock_is_free(): void {
		$this->assertNull( $this->sut->get_seconds_until_takeover( ProductImportLock::HOLDER_CLI ) );
	}

	/**
	 * @testdox A due refresh should not create a lock when no import is running.
	 */
	public function test_refresh_if_due_does_nothing_without_a_lock(): void {
		$this->sut->refresh_if_due();

		$this->assertNull( $this->sut->get_age() );
	}

	/**
	 * Rewrite the lock so it looks like it was last refreshed in the past, keeping its holder and token.
	 *
	 * @param int $seconds How long ago the lock should appear to have been refreshed.
	 */
	private function age_lock( int $seconds ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = CONCAT( %d, SUBSTRING( option_value, LOCATE( ':', option_value ) ) ) WHERE option_name = 'wc_product_importing'",
				time() - $seconds
			)
		);
	}
}
