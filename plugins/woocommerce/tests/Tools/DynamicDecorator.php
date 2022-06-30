<?php
/**
 * DynamicDecorator class file.
 *
 * @package WooCommerce\Testing\Tools
 */

namespace Automattic\WooCommerce\Testing\Tools;

/**
 * A class that allows to dynamically decorate an arbitrary object by selectively replace its public members,
 * the decorator class can then be used as a replacement of the original object; invocations to non replaced members
 * will be redirected to the decorated object. This is useful in the context of unit testing, especially when
 * using mocks.
 */
class DynamicDecorator {

	/**
	 * The object being decorated.
	 *
	 * @var object
	 */
	public $decorated_object;

	/**
	 * This property is not used by the decorator itself, but may be optionally used by callers
	 * in order to keep state information between member invocations, especially when using callbacks.
	 *
	 * @var mixed
	 */
	public $decorator_state;

	/**
	 * Replacement values or callbacks for property reads.
	 *
	 * @var array
	 */
	private $property_get_replacements = array();

	/**
	 * Replacement callbacks for property writes.
	 *
	 * @var array
	 */
	private $property_set_replacements = array();

	/**
	 * Replacement callbacks for methods.
	 *
	 * @var array
	 */
	private $method_replacements = array();

	/**
	 * Creates a new instance of the class.
	 *
	 * @param object $decorated_object Object that will be decorated.
	 * @param mixed  $initial_state Initial value for the $decorator_state variable.
	 */
	public function __construct( object $decorated_object, $initial_state = null ) {
		$this->decorated_object = $decorated_object;
		$this->decorator_state  = $initial_state;
	}

	/**
	 * Register a replacement value or callback to be used when a given property of the decorated object is read.
	 *
	 * If a callback is passed, it will be executed and its return value will be use as the value
	 * read from the property. The callback must accept one argument: the decorator instance itself.
	 *
	 * If anything else is passed, then the supplied value will be directly used as the value read from the property.
	 *
	 * @param string         $property_name The name of the property to replace in the decorated object.
	 * @param mixed|callable $replacement Either a value, or a callback accepting one argument (the decorator instance itself).
	 * @return void
	 */
	public function register_property_get_replacement( string $property_name, $replacement ): void {
		$this->property_get_replacements[ $property_name ] = $replacement;
	}

	/**
	 * Register a replacement callback to be used when a given property of the decorated object is written.
	 *
	 * The return value from the callback will be executed instead of the property being set in the decorated object.
	 * The callback must accept two argument: the decorator instance itself, and the value being set.
	 *
	 * @param string   $property_name The name of the property to replace in the decorated object.
	 * @param callable $replacement A callback accepting two arguments (the decorator instance itself and the value being set).
	 * @return void
	 */
	public function register_property_set_replacement( string $property_name, callable $replacement ) {
		$this->property_set_replacements[ $property_name ] = $replacement;
	}

	/**
	 * Register a replacement callback to be used when a given method is invoked in the decorated object.
	 *
	 * The callback will be executed in place of the actual method. The callback will receive the decorator
	 * instance itself as the first argument, and then the rest of the arguments passed to the invoked method
	 * in the same order. So e.g. for a method "foobar($a, $b, $c)" the callback will receive
	 * ($decorator_instance, $a, $b, $c).
	 *
	 * @param string   $method_name The name of the method to replace in the decorated object.
	 * @param callable $replacement A callback accepting as many argument as the replaced method plus one.
	 * @return void
	 */
	public function register_method_replacement( string $method_name, callable $replacement ) {
		$this->method_replacements[ $method_name ] = $replacement;
	}

	/**
	 * Removes all the registered property and method replacements.
	 *
	 * @return void
	 */
	public function reset_replacements() {
		$this->property_get_replacements = array();
		$this->property_set_replacements = array();
		$this->method_replacements       = array();
	}

	/**
	 * Calls the original method in the decorated object.
	 *
	 * Example usage:
	 *
	 * $decorator->register_method_replacement(
	 *   'some_method',
	 *   function( ...$args ) {
	 *     $decorator = $args[0];
	 *     //Do something, e.g. log the args
	 *     return $decorator->call_original_method('some_method', $args);
	 *   }
	 * );
	 *
	 * @param string $method_name The method to call.
	 * @param array  $parameters The parameters as passed to the replacement callback (so the first one is the decorator itself).
	 * @return mixed The return value from the original method.
	 */
	public function call_original_method( string $method_name, array $parameters ) {
		array_shift( $parameters );
		return call_user_func_array( array( $this->decorated_object, $method_name ), $parameters );
	}

	/**
	 * Handle a method invocation, uses the registered replacement if available or redirects the call to the decorated object.
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Arguments for the method.
	 * @return mixed Return value from either the replacement or the original method invocation.
	 */
	public function __call( $name, $arguments ) {
		if ( array_key_exists( $name, $this->method_replacements ) ) {
			array_unshift( $arguments, $this );
			return call_user_func_array( $this->method_replacements[ $name ], $arguments );
		} else {
			return call_user_func_array( array( $this->decorated_object, $name ), $arguments );
		}
	}

	/**
	 * Handle a property read, uses the registered replacement if available or redirects the read to the decorated object.
	 *
	 * @param string $name Property name.
	 * @return mixed Registered replacement value, return value from the registered replacement method, or value read from the decorated object.
	 */
	public function __get( $name ) {
		if ( ! array_key_exists( $name, $this->property_get_replacements ) ) {
			return $this->decorated_object->$name;
		}

		$replacement = $this->property_get_replacements[ $name ];
		if ( is_callable( $replacement ) ) {
			return $replacement( $this );
		} else {
			return $replacement;
		}
	}

	/**
	 * Handle a property write, uses the registered replacement if available or redirects the read to the decorated object.
	 *
	 * @param string $name Property name.
	 * @param mixed  $value Value to set.
	 * @return void
	 */
	public function __set( $name, $value ) {
		if ( array_key_exists( $name, $this->property_set_replacements ) ) {
			$this->property_set_replacements[ $name ]( $this, $value );
		} else {
			$this->decorated_object->$name = $value;
		}
	}
}
