<?php
/**
 * MockableLegacyProxy class file.
 *
 * @package Automattic\WooCommerce\Testing\Tools\DependencyManagement
 */

namespace Automattic\WooCommerce\Testing\Tools\DependencyManagement;

/**
 * Mockable version of LegacyProxy.
 *
 * This version contains methods that allow to easily mock any standalone function or static class method,
 * as well as mocking the instantiation of legacy classes, within unit tests.
 * By default, and unless any mock is registered, this class acts exactly as LegacyProxy does.
 *
 * @package Automattic\WooCommerce\Testing\Tools\DependencyManagement
 */
class MockableLegacyProxy extends \Automattic\WooCommerce\Proxies\LegacyProxy {

	/**
	 * The currently registered mocks for classes.
	 *
	 * @var array
	 */
	private $mocked_classes = array();

	/**
	 * The currently registered mocks for functions.
	 *
	 * @var array
	 */
	private $mocked_functions = array();

	/**
	 * The currently registered mocks for static methods.
	 *
	 * @var array
	 */
	private $mocked_statics = array();

	/**
	 * Reset the instance to its initial state by removing all the mocks.
	 */
	public function reset() {
		$this->mocked_classes   = array();
		$this->mocked_functions = array();
		$this->mocked_statics   = array();
	}

	/**
	 * Register the function mocks to use.
	 *
	 * @param array $mocks An associative array where keys are function names and values are function replacement callbacks.
	 *
	 * @throws \Exception Invalid parameter.
	 */
	public function register_function_mocks( array $mocks ) {
		foreach ( $mocks as $function_name => $mock ) {
			if ( ! is_string( $function_name ) || ! is_callable( $mock ) ) {
				throw new \Exception( 'MockableLegacyProxy::register_function_mocks: The supplied mocks array must have function names as keys and function replacement callbacks as values.' );
			}
		}

		$this->mocked_functions = array_merge( $this->mocked_functions, $mocks );
	}

	/**
	 * Register the static method mocks to use.
	 *
	 * @param array $mocks An associative array where keys are class names and values are associative arrays, in which keys are method names and values are method replacement callbacks.
	 *
	 * @throws \Exception Invalid parameter.
	 */
	public function register_static_mocks( array $mocks ) {
		$exception_text = 'MockableLegacyProxy::register_static_mocks: $mocks must be an associative array of class name => associative array of method name => callable.';

		foreach ( $mocks as $class_name => $class_mocks ) {
			if ( ! is_string( $class_name ) || ! is_array( $class_mocks ) ) {
				throw new \Exception( $exception_text );
			}
			foreach ( $class_mocks as $method_name => $method_mock ) {
				if ( ! is_string( $method_name ) || ! is_callable( $method_mock ) ) {
					throw new \Exception( $exception_text );
				}
			}
		}

		// TODO: replace the following with just "$this->mocked_statics = array_merge_recursive( $this->mocked_statics, $mocks )" once the minimum PHP version is bumped to 7.1 or newer (see https://bugs.php.net/bug.php?id=76505).

		$class_names = array_keys( $mocks );
		foreach ( $class_names as $class_name ) {
			if ( array_key_exists( $class_name, $this->mocked_statics ) ) {
				$this->mocked_statics[ $class_name ] = array_merge( $this->mocked_statics[ $class_name ], $mocks[ $class_name ] );
			} else {
				$this->mocked_statics[ $class_name ] = $mocks[ $class_name ];
			}
		}
	}

	/**
	 * Register the class mocks to use.
	 *
	 * @param array $mocks An associative array where keys are class names and values are either factory callbacks (with any extra arguments that get_instance_of would accept) or objects.
	 *
	 * @throws \Exception Invalid parameter.
	 */
	public function register_class_mocks( array $mocks ) {
		foreach ( $mocks as $class_name => $mock ) {
			if ( ! is_string( $class_name ) || ( ! is_object( $mock ) && ! is_callable( $mock ) ) ) {
				throw new \Exception( 'MockableLegacyProxy::register_class_mocks: $mocks must be an associative array of class_name => object or factory callback.' );
			}
		}

		$this->mocked_classes = array_merge( $this->mocked_classes, $mocks );
	}

	/**
	 * Call a user function. This should be used to execute any non-idempotent function, especially
	 * those in the `includes` directory or provided by WordPress.
	 *
	 * If a mock has been defined for the requested function using `register_function_mocks`, then the registered
	 * callback will be executed instead of the function.
	 *
	 * @param string $function_name The function to execute.
	 * @param mixed  ...$parameters The parameters to pass to the function.
	 *
	 * @return mixed The result from the function or mock callback.
	 */
	public function call_function( $function_name, ...$parameters ) {
		if ( array_key_exists( $function_name, $this->mocked_functions ) ) {
			return call_user_func_array( $this->mocked_functions[ $function_name ], $parameters );
		}

		return parent::call_function( $function_name, ...$parameters );
	}

	/**
	 * Call a static method in a class. This should be used to execute any non-idempotent method in classes
	 * from the `includes` directory.
	 *
	 * If a mock has been defined for the requested class and method using `register_function_mocks`,
	 * then the registered callback will be executed instead of the method.
	 *
	 * @param string $class_name The name of the class containing the method.
	 * @param string $method_name The name of the method.
	 * @param mixed  ...$parameters The parameters to pass to the method.
	 *
	 * @return mixed The result from the method or mock callback.
	 */
	public function call_static( $class_name, $method_name, ...$parameters ) {
		if ( array_key_exists( $class_name, $this->mocked_statics ) ) {
			$class_mocks = $this->mocked_statics[ $class_name ];
			if ( array_key_exists( $method_name, $class_mocks ) ) {
				$method_mock = $class_mocks[ $method_name ];
				return call_user_func_array( $method_mock, $parameters );
			}
		}

		return parent::call_static( $class_name, $method_name, ...$parameters );
	}

	/**
	 * Gets an instance of a given legacy class.
	 * This must not be used to get instances of classes in the `src` directory.
	 *
	 * If a mock has been defined for the requested class using `register_class_mocks`, then the registered
	 * object will be returned or the registered callback will be used to generate the instance to return.
	 *
	 * @param string $class_name The name of the class to get an instance for.
	 * @param mixed  ...$args Parameters to be passed to the callback function or to the parent class method.
	 *
	 * @return object The (possibly mocked) instance of the class.
	 * @throws \Exception The requested class belongs to the `src` directory, or there was an error creating an instance of the class.
	 */
	public function get_instance_of( string $class_name, ...$args ) {
		if ( array_key_exists( $class_name, $this->mocked_classes ) ) {
			$mock = $this->mocked_classes[ $class_name ];
			return is_callable( $mock ) ? call_user_func_array( $mock, $args ) : $mock;
		}

		return parent::get_instance_of( $class_name, ...$args );
	}
}
