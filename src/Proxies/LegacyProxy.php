<?php
/**
 * LegacyProxy class file.
 *
 * @package Automattic/WooCommerce/Tools/Proxies
 */

namespace Automattic\WooCommerce\Proxies;

use \Psr\Container\ContainerInterface as Container;

/**
 * Proxy class to access legacy WooCommerce functionality.
 *
 * This class should be used to interact with code outside the `src` directory, especially functions and classes
 * in the `includes` directory, unless a more specific proxy exists for the functionality at hand (e.g. `ActionsProxy`).
 * Idempotent functions can be executed directly.
 *
 * @package Automattic\WooCommerce\Tools\Proxies
 *
 * @public
 */
class LegacyProxy {

	/**
	 * Gets an instance of a given legacy class.
	 * This must not be used to get instances of classes in the `src` directory.
	 *
	 * @param string $class_name The name of the class to get an instance for.
	 *
	 * @return object The instance of the class.
	 * @throws \Exception The requested class belongs to the `src` directory, or there was an error creating an instance of the class.
	 */
	public function get_instance_of( string $class_name ) {
		if ( false !== strpos( $class_name, '\\' ) ) {
			throw new \Exception( 'The LegacyProxy class is not intended for getting instances of classes in the src directory, please use constructor injection or the instance of \\Psr\\Container\\ContainerInterface for that.' );
		}

		try {
			// If a class has a special procedure to obtain a instance, use it.
			$instance = $this->get_special_instance_of( $class_name );
			if ( ! is_null( $instance ) ) {
				return $instance;
			}

			// If the class is a singleton, use the "instance" method.
			if ( method_exists( $class_name, 'instance' ) ) {
				return $class_name::instance();
			}

			// Fallback to simply creating a new instance of the class.
			return new $class_name();
		} catch ( \Exception $e ) {
			throw new \Exception( __CLASS__ . '::' . __FUNCTION__ . ": error when getting a new instance of $class_name: {$e->getMessage()}", $e );
		}
	}

	/**
	 * Get an instance of a class using a special procedure, if possible.
	 *
	 * @param string $class_name The name of the class to get an instance for.
	 *
	 * @return object|null The instance, or null if there's no special procedure for that class.
	 */
	private function get_special_instance_of( string $class_name ) {
		if ( \WC_Queue_Interface::class === $class_name ) {
			return \WC_Queue::instance();
		}

		return null;
	}

	/**
	 * Call a user function. This should be used to execute any non-idempotent function, especially
	 * those in the `includes` directory or provided by WordPress.
	 *
	 * @param string $function_name The function to execute.
	 * @param mixed  ...$parameters The parameters to pass to the function.
	 *
	 * @return mixed The result from the function.
	 */
	public function call_function( $function_name, ...$parameters ) {
		return call_user_func_array( $function_name, $parameters );
	}

	/**
	 * Call a static method in a class. This should be used to execute any non-idempotent method in classes
	 * from the `includes` directory.
	 *
	 * @param string $class_name The name of the class containing the method.
	 * @param string $method_name The name of the method.
	 * @param mixed  ...$parameters The parameters to pass to the method.
	 *
	 * @return mixed The result from the method.
	 */
	public function call_static( $class_name, $method_name, ...$parameters ) {
		return call_user_func_array( "$class_name::$method_name", $parameters );
	}
}
