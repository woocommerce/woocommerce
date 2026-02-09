<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers\TransformerInterface;
use InvalidArgumentException;
use stdClass;

/**
 * Find an array value by dot notation.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class DotNotation implements TransformerInterface {

	/**
	 * Find given path from the given value.
	 *
	 * @param mixed         $value a value to transform.
	 * @param stdClass|null $arguments required argument 'path'.
	 * @param string|null   $default_value default value.
	 *
	 * @throws InvalidArgumentException Throws when the required 'path' is missing.
	 *
	 * @return mixed
	 */
	public function transform( $value, ?stdclass $arguments = null, $default_value = null ) {
		if ( is_object( $value ) ) {
			// if the value is an object, convert it to an array.
			$value = json_decode( wp_json_encode( $value ), true );
		}

		return $this->get( $value, $arguments->path, $default_value );
	}

	/**
	 * Find the given $path in $array_to_search by dot notation.
	 *
	 * @param array  $array_to_search an array to search in.
	 * @param string $path a path in the given array.
	 * @param null   $default_value default value to return if $path was not found.
	 *
	 * @return mixed|null
	 */
	public function get( $array_to_search, $path, $default_value = null ) {
		if ( ! is_array( $array_to_search ) ) {
			return $default_value;
		}

		if ( isset( $array_to_search[ $path ] ) ) {
			return $array_to_search[ $path ];
		}

		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $array_to_search ) || ! array_key_exists( $segment, $array_to_search ) ) {
				return $default_value;
			}

			$array_to_search = $array_to_search[ $segment ];
		}

		return $array_to_search;
	}

	/**
	 * Validate Transformer arguments.
	 *
	 * @param stdClass|null $arguments arguments to validate.
	 *
	 * @return mixed
	 */
	public function validate( ?stdClass $arguments = null ) {
		if ( ! isset( $arguments->path ) ) {
			return false;
		}

		return true;
	}
}
