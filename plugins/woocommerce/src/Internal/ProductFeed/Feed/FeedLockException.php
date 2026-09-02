<?php
/**
 * Feed Lock Exception.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

use Exception;

/**
 * Thrown when a feed file cannot be locked because another generation process already holds the lock.
 *
 * This is distinct from a genuine generation failure: it means another process owns the feed and is
 * actively writing it, so the caller should step aside rather than mark the job as failed.
 *
 * @since 11.0.0
 */
class FeedLockException extends Exception {}
