<?php
/**
 * StaticMockerHack class file.
 *
 * @package WooCommerce\Testing
 */

namespace Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks;

/**
 * Hack to mock public static methods and properties.
 *
 * How to use:
 *
 * 1. Invoke 'StaticMockerHack::initialize' once, passing an array with the names of the classes
 * that can be mocked.
 *
 * 2. Invoke 'CodeHacker::add_hack(StaticMockerHack::get_hack_instance())' once.
 *
 * 3. Use 'add_method_mocks' in tests as needed to register callbacks to be executed instead of the functions, e.g.:
 *
 * StaticMockerHack::add_method_mocks(
 * [
 *     'SomeClass' => [
 *         'some_method' => function($some_arg) {
 *              return 'foo' === $some_arg ? 'bar' : SomeClass::some_method($some_arg);
 *         }
 *     ]
 * ]);
 *
 * 1 and 2 must be done during the unit testing bootstrap process.
 *
 * Note that unless the tests directory is included in the hacking via 'CodeHacker::initialize'
 * (and they shouldn't!), test code files aren't hacked, therefore the original functions are always
 * executed inside tests (and thus the above example won't stack-overflow).
 */
final class StaticMockerHack extends CodeHack {

	/**
	 * @var StaticMockerHack Holds the only existing instance of the class.
	 */
	private static $instance;

	/**
	 * Initializes the class.
	 *
	 * @param array $mockable_classes An associative array of class name => array of class methods.
	 *
	 * @throws \Exception $mockable_functions is not an array or is empty.
	 */
	public static function initialize( $mockable_classes ) {
		if ( ! is_array( $mockable_classes ) || empty( $mockable_classes ) ) {
			throw new \Exception( 'StaticMockerHack::initialize:: $mockable_classes must be a non-empty associative array of class name => array of class methods.' );
		}

		self::$instance = new StaticMockerHack( $mockable_classes );
	}

	/**
	 * StaticMockerHack constructor.
	 *
	 * @param array $mockable_classes An associative array of class name => array of class methods.
	 */
	private function __construct( $mockable_classes ) {
		$this->mockable_classes = $mockable_classes;
	}

	/**
	 * Hacks code by replacing elegible method invocations with an invocation a static method on this class composed from the class and the method names.
	 *
	 * @param string $code The code to hack.
	 * @param string $path The path of the file containing the code to hack.
	 * @return string The hacked code.
	 *
	 */
	public function hack( $code, $path ) {
		$last_item = null;

		$tokens        = $this->tokenize( $code );
		$code          = '';
		$current_token = null;

		// phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $current_token = current( $tokens ) ) {
			if ( $this->is_token_of_type( $current_token, T_STRING ) && in_array( $current_token[1], $this->mockable_classes, true ) ) {
				$class_name = $current_token[1];
				$next_token = next( $tokens );
				if ( $this->is_token_of_type( $next_token, T_DOUBLE_COLON ) ) {
					$called_member = next( $tokens )[1];
					$code         .= __CLASS__ . "::invoke__{$called_member}__for__{$class_name}";
				} else {
					// Reference to source class, but not followed by '::'.
					$code .= $this->token_to_string( $current_token ) . $this->token_to_string( $next_token );
				}
			} else {
				// Not a reference to source class.
				$code .= $this->token_to_string( $current_token );
			}
			next( $tokens );
		}

		return $code;
	}

	/**
	 * @var array Associative array of class name => associative array of method name => callback.
	 */
	private $method_mocks = array();

	/**
	 * Register method mocks.
	 *
	 * @param array $mocks Mocks as an associative array of class name => associative array of method name => mock method with the same arguments as the original method.
	 *
	 * @throws \Exception Invalid input.
	 */
	public function register_method_mocks( $mocks ) {
		$exception_text = 'StaticMockerHack::register_method_mocks: $mocks must be an associative array of class name => associative array of method name => callable.';

		if ( ! is_array( $mocks ) ) {
			throw new \Exception( $exception_text );
		}

		foreach ( $mocks as $class_name => $class_mocks ) {
			if ( ! is_string( $class_name ) || ! is_array( $class_mocks ) ) {
				throw new \Exception( $exception_text );
			}
			foreach ( $class_mocks as $method_name => $method_mock ) {
				if ( ! is_string( $method_name ) || ! is_callable( $method_mock ) ) {
					throw new \Exception( $exception_text );
				}
				if ( ! in_array( $class_name, $this->mockable_classes, true ) ) {
					throw new \Exception( "FunctionsMockerHack::add_function_mocks: Can't mock methods of the '$class_name' class since it isn't in the list of mockable classes supplied to 'initialize'." );
				}

				$this->method_mocks[ $class_name ][ $method_name ] = $method_mock;
			}
		}
	}

	/**
	 * Register method mocks.
	 *
	 * @param array $mocks Mocks as an associative array of class name => associative array of method name => mock method with the same arguments as the original method.
	 *
	 * @throws \Exception Invalid input.
	 */
	public static function add_method_mocks( $mocks ) {
		self::$instance->register_method_mocks( $mocks );
	}

	/**
	 * Unregister all the registered method mocks.
	 */
	public function reset() {
		$this->method_mocks = array();
	}

	/**
	 * Handler for undefined static methods on this class, it invokes the mock for the method if both the class and the method are registered, or the original method in the original class if not.
	 *
	 * @param string $name Name of the method.
	 * @param array  $arguments Arguments for the function.
	 *
	 * @return mixed The return value from the invoked callback or method.
	 *
	 * @throws \Exception Invalid method name.
	 */
	public static function __callStatic( $name, $arguments ) {
		preg_match( '/invoke__(.+)__for__(.+)/', $name, $matches );
		if ( empty( $matches ) ) {
			throw new \Exception( 'Invalid method ' . __CLASS__ . "::{$name}" );
		}

		$class_name  = $matches[2];
		$method_name = $matches[1];

		if ( array_key_exists( $class_name, self::$instance->method_mocks ) && array_key_exists( $method_name, self::$instance->method_mocks[ $class_name ] ) ) {
			return call_user_func_array( self::$instance->method_mocks[ $class_name ][ $method_name ], $arguments );
		} else {
			return call_user_func_array( "{$class_name}::{$method_name}", $arguments );
		}
	}

	/**
	 * Get the only existing instance of this class. 'get_instance' is not used to avoid conflicts since that's a widely used method name.
	 *
	 * @return StaticMockerHack The only existing instance of this class.
	 */
	public static function get_hack_instance() {
		return self::$instance;
	}
}
