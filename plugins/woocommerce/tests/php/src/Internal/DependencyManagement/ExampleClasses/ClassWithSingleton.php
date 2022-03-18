<?php
/**
 * ClassWithSingleton class file.
 */

// This class is in the root namespace on purpose, since it simulates being a legacy class in the 'includes' directory.

/**
 * An example of a class that holds a singleton instance.
 */
class ClassWithSingleton {

	/**
	 * @var ClassWithSingleton The singleton instance of the class.
	 */
	public static $instance;

	/**
	 * @var array The arguments supplied to 'instance'.
	 */
	public static $instance_args;

	/**
	 * Gets the singleton instance of the class.
	 *
	 * @param mixed ...$args Any arguments required by the method.
	 *
	 * @return ClassWithSingleton The singleton instance of the class.
	 */
	public static function instance( ...$args ) {
		if ( is_null( self::$instance ) ) {
			self::$instance      = new ClassWithSingleton();
			self::$instance_args = $args;
		}
		return self::$instance;
	}
}
