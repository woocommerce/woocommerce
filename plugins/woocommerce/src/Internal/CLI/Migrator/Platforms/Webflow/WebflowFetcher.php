<?php
/**
 * Webflow Fetcher
 *
 * @package Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Webflow;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformFetcherInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches products (and ecommerce category metadata) from the Webflow v2 API.
 *
 * The Webflow API exposes offset/limit pagination rather than cursors, but the
 * migrator's PlatformFetcherInterface speaks cursors. We use the next offset as
 * a stringified cursor and translate it back to an `offset` query parameter.
 *
 * Webflow does not expose a `/products/categories` endpoint. Categories live in
 * an auto-provisioned CMS collection (slug `category`). We resolve them once
 * via `/sites/{id}/collections` + `/collections/{id}/items` and stash a map of
 * `cms_item_id => { name, slug }` and decorates each fetched product item with a
 * `_resolved_categories` property so the mapper can read categories inline.
 *
 * @internal This class is part of the CLI Migrator feature and should not be used directly.
 */
class WebflowFetcher implements PlatformFetcherInterface {

	/**
	 * Maximum page size allowed by Webflow.
	 *
	 * @var int
	 */
	private const MAX_PAGE_SIZE = 100;

	/**
	 * The Webflow client instance.
	 *
	 * @var WebflowClient
	 */
	private WebflowClient $webflow_client;

	/**
	 * Cached category map: cms_item_id => array{name:string, slug:string}.
	 *
	 * Null until resolved; empty array if the site has no categories collection.
	 *
	 * @var array<string,array{name:string,slug:string}>|null
	 */
	private ?array $category_cache = null;

	/**
	 * Constructor.
	 *
	 * @param array $credentials Platform credentials array.
	 */
	public function __construct( array $credentials ) {
		$this->webflow_client = new WebflowClient( $credentials );
	}

