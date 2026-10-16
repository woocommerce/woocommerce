<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductCSVImport;

use Automattic\WooCommerce\Internal\ProductCSVImport\DeferredRows;
use WC_Unit_Test_Case;

/**
 * Tests persistent deferred CSV rows.
 */
class DeferredRowsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Deferred rows survive new instances, preserve source order, and are read in bounded batches.
	 */
	public function test_persistent_ordered_batches(): void {
		$sut = new DeferredRows( 'import.csv' );
		$sut->save( 100, array( 'sku' => 'later' ) );
		$sut->save( 20, array( 'sku' => 'earlier' ) );
		$sut->save( 20, array( 'sku' => 'earlier' ) );

		$sut = new DeferredRows( 'import.csv' );
		$this->assertSame( array( 20 ), array_keys( $sut->get_batch( 1 ) ), 'The earliest source row should be returned only once.' );
		$this->assertSame( 'earlier', $sut->get_batch( 1 )[20]['sku'], 'Formatted data should survive a new request.' );
		$sut->delete( 20 );
		$this->assertSame( array( 100 ), array_keys( $sut->get_batch() ), 'Processed rows should no longer be pending.' );
		$sut->delete( 100 );
		$this->assertFalse( $sut->has_rows(), 'The queue should be empty after the last row.' );
	}

	/**
	 * @testdox Clearing an import leaves other users and source files untouched.
	 */
	public function test_import_isolation_and_restart(): void {
		$sut = new DeferredRows( 'import.csv' );
		$sut->save( 1, array( 'sku' => 'first-user' ) );
		$other_file = new DeferredRows( 'other.csv' );
		$other_file->save( 1, array( 'sku' => 'other-file' ) );

		wp_set_current_user( self::factory()->user->create() );
		$other_user = new DeferredRows( 'import.csv' );
		$this->assertFalse( $other_user->has_rows(), 'Users must not share pending rows.' );
		$other_user->save( 1, array( 'sku' => 'second-user' ) );

		$sut->clear();
		$this->assertFalse( $sut->has_rows(), 'Restarting should discard the source queue.' );
		$this->assertTrue( $other_file->has_rows(), 'Restarting should not discard another file.' );
		$this->assertTrue( $other_user->has_rows(), 'Restarting should not discard another user’s queue.' );
	}
}
