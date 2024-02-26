<?php

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\TransformerInterface;
use stdClass;

/**
 * Prepare site URL for comparison.
 *
 * @package Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers
 */
class PrepareUrl implements TransformerInterface {
	/**
	 * Prepares the site URL by removing the protocol and trailing slash.
	 *
	 * @param string        $value a value to transform.
	 * @param stdClass|null $arguments arguments.
	 * @param string|null   $default default value.
	 *
	 * @return mixed|null
	 */
	public function transform( $value, stdClass $arguments = null, $default = null ) {
		if ( ! is_string( $value ) ) {
			return $default;
		}

		$url_parts = wp_parse_url( rtrim( $value, '/' ) );

		if ( ! $url_parts ) {
			return $default;
		}

		if ( ! isset( $url_parts['host'] ) ) {
			return $default;
		}

		if ( isset( $url_parts['path'] ) ) {
			return $url_parts['host'] . $url_parts['path'];
		}

		return $url_parts['host'];
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
