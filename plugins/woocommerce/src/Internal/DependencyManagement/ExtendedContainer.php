<?php
/**
 * ExtendedContainer class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement;

use Automattic\WooCommerce\Container;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Utilities\StringUtil;
use Automattic\WooCommerce\Vendor\League\Container\Container as BaseContainer;
use Automattic\WooCommerce\Vendor\League\Container\Definition\DefinitionInterface;

/**
 * This class extends the original League's Container object by adding some functionality
 * that we need for WooCommerce.
 */
class ExtendedContainer extends BaseContainer {

	/**
	 * The root namespace of all WooCommerce classes in the `src` directory.
	 *
	 * @var string
	 */
	private $woocommerce_namespace = 'Automattic\\WooCommerce\\';

	/**
	 * Holds the original registrations so that 'reset_replacement' can work, keys are class names and values are the original concretes.
	 *
	 * @var array
	 */
	private $original_concretes = array();

	/**
	 * Whitelist of classes that we can register using the container
	 * despite not belonging to the WooCommerce root namespace.
	 *
	 * In general we allow only the registration of classes in the
	 * WooCommerce root namespace to prevent registering 3rd party code
	 * (which doesn't really belong to this container) or old classes
	 * (which may be eventually deprecated, also the LegacyProxy
	 * should be used for those).
	 *
	 * @var string[]
	 */
	private $registration_whitelist = array(
		Container::class,
	);

	/**
	 * A list of tags that have already been fully resolved, see 'get' for details.
	 *
	 * @var array
	 */
	private array $known_tags = array();

	/**
	 * Register a class in the container.
	 *
	 * @param string    $class_name Class name.
	 * @param mixed     $concrete How to resolve the class with `get`: a factory callback, a concrete instance, another class name, or null to just create an instance of the class.
	 * @param bool|null $shared Whether the resolution should be performed only once and cached.
	 *
	 * @return DefinitionInterface The generated definition for the container.
	 * @throws ContainerException Invalid parameters.
	 */
	public function add( string $class_name, $concrete = null, bool $shared = null ) : DefinitionInterface {
		if ( ! $this->is_class_allowed( $class_name ) ) {
			throw new ContainerException( "You cannot add '$class_name', only classes in the {$this->woocommerce_namespace} namespace are allowed." );
		}

		$concrete_class = $this->get_class_from_concrete( $concrete );
		if ( isset( $concrete_class ) && ! $this->is_class_allowed( $concrete_class ) ) {
			throw new ContainerException( "You cannot add concrete '$concrete_class', only classes in the {$this->woocommerce_namespace} namespace are allowed." );
		}

		// We want to use a definition class that does not support constructor injection to avoid accidental usage.
		if ( ! $concrete instanceof DefinitionInterface ) {
			$concrete = new Definition( $class_name, $concrete );
		}

		return parent::add( $class_name, $concrete, $shared );
	}

	/**
	 * Replace an existing registration with a different concrete. See also 'reset_replacement' and 'reset_all_replacements'.
	 *
	 * @param string $class_name The class name whose definition will be replaced.
	 * @param mixed  $concrete The new concrete (same as "add").
	 *
	 * @return DefinitionInterface The modified definition.
	 * @throws ContainerException Invalid parameters.
	 */
	public function replace( string $class_name, $concrete ) : DefinitionInterface {
		if ( ! $this->has( $class_name ) ) {
			throw new ContainerException( "The container doesn't have '$class_name' registered, please use 'add' instead of 'replace'." );
		}

		$concrete_class = $this->get_class_from_concrete( $concrete );
		if ( isset( $concrete_class ) && ! $this->is_class_allowed( $concrete_class ) && ! $this->is_anonymous_class( $concrete_class ) ) {
			throw new ContainerException( "You cannot use concrete '$concrete_class', only classes in the {$this->woocommerce_namespace} namespace are allowed." );
		}

		if ( ! array_key_exists( $class_name, $this->original_concretes ) ) {
			// LegacyProxy is a special case: we replace it with MockableLegacyProxy at unit testing bootstrap time.
			$original_concrete                       = LegacyProxy::class === $class_name ? MockableLegacyProxy::class : $this->extend( $class_name )->getConcrete( $concrete );
			$this->original_concretes[ $class_name ] = $original_concrete;
		}

		return $this->extend( $class_name )->setConcrete( $concrete );
	}