	/**
	 * Fetches a batch of products from the Webflow REST API.
	 *
	 * @param array $args Arguments for fetching. Supported keys:
	 *                    - 'limit': Max number of items per batch (default: 50, capped at 100).
	 *                    - 'after_cursor': Stringified next-offset for pagination (optional).
	 *
	 * @return array{items: array, cursor: ?string, has_next_page: bool}
	 */
	public function fetch_batch( array $args ): array {
		$site_id = $this->webflow_client->get_site_id();
		if ( is_wp_error( $site_id ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::warning( 'Failed to fetch Webflow products: ' . $site_id->get_error_message() );
			return $this->empty_batch();
		}

		$limit  = (int) ( $args['limit'] ?? 50 );
		$limit  = max( 1, min( $limit, self::MAX_PAGE_SIZE ) );
		$offset = 0;
		if ( isset( $args['after_cursor'] ) && is_numeric( $args['after_cursor'] ) ) {
			$offset = max( 0, (int) $args['after_cursor'] );
		}

		$query = array(
			'limit'  => $limit,
			'offset' => $offset,
		);

		$response = $this->webflow_client->rest_request( "/sites/{$site_id}/products", $query );

		if ( is_wp_error( $response ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::warning( 'Failed to fetch products from Webflow: ' . $response->get_error_message() );
			return $this->empty_batch();
		}

		if ( ! is_object( $response ) || ! isset( $response->items ) || ! is_array( $response->items ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::warning( 'Invalid Webflow response: missing items array.' );
			return $this->empty_batch();
		}

		$items    = $response->items;
		$count    = count( $items );
		$total    = isset( $response->pagination->total ) ? (int) $response->pagination->total : ( $offset + $count );
		$next     = $offset + $count;
		$has_next = $count > 0 && $next < $total;

		$this->ensure_category_cache_loaded( $site_id );
		$this->decorate_items_with_categories( $items );

		// The cursor is simply the next offset. Unlike Shopify's stable cursors, offset paging can
		// skip or double-import items if the Webflow catalog changes (a product added or removed)
		// between batches. Acceptable here given Webflow's API offers no stable cursor.
		return array(
			'items'         => $items,
			'cursor'        => $has_next ? (string) $next : null,
			'has_next_page' => $has_next,
		);
	}

	/**
	 * Fetches the total count of products from Webflow.
	 *
	 * @param array $args Filter arguments (unused; Webflow's products endpoint does not accept filters).
	 * @return int Total product count, or 0 on failure.
	 */
	public function fetch_total_count( array $args ): int {
		unset( $args );
		// Webflow does not support filtered count queries here.

		$site_id = $this->webflow_client->get_site_id();
		if ( is_wp_error( $site_id ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::warning( 'Could not fetch Webflow product count: ' . $site_id->get_error_message() );
			return 0;
		}

		$response = $this->webflow_client->rest_request(
			"/sites/{$site_id}/products",
			array(
				'limit'  => 1,
				'offset' => 0,
			)
		);

		if ( is_wp_error( $response ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::warning( 'Could not fetch Webflow product count: ' . $response->get_error_message() );
			return 0;
		}

		if ( ! is_object( $response ) || ! isset( $response->pagination->total ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::warning( 'Unexpected response from Webflow products endpoint - missing pagination.total.' );
			return 0;
		}

		return (int) $response->pagination->total;
	}

	/**
	 * Decorate each product item with a `_resolved_categories` property whose value is an
	 * array of `{name, slug}` for every recognized CMS item id in `product.fieldData.category`.
	 *
	 * This is how the mapper learns about category names without needing access to the
	 * fetcher or making its own API calls. Missing/archived ids are silently dropped.
	 *
	 * @param array<int,object> $items Items array (mutated in place).
	 * @return void
	 */
	private function decorate_items_with_categories( array &$items ): void {
		if ( null === $this->category_cache || empty( $this->category_cache ) ) {
			foreach ( $items as $item ) {
				if ( is_object( $item ) ) {
					// @phpstan-ignore-next-line property.notFound
					$item->_resolved_categories = array();
				}
			}
			return;
		}

		foreach ( $items as $item ) {
			if ( ! is_object( $item ) ) {
				continue;
			}

			$category_ids = array();
			if ( isset( $item->product->fieldData->category ) && is_array( $item->product->fieldData->category ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
				$category_ids = $item->product->fieldData->category; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
			}

			$resolved = array();
			foreach ( $category_ids as $cms_item_id ) {
				$cms_item_id = (string) $cms_item_id;
				if ( isset( $this->category_cache[ $cms_item_id ] ) ) {
					$resolved[] = $this->category_cache[ $cms_item_id ];
				}
			}

			// @phpstan-ignore-next-line property.notFound
			$item->_resolved_categories = $resolved;
		}
	}

	/**
	 * Ensures the category cache has been resolved at most once per fetcher instance.
	 *
	 * @param string $site_id Webflow site ID.
	 * @return void
	 */
	private function ensure_category_cache_loaded( string $site_id ): void {
		if ( null !== $this->category_cache ) {
			return;
		}

		$this->category_cache = array();

		$collections_response = $this->webflow_client->rest_request( "/sites/{$site_id}/collections" );
		if ( is_wp_error( $collections_response ) ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::debug( 'Could not load Webflow collections list (categories will not be resolved): ' . $collections_response->get_error_message() );
			return;
		}

		$collections = $this->extract_collections_list( $collections_response );
		$category_id = $this->find_category_collection_id( $collections );

		if ( null === $category_id ) {
			// @phpstan-ignore-next-line class.notFound
			\WP_CLI::debug( 'No "category" collection found on Webflow site; product categories will be skipped.' );
			return;
		}

		$this->category_cache = $this->load_collection_items_map( $category_id );
	}

	/**
	 * Webflow's /collections endpoint sometimes returns a bare array and sometimes an envelope.
	 * Normalize to a flat list of collection objects.
	 *
	 * @param mixed $response The raw decoded API response.
	 * @return array<int,object>
	 */
	private function extract_collections_list( $response ): array {
		if ( is_array( $response ) ) {
			return $response;
		}

		if ( is_object( $response ) && isset( $response->collections ) && is_array( $response->collections ) ) {
			return $response->collections;
		}

		return array();
	}

	/**
	 * Find the auto-provisioned Ecommerce Categories collection.
	 *
	 * @param array<int,object> $collections List of collection objects.
	 * @return string|null Collection ID or null if not found.
	 */
	private function find_category_collection_id( array $collections ): ?string {
		foreach ( $collections as $collection ) {
			if ( ! is_object( $collection ) || empty( $collection->id ) ) {
				continue;
			}
			$slug         = isset( $collection->slug ) ? (string) $collection->slug : '';
			$display_name = isset( $collection->displayName ) ? (string) $collection->displayName : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.

			if ( 'category' === $slug || 'Categories' === $display_name ) {
				return (string) $collection->id;
			}
		}

		return null;
	}

	/**
	 * Page through a CMS collection and build an id => {name, slug} map.
	 *
	 * @param string $collection_id The Webflow CMS collection ID.
	 * @return array<string,array{name:string,slug:string}>
	 */
	private function load_collection_items_map( string $collection_id ): array {
		$map       = array();
		$offset    = 0;
		$page_size = self::MAX_PAGE_SIZE;
		$max_pages = 100;
		// Safety net to avoid runaway loops on broken pagination.
		$page_index = 0;

		while ( $page_index < $max_pages ) {
			$response = $this->webflow_client->rest_request(
				"/collections/{$collection_id}/items",
				array(
					'limit'  => $page_size,
					'offset' => $offset,
				)
			);

			if ( is_wp_error( $response ) ) {
				// @phpstan-ignore-next-line class.notFound
				\WP_CLI::debug( 'Could not load Webflow category items: ' . $response->get_error_message() );
				break;
			}

			$items = ( is_object( $response ) && isset( $response->items ) && is_array( $response->items ) ) ? $response->items : array();
			if ( empty( $items ) ) {
				break;
			}

			foreach ( $items as $item ) {
				if ( ! is_object( $item ) || empty( $item->id ) ) {
					continue;
				}
				$field_data = isset( $item->fieldData ) && is_object( $item->fieldData ) ? $item->fieldData : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Webflow API uses camelCase.
				if ( null === $field_data ) {
					continue;
				}

				$name = isset( $field_data->name ) ? (string) $field_data->name : '';
				$slug = isset( $field_data->slug ) ? (string) $field_data->slug : sanitize_title( $name );

				if ( '' === $name ) {
					continue;
				}

				$map[ (string) $item->id ] = array(
					'name' => $name,
					'slug' => $slug,
				);
			}

			$total   = isset( $response->pagination->total ) ? (int) $response->pagination->total : ( $offset + count( $items ) );
			$offset += count( $items );
			if ( $offset >= $total ) {
				break;
			}

			++$page_index;
		}

		return $map;
	}

	/**
	 * Empty-batch response helper.
	 *
	 * @return array{items: array, cursor: null, has_next_page: false}
	 */
	private function empty_batch(): array {
		return array(
			'items'         => array(),
			'cursor'        => null,
			'has_next_page' => false,
		);
	}
}
