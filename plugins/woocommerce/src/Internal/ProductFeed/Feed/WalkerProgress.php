<?php
/**
 * Walker Progress class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

/**
 * Simple class that tracks/indicates the progress of a walker.
 */
class WalkerProgress {
	/**
	 * Total number of items to process.
	 *
	 * @var int
	 */
	public int $total_count;

	/**
	 * Total number of batches to process.
	 *
	 * @var int
	 */
	public int $total_batch_count;

	/**
	 * Number of items processed so far.
	 *
	 * @var int
	 */
	public int $processed_items = 0;

	/**
	 * Number of batches processed so far.
	 *
	 * @var int
	 */
	public int $processed_batches = 0;

	/**
	 * Creates a WalkerProgress instance from a WooCommerce products query result.
	 *
	 * @param object $result The result object from wc_get_products().
	 * @return static
	 */
	public static function from_wc_get_products_result( object $result ): self {
		$progress = new static();

		$progress->total_count       = $result->total;
		$progress->total_batch_count = $result->max_num_pages;
		$progress->processed_items   = 0;
		$progress->processed_batches = 0;

		return $progress;
	}
}
