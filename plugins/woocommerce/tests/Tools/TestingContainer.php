<?php
/**
 * TestingContainer class file.
 *
 * @package WooCommerce\Testing\Tools
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Testing\Tools;

use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\RuntimeContainer;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;

/**
 * Dependency injection container to be used in unit tests.
 */
class TestingContainer extends RuntimeContainer {

	/**
	 * Class replacements as a dictionary of class name => instance.
	 *
	 * @var array
	 */
	private $replacements = array();

	/**
	 * Initializes the instance.
	 *
	 * @param RuntimeContainer $base_container Base container to use for initialization.
	 */
	public function __construct( RuntimeContainer $base_container ) {
		$initial_resolved_cache                       = $base_container->initial_resolved_cache;
		$initial_resolved_cache[ LegacyProxy::class ] = new MockableLegacyProxy();
		parent::__construct( $initial_resolved_cache );
	}

	/**
	 * Register a class replacement, so that whenever the class name is requested with 'get', the replacement will be returned instead.
	 *
	 * Note that if the instance of the specified class is already cached (the class was requested already)
	 * this will have no effect unless 'reset_all_resolved' is invoked.
	 *
	 * @param string $class_name The class name to replace.
	 * @param object $concrete The object that will be replaced when the class is requested.
	 */
	public function replace( string $class_name, object $concrete ): void {
		$this->replacements[ $class_name ] = $concrete;
	}

	/**
	 * Replacement to the core 'get' method to take in account replacements registered with 'replace'.
	 *
	 * @param string $class_name The class name.
	 * @param array  $resolve_chain Classes already resolved in this resolution chain. Passed between recursive calls to the method in order to detect a recursive resolution condition.
	 * @return object The resolved object.
	 * @throws ContainerException Error when resolving the class to an object instance.
	 */
	protected function get_core( string $class_name, array &$resolve_chain ) {
		if ( isset( $this->replacements[ $class_name ] ) ) {
			return $this->replacements[ $class_name ];
		}

		return parent::get_core( $class_name, $resolve_chain );
	}

	/**
	 * Reset a given class replacement, so that 'get' will return an instance of the original class again.
	 *
	 * @param string $class_name The class name whose replacement is to be reset.
	 */
	public function reset_replacement( string $class_name ) {
		unset( $this->replacements[ $class_name ] );
	}

	/**
	 * Reset all the replacements registered with 'replace', so that 'get' will return instances of the original classes again.
	 */
	public function reset_all_replacements() {
		$this->replacements = array();
	}

	/**
	 * Reset all the cached resolutions, so any further calls to 'get' will generate the appropriate class instances again.
	 */
	public function reset_all_resolved() {
		$this->resolved_cache = $this->initial_resolved_cache;
	}
}
