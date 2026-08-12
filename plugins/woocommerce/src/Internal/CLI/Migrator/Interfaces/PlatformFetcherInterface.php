<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces;

/**
 * Defines the contract for classes responsible for retrieving
 * data (like products or orders) from a source platform API.
 *
 * Implementations should accept platform credentials via constructor:
 * public function __construct(array $credentials)
 */
interface PlatformFetcherInterface {

	/**
	 * Fetches a batch of items from the source platform.
	 *
	 * @param array $args Arguments for fetching (e.g., limit, cursor, filters).
	 *                    Specific arguments depend on the implementation.
	 *
	 * @return array An array containing:
	 *               'items'       => array Raw items fetched from the platform.
	 *               'cursor'      => ?string The cursor for the next page, or null if no more pages.
	 *               'has_next_page' => bool Indicates if there are more pages to fetch.
	 */
	public function fetch_batch( array $args ): array;

	/**
	 * Fetches the estimated total count of items available for migration.
	 *
	 * Used primarily for progress indicators. If a total count is not available,
	 * this method should return 0.
	 *
	 * @param array $args Arguments for filtering the count (e.g., status, date range).
	 *                    Specific arguments depend on the implementation.
	 *
	 * @return int The total estimated count.
	 */
	public function fetch_total_count( array $args ): int;
}
