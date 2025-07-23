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
 * Uses ShopifyClient for REST API communication and will be extended with
 * GraphQL API logic in future PRs.
 */
class ShopifyFetcher implements PlatformFetcherInterface {

	/**
	 * The Shopify client instance.
	 *
	 * @var ShopifyClient
	 */
	private $shopify_client;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Initialize the fetcher with dependencies.
	 *
	 * @internal
	 * @param ShopifyClient $shopify_client The Shopify client.
	 */
	final public function init( ShopifyClient $shopify_client ): void {
		$this->shopify_client = $shopify_client;
	}

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
	 * Fetches the total count of products from the Shopify REST API.
	 *
	 * @param array $args Arguments for filtering the count (e.g., status, date range).
	 *
	 * @return int The total count, or 0 on failure.
	 */
	public function fetch_total_count( array $args ): int {
		// Handle special case: if specific IDs are provided, count them directly.
		if ( isset( $args['ids'] ) ) {
			\WP_CLI::debug( 'Calculating total count based on provided product IDs.' );
			$ids = is_array( $args['ids'] ) ? $args['ids'] : explode( ',', $args['ids'] );
			return count( array_filter( $ids ) ); // Filter out empty values.
		}

		$rest_api_path = '/products/count.json';
		$query_params  = $this->build_count_query_params( $args );

		$response = $this->shopify_client->rest_request( $rest_api_path, $query_params );

		if ( is_wp_error( $response ) ) {
			\WP_CLI::warning( 'Could not fetch total product count from Shopify REST API: ' . $response->get_error_message() );
			return 0;
		}

		if ( ! isset( $response->count ) ) {
			\WP_CLI::warning( 'Unexpected response format from Shopify count API - missing count field.' );
			return 0;
		}

		return (int) $response->count;
	}

	/**
	 * Build query parameters for the count API request.
	 *
	 * @param array $args Filter arguments.
	 * @return array Query parameters for the REST API.
	 */
	private function build_count_query_params( array $args ): array {
		$query_params = array();

		// Map standard filter args to Shopify REST count query params.
		if ( isset( $args['status'] ) ) {
			$query_params['status'] = strtolower( $args['status'] ); // REST uses lowercase.
		}

		if ( isset( $args['created_at_min'] ) ) {
			$query_params['created_at_min'] = $args['created_at_min'];
		}

		if ( isset( $args['created_at_max'] ) ) {
			$query_params['created_at_max'] = $args['created_at_max'];
		}

		if ( isset( $args['updated_at_min'] ) ) {
			$query_params['updated_at_min'] = $args['updated_at_min'];
		}

		if ( isset( $args['updated_at_max'] ) ) {
			$query_params['updated_at_max'] = $args['updated_at_max'];
		}

		if ( isset( $args['vendor'] ) ) {
			$query_params['vendor'] = $args['vendor'];
		}

		if ( isset( $args['product_type'] ) ) {
			$query_params['product_type'] = $args['product_type'];
		}

		return $query_params;
	}
}
