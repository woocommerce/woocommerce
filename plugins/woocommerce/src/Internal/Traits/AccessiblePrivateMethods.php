<?php

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * This trait allows making private methods of a class accessible from outside.
 * This is useful to define hook handlers with the [$this, 'method'] syntax without having to
 * make the method public (and thus having to keep it forever for backwards compatibility).
 *
 * Example:
 *
 * class Foobar {
 *   use AccessiblePrivateMethods;
 *
 *   public function __construct() {
 *     $this->add_action('some_action', [$this, 'handle_some_action']);
 *   }
 *
 *   private function handle_some_action() {
 *   }
 * }
 *
 * Additionally, 'add_action' and 'add_filter' accept the name of a method in the containing class,
 * so the following is equivalent to the above example:
 *
 * $this->add_action('some_action', 'handle_some_action');
 *
 * Static methods are supported as well, but in this case the trait methods to use are
 * 'add_static_ation' and 'add_static_filter':
 *
 * self::add_static action('some_action', [__CLASS__, 'handle_some_action']);
 */
trait AccessiblePrivateMethods {

	//phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
	/**
	 * List of instance methods marked as externally accessible.
	 *
	 * @var array
	 */
	private $_accessible_private_methods = array();

	/**
	 * List of static methods marked as externally accessible.
	 *
	 * @var array
	 */
	private static $_accessible_static_private_methods = array();
	//phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Register a WordPress action.
	 * If the callback refers to a private or protected instance method in this class, the method is marked as externally accessible.
	 *
	 * $callback can be a standard callable, or a string representing the name of a method in this class.
	 *
	 * @param string   $hook_name       The name of the action to add the callback to.
	 * @param callable $callback        The callback to be run when the action is called.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	protected function add_action( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->add_filter( $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Register a WordPress action.
	 * If the callback refers to a private or protected static method in this class, the method is marked as externally accessible.
	 *
	 * $callback can be a standard callable, or a string representing the name of a method in this class.
	 *
	 * @param string   $hook_name       The name of the action to add the callback to.
	 * @param callable $callback        The callback to be run when the action is called.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	protected static function add_static_action( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		static::add_static_filter( $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Register a WordPress filter.
	 * If the callback refers to a private or protected instance method in this class, the method is marked as externally accessible.
	 *
	 * $callback can be a standard callable, or a string representing the name of a method in this class.
	 *
	 * @param string   $hook_name       The name of the action to add the callback to.
	 * @param callable $callback        The callback to be run when the action is called.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	protected function add_filter( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( is_string( $callback ) && method_exists( $this, $callback ) ) {
			ArrayUtil::push_once( $this->_accessible_private_methods, $callback );
			$callback = array( $this, $callback );
		} elseif ( is_array( $callback ) && count( $callback ) >= 2 && $callback[0] === $this ) {
			$this->mark_method_as_accessible( $callback[1] );
		}
		add_filter( $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Register a WordPress filter.
	 * If the callback refers to a private or protected static method in this class, the method is marked as externally accessible.
	 *
	 * $callback can be a standard callable, or a string representing the name of a method in this class.
	 *
	 * @param string   $hook_name       The name of the action to add the callback to.
	 * @param callable $callback        The callback to be run when the action is called.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action. Default 10.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 */
	protected static function add_static_filter( string $hook_name, $callback, int $priority = 10, int $accepted_args = 1 ): void {
		if ( is_string( $callback ) && method_exists( __CLASS__, $callback ) ) {
			ArrayUtil::push_once( static::$_accessible_static_private_methods, $callback );
			$callback = array( __CLASS__, $callback );
		} elseif ( is_array( $callback ) && count( $callback ) >= 2 && __CLASS__ === $callback[0] ) {
			static::mark_static_method_as_accessible( $callback[1] );
		}
		add_filter( $hook_name, $callback, $priority, $accepted_args );
	}

	/**
	 * Register a private or protected instance method of this class as externally accessible.
	 *
	 * @param string $method_name Method name.
	 * @return void
	 */
	protected function mark_method_as_accessible( string $method_name ): void {
		// Note that a "is_callable" check would be useless here:
		// "is_callable" always returns true if the class implements __call.
		if ( method_exists( $this, $method_name ) ) {
			ArrayUtil::push_once( $this->_accessible_private_methods, $method_name );
		}
	}

	/**
	 * Register a private or protected static method of this class as externally accessible.
	 *
	 * @param string $method_name Method name.
	 * @return void
	 */
	protected static function mark_static_method_as_accessible( string $method_name ): void {
		if ( method_exists( __CLASS__, $method_name ) ) {
			ArrayUtil::push_once( static::$_accessible_static_private_methods, $method_name );
		}
	}


	/**
	 * Undefined/unaccessible instance method call handler.
	 *
	 * @param string $name Called method name.
	 * @param array  $arguments Called method arguments.
	 * @return mixed
	 * @throws \Error The called instance method doesn't exist or is private/protected and not marked as externally accessible.
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->_accessible_private_methods, true ) ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		} elseif ( is_callable( array( 'parent', '__call' ) ) ) {
			return parent::__call( $name, $arguments );
		} elseif ( method_exists( $this, $name ) ) {
			throw new \Error( 'Call to private method ' . get_class( $this ) . '::' . $name );
		} else {
			throw new \Error( 'Call to undefined method ' . get_class( $this ) . '::' . $name );
		}
	}

	/**
	 * Undefined/unaccessible static method call handler.
	 *
	 * @param string $name Called method name.
	 * @param array  $arguments Called method arguments.
	 * @return mixed
	 * @throws \Error The called static method doesn't exist or is private/protected and not marked as externally accessible.
	 */
	public static function __callStatic( $name, $arguments ) {
		if ( in_array( $name, static::$_accessible_static_private_methods, true ) ) {
			return call_user_func_array( array( __CLASS__, $name ), $arguments );
		} elseif ( is_callable( array( 'parent', '__callStatic' ) ) ) {
			return parent::__callStatic( $name, $arguments );
		} elseif ( 'add_action' === $name || 'add_filter' === $name ) {
			$proper_method_name = 'add_static_' . substr( $name, 4 );
			throw new \Error( __CLASS__ . '::' . $name . " can't be called statically, did you mean '$proper_method_name'?" );
		} elseif ( method_exists( __CLASS__, $name ) ) {
			throw new \Error( 'Call to private method ' . __CLASS__ . '::' . $name );
		} else {
			throw new \Error( 'Call to undefined method ' . __CLASS__ . '::' . $name );
		}
	}
}
