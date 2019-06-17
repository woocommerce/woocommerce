<?php
/**
 * Convert an object to the product schema format.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractResponse class.
 */
abstract class AbstractResponse {

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param mixed $object Object.
	 * @param string $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	abstract public function prepare_response( $object, $context );
}
