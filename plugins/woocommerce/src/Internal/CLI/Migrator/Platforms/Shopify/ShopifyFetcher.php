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
	 * Comprehensive GraphQL query for fetching Shopify products.
	 *
	 * This query fetches all necessary product data including variants, images,
	 * collections, and metadata for migration to WooCommerce.
	 */
	const SHOPIFY_PRODUCT_QUERY = <<<'GRAPHQL'
	query GetShopifyProducts(
		$first: Int!,
		$after: String,
		$query: String,
		$variantsFirst: Int = 100
	) {
		products(first: $first, after: $after, query: $query) {
			edges {
				cursor
				node {
					id
					title
					handle
					descriptionHtml
					status
					createdAt
					vendor
					tags
					onlineStoreUrl
					options(first: 10) {
						id
						name
						position
						values
					}
					featuredMedia {
						... on MediaImage {
							id
							image {
								url
								altText
							}
						}
					}
					media(first: 50) {
						edges {
							node {
								... on MediaImage {
									id
									image {
										url
										altText
									}
								}
							}
						}
					}
					variants(first: $variantsFirst) {
						edges {
							node {
								id
								product { id }
								price
								compareAtPrice
								sku
								taxable
								inventoryPolicy
								inventoryQuantity
								position
								inventoryItem {
									tracked
									unitCost {
										amount
										currencyCode
									}
									measurement {
										weight {
											value
											unit
										}
									}
								}
								media(first: 1) {
									edges {
										node {
											... on MediaImage {
												id
												image {
													url
													altText
												}
											}
										}
									}
								}
								selectedOptions {
									name
									value
								}
							}
						}
					}
					collections(first: 20) {
						edges {
							node {
								id
								handle
								title
							}
						}
					}
					metafields(first: 20, namespace: "global") {
						edges {
							node {
								namespace
								key
								value
							}
						}
					}
				}
			}
			pageInfo {
				hasNextPage
			}
		}
	}
	GRAPHQL;

	/**
	 * The Shopify client instance.
	 *
	 * @var ShopifyClient
	 */
	private $shopify_client;

	/**
	 * Platform credentials.
	 *
	 * @var array
	 */
	private array $credentials;

	/**
	 * Constructor.
	 *
	 * @param array $credentials Platform credentials array.
	 */
	public function __construct( array $credentials ) {
		$this->credentials    = $credentials;
		$this->shopify_client = new ShopifyClient( $credentials );
	}

	/**
	 * Fetches a batch of products from the Shopify GraphQL API.
	 *
	 * @param array $args Arguments for fetching. Supported keys:
	 *                    - 'limit': Max number of items per batch (default: 50).
	 *                    - 'after_cursor': Cursor for pagination (optional).
	 *                    - 'query_filter': GraphQL query filter string (optional).
	 *                    - 'variants_per_product': Max variants per product (default: 100).
	 *
	 * @return array An array containing:
	 *               'items'       => array Raw product edges fetched from Shopify.
	 *               'cursor'      => ?string The cursor for the next page, or null if no more pages.
	 *               'has_next_page' => bool Indicates if there are more pages to fetch.
	 */
	public function fetch_batch( array $args ): array {
		$variables = $this->build_graphql_variables( $args );

		$response_data = $this->shopify_client->graphql_request( self::SHOPIFY_PRODUCT_QUERY, $variables );

		if ( is_wp_error( $response_data ) ) {
			\WP_CLI::warning( 'Failed to fetch products via GraphQL: ' . $response_data->get_error_message() );
			return array(
				'items'         => array(),
				'cursor'        => null,
				'has_next_page' => false,
			);
		}

		if ( ! isset( $response_data->products->edges ) ) {
			\WP_CLI::warning( 'Invalid GraphQL response structure - missing products.edges field.' );
			return array(
				'items'         => array(),
				'cursor'        => null,
				'has_next_page' => false,
			);
		}

		$items       = $response_data->products->edges;
		$page_info   = $response_data->products->pageInfo ?? null;
		$last_cursor = null;

		if ( ! empty( $items ) ) {
			$last_edge   = end( $items );
			$last_cursor = $last_edge->cursor ?? null;
		}

		return array(
			'items'         => $items,
			'cursor'        => $last_cursor,
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL response property
			'has_next_page' => $page_info ? $page_info->hasNextPage : false,
		);
	}

	/**
	 * Build GraphQL variables from fetch arguments.
	 *
	 * @param array $args The fetch arguments.
	 * @return array The GraphQL variables.
	 */
	private function build_graphql_variables( array $args ): array {
		$variables = array(
			'first'         => $args['limit'] ?? 50,
			'after'         => $args['after_cursor'] ?? null,
			'query'         => $this->build_graphql_query_string( $args ),
			'variantsFirst' => $args['variants_per_product'] ?? 100,
		);

		// Remove null values to avoid GraphQL issues.
		return array_filter(
			$variables,
			function ( $value ) {
				return null !== $value && '' !== $value;
			}
		);
	}

	/**
	 * Build GraphQL query string from filter arguments.
	 *
	 * @param array $args Filter arguments.
	 * @return string GraphQL query string.
	 */
	private function build_graphql_query_string( array $args ): string {
		$query_parts = array();

		if ( isset( $args['status'] ) ) {
			$query_parts[] = 'status:' . strtoupper( $args['status'] );
		}

		if ( isset( $args['product_type'] ) ) {
			$query_parts[] = 'product_type:"' . $args['product_type'] . '"';
		}

		if ( isset( $args['vendor'] ) ) {
			$query_parts[] = 'vendor:"' . $args['vendor'] . '"';
		}

		if ( isset( $args['handle'] ) ) {
			$query_parts[] = 'handle:' . $args['handle'];
		}

		if ( isset( $args['created_after'] ) ) {
			$query_parts[] = 'created_at:>=' . $args['created_after'];
		}

		if ( isset( $args['created_before'] ) ) {
			$query_parts[] = 'created_at:<=' . $args['created_before'];
		}

		if ( isset( $args['ids'] ) ) {
			$ids = is_array( $args['ids'] ) ? $args['ids'] : explode( ',', $args['ids'] );
			$ids = array_filter( array_map( 'trim', $ids ) );
			if ( ! empty( $ids ) ) {
				$formatted_ids = array_map(
					function ( $id ) {
						return 'gid://shopify/Product/' . $id;
					},
					$ids
				);
				$query_parts[] = 'id:(' . implode( ' OR ', $formatted_ids ) . ')';
			}
		}

		return implode( ' AND ', $query_parts );
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
			return count( array_filter( $ids ) );
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
