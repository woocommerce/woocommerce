<?php
/**
 * WooCommerce Validator Registration
 */

namespace Automattic\WooCommerce\Admin\SchemaBuilder\Validation;

/**
 * Validator registry.
 */
class ValidatorRegistry {

	/**
	 * Singleton instance.
	 *
	 * @var ValidatorRegistry
	 */
	private static $instance = null;

	/**
	 * Registered validator callbacks.
	 *
	 * @var array
	 */
	private $validator_callbacks = array();

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance(): ValidatorRegistry {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a validator callback.
	 *
	 * @param string         $name Validator name.
	 * @param callable|array $callback Callback function.
	 */
	public function register( $name, $callback ) {
		if ( ! is_callable( $callback ) ) {
			throw new InvalidCallbackException();
		}
		$this->validator_callbacks[ $name ] = $callback;
	}

	/**
	 * Get a validator callback.
	 *
	 * @param string $name Validator name.
	 * @return callable|array
	 */
	public function get( $name ) {
		if ( ! isset( $this->validator_callbacks[ $name ] ) ) {
			throw new ValidatorNotFoundException();
		}
		return $this->validator_callbacks[ $name ];
	}

}
