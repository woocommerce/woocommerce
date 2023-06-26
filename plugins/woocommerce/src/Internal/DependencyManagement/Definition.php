<?php
/**
 * An extension to the Definition class to prevent constructor injection from being possible.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement;

use Automattic\WooCommerce\Vendor\League\Container\Definition\Definition as BaseDefinition;

/**
 * An extension of the definition class that replaces constructor injection with method injection.
 */
class Definition extends BaseDefinition {

	/**
	 * The standard method that we use for dependency injection.
	 */
	public const INJECTION_METHOD = 'init';

	/**
	 * Resolve a class using method injection instead of constructor injection.
	 *
	 * @param string $concrete The concrete to instantiate.
	 *
	 * @return object
	 */
	protected function resolveClass( string $concrete ) {
		$instance = new $concrete();
		$this->invokeInit( $instance );
		return $instance;
	}

	/**
	 * Invoke methods on resolved instance, including 'init'.
	 *
	 * @param object $instance The concrete to invoke methods on.
	 *
	 * @return object
	 */
	protected function invokeMethods( $instance ) {
		$this->invokeInit( $instance );
		parent::invokeMethods( $instance );
		return $instance;
	}

	/**
	 * Invoke the 'init' method on a resolved object.
	 *
	 * Constructor injection causes backwards compatibility problems
	 * so we will rely on method injection via an internal method.
	 *
	 * @param object $instance The resolved object.
	 * @return void
	 */
	private function invokeInit( $instance ) {
		$resolved = $this->resolveArguments( $this->arguments );

		if ( method_exists( $instance, static::INJECTION_METHOD ) ) {
			call_user_func_array( array( $instance, static::INJECTION_METHOD ), $resolved );
		}
	}

	/**
	 * Forget the cached resolved object, so the next time it's requested
	 * it will be resolved again.
	 */
	public function forgetResolved() {
		$this->resolved = null;
	}
}
