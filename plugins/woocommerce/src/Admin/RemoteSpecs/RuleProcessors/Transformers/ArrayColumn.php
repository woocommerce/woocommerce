<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\TransformerInterface;
use InvalidArgumentException;
use stdClass;

/**
 * Search array value by one of its key.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class ArrayColumn implements TransformerInterface {
	/**
	 * Search array value by one of its key.
	 *
	 * @param mixed         $value a value to transform.
	 * @param stdClass|null $arguments required arguments 'key'.
	 * @param string|null   $default_value default value.
	 *
	 * @throws InvalidArgumentException Throws when the required argument 'key' is missing.
	 *
	 * @return mixed
	 */
	public function transform( $value, ?stdClass $arguments = null, $default_value = array() ) {
		if ( ! is_array( $value ) ) {
			return $default_value;
		}

		return array_column( $value, $arguments->key );
	}

	/**
	 * Validate Transformer arguments.
	 *
	 * @param stdClass|null $arguments arguments to validate.
	 *
	 * @return mixed
	 */
	public function validate( ?stdClass $arguments = null ) {
		if ( ! isset( $arguments->key ) ) {
			return false;
		}

		if (
			null !== $arguments->key &&
			! is_string( $arguments->key ) &&
			! is_int( $arguments->key )
		) {
			return false;
		}

		return true;
	}
}
