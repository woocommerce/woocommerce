<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement;

use Automattic\WooCommerce\Blocks\Package as BlocksPackage;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\Utilities\StringUtil;

/**
 * Dependency injection container used at runtime.
 *
 * This is a simple container that doesn't implement explicit class registration.
 * Instead, all the classes in the Automattic\WooCommerce namespace can be resolved
 * and are considered as implicitly registered as single-instance classes
 * (so each class will be instantiated only once and the instance will be cached).
 */
class RuntimeContainer {
	/**
	 * The root namespace of all WooCommerce classes in the `src` directory.
	 *
	 * @var string
	 */
	const WOOCOMMERCE_NAMESPACE = 'Automattic\\WooCommerce\\';

	/**
	 * Cache of classes already resolved.
	 *
	 * @var array
	 */
	protected array $resolved_cache;

	/**
	 * A copy of the initial resolved classes cache passed to the constructor.
	 *
	 * @var array
	 */
	protected array $initial_resolved_cache;

	/**
	 * Initializes a new instance of the class.
	 *
	 * @param array $initial_resolved_cache Dictionary of class name => instance, to be used as the starting point for the resolved classes cache.
	 */
	public function __construct( array $initial_resolved_cache ) {
		$this->initial_resolved_cache = $initial_resolved_cache;
		$this->resolved_cache         = $initial_resolved_cache;
	}

	/**
	 * Get an instance of a class.
	 *
	 * ContainerException will be thrown in these cases:
	 *
	 * - $class_name is outside the WooCommerce root namespace (and wasn't included in the initial resolve cache).
	 * - The class referred by $class_name doesn't exist.
	 * - Recursive resolution condition found.
	 * - Reflection exception thrown when instantiating or initializing the class.
	 *
	 * A "recursive resolution condition" happens when class A depends on class B and at the same time class B depends on class A, directly or indirectly;
	 * without proper handling this would lead to an infinite loop.
	 *
	 * Note that this method throwing ContainerException implies that code fixes are needed, it's not an error condition that's recoverable at runtime.
	 *
	 * @param string $class_name The class name.
	 *
	 * @return object The instance of the requested class.
	 * @throws ContainerException Error when resolving the class to an object instance.
	 * @throws \Exception Exception thrown in the constructor or in the 'init' method of one of the resolved classes.
	 */
	public function get( string $class_name ) {
		$class_name    = trim( $class_name, '\\' );
		$resolve_chain = array();
		return $this->get_core( $class_name, $resolve_chain );
	}

