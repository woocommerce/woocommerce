<?php
/**
 * ObjectContainer class file.
 *
 * @package Automattic\WooCommerce\Tools\DependencyManagement
 */

namespace Automattic\WooCommerce;

use \Automattic\WooCommerce\Tools\DependencyManagement\ReflectionContainer as WooReflectionContainer;
use League\Container\Definition\DefinitionAggregateInterface;
use League\Container\Inflector\InflectorAggregateInterface;
use League\Container\ServiceProvider\ServiceProviderAggregateInterface;

/**
 * Dependency injection container for WooCommerce.
 *
 * This class inherits from PHP League's `Container` adding the following features:
 *
 * - Registers itself so it can be resolved via itself as a shared instance.
 * - Registers a reflection container as a delegate so autowiring is available "out of the box".
 * - Adds a `defineAsSharedAutowired` method that allows to declare an individual class as shared while
 *   using autowiring, without having to activate "default to shared".
 * - And as a bonus, uses the `Automattic\WooCommerce` namespace instead of the vendor namespace.
 *
 * To register class resolutions for WooCommerce itself, you can use `add` as appropriate in the
 * `WooCommerce::init_container` method. Using service providers for that is however the preferred approach,
 * these should go in the `Src/Tools/DependencyManagement/ServiceProviders` folder and their names be added
 * to the the `WooCommerce::$service_providers` array.
 *
 * To resolve classes from legacy code use `WooCommerce::get_instance_of`. This should be done ONLY to turn obsolete
 * functions and classes into stubs that redirect its functionality to new code in the `src` directory.
 * New code should always be added to the `src` directory, and the container should be used indirectly via
 * constructor injection (or if delayed resolution is required, the container itself should be injected to be used
 * when appropriate).
 *
 * Note: autowiring means the ability to automatically resolve a class name to an instance of that class via
 *       `get(TheClass::class)` or by adding a typed argument to class constructors.
 */
final class Container extends \League\Container\Container {

	/**
	 *  The underlying reflection delegate used for autowiring.
	 *
	 * @var WooReflectionContainer
	 */
	private $reflection_container;

	/**
	 * Class constructor.
	 *
	 * @param DefinitionAggregateInterface|null      $definitions DefinitionAggregateInterface to use.
	 * @param ServiceProviderAggregateInterface|null $providers   ServiceProviderAggregateInterface to use.
	 * @param InflectorAggregateInterface|null       $inflectors  InflectorAggregateInterface to use.
	 */
	public function __construct(
		DefinitionAggregateInterface $definitions = null,
		ServiceProviderAggregateInterface $providers = null,
		InflectorAggregateInterface $inflectors = null
	) {
		parent::__construct( $definitions, $providers, $inflectors );

		// Register ourselves so we become resolvable as a shared instance.
		$this->add( __CLASS__, $this );

		// Register the reflection container to allow autowiring.
		$this->reflection_container = new WooReflectionContainer();
		$this->delegate( $this->reflection_container );
	}

	/**
	 * Defines a class as shared while making it available via autowiring. In other words, the class will be resolved
	 * whenever needed using autowiring, but only one shared instance will be created.
	 *
	 * @param string $class_name The name of the class to define as shared.
	 */
	public function defineAsSharedAutowired( string $class_name ) {
		$this->reflection_container->share( $class_name );
	}
}
