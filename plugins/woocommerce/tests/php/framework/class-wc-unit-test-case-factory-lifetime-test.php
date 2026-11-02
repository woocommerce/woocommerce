<?php
/**
 * Tests for per-test factory lifetime.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/** Tests the complete WC lifecycle without nesting WordPress transactions. */
class WC_Unit_Test_Case_Factory_Lifetime_Test extends PHPUnit\Framework\TestCase {
	/**
	 * Load the concrete probe after PHPUnit has discovered the runnable tests.
	 */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../helpers/class-wc-unit-test-case-factory-lifetime-probe.php';
	}

	/**
	 * @testdox Releases the owned factory after the complete lifecycle.
	 */
	public function test_releases_owned_factory_after_complete_lifecycle(): void {
		$probe = new WC_Unit_Test_Case_Factory_Lifetime_Probe( 'exercise_factory' );
		$probe->runBare();
		$this->assertTrue( $probe->factory_accessible_after_parent_teardown );
		$this->assertFalse( $probe->is_factory_initialized(), 'Completed test instances must release their owned factory.' );
		gc_collect_cycles();
		$this->assertNull( $probe->factory_reference->get() );
	}

	/**
	 * @testdox Preserves explicit return value ownership.
	 */
	public function test_preserves_explicit_return_value_ownership(): void {
		$probe = new WC_Unit_Test_Case_Factory_Lifetime_Probe( 'return_factory' );
		$probe->runBare();
		$returned = $probe->getResult();
		gc_collect_cycles();
		$this->assertInstanceOf( WC_Unit_Test_Factory::class, $returned );
		$this->assertSame( $returned, $probe->factory_reference->get() );
		$this->assertSame( $returned, $returned->post->factory );
	}

	/**
	 * @testdox Releases the factory after a failed test body.
	 */
	public function test_releases_factory_after_failed_body(): void {
		$probe = new WC_Unit_Test_Case_Factory_Lifetime_Probe( 'fail_body' );
		try {
			$probe->runBare();
			$this->fail( 'The inner failure should propagate unchanged.' );
		} catch ( PHPUnit\Framework\AssertionFailedError $failure ) {
			$this->assertSame( 'Fixture lifetime probe failure', $failure->getMessage() );
		}
		$this->assertTrue( $probe->factory_accessible_after_parent_teardown );
		$this->assertFalse( $probe->is_factory_initialized() );
	}

	/**
	 * @testdox Constructs fresh factories on repeated runs.
	 */
	public function test_repeated_runs_construct_fresh_factories(): void {
		$probe = new WC_Unit_Test_Case_Factory_Lifetime_Probe( 'exercise_factory' );
		$probe->runBare();
		$first = $probe->factory_reference;
		$probe->runBare();
		gc_collect_cycles();
		$this->assertNull( $first->get() );
		$this->assertFalse( $probe->is_factory_initialized() );
	}
}
