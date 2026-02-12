<?php
/**
 * PushTokenInvalidDataException class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Exceptions;

defined( 'ABSPATH' ) || exit;

use WC_Data_Exception;
use WP_Http;

/**
 * Exception thrown when push token data is invalid.
 *
 * @internal
 */
class PushTokenInvalidDataException extends WC_Data_Exception {
	/**
	 * Constructor.
	 *
	 * @internal
	 * @param string $message The validation error message.
	 */
	public function __construct( string $message ) {
		parent::__construct(
			'woocommerce_invalid_data',
			$message,
			WP_Http::BAD_REQUEST
		);
	}
}
