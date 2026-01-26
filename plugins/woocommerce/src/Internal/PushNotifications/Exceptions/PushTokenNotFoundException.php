<?php
/**
 * PushTokenNotFoundException class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Exceptions;

defined( 'ABSPATH' ) || exit;

use Exception;
use WP_Http;

/**
 * Exception thrown when a push token cannot be found.
 *
 * @since 10.5.0
 */
class PushTokenNotFoundException extends Exception {}
