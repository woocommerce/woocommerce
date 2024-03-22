<?php
/**
 * ComparisonOperation Tests
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotification
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\ComparisonOperation;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_Comparison_Operation
 */
class WC_Admin_Tests_RemoteInboxNotifications_Comparison_Operation extends WC_Unit_Test_Case {
	/**
	 * @var ComparisonOperation $operation
	 */
	private $operation;

	/**
	 * setUp
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->operation = new ComparisonOperation();
	}

	/**
	 * Test range
	 *
	 * @group fast
	 * @return void
	 */
	public function test_range() {
		$this->assertTrue( $this->operation->compare( 1, array( 1, 10 ), 'range' ) );
		$this->assertFalse( $this->operation->compare( 11, array( 1, 10 ), 'range' ) );
		$this->assertFalse( $this->operation->compare( 11, array( 1, 10, 2 ), 'range' ) );
		$this->assertFalse( $this->operation->compare( 11, 'string', 'range' ) );

	}

	/**
	 * Test contains
	 *
	 * @group fast
	 * @return void
	 */
	public function test_contains() {
		$this->assertTrue( $this->operation->compare( array( 'test', 'test1' ), 'test', 'contains' ) );
		$this->assertFalse( $this->operation->compare( array( 'a', 'b' ), 'test', 'contains' ) );
	}

	/**
	 * Test !contains
	 *
	 * @group fast
	 * @return void
	 */
	public function test_not_contains() {
		$this->assertTrue( $this->operation->compare( array( 'test1', 'test2' ), 'test', '!contains' ) );
		$this->assertFalse( $this->operation->compare( array( 'test' ), 'test', '!contains' ) );
	}

	/**
	 * Test in
	 *
	 * @group fast
	 * @return void
	 */
	public function test_in() {
		$this->assertTrue( $this->operation->compare( 'test', array( 'test', 'test2' ), 'in' ) );
		$this->assertFalse( $this->operation->compare( 'test', array( 'test1', 'test2' ), 'in' ) );
	}

	/**
	 * Test !in
	 *
	 * @group fast
	 * @return void
	 */
	public function test_not_in() {
		$this->assertTrue( $this->operation->compare( 'test', array( 'test1', 'test2' ), '!in' ) );
		$this->assertFalse( $this->operation->compare( 'test', array( 'test', 'test2' ), '!in' ) );
	}
}
