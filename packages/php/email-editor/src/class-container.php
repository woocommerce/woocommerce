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
	 * @var array $services
	 */
	protected array $services = array();

	/**
	 * A list of created instances
	 *
	 * @var array
	 */
	protected array $instances = array();

	/**
	 * The method for registering a new service
	 *
	 * @param string   $name    The name of the service.
	 * @param callable $callback The callable that will be used to create the service.
	 * @return void
	 */
	public function set( string $name, callable $callback ): void {
		$this->services[ $name ] = $callback;
	}

	/**
	 * Method for getting a registered service
	 *
	 * @template T
	 * @param class-string<T> $name The name of the service.
	 * @return T
	 * @throws \Exception If the service is not found.
	 */
	public function get( $name ) {
		// Check if the service is already instantiated.
		if ( isset( $this->instances[ $name ] ) ) {
			return $this->instances[ $name ];
		}

		// Check if the service is registered.
		if ( ! isset( $this->services[ $name ] ) ) {
			throw new \Exception( esc_html( "Service not found: $name" ) );
		}

		$this->instances[ $name ] = $this->services[ $name ]( $this );

		return $this->instances[ $name ];
	}
}
