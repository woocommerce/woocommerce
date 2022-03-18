<?php
/**
 * FunctionsMockerHack class file.
 *
 * @package WooCommerce\Testing
 */

namespace Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks;

use ReflectionMethod;
use ReflectionClass;

/**
 * Hack to mock standalone functions.
 *
 * How to use:
 *
 * 1. Invoke 'FunctionsMockerHack::initialize' once, passing an array with the names of the functions
 * that can be mocked.
 *
 * 2. Invoke 'CodeHacker::add_hack(FunctionsMockerHack::get_hack_instance())' once.
 *
 * 3. Use 'add_function_mocks' in tests as needed to register callbacks to be executed instead of the functions, e.g.:
 *
 * FunctionsMockerHack::add_function_mocks([
 *   'get_option' => function($name, $default) {
 *       return 'foo' === $name ? 'bar' : get_option($name, $default);
 *    }
 * ]);
 *
 * 1 and 2 must be done during the unit testing bootstrap process.
 *
 * Note that unless the tests directory is included in the hacking via 'CodeHacker::initialize'
 * (and they shouldn't!), test code files aren't hacked, therefore the original functions are always
 * executed inside tests (and thus the above example won't stack-overflow).
 */
final class FunctionsMockerHack extends CodeHack {
	/**
	 * Tokens that precede a non-standalone-function identifier.
	 *
	 * @var array
	 */
	private static $non_global_function_tokens = array(
		T_PAAMAYIM_NEKUDOTAYIM,
		T_DOUBLE_COLON,
		T_OBJECT_OPERATOR,
		T_FUNCTION,
		T_CLASS,
		T_EXTENDS,
	);

	/**
	 * @var FunctionsMockerHack Holds the only existing instance of the class.
	 */
	private static $instance;

	/**
	 * Initializes the class.
	 *
	 * @param array $mockable_functions An array containing the names of the functions that will become mockable.
	 *
	 * @throws \Exception $mockable_functions is not an array or is empty.
	 */
	public static function initialize( $mockable_functions ) {
		if ( ! is_array( $mockable_functions ) || empty( $mockable_functions ) ) {
			throw new \Exception( 'FunctionsMockeHack::initialize: $mockable_functions must be a non-empty array of function names.' );
		}

		self::$instance = new FunctionsMockerHack( $mockable_functions );
	}

	/**
	 * FunctionsMockerHack constructor.
	 *
	 * @param array $mockable_functions An array containing the names of the functions that will become mockable.
	 */
	private function __construct( $mockable_functions ) {
		$this->mockable_functions = $mockable_functions;
	}

	/**
	 * Hacks code by replacing elegible function invocations with an invocation to this class' static method with the same name.
	 *
	 * @param string $code The code to hack.
	 * @param string $path The path of the file containing the code to hack.
	 * @return string The hacked code.
	 */
	public function hack( $code, $path ) {
		$tokens = $this->tokenize( $code );
		$code   = '';
		$previous_token_is_non_global_function_qualifier = false;

		foreach ( $tokens as $token ) {
			$token_type = $this->token_type_of( $token );
			if ( T_WHITESPACE === $token_type ) {
				$code .= $this->token_to_string( $token );
			} elseif ( T_STRING === $token_type && ! $previous_token_is_non_global_function_qualifier && in_array( $token[1], $this->mockable_functions, true ) ) {
				$code .= __CLASS__ . "::{$token[1]}";
				$previous_token_is_non_global_function_qualifier = false;
			} else {
				$code .= $this->token_to_string( $token );
				$previous_token_is_non_global_function_qualifier = in_array( $token_type, self::$non_global_function_tokens, true );
			}
		}

		return $code;
	}

	/**
	 * @var array Functions that can be mocked, associative array of function name => callback.
	 */
	private $function_mocks = array();

	/**
	 * Register function mocks.
	 *
	 * @param array $mocks Mocks as an associative array of function name => mock function with the same arguments as the original function.
	 *
	 * @throws \Exception Invalid input.
	 */
	public function register_function_mocks( $mocks ) {
		if ( ! is_array( $mocks ) ) {
			throw new \Exception( 'FunctionsMockerHack::add_function_mocks: $mocks must be an associative array of function name => callable.' );
		}

		foreach ( $mocks as $function_name => $mock ) {
			if ( ! in_array( $function_name, $this->mockable_functions, true ) ) {
				throw new \Exception( "FunctionsMockerHack::add_function_mocks: Can't mock '$function_name' since it isn't in the list of mockable functions supplied to 'initialize'." );
			}
			if ( ! is_callable( $mock ) ) {
				throw new \Exception( "FunctionsMockerHack::add_function_mocks: The mock supplied for '$function_name' isn't callable." );
			}

			$this->function_mocks[ $function_name ] = $mock;
		}
	}

	/**
	 * Register function mocks.
	 *
	 * @param array $mocks Mocks as an associative array of function name => mock function with the same arguments as the original function.
	 *
	 * @throws \Exception Invalid input.
	 */
	public static function add_function_mocks( $mocks ) {
		self::$instance->register_function_mocks( $mocks );
	}

	/**
	 * Unregister all the registered function mocks.
	 */
	public function reset() {
		$this->function_mocks = array();
	}

	/**
	 * Handler for undefined static methods on this class, it invokes the mock for the function if registered or the original function if not.
	 *
	 * @param string $name Name of the function.
	 * @param array  $arguments Arguments for the function.
	 *
	 * @return mixed The return value from the invoked callback or function.
	 */
	public static function __callStatic( $name, $arguments ) {
		if ( array_key_exists( $name, self::$instance->function_mocks ) ) {
			return call_user_func_array( self::$instance->function_mocks[ $name ], $arguments );
		} else {
			return call_user_func_array( $name, $arguments );
		}
	}

	/**
	 * Get the only existing instance of this class. 'get_instance' is not used to avoid conflicts since that's a widely used method name.
	 *
	 * @return FunctionsMockerHack The only existing instance of this class.
	 */
	public static function get_hack_instance() {
		return self::$instance;
	}
}
