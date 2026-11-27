<?php
/**
 * Release PHPUnit source metadata after its class has finished.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\Annotation\Registry;

/**
 * Bounds the annotation objects scanned by PHP's garbage collector.
 *
 * PHPUnit parses inherited WordPress and WooCommerce helpers under every test
 * class. Their source annotations can be rebuilt after the class completes.
 */
final class WC_PHPUnit_Annotation_Cache_Cleanup implements TestListener {

	use TestListenerDefaultImplementation;

	/**
	 * Release annotations after the class's tests and class hooks finish.
	 *
	 * @param TestSuite $suite Completed suite.
	 */
	public function endTestSuite( TestSuite $suite ): void {
		$class = $suite->getName();
		if ( class_exists( $class, false ) && is_subclass_of( $class, TestCase::class ) ) {
			self::evict_for_class( $class );
		}
	}

	/**
	 * Remove only the completed class from PHPUnit 9.6's source annotation caches.
	 *
	 * @param class-string $test_class Completed test class.
	 */
	public static function evict_for_class( string $test_class ): void {
		$registry = Registry::getInstance();
		foreach ( array( 'classDocBlocks', 'methodDocBlocks' ) as $field ) {
			$property = new ReflectionProperty( Registry::class, $field );
			$property->setAccessible( true );
			/** @var array<string, mixed> $annotations */
			$annotations = $property->getValue( $registry );
			unset( $annotations[ $test_class ] );
			$property->setValue( $registry, $annotations );
		}
	}
}
