<?php
/**
 * ObjectContainer class file.
 *
 * @package Automattic\WooCommerce\Tools\DependencyManagement
 */

namespace Automattic\WooCommerce\Tools\DependencyManagement;

use \League\Container\Container;
use \Automattic\WooCommerce\Tools\DependencyManagement\ReflectionContainer as WooReflectionContainer;

/**
 * Dependency injection container for WooCommerce.
 *
 * This class uses PHP League's `Container` under the hood. A separate container class is used to
 * insulate the codebase from the exact DI engine used, and to encapsulate all the class and service provider registrations.
 *
 * Usage:
 *
 * - To register new class resolutions:
 *   - Do it in the `register_classes` method, using the `container` and `reflection_container` class properties.
 *     In particular, use `reflection_container->share` to register autowiring singletons.
 *     No extra action is needed to use non-singleton autowiring.
 *   - Or alternatively, create a new service provider inside the `ServiceProviders` folder and it will be
 *     automatically registered. Service providers are recommended when instantiating the class is a complex
 *     or slow process.
 *
 * - To resolve classes to instances:
 *   - Ideally you just add your dependency as a typed argument in the class constructor, and it gets resolved
 *     automatically (via autowiring) when the class is instantiated via the container. This is the preferred approach.
 *   - If the above is not possible, you can add an argument of type `ObjectContainer` to the class constructor,
 *     and then use the `get` method of the supplied instance whenever needed.
 *   - For code outside classes (inside standalone functions) or in static class methods, use
 *    `ObjectContainer::get_instance_of`.
 *
 * Note: autowiring means the ability to automatically resolve a class name to an instance of that class via
 *       `get(TheClass::class)` or by adding a typed argument to class constructors.
 */
final class ObjectContainer {

	/**
	 *  The singleton instance of ObjectContainer.
	 *
	 * @var ObjectContainer
	 */
	private static $instance = null;

	/**
	 *  The underlying dependency injection engine used.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 *  The underlying reflection delegate used for autowiring.
	 *
	 * @var ReflectionContainer
	 */
	private $reflection_container;

	/**
	 *  Get the singleton instance of ObjectContainer.
	 *
	 * @return ObjectContainer
	 */
	public static function instance() {
		return self::$instance;
	}

	/**
	 * Initialize the dependency injection engine and do all the necessary registrations.
	 */
	public static function init() {
		global $wp_actions;

		if ( ! function_exists( 'did_action' ) || did_action( 'plugins_loaded' ) ) {
			self::_init();
		} else {
			// TODO: This is a hack to overcome the inability to use the autoloader before plugins have been loaded, remove after https://github.com/Automattic/jetpack/pull/15106 is merged.
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$wp_actions['plugins_loaded'] = 1;
			self::_init();
			unset( $wp_actions['plugins_loaded'] );
		}
	}

	/**
	 * Initialize the dependency injection engine and do all the necessary registrations.
	 */
	public static function _init() {
		// Instantiate the underlying container and create the singleton instance of ourselves.
		$container      = new Container();
		self::$instance = new self( $container );

		// Register our singleton instance so we're able to resolve ourselves.
		$container->add( __CLASS__, self::$instance );

		// Register the reflection container to allow autowiring.
		self::$instance->reflection_container = new WooReflectionContainer();
		$container->delegate( self::$instance->reflection_container );

		// Perform any required manual class and service provider registration.
		self::$instance->register_classes();
		self::$instance->register_service_providers();
	}

	/**
	 * Class constructor.
	 *
	 * @param Container $container The instance of League\Container\Container to use for dependency injection.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register class resolutions for which default autowiring is not appropriate/enough.
	 */
	private function register_classes() {
		$singletons = array(
			\CustomerProvider::class,
			\WC_Checkout::class,
			\WC_Emails::class,
			\WC_Payment_Gateways::class,
			\WC_Shipping::class,
			\WooCommerce::class,
		);

		foreach ( $singletons as $class_name ) {
			$this->reflection_container->share( $class_name );
		}
	}

	/**
	 * Register all the service providers inside the ServiceProviders directory.
	 */
	private function register_service_providers() {
		$service_provider_files = array_diff( scandir( __DIR__ . '/ServiceProviders' ), array( '..', '.' ) );

		foreach ( $service_provider_files as $file ) {
			$filename = pathinfo( $file )['filename'];
			$this->container->addServiceProvider( __NAMESPACE__ . "\\ServiceProviders\\$filename" );
		}
	}

	/**
	 * Resolve an instance of a class.
	 *
	 * @param string $class_name Class name to get an instance of.
	 *
	 * @return object The resolved object.
	 */
	public function get( string $class_name ) {
		return $this->container->get( $class_name );
	}

	/**
	 * Resolve an instance of a class.
	 *
	 * @param string $class_name Class name to get an instance of.
	 *
	 * @return object The resolved object.
	 */
	public static function get_instance_of( string $class_name ) {
		return self::instance()->get( $class_name );
	}
}