	/**
	 * Reset a replaced registration back to its original concrete.
	 *
	 * @param string $class_name The class name whose definition had been replaced.
	 * @return bool True if the registration has been reset, false if no replacement had been made for the specified class name.
	 */
	public function reset_replacement( string $class_name ) : bool {
		if ( ! array_key_exists( $class_name, $this->original_concretes ) ) {
			return false;
		}

		$this->extend( $class_name )->setConcrete( $this->original_concretes[ $class_name ] );
		unset( $this->original_concretes[ $class_name ] );

		return true;
	}

	/**
	 * Reset all the replaced registrations back to their original concretes.
	 */
	public function reset_all_replacements() {
		foreach ( $this->original_concretes as $class_name => $concrete ) {
			$this->extend( $class_name )->setConcrete( $concrete );
		}

		$this->original_concretes = array();
	}

	/**
	 * Reset all the cached resolutions, so any further "get" for shared definitions will generate the instance again.
	 */
	public function reset_all_resolved() {
		foreach ( $this->definitions->getIterator() as $definition ) {
			$definition->forgetResolved();
		}
	}

	/**
	 * Get an instance of a registered class.
	 *
	 * @param string $id The class name.
	 * @param bool   $new True to generate a new instance even if the class was registered as shared.
	 *
	 * @return object An instance of the requested class.
	 * @throws ContainerException Attempt to get an instance of a non-namespaced class.
	 */
	public function get( $id, bool $new = false ) {
		if ( false === strpos( $id, '\\' ) ) {
			throw new ContainerException( "Attempt to get an instance of the non-namespaced class '$id' from the container, did you forget to add a namespace import?" );
		}

		// This is a workaround for an issue that arises when using service providers inheriting from AbstractInterfaceServiceProvider:
		// if one of these providers registers classes both by name and by tag, and one of its registered classes is requested
		// with 'get' by name before a list of classes is requested by tag, then that service provider gets locked as
		// the only one providing that tag, and the others get ignored. This is due to the fact that container definitions
		// are created "on the fly" as needed and the base 'get' method won't try to register additional providers
		// if the requested tag is already provided by at least one of the already existing definitions.
		if ( $this->definitions->hasTag( $id ) && ! in_array( $id, $this->known_tags, true ) && $this->providers->provides( $id ) ) {
			$this->providers->register( $id );
			$this->known_tags[] = $id;
		}

		return parent::get( $id, $new );
	}

	/**
	 * Gets the class from the concrete regardless of type.
	 *
	 * @param mixed $concrete The concrete that we want the class from..
	 *
	 * @return string|null The class from the concrete if one is available, null otherwise.
	 */
	protected function get_class_from_concrete( $concrete ) {
		if ( is_object( $concrete ) && ! is_callable( $concrete ) ) {
			if ( $concrete instanceof DefinitionInterface ) {
				return $this->get_class_from_concrete( $concrete->getConcrete() );
			}

			return get_class( $concrete );
		}

		if ( is_string( $concrete ) && class_exists( $concrete ) ) {
			return $concrete;
		}

		return null;
	}

	/**
	 * Checks to see whether or not a class is allowed to be registered.
	 *
	 * @param string $class_name The class to check.
	 *
	 * @return bool True if the class is allowed to be registered, false otherwise.
	 */
	protected function is_class_allowed( string $class_name ): bool {
		return StringUtil::starts_with( $class_name, $this->woocommerce_namespace, false ) || in_array( $class_name, $this->registration_whitelist, true );
	}

	/**
	 * Check if a class name corresponds to an anonymous class.
	 *
	 * @param string $class_name The class name to check.
	 * @return bool True if the name corresponds to an anonymous class.
	 */
	protected function is_anonymous_class( string $class_name ): bool {
		return StringUtil::starts_with( $class_name, 'class@anonymous' );
	}
}
