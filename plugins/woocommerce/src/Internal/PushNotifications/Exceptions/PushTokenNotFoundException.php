<?php
/**
 * PushTokenNotFoundException class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Exceptions;

defined( 'ABSPATH' ) || exit;

use WC_Data_Exception;
use WP_Http;

/**
 * Exception thrown when a push token cannot be found.
 *
 * @internal
 */
class PushTokenNotFoundException extends WC_Data_Exception {
	/**
	 * Constructor.
	 *
	 * @internal
	 */
	public function __construct() {
		parent::__construct(
			'woocommerce_invalid_push_token',
			'Push token could not be found.',
			WP_Http::NOT_FOUND
		);
	}
}
