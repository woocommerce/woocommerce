<?php
/**
 * WooCommerce Validation Registration
 */

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Validation;

/**
 * Validation registry.
 */
class ValidationRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var BlockRegistry
	 */
	private static $instance = null;

	/**
	 * Registered validation callbacks.
	 *
	 * @var array
	 */
	private $validation_callbacks = array();

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance(): ValidationRegistry {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a validation callback.
	 *
	 * @param string         $name Validation name.
	 * @param callable|array $callback Callback function.
	 */
	public function register( $name, $callback ) {
		if ( ! is_callable( $callback ) ) {
			throw new InvalidCallbackException();
		}
		$this->validation_callbacks[ $name ] = $callback;
	}

	/**
	 * Get a validation callback.
	 *
	 * @param string $name Validation name.
	 * @return callable|array
	 */
	public function get( $name ) {
		if ( ! isset( $this->validation_callbacks[ $name ] ) ) {
			throw new ValidationNotFoundException();
		}
		return $this->validation_callbacks[ $name ];
	}

}
