<?php
/**
 * Convert an object to the product schema format.
 *
 * @package Automattic/WooCommerce/RestApi
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Responses;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractObjectResponse class.
 */
abstract class AbstractObjectResponse {

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param mixed $object Object.
	 * @param string $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	abstract public function prepare_response( $object, $context );
}
