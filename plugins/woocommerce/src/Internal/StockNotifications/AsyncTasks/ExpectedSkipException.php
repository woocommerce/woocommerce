<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks;

use RuntimeException;

/**
 * Thrown when a notifications batch stops for an expected reason, such as a product
 * that is no longer eligible or has gone back out of stock before the job ran.
 *
 * @since 11.3.0
 */
class ExpectedSkipException extends RuntimeException {}
