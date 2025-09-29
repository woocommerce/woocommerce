<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;

/**
 * Class Container is a simple dependency injection container.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */
class Container {
	/**
	 * A list of registered services
	 *
	 * @var array<string, callable> $services
	 */
	protected array $services = array();

	/**
	 * A list of created instances
	 *
	 * @var array<string, object> $instances
	 */
	protected array $instances = array();

	/**
	 * The method for registering a new service.
	 *
	 * @param string   $name     The name of the service.
	 * @param callable $callback The callable that will be used to create the service.
	 * @return void
	 * @phpstan-template T of object
	 * @phpstan-param class-string<T> $name
	 */
	public function set( string $name, callable $callback ): void {
		$this->services[ $name ] = $callback;
	}

	/**
	 * Method for getting a registered service.
	 *
	 * @param string $name The name of the service.
	 * @return object The service instance.
	 * @throws \Exception If the service is not found.
	 * @phpstan-template T of object
	 * @phpstan-param class-string<T> $name
	 * @phpstan-return T
	 */
	public function get( string $name ): object {
		// Check if the service is already instantiated.
		if ( isset( $this->instances[ $name ] ) ) {
			/**
			 * Instance.
			 *
			 * @var T $instance Instance of requested service.
			 */
			$instance = $this->instances[ $name ];
			return $instance;
		}

		// Check if the service is registered.
		if ( ! isset( $this->services[ $name ] ) ) {
			throw new \Exception( esc_html( "Service not found: $name" ) );
		}

		/**
		 * Instance.
		 *
		 * @var T $instance Instance of requested service.
		 */
		$instance                 = $this->services[ $name ]( $this );
		$this->instances[ $name ] = $instance;

		return $instance;
	}
}
