<?php
/**
 * PHPUnit bootstrap helper tests.
 *
 * @package WooCommerce\ExtensionTools
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\ExtensionTools\Testing\Test_Bootstrap;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the shared parts of an extension's WooCommerce PHPUnit bootstrap.
 */
class TestBootstrapTest extends TestCase {

	/**
	 * The unit test case can extend the HTTP test case only if it loads after it.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loads_core_test_classes_in_order() {
		Test_Bootstrap::load_core_test_cases( __DIR__ . '/fixtures/wc-test-framework' );
		$this->assertTrue( is_subclass_of( WC_Unit_Test_Case::class, WP_HTTP_TestCase::class ) );
		// Loading again should not redeclare the test classes.
		Test_Bootstrap::load_core_test_cases( __DIR__ . '/fixtures/wc-test-framework' );
	}

	/**
	 * The legacy test helpers and WooCommerce must see the same testing container.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_replaces_the_inner_and_global_containers() {
		require __DIR__ . '/fixtures/wc-container.php';
		require __DIR__ . '/fixtures/class-testbootstrapreplaceablecontainer.php';

		$cases = array(
			array( false, new TestBootstrapReplaceableContainer() ),
			array( true, new stdClass() ),
		);
		foreach ( $cases as list( $prefer_existing_container, $original ) ) {
			$container               = new TestBootstrapContainer( $original );
			$GLOBALS['wc_container'] = $container;
			Test_Bootstrap::initialize_test_container( $prefer_existing_container );

			$replacement = wc_get_container();
			$this->assertInstanceOf( TestingContainer::class, $replacement );
			$this->assertSame( $original, $replacement->original );
			$this->assertSame( $replacement, $container->inner() );
		}
	}

	/**
	 * Preserve the container and reset its services when the suite opts into reuse.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_reuses_a_replaceable_inner_container_when_requested() {
		require __DIR__ . '/fixtures/wc-container.php';
		require __DIR__ . '/fixtures/class-testbootstrapreplaceablecontainer.php';
		require __DIR__ . '/fixtures/class-testbootstrapmockablelegacyproxy.php';
		class_alias( TestBootstrapMockableLegacyProxy::class, MockableLegacyProxy::class );

		$inner                   = new TestBootstrapReplaceableContainer();
		$container               = new TestBootstrapContainer( $inner );
		$GLOBALS['wc_container'] = $container;
		Test_Bootstrap::initialize_test_container( true );

		$this->assertSame( $inner, wc_get_container() );
		$this->assertSame( $inner, $container->inner() );
		$this->assertCount( 2, $inner->calls );
		$this->assertSame( LegacyProxy::class, $inner->calls[0][0] );
		$this->assertInstanceOf( MockableLegacyProxy::class, $inner->calls[0][1] );
		$this->assertSame( array( 'reset_all_resolved' ), $inner->calls[1] );
	}

	/**
	 * Preserve extension suites' fallback when a different container layout is used.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_leaves_an_unrecognized_container_alone() {
		require __DIR__ . '/fixtures/class-testbootstraptestingcontainer.php';
		class_alias( TestBootstrapTestingContainer::class, 'Automattic\WooCommerce\Container' );

		$container               = new TestBootstrapTestingContainer( new stdClass() );
		$GLOBALS['wc_container'] = $container;
		Test_Bootstrap::initialize_test_container();

		$this->assertSame( $container, $GLOBALS['wc_container'] );
	}
}
