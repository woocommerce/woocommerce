<?php
/**
 * AbstractServiceProvider class file.
 *
 * @package Automattic\WooCommerce\Internal\DependencyManagement
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement;

use League\Container\Argument\RawArgument;
use League\Container\Definition\DefinitionInterface;
use League\Container\Definition\Definition;

/**
 * Base class for the service providers used to register classes in the container.
 *
 * See the documentation of the original class this one is based on (https://container.thephpleague.com/3.x/service-providers)
 * for basic usage details. What this class adds is:
 *
 * - The `add_with_auto_arguments` method that allows to register classes without having to specify the constructor arguments.
 * - The `share_with_auto_arguments` method, sibling of the above.
 * - Convenience `add` and `share` methods that are just proxies for the same methods in `$this->getContainer()`.
 */
abstract class AbstractServiceProvider extends \League\Container\ServiceProvider\AbstractServiceProvider {

	/**
	 * Register a class in the container and use reflection to guess the constructor arguments.
	 *
	 * WARNING: this method uses reflection, so please have performance in mind when using it.
	 *
	 * @param string $class_name Class name to register.
	 * @param mixed  $concrete   The concrete to register. Can be a shared instance, a factory callback, or a class name.
	 * @param bool   $shared Whether to register the class as shared (`get` always returns the same instance) or not.
	 *
	 * @return DefinitionInterface The generated container definition.
	 *
	 * @throws ContainerException Error when reflecting the class, or class constructor is not public, or an argument has no valid type hint.
	 */
	protected function add_with_auto_arguments( string $class_name, $concrete = null, bool $shared = false ) : DefinitionInterface {
		$definition = new Definition( $class_name, $concrete );

		$constructor = $this->verify_class_and_concrete( $class_name, $concrete );

		if ( ! is_null( $constructor ) ) {
			$constructor_arguments = $constructor->getParameters();
			foreach ( $constructor_arguments as $argument ) {
				if ( $argument->isDefaultValueAvailable() ) {
					$default_value = $argument->getDefaultValue();
					$definition->addArgument( new RawArgument( $default_value ) );
				} else {
					$argument_class = $argument->getClass();
					if ( is_null( $argument_class ) ) {
						throw new ContainerException( "AbstractServiceProvider::add_with_auto_arguments: constructor argument '{$argument->getName()}' of class '$class_name' doesn't have a type hint or has one that doesn't specify a class." );
					}

					$definition->addArgument( $argument_class->name );
				}
			}
		}

		// Register the definition only after being sure that no exception will be thrown.

		$this->getContainer()->add( $definition->getAlias(), $definition, $shared );

		return $definition;
	}

	/**
	 * Check if a combination of class name and concrete is valid for registration.
	 * Also return the class constructor if the concrete is either a class name or null (then use the supplied class name).
	 *
	 * @param string $class_name The class name to check.
	 * @param mixed  $concrete   The concrete to check.
	 *
	 * @return \ReflectionMethod|null A ReflectionMethod representing the class constructor, if $concrete is null or a class name; null otherwise.
	 * @throws ContainerException Class has a private constructor, or can't reflect class, or the concrete is invalid.
	 */
	private function verify_class_and_concrete( string $class_name, $concrete ) {
		if ( ! isset( $concrete ) || is_string( $concrete ) && class_exists( $concrete ) ) {
			try {
				$class       = $concrete ?? $class_name;
				$reflector   = new \ReflectionClass( $class );
				$constructor = $reflector->getConstructor();
				if ( isset( $constructor ) && ! $constructor->isPublic() ) {
					throw new ContainerException( "AbstractServiceProvider::add_with_auto_arguments: constructor of class '$class' isn't public, instances can't be created." );
				}
				return $constructor;
			} catch ( \ReflectionException $ex ) {
				throw new ContainerException( "AbstractServiceProvider::add_with_auto_arguments: error when reflecting class '$class': {$ex->getMessage()}" );
			}
		} elseif ( ! is_object( $concrete ) && ! is_callable( $concrete ) ) {
			throw new ContainerException( 'AbstractServiceProvider::add_with_auto_arguments: concrete must be a valid class name, function name, object, or callable.' );
		}

		return null;
	}

	/**
	 * Register a class in the container and use reflection to guess the constructor arguments.
	 * The class is registered as shared, so `get` on the container always returns the same instance.
	 *
	 * WARNING: this method uses reflection, so please have performance in mind when using it.
	 *
	 * @param string $class_name Class name to register.
	 * @param mixed  $concrete   The concrete to register. Can be a shared instance, a factory callback, or a class name.
	 *
	 * @return DefinitionInterface The generated container definition.
	 *
	 * @throws ContainerException Error when reflecting the class, or class constructor is not public, or an argument has no valid type hint.
	 */
	protected function share_with_auto_arguments( string $class_name, $concrete = null ) : DefinitionInterface {
		return $this->add_with_auto_arguments( $class_name, $concrete, true );
	}

	/**
	 * Register an entry in the container.
	 *
	 * @param string     $id Entry id (typically a class or interface name).
	 * @param mixed|null $concrete Concrete entity to register under that id, null for automatic creation.
	 * @param bool|null  $shared Whether to register the class as shared (`get` always returns the same instance) or not.
	 *
	 * @return DefinitionInterface The generated container definition.
	 */
	protected function add( string $id, $concrete = null, bool $shared = null ) : DefinitionInterface {
		return $this->getContainer()->add( $id, $concrete, $shared );
	}

	/**
	 * Register a shared entry in the container (`get` always returns the same instance).
	 *
	 * @param string     $id Entry id (typically a class or interface name).
	 * @param mixed|null $concrete Concrete entity to register under that id, null for automatic creation.
	 *
	 * @return DefinitionInterface The generated container definition.
	 */
	protected function share( string $id, $concrete = null ) : DefinitionInterface {
		return $this->add( $id, $concrete, true );
	}
}
