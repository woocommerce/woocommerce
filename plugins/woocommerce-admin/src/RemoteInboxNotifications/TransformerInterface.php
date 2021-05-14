<?php

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

use stdClass;

/**
 * An interface to define a transformer.
 *
 * Interface TransformerInterface
 *
 * @package Automattic\WooCommerce\Admin\RemoteInboxNotifications
 */
interface TransformerInterface {
	/**
	 * Transform given value to a different value.
	 *
	 * @param mixed         $value a value to transform.
	 * @param stdClass|null $arguments arguments.
	 *
	 * @return mixed|null
	 */
	public function transform( $value, stdClass $arguments = null);

	/**
	 * Validate Transformer arguments.
	 *
	 * @param stdClass|null $arguments arguments to validate.
	 *
	 * @return mixed
	 */
	public function validate( stdClass $arguments = null );
}
