<?php
/**
 * StaticWrapper class file.
 *
 * @package WooCommerce/Testing
 */

namespace Automattic\WooCommerce\Testing\CodeHacking;

use ReflectionClass;
use ReflectionMethod;

/**
 * This is the base class for defining a dynamically configurable wrapper for static methods in a class.
 *
 * How to use:
 *
 * 1. Define a wrapper for the class and register it as a code hack using StaticMockerHack:
 *
 * $wrapper_class = StaticWrapper::define_for('MyClass');
 * CodeHacker::add_hack(new StaticMockerHack($class, $wrapper_class, true));
 * CodeHacker::enable();
 *
 * 2. Define a class with the mock methods, and register it with set_mock_class_for:
 *
 * class MyClass_Mock {
 *   public static function method_to_mock() { ... }
 * }
 *
 * StaticWrapper::set_mock_class_for('MyClass', 'MyClass_Mock');
 *
 * 3. Or alternatively, define the mocked methods as standalone functions, and register them with set_mock_functions_for:
 *
 * $functions = array(
 *   'method_to_mock' => function(...);
 * );
 *
 * StaticWrapper::set_mock_functions_for('MyClass', $functions);
 *
 * You can use 2 and 3 at the same time, functions have precedence over mock class methods in case of conflict.
 *
 * @package Automattic\WooCommerce\Testing\CodeHacking
 */
abstract class StaticWrapper {

	// phpcs:disable Squiz.Commenting.VariableComment.Missing
	protected static $mock_class_name       = null;
	protected static $methods_in_mock_class = array();
	protected static $mocking_functions     = array();

	private static $wrapper_suffix = '_Wrapper';
	// phpcs:enable Squiz.Commenting.VariableComment.Missing

	/**
	 * Define a new class that inherits from StaticWrapper, to be used as a static wrapper for a given class.
	 *
	 * @param string $class_name Class for which the static wrapper will be generated.
	 *
	 * @return string Name of the generated class.
	 */
	public static function define_for( $class_name ) {
		$wrapper_name = self::wrapper_for( $class_name );
		// phpcs:ignore Squiz.PHP.Eval.Discouraged
		eval( 'class ' . $wrapper_name . ' extends ' . __CLASS__ . ' {}' );
		return $wrapper_name;
	}

	/**
	 * Returns the name of the wrapper class that define_for will create for a given class.
	 *
	 * @param string $class_name Class to return the wrapper name for.
	 *
	 * @return string Name of the class that define_for defines.
	 */
	public static function wrapper_for( $class_name ) {
		return $class_name . self::$wrapper_suffix;
	}

	/**
	 * Registers the class that will be used to mock the original class.
	 * Static methods called in the wrapper class that also exist in the mock class will be redirected
	 * to the mock class.
	 *
	 * @param string $mock_class_name Mock class whose methods will be invoked.
	 *
	 * @throws \ReflectionException Error when creating ReflectionClass or ReflectionMethod.
	 */
	public static function set_mock_class( $mock_class_name ) {
		self::$mock_class_name = $mock_class_name;

		$rc = new ReflectionClass( $mock_class_name );

		$static_methods = $rc->getMethods( ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC );
		$static_methods = array_map(
			function( $item ) {
				return $item->getName();
			},
			$static_methods
		);

		self::$methods_in_mock_class = $static_methods;
	}

	/**
	 * This method is the same as set_mock_class, but it's intended to be invoked directly on StaticWrapper,
	 * passing the original class name as a parameter.
	 *
	 * @param string $class_name Class name whose set_mock_class method will be invoked.
	 * @param string $mock_class_name Parameter that will be passed to set_mock_class method in $class_name.
	 */
	public static function set_mock_class_for( $class_name, $mock_class_name ) {
		( self::wrapper_for( $class_name ) )::set_mock_class( $mock_class_name );
	}

	/**
	 * Registers an array of functions that will be used to mock the original class. It must be an associative
	 * array of name => function. Static methods called in the wrapper class having a corresponding key in the array
	 * will be redirected to the corresponding function.
	 *
	 * @param array $mocking_functions Associative array where keys are method names and values are functions.
	 */
	public static function set_mock_functions( $mocking_functions ) {
		self::$mocking_functions = $mocking_functions;
	}

	/**
	 * This method is the same as set_mock_functions, but it's intended to be invoked directly on StaticWrapper,
	 * passing the original class name as a parameter.
	 *
	 * @param string $class_name Class name whose set_mock_functions method will be invoked.
	 * @param array  $mocking_functions Parameter that will be passed to set_mock_functions method in $class_name.
	 */
	public static function set_mock_functions_for( $class_name, $mocking_functions ) {
		( self::wrapper_for( $class_name ) )::set_mock_functions( $mocking_functions );
	}

	/**
	 * Intercepts calls to undefined static methods in the wrapper and redirects them to the mock class/function
	 * if available, or to the original class otherwise.
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Method arguments.
	 *
	 * @return mixed Return value from the invoked method.
	 */
	public static function __callStatic( $name, $arguments ) {
		if ( array_key_exists( $name, self::$mocking_functions ) ) {
			return call_user_func( self::$mocking_functions[ $name ], ...$arguments );
		} elseif ( ! is_null( self::$mock_class_name ) && in_array( $name, self::$methods_in_mock_class, true ) ) {
			return ( self::$mock_class_name )::$name( ...$arguments );
		} else {
			$original_class_name = preg_replace( '/' . self::$wrapper_suffix . '$/', '', get_called_class() );
			return $original_class_name::$name( ...$arguments );
		}
	}
}
