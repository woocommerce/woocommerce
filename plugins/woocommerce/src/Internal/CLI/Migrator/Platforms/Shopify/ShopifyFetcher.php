<?php
/**
 * Shopify Fetcher
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;

defined( 'ABSPATH' ) || exit;

/**
 * ShopifyFetcher class.
 *
 * This class is responsible for fetching data from the Shopify platform.
 * Currently contains stub implementations that will be replaced with actual
 * Shopify GraphQL API logic in future PRs.
 */
class ShopifyFetcher implements PlatformFetcherInterface {

	/**
	 * Fetches a batch of items from the Shopify platform.
	 *
	 * @param array $args Arguments for fetching (e.g., limit, cursor, filters).
	 *
	 * @return array An array containing:
	 *               'items'       => array Raw items fetched from the platform.
	 *               'cursor'      => ?string The cursor for the next page, or null if no more pages.
	 *               'hasNextPage' => bool Indicates if there are more pages to fetch.
	 */
	public function fetch_batch( array $args ): array {
		// Stub implementation - will be replaced with actual Shopify GraphQL API calls.
		return array(
			'items'       => array(),
			'cursor'      => null,
			'hasNextPage' => false,
		);
	}

	/**
	 * Fetches the estimated total count of items available for migration from Shopify.
	 *
	 * @param array $args Arguments for filtering the count (e.g., status, date range).
	 *
	 * @return int The total estimated count.
	 */
	public function fetch_total_count( array $args ): int {
		// Stub implementation - will be replaced with actual Shopify GraphQL API calls.
		return 0;
	}
}
