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
	 * @testdox An admin import should take over a fresh lock held by another admin import, so an abandoned run can be retried.
	 */
	public function test_admin_takes_over_a_fresh_admin_lock(): void {
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
		$this->assertTrue( $this->sut->acquire( ProductImportLock::HOLDER_ADMIN ) );
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
		$this->assertSame( 0, $this->sut->get_age() );
	}
}
