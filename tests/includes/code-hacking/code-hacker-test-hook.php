<?php

use PHPUnit\Runner\BeforeTestHook;

/**
 * Helper to use the CodeHacker class in PHPUnit.
 *
 * How to use:
 *
 * 1. Add this to phpunit.xml:
*
 *    <extensions>
 *      <extension class="CodeHackerTestHook" file="path/to/code-hacker-test-hook.php" />
 *    </extensions>
 *
 * 2. Add the following to the test classes:
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
 */
final class CodeHackerTestHook implements BeforeTestHook {

	public function executeBeforeTest( string $test ): void {
		$parts       = explode( '::', $test );
		$class_name  = $parts[0];
		$method_name = $parts[1];

		$methods = array( 'before_all', "before_{$method_name}" );
		$methods = array_filter(
			$methods,
			function( $item ) use ( $class_name ) {
				return method_exists( $class_name, $item );
			}
		);

		if ( empty( $methods ) ) {
			return;
		}

		// Make code hacker class and individual hack classes available to tests
		include_once __DIR__ . '/code-hacker.php';
		foreach ( glob( __DIR__ . '/hacks/*.php' ) as $hack_class_file ) {
			include_once $hack_class_file;
		}

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
