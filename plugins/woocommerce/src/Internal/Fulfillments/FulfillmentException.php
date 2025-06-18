<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;

/**
 * FulfillmentException class.
 * This exception is thrown when there is an issue with fulfillment operations,
 * such as creating, updating, or deleting fulfillments.
 */
class FulfillmentException extends ApiException {
	/**
	 * Setup exception.
	 *
	 * @param string $message          User-friendly translated error message, e.g. 'Fulfillment creation failed'.
	 * @param int    $http_status_code Optional. Proper HTTP status code to respond with.
	 *                                 Defaults to 400 (Bad request).
	 * @param array  $additional_data  Optional. Extra data (key value pairs) to expose in the error response.
	 *                                 Defaults to empty array.
	 */
	public function __construct( string $message, int $http_status_code = 400, array $additional_data = array() ) {
		parent::__construct( 'woocommerce_fulfillment_error', $message, $http_status_code, $additional_data );
	}
}
