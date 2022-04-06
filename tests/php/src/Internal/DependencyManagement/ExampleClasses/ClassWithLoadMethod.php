<?php
/**
 * ClassWithSingleton class file.
 */

// This class is in the root namespace on purpose, since it simulates being a legacy class in the 'includes' directory.

/**
 * An example of a class that holds a singleton instance.
 */
class ClassWithLoadMethod {

	/**
	 * @var ClassWithLoadMethod The last instance of the class that has been loaded.
	 */
	public static $loaded;

	/**
	 * @var array The arguments supplied to 'load'.
	 */
	public static $loaded_args;

	/**
	 * Load an instance of the class.
	 *
	 * @param mixed ...$args Any arguments required by the method.
	 *
	 * @return ClassWithLoadMethod The singleton instance of the class.
	 */
	public static function load( ...$args ) {
		self::$loaded      = new ClassWithLoadMethod();
		self::$loaded_args = $args;
		return self::$loaded;
	}
}