	/**
	 * Core function to get an instance of a class.
	 *
	 * @param string $class_name The class name.
	 * @param array  $resolve_chain Classes already resolved in this resolution chain. Passed between recursive calls to the method in order to detect a recursive resolution condition.
	 * @return object The resolved object.
	 * @throws ContainerException Error when resolving the class to an object instance.
	 */
	protected function get_core( string $class_name, array &$resolve_chain ) {
		if ( isset( $this->resolved_cache[ $class_name ] ) ) {
			return $this->resolved_cache[ $class_name ];
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		if ( in_array( $class_name, $resolve_chain, true ) ) {
			throw new ContainerException( "Recursive resolution of class '$class_name'. Resolution chain: " . implode( ', ', $resolve_chain ) );
		}

		if ( ! $this->is_class_allowed( $class_name ) ) {
			throw new ContainerException( "Attempt to get an instance of class '$class_name', which is not in the " . self::WOOCOMMERCE_NAMESPACE . ' namespace. Did you forget to add a namespace import?' );
		}

		if ( ! class_exists( $class_name ) ) {
			throw new ContainerException( "Attempt to get an instance of class '$class_name', which doesn't exist." );
		}

		// Account for the containers used by the Store API and Blocks.
		if ( StringUtil::starts_with( $class_name, 'Automattic\WooCommerce\StoreApi\\' ) ) {
			return StoreApi::container()->get( $class_name );
		}
		if ( StringUtil::starts_with( $class_name, 'Automattic\WooCommerce\Blocks\\' ) ) {
			return BlocksPackage::container()->get( $class_name );
		}

		$resolve_chain[] = $class_name;

		try {
			$instance = $this->instantiate_class_using_reflection( $class_name, $resolve_chain );
		} catch ( \ReflectionException $e ) {
			throw new ContainerException( "Reflection error when resolving '$class_name': (" . get_class( $e ) . ") {$e->getMessage()}", 0, $e );
		}

		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		$this->resolved_cache[ $class_name ] = $instance;

		return $instance;
	}

	/**
	 * Get an instance of a class using reflection.
	 * This method recursively calls 'get_core' (which in turn calls this method) for each of the arguments
	 * in the 'init' method of the resolved class (if the method is public and non-static).
	 *
	 * @param string $class_name The name of the class to resolve.
	 * @param array  $resolve_chain Classes already resolved in this resolution chain. Passed between recursive calls to the method in order to detect a recursive resolution condition.
	 * @return object The resolved object.
	 *
	 * @throws ContainerException The 'init' method has invalid arguments.
	 * @throws \ReflectionException Something went wrong when using reflection to get information about the class to resolve.
	 */
	private function instantiate_class_using_reflection( string $class_name, array &$resolve_chain ): object {
		$ref_class = new \ReflectionClass( $class_name );
		$instance  = $ref_class->newInstance();
		if ( ! $ref_class->hasMethod( 'init' ) ) {
			return $instance;
		}

		$init_method = $ref_class->getMethod( 'init' );
		if ( ! $init_method->isPublic() || $init_method->isStatic() ) {
			return $instance;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		$init_args          = $init_method->getParameters();
		$init_arg_instances = array_map(
			function ( \ReflectionParameter $arg ) use ( $class_name, &$resolve_chain ) {
				$arg_type = $arg->getType();
				if ( ! ( $arg_type instanceof \ReflectionNamedType ) ) {
					throw new ContainerException( "Error resolving '$class_name': argument '\${$arg->getName()}' doesn't have a type declaration." );
				}
				if ( $arg_type->isBuiltin() ) {
					throw new ContainerException( "Error resolving '$class_name': argument '\${$arg->getName()}' is not of a class type." );
				}
				if ( $arg->isPassedByReference() ) {
					throw new ContainerException( "Error resolving '$class_name': argument '\${$arg->getName()}' is passed by reference." );
				}
				return $this->get_core( $arg_type->getName(), $resolve_chain );
			},
			$init_args
		);

		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		$init_method->invoke( $instance, ...$init_arg_instances );

		return $instance;
	}

	/**
	 * Tells if the 'get' method can be used to resolve a given class.
	 *
	 * @param string $class_name The class name.
	 * @return bool True if the class with the supplied name can be resolved with 'get'.
	 */
	public function has( string $class_name ): bool {
		$class_name = trim( $class_name, '\\' );
		return $this->is_class_allowed( $class_name ) || isset( $this->resolved_cache[ $class_name ] );
	}

	/**
	 * Checks to see whether a class is allowed to be registered.
	 *
	 * @param string $class_name The class to check.
	 *
	 * @return bool True if the class is allowed to be registered, false otherwise.
	 */
	protected function is_class_allowed( string $class_name ): bool {
		return StringUtil::starts_with( $class_name, self::WOOCOMMERCE_NAMESPACE, false );
	}

	/**
	 * Tells if this class should be used as the core WooCommerce dependency injection container (or if the old ExtendedContainer should be used instead).
	 *
	 * By default, this returns true, to have it return false you can:
	 *
	 * 1. Define the WOOCOMMERCE_USE_OLD_DI_CONTAINER constant with a value of true; or
	 * 2. Hook on the 'woocommerce_use_old_di_container' filter and have it return false (it receives the value of WOOCOMMERCE_USE_OLD_DI_CONTAINER, or false if the constant doesn't exist).
	 *
	 * @return bool True if this class should be used as the core WooCommerce dependency injection container, false if ExtendedContainer should be used instead.
	 */
	public static function should_use(): bool {
		$should_use = ! defined( 'WOOCOMMERCE_USE_OLD_DI_CONTAINER' ) || true !== WOOCOMMERCE_USE_OLD_DI_CONTAINER;

		/**
		 * Hook to decide if the old ExtendedContainer class (instead of RuntimeContainer) should be used as the underlying WooCommerce dependency injection container.
		 *
		 * NOTE: This hook will be removed in WooCommerce 9.5.
		 *
		 * @param bool $should_use Value of the WOOCOMMERCE_USE_OLD_DI_CONTAINER constant, false if the constant doesn't exist.
		 *
		 * @since 9.5.0
		 */
		return apply_filters( 'woocommerce_use_old_di_container', $should_use );
	}
}
