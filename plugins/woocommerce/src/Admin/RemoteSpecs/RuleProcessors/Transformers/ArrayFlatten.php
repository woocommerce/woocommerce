<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\TransformerInterface;
use stdClass;

/**
 * Flatten nested array.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class ArrayFlatten implements TransformerInterface {
	/**
	 * Search a given value in the array.
	 *
	 * @param mixed         $value a value to transform.
	 * @param stdClass|null $arguments arguments.
	 * @param string|null   $default_value default value.
	 *
	 * @return mixed|null
	 */
	public function transform( $value, stdClass $arguments = null, $default_value = array() ) {
		if ( ! is_array( $value ) ) {
			return $default_value;
		}

		$return = array();
		array_walk_recursive(
			$value,
			function ( $item ) use ( &$return ) {
				$return[] = $item;
			}
		);

		return $return;
	}

	/**
	 * Validate Transformer arguments.
	 *
	 * @param stdClass|null $arguments arguments to validate.
	 *
	 * @return mixed
	 */
	public function validate( stdClass $arguments = null ) {
		return true;
	}
}
