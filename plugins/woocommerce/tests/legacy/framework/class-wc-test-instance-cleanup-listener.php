<?php
/**
 * Releases what a finished test left on its own properties.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * PHPUnit keeps every test instance until the run ends, so fixtures, mocks and services stored on
 * test properties stay reachable and grow the heap the garbage collector scans. Unsets the
 * properties declared by the test's own classes after each test; PHPUnit and WordPress base class
 * state is left alone.
 */
final class WC_Test_Instance_Cleanup_Listener implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Classes where the walk up the hierarchy stops; their properties and their ancestors' are kept.
	 */
	private const BOUNDARY_CLASSES = array( TestCase::class, 'WP_UnitTestCase_Base' );

	/**
	 * Releasable property names grouped by declaring class, keyed by test class.
	 *
	 * @var array<class-string, array<class-string, string[]>>
	 */
	private $properties_by_class = array();

	/**
	 * Unset the finished test's properties.
	 *
	 * @param Test  $test The test that just finished.
	 * @param float $time Unused.
	 */
	public function endTest( Test $test, float $time ): void {
		if ( ! $test instanceof TestCase ) {
			return;
		}

		foreach ( $this->get_releasable_properties( $test ) as $declaring_class => $names ) {
			Closure::bind(
				function () use ( $names ) {
					foreach ( $names as $name ) {
						unset( $this->$name );
					}
				},
				$test,
				$declaring_class
			)();
		}
	}

	/**
	 * Non-static, non-readonly properties declared below the boundary classes; readonly ones cannot be unset.
	 *
	 * @param TestCase $test The finished test.
	 * @return array<class-string, string[]>
	 */
	private function get_releasable_properties( TestCase $test ): array {
		$test_class = get_class( $test );
		if ( isset( $this->properties_by_class[ $test_class ] ) ) {
			return $this->properties_by_class[ $test_class ];
		}

		$properties = array();
		$class      = new ReflectionClass( $test );
		while ( $class && ! in_array( $class->getName(), self::BOUNDARY_CLASSES, true ) ) {
			foreach ( $class->getProperties() as $property ) {
				if ( $property->isStatic() || $property->getDeclaringClass()->getName() !== $class->getName() ) {
					continue;
				}
				if ( method_exists( $property, 'isReadOnly' ) && $property->isReadOnly() ) {
					continue;
				}
				$properties[ $class->getName() ][] = $property->getName();
			}
			$class = $class->getParentClass();
		}

		$this->properties_by_class[ $test_class ] = $properties;

		return $properties;
	}
}
