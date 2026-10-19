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
	 * @testdox Product identity indexes persist until the last matching source row is removed and clear on restart.
	 */
	public function test_product_identity_index_lifecycle(): void {
		$sut = new DeferredRows( 'import.csv' );
		$sut->save( 100, array( 'sku' => 'later' ), array( 'id:123', 'sku:later' ) );
		$sut->save( 20, array( 'sku' => 'earlier' ), array( 'id:123', 'sku:earlier' ) );
		$sut->save( 20, array( 'sku' => 'earlier' ), array( 'id:123', 'sku:earlier' ) );

		$sut = new DeferredRows( 'import.csv' );
		$this->assertTrue( $sut->has_identifier( 'id:123' ), 'Pending product identities must survive new requests.' );
		$this->assertFalse( $sut->has_identifier( 'id:456' ), 'Unrelated products should not wait for the queue.' );
		$this->assertArrayNotHasKey( '_deferred_identifiers', $sut->get_batch( 1 )[20], 'Internal indexes must not reach import hooks.' );
		$sut->delete( 20 );
		$this->assertTrue( $sut->has_identifier( 'id:123' ), 'Deleting the first row must retain the later row index.' );
		$this->assertFalse( $sut->has_identifier( 'sku:earlier' ), 'An identity with no pending rows must be removed.' );
		$sut->clear();
		$this->assertFalse( $sut->has_identifier( 'id:123' ), 'Restarting must remove product indexes.' );
		$this->assertFalse( $sut->has_identifier( 'sku:later' ), 'Restarting must remove proposed SKU indexes.' );
		$this->assertFalse( $sut->has_rows(), 'Identity indexes must not be mistaken for source rows.' );
	}

	/**
	 * @testdox SKU identities share an index across case differences without changing the stored rows.
	 */
	public function test_case_insensitive_sku_identity_lifecycle(): void {
		$sut = new DeferredRows( 'import.csv' );
		$sut->save( 20, array( 'sku' => 'Child-New' ), array( 'sku:Child-New' ) );
		$sut = new DeferredRows( 'import.csv' );

		$this->assertTrue( $sut->has_identifier( 'sku:child-new' ), 'A differently cased reference must find the pending SKU.' );
		$this->assertSame( 'Child-New', $sut->get_batch( 1 )[20]['sku'], 'Identity matching must not change the imported SKU.' );
		$sut->save( 100, array( 'sku' => 'child-new' ), array( 'sku:child-new' ) );
		$sut->delete( 20 );
		$this->assertTrue( $sut->has_identifier( 'sku:CHILD-NEW' ), 'Deleting the earlier row must retain the shared index for the later row.' );
		$sut->clear();
		$this->assertFalse( $sut->has_identifier( 'sku:Child-New' ), 'Clearing the last row must remove the shared SKU index.' );
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
