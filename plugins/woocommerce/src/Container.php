<?php
/**
 * Container class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\RuntimeContainer;

/**
 * PSR11 compliant dependency injection container for WooCommerce.
 *
 * Classes in the `src` directory should specify dependencies from that directory via an 'init' method having arguments
 * with type hints. If an instance of the container itself is needed, the type hint to use is \Psr\Container\ContainerInterface.
 *
 * Classes in the `src` directory should interact with anything outside (especially code in the `includes` directory
 * and WordPress functions) by using the classes in the `Proxies` directory. The exception is idempotent
 * functions (e.g. `wp_parse_url`), those can be used directly.
 *
 * Classes in the `includes` directory should use the `wc_get_container` function to get the instance of the container when
 * they need to get an instance of a class from the `src` directory.
 *
 * Internally, an instance of RuntimeContainer will be used for the actual class resolution. This class uses reflection
 * to instantiate classes and figure out dependencies, so there's no need for explicit class registration.
 * When running the unit tests suite this will be replaced with an instance of TestingContainer,
 * which provides additional functionality.
 */
final class Container {
	/**
	 * The underlying container.
	 *
	 * @var RuntimeContainer
	 */
	private $container;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// When the League container was in use we allowed to retrieve the container itself
		// by using 'Psr\Container\ContainerInterface' as the class identifier,
		// we continue allowing that for compatibility.
		$this->container = new RuntimeContainer(
			array(
				__CLASS__                          => $this,
				'Psr\Container\ContainerInterface' => $this,
			)
		);
	}

	/**
	 * Returns an instance of the specified class.
	 * See the comment about ContainerException in RuntimeContainer::get.
	 *
	 * @template T of object
	 * @param string $id Class name.
	 * @phpstan-param class-string<T> $id
	 *
	 * @return T Object instance.
	 *
	 * @throws ContainerException Error when resolving the class to an object instance, or class not found.
	 * @throws \Exception Exception thrown in the constructor or in the 'init' method of one of the resolved classes.
	 */
	public function get( string $id ) {
		return $this->container->get( $id );
	}

	/**
	 * Returns true if the container can return an instance of the given class or false otherwise.
	 * See the comment in RuntimeContainer::has.
	 *
	 * @param class-string $id Class name.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return $this->container->has( $id );
	}
}
