<?php

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\TransformerInterface;
use stdClass;

/**
 * Count elements in Array or Countable object.
 *
 * @package Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers
 */
class Count implements TransformerInterface {
	/**
	 *  Count elements in Array or Countable object.
	 *
	 * @param array|Countable $value an array to count.
	 * @param stdClass|null   $arguments arguments.
	 * @param string|null     $default default value.
	 *
	 * @return number
	 */
	public function transform( $value, stdClass $arguments = null, $default = null ) {
		if ( ! is_array( $value ) && ! $value instanceof \Countable ) {
			return $default;
		}

		return count( $value );
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
