<?php
/**
 * Outer container fixture.
 *
 * @package WooCommerce\ExtensionTools
 */

/**
 * Model WooCommerce's private container property.
 */
class TestBootstrapContainer {

	/**
	 * The inner container.
	 *
	 * @var object
	 */
	private $container;

	/**
	 * Set up a test container.
	 *
	 * @param object $inner The original inner container.
	 */
	public function __construct( $inner ) {
		$this->container = $inner;
	}

	/**
	 * Observe the private property after initialization.
	 *
	 * @return object
	 */
	public function inner() {
		return $this->container;
	}
}
