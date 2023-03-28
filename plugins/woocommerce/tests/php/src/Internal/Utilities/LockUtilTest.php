<?php
/**
 * Tests for the Lock utility.
 */

use Automattic\WooCommerce\Internal\Utilities\LockUtil;

/**
 * Tests relating to LockUtil.
 */
class LockUtilTest extends WC_Unit_Test_Case {

	/**
	 * Set-up subject under test.
	 */
	public function set_up() {
		parent::set_up();
	}

	/**
	 * @testdox Test a lock can't be locked twice.
	 */
	public function test_cant_lock_twice() {
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_1', 30 ) );
		$this->assertFalse( LockUtil::test_and_set_expiring( 'test_lock_1', 30 ) );
	}

	/**
	 * @testdox Test 2 different locks can be locked independently.
	 */
	public function test_different_locks() {
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_2', 30 ) );
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_3', 30 ) );
	}

	/**
	 * @testdox Test a lock can be unlocked.
	 */
	public function test_unlock() {
		global $wpdb;
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_4', 30 ) );
		LockUtil::unlock( 'test_lock_4' );
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_4', 30 ) );
	}

	/**
	 * @testdox Test a lock expires.
	 */
	public function test_expires() {
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_5', 1 ) );
		sleep( 2 );
		$this->assertTrue( LockUtil::test_and_set_expiring( 'test_lock_5', 30 ) );
	}
}
