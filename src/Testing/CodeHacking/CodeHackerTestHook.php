<?php

namespace Automattic\WooCommerce\Testing\CodeHacking;

use PHPUnit\Runner\BeforeTestHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Util\Test;
use ReflectionClass;
use ReflectionMethod;
use Exception;

/**
 * Helper to use the CodeHacker class in PHPUnit.
 *
 * How to use:
 *
 * 1. Add this to phpunit.xml:
*
 *    <extensions>
 *      <extension class="CodeHackerTestHook" />
 *    </extensions>
 *
 * 2. Add the following to the test classes:
 *
 *    use Automattic\WooCommerce\Testing\CodeHacking\CodeHacker;
 *    use Automattic\WooCommerce\Testing\CodeHacking\Hacks\...
 *
 *    public static function before_all($method_name) {
 *      CodeHacker::add_hack(...);
 *      //Register as many hacks as needed
 *      CodeHacker::enable();
 *    }
 *
 *    $method_name is optional, 'before_all()' is also a valid method signature.
 *
 *    You can also define a test-specific 'before_{$test_method_name}' hook.
 *    If both exist, first 'before_all' will be executed, then the test-specific one.
 *
 * 3. Additionally, you can register hacks via class/method annotations
 *    (note that then you don't need the `use`s anymore):
 *
 *    /**
 *     * @hack HackClassName param1 param2
 *     * /
 *    class Some_Test
 *    {
 *       /**
 *        * @hack HackClassName param1 param2
 *        * /
 *       public function test_something() {
 *       }
 *    }
 *
 *    If the class name ends with 'Hack' you can omit that suffix in the annotation (e.g. 'Foo' instead of 'FooHack').
 *    Parameters specified after the class name will be passed to the class constructor.
 *    Hacks defined as class annotations will be applied to all tests.
 */
final class CodeHackerTestHook implements BeforeTestHook, AfterTestHook {

	public function executeAfterTest( string $test, float $time ): void {
		CodeHacker::restore();
	}

	public function executeBeforeTest( string $test ): void {
		/**
		 * Possible formats of $test:
		 * TestClass::TestMethod
		 * TestClass::TestMethod with data set #...
		 * Warning
		 */
		$parts = explode( '::', $test );
		if ( count( $parts ) < 2 ) {
			return;
		}
		$class_name  = $parts[0];
		$method_name = explode( ' ', $parts[1] )[0];

		CodeHacker::clear_hacks();

		$this->execute_before_methods( $class_name, $method_name );

		$has_class_annotation_hacks = $this->add_hacks_from_annotations( new ReflectionClass( $class_name ) );
		$has_method_annotaion_hacks = $this->add_hacks_from_annotations( new ReflectionMethod( $class_name, $method_name ) );
		if ( $has_class_annotation_hacks || $has_method_annotaion_hacks ) {
			CodeHacker::enable();
		}
	}

	/**
	 * Apply hacks defined in @hack annotations.
	 *
	 * @param object $reflection_object The class or method reflection object whose doc comment will be parsed.
	 * @return bool True if at least one valid @hack annotation was found.
	 * @throws Exception
	 */
	private function add_hacks_from_annotations( $reflection_object ) {
		$annotations = Test::parseAnnotations( $reflection_object->getDocComment() );
		$hacks_added = false;

		foreach ( $annotations as $id => $annotation_instances ) {
			if ( $id !== 'hack' ) {
				continue;
			}

			foreach ( $annotation_instances as $annotation ) {
				preg_match_all( '/"(?:\\\\.|[^\\\\"])*"|\S+/', $annotation, $matches );
				$params = $matches[0];

				$hack_class = array_shift( $params );
				if(false === strpos( $hack_class, '\\' )) {
					$hack_class = __NAMESPACE__ . '\\Hacks\\' . $hack_class;
				}

				if ( ! class_exists( $hack_class ) ) {
					$original_hack_class = $hack_class;
					$hack_class         .= 'Hack';
					if ( ! class_exists( $hack_class ) ) {
						throw new Exception( "Hack class '{$original_hack_class}' defined via annotation in {$class_name}::{$method_name} doesn't exist." );
					}
				}

				CodeHacker::add_hack( new $hack_class( ...$params ) );
				$hacks_added = true;
			}
		}

		return $hacks_added;
	}

	/**
	 * Run the 'before_all' and 'before_{test_method_name}' methods in a class.
	 *
	 * @param string $class_name Test class name.
	 * @param string $method_name Test method name.
	 * @throws ReflectionException
	 */
	private function execute_before_methods( $class_name, $method_name ) {
		$methods = array( 'before_all', "before_{$method_name}" );
		$methods = array_filter(
			$methods,
			function( $item ) use ( $class_name ) {
				return method_exists( $class_name, $item );
			}
		);

		$rc = new ReflectionClass( $class_name );

		foreach ( $methods as $method ) {
			if ( 0 === $rc->getMethod( $method_name )->getNumberOfParameters() ) {
				$class_name::$method();
			} else {
				$class_name::$method( $method_name );
			}
		}
	}
}
