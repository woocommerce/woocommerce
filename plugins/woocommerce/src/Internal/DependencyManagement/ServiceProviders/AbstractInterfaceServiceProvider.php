<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Vendor\League\Container\Definition\DefinitionInterface;

/**
 * Extends AbstractServiceProvider to register services and automatically tag them based on their implemented interfaces.
 * By using the `add_with_implements_tags` and `share_with_implements_tags` methods, it becomes possible to retrieve
 * all the services that implement a given interface with a single `get` call.
 *
 * @since 8.5.0
 */
abstract class AbstractInterfaceServiceProvider extends AbstractServiceProvider {

	/**
	 * Determine whether this service provides the given alias.
	 *
	 * @param string $alias The alias to check.
	 *
	 * @return bool
	 */
	public function provides( string $alias ): bool {
		$provides = parent::provides( $alias );
		if ( $provides ) {
			return true;
		}

		static $implements = array();
		if ( empty( $implements ) ) {
			foreach ( $this->provides as $class ) {
				$implements_more = class_implements( $class );
				if ( $implements_more ) {
					$implements = array_merge( $implements, $implements_more );
				}
			}

			$implements = array_unique( $implements );
		}

		return array_key_exists( $alias, $implements );
	}

	/**
	 * Register a class in the container and add tags for all the interfaces it implements.
	 *
	 * This also updates the `$this->provides` property with the interfaces provided by the class, and ensures
	 * that the property doesn't contain duplicates.
	 *
	 * @param string     $id       Entry ID (typically a class or interface name).
	 * @param mixed|null $concrete Concrete entity to register under that ID, null for automatic creation.
	 * @param bool|null  $shared   Whether to register the class as shared (`get` always returns the same instance)
	 *                             or not.
	 *
	 * @return DefinitionInterface
	 */
	protected function add_with_implements_tags( string $id, $concrete = null, bool $shared = null ): DefinitionInterface {
		$definition = $this->add( $id, $concrete, $shared );
		foreach ( class_implements( $id ) as $interface ) {
			$definition->addTag( $interface );
		}

		return $definition;
	}

	/**
	 * Register a shared class in the container and add tags for all the interfaces it implements.
	 *
	 * @param string     $id       Entry ID (typically a class or interface name).
	 * @param mixed|null $concrete Concrete entity to register under that ID, null for automatic creation.
	 *
	 * @return DefinitionInterface
	 */
	protected function share_with_implements_tags( string $id, $concrete = null ): DefinitionInterface {
		return $this->add_with_implements_tags( $id, $concrete, true );
	}
}
