<?php
/**
 * Database Locks.
 * @package WooCommerce\Tests\Locks
 */
class WC_Tests_Locks extends WC_Unit_Test_Case {

	/**
	 * test_get_request_id.
	 */
	function test_get_request_id() {
		$request_id = WC_Database_Locks::get_request_id();

		$this->assertEquals( $request_id, WC_Database_Locks::get_request_id() );
	}

	/**
	 * test_create_lock.
	 */
	function test_create_lock() {
		// Create a new lock twice.
		$this->assertTrue( WC_Database_Locks::create_lock( 'test_lock' ) );
		$this->assertFalse( WC_Database_Locks::create_lock( 'test_lock' ) );

		// Lock which expires in 1 second.
		$this->assertTrue( WC_Database_Locks::create_lock( 'test_lock_2', 1 ) );
		sleep( 1 );
		$this->assertTrue( WC_Database_Locks::create_lock( 'test_lock_2', 1 ) );
	}

	/**
	 * test_aquire_lock.
	 */
	function test_aquire_lock() {
		// Create lock which expires in 1 second.
		WC_Database_Locks::create_lock( 'test_aquire_lock', 1 );

		$lock_aquired = false;

		if ( $lock = WC_Database_Locks::aquire_lock( 'test_aquire_lock' ) ) {
			$lock_aquired = true;
		}

		$this->assertTrue( $lock_aquired );

		// Create lock which expires in 5 seconds but only wait 2 to aquire.
		WC_Database_Locks::create_lock( 'test_aquire_lock_2', 5 );

		$lock_aquired = false;

		if ( $lock = WC_Database_Locks::aquire_lock( 'test_aquire_lock_2', 2 ) ) {
			$lock_aquired = true;
		}

		$this->assertFalse( $lock_aquired );
	}

	/**
	 * test_release_lock.
	 */
	function test_release_lock() {
		WC_Database_Locks::create_lock( 'lock_to_release' );

		$this->assertNotNull( WC_Database_Locks::get_lock( 'lock_to_release' ) );
		$this->assertTrue( WC_Database_Locks::release_lock( 'lock_to_release' ) );
		$this->assertNull( WC_Database_Locks::get_lock( 'lock_to_release' ) );
	}

	/**
	 * test_release_all_locks.
	 */
	function test_release_all_locks() {
		$this->assertNotNull( WC_Database_Locks::create_lock( 'release_all_locks_1' ) );
		$this->assertNotNull( WC_Database_Locks::create_lock( 'release_all_locks_2' ) );
		$this->assertNotNull( WC_Database_Locks::create_lock( 'release_all_locks_3' ) );

		WC_Database_Locks::release_all_locks();

		$this->assertNull( WC_Database_Locks::get_lock( 'release_all_locks_1' ) );
		$this->assertNull( WC_Database_Locks::get_lock( 'release_all_locks_2' ) );
		$this->assertNull( WC_Database_Locks::get_lock( 'release_all_locks_3' ) );
	}

	/**
	 * test_get_lock.
	 */
	function test_get_lock() {
		$this->assertTrue( WC_Database_Locks::create_lock( 'test_lock' ) );
		$this->assertNotNull( WC_Database_Locks::get_lock( 'test_lock' ) );
		$this->assertNull( WC_Database_Locks::get_lock( 'this_lock_does_not_exist' ) );
	}
}
