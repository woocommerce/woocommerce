<?php
/**
 * Tests for the completed-class annotation cache cleanup.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Util\Annotation\Registry;
use PHPUnit\Util\Test as TestUtil;

require_once __DIR__ . '/../../legacy/framework/class-wc-phpunit-annotation-cache-cleanup.php';

/** Tests annotation metadata, provider and ownership contracts for cache cleanup. */
class WC_PHPUnit_Annotation_Cache_Cleanup_Test extends WC_Unit_Test_Case {
	/**
	 * Load concrete fixtures after PHPUnit has discovered the runnable tests.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../helpers/class-wc-phpunit-annotation-child-fixture.php';
	}

	/**
	 * @testdox Keeps the completed suite's tests, counts and groups intact.
	 */
	public function test_preserves_completed_suite_structure(): void {
		$class = WC_PHPUnit_Annotation_Child_Fixture::class;
		$suite = new PHPUnit\Framework\TestSuite();
		$suite->setName( $class );
		$suite->addTest( new WC_PHPUnit_Annotation_Child_Fixture( 'exercise' ), array( 'annotation-fixture' ) );
		$before   = array( $suite->tests(), count( $suite ), $suite->getGroupDetails() );
		$metadata = WeakReference::create( Registry::getInstance()->forClassName( $class ) );

		( new WC_PHPUnit_Annotation_Cache_Cleanup() )->endTestSuite( $suite );

		$this->assertNull( $metadata->get() );
		$this->assertSame( $before, array( $suite->tests(), count( $suite ), $suite->getGroupDetails() ) );
	}

	/**
	 * @testdox Leaves metadata available when a data-provider suite finishes.
	 */
	public function test_ignores_data_provider_suite_boundaries(): void {
		$class    = WC_PHPUnit_Annotation_Child_Fixture::class;
		$metadata = Registry::getInstance()->forClassName( $class );
		$suite    = new PHPUnit\Framework\TestSuite();
		$suite->setName( $class . '::exercise' );

		( new WC_PHPUnit_Annotation_Cache_Cleanup() )->endTestSuite( $suite );

		$this->assertSame( $metadata, Registry::getInstance()->forClassName( $class ) );
	}

	/**
	 * @testdox Releases only the completed class metadata.
	 */
	public function test_releases_only_completed_class_metadata(): void {
		$registry  = Registry::getInstance();
		$class     = WC_PHPUnit_Annotation_Child_Fixture::class;
		$method    = $registry->forMethod( $class, 'exercise' );
		$before    = array( $method->symbolAnnotations(), $method->requirements() );
		$reference = WeakReference::create( $method );
		unset( $method );
		$class_reference = WeakReference::create( $registry->forClassName( $class ) );
		$other           = $registry->forMethod( WC_PHPUnit_Annotation_Parent_Fixture::class, 'exercise' );
		WC_PHPUnit_Annotation_Cache_Cleanup::evict_for_class( $class );
		$this->assertNull( $reference->get(), 'Completed method metadata should be releasable.' );
		$this->assertNull( $class_reference->get(), 'Completed class metadata should be releasable.' );
		$this->assertSame( $registry, Registry::getInstance() );
		$this->assertSame( $other, $registry->forMethod( WC_PHPUnit_Annotation_Parent_Fixture::class, 'exercise' ) );
		$method = $registry->forMethod( $class, 'exercise' );
		$this->assertSame( $before, array( $method->symbolAnnotations(), $method->requirements() ) );
	}

	/**
	 * @testdox Preserves provider context and execution count.
	 */
	public function test_preserves_provider_context_and_execution_count(): void {
		WC_PHPUnit_Annotation_Parent_Fixture::$provider_calls = 0;
		$registry = Registry::getInstance();
		$before   = $registry->forMethod( WC_PHPUnit_Annotation_Child_Fixture::class, 'exercise' )->getProvidedData();
		WC_PHPUnit_Annotation_Cache_Cleanup::evict_for_class( WC_PHPUnit_Annotation_Child_Fixture::class );
		$this->assertSame( 1, WC_PHPUnit_Annotation_Parent_Fixture::$provider_calls, 'Eviction must not execute providers.' );
		$after = $registry->forMethod( WC_PHPUnit_Annotation_Child_Fixture::class, 'exercise' )->getProvidedData();
		$this->assertSame( 2, WC_PHPUnit_Annotation_Parent_Fixture::$provider_calls );
		$this->assertSame( array( array( WC_PHPUnit_Annotation_Child_Fixture::class ) ), $before );
		$this->assertSame( $before, $after );
	}

	/**
	 * @testdox Preserves hook names without invoking hooks.
	 */
	public function test_preserves_hook_names_without_invoking_hooks(): void {
		WC_PHPUnit_Annotation_Parent_Fixture::$hook_calls = 0;
		$before = TestUtil::getHookMethods( WC_PHPUnit_Annotation_Child_Fixture::class );
		WC_PHPUnit_Annotation_Cache_Cleanup::evict_for_class( WC_PHPUnit_Annotation_Child_Fixture::class );
		$this->assertSame( $before, TestUtil::getHookMethods( WC_PHPUnit_Annotation_Child_Fixture::class ) );
		$this->assertContains( 'before_fixture', $before['before'] );
		$this->assertContains( 'after_fixture_class', $before['afterClass'] );
		$this->assertSame( 0, WC_PHPUnit_Annotation_Parent_Fixture::$hook_calls );
	}
}
