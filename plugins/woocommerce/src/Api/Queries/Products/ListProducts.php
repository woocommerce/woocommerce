<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Queries\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\Unroll;
use Automattic\WooCommerce\Api\Enums\Products\ProductType;
use Automattic\WooCommerce\Api\Enums\Products\StockStatus;
use Automattic\WooCommerce\Api\InputTypes\Products\ProductFilterInput;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\Edge;
use Automattic\WooCommerce\Api\Pagination\IdCursorFilter;
use Automattic\WooCommerce\Api\Pagination\PageInfo;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;
use Automattic\WooCommerce\Api\Interfaces\Product;
use Automattic\WooCommerce\Api\Utils\Products\ProductMapper;

/**
 * Query to list products with cursor-based pagination.
 *
 * Demonstrates: #[Unroll] on parameter, enum as direct param, multiple capabilities.
 */
#[Name( 'products' )]
#[Description( 'List products with cursor-based pagination and optional filtering.' )]
#[RequiredCapability( 'manage_woocommerce' )]
#[RequiredCapability( 'edit_products' )]
class ListProducts {
	/**
	 * List products with optional filtering and pagination.
	 *
	 * @param PaginationParams   $pagination   The pagination parameters.
	 * @param ProductFilterInput $filters      Filter criteria (unrolled to flat args).
	 * @param ?ProductType       $product_type Optional product type filter.
	 * @param ?array             $_query_info  Unified query info tree from the GraphQL request.
	 * @return Connection
	 * @throws ApiException When an unsupported `stock_status` filter value is passed.
	 */
	#[ConnectionOf( Product::class )]
	public function execute(
		PaginationParams $pagination,
		#[Unroll]
		ProductFilterInput $filters,
		#[Description( 'Filter by product type.' )]
		?ProductType $product_type = null,
		?array $_query_info = null,
	): Connection {
		$first  = $pagination->first;
		$last   = $pagination->last;
		$after  = $pagination->after;
		$before = $pagination->before;
		$limit  = $first ?? $last ?? PaginationParams::get_default_page_size();

		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit + 1,
			'orderby'        => 'ID',
			'order'          => null !== $last ? 'DESC' : 'ASC',
			'post_status'    => $filters->status?->value ?? 'any',
		);

		// Product type filter via taxonomy. `ProductType::Other` is the
		// output-only signal for "stored product_type doesn't match any
		// known standard" (typically plugin-added types), mirroring how
		// `StockStatus::Other` is handled for the meta-query path above.
		// Map it to NOT IN the standard slugs rather than the literal
		// 'other' term, which wouldn't match anything.
		if ( null !== $product_type ) {
			if ( ProductType::Other === $product_type ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array_values(
							array_filter(
								array_map(
									static fn( ProductType $t ): string => $t->value,
									ProductType::cases()
								),
								static fn( string $slug ): bool => ProductType::Other->value !== $slug
							)
						),
						'operator' => 'NOT IN',
					),
				);
			} else {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => $product_type->value,
					),
				);
			}
		}

		// Stock status filter via meta. `StockStatus::Other` means "stored
		// _stock_status isn't one of the three standard WooCommerce values"
		// (typically a plugin-added custom status), so it maps to NOT IN
		// those three. `default` throws INVALID_ARGUMENT so any future
		// enum case added without updating this match fails loudly with a
		// clean 400 instead of a PHP-level UnhandledMatchError → HTTP 500.
		if ( null !== $filters->stock_status ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
			$meta_clause = match ( $filters->stock_status ) {
				StockStatus::InStock     => array(
					'key'   => '_stock_status',
					'value' => 'instock',
				),
				StockStatus::OutOfStock  => array(
					'key'   => '_stock_status',
					'value' => 'outofstock',
				),
				StockStatus::OnBackorder => array(
					'key'   => '_stock_status',
					'value' => 'onbackorder',
				),
				StockStatus::Other       => array(
					'key'     => '_stock_status',
					'value'   => array( 'instock', 'outofstock', 'onbackorder' ),
					'compare' => 'NOT IN',
				),
				default                  => throw new ApiException(
					sprintf( 'Unsupported stock_status filter value: %s.', $filters->stock_status->name ),
					'INVALID_ARGUMENT',
					status_code: 400,
				),
			};
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			$query_args['meta_query'] = array( $meta_clause );
		}

		// Search filter.
		if ( null !== $filters->search ) {
			$query_args['s'] = $filters->search;
		}

		// Total count query. Derive from $query_args — which already has
		// the tax_query / meta_query / search clauses applied — *before*
		// we set cursor query vars on it. Building $count_args from scratch
		// with only post_status would drop every user filter and report the
		// count of "all products in that status" instead of "all products
		// matching the filters", making Relay consumers' "X of Y" wrong.
		// Only `found_posts` is read, so posts_per_page => 1 keeps the
		// underlying SELECT cheap.
		$count_args                   = $query_args;
		$count_args['posts_per_page'] = 1;
		$count_args['fields']         = 'ids';
		$count_query                  = new \WP_Query( $count_args );
		$total_count                  = $count_query->found_posts;

		// Cursor-based filtering via IdCursorFilter (see class docblock).
		if ( null !== $after ) {
			$query_args[ IdCursorFilter::AFTER_ID ] = IdCursorFilter::decode_id_cursor( $after, 'after' );
		}
		if ( null !== $before ) {
			$query_args[ IdCursorFilter::BEFORE_ID ] = IdCursorFilter::decode_id_cursor( $before, 'before' );
		}
		IdCursorFilter::ensure_registered();

		$query = new \WP_Query( $query_args );
		$posts = $query->posts;

		// Determine pagination.
		$has_extra = count( $posts ) > $limit;
		if ( $has_extra ) {
			$posts = array_slice( $posts, 0, $limit );
		}

		if ( null !== $last ) {
			$posts = array_reverse( $posts );
		}

		// Narrow $_query_info to the per-node selection so each mapped
		// product only fetches the subtrees the client actually asked for
		// under `nodes { ... }` / `edges { node { ... } }`. Without this,
		// ProductMapper::populate_common_fields() hits its null-$query_info
		// fallback and runs build_reviews() (plus its count query) for
		// every product on the page — N+1 on reviews even when no client
		// selected them.
		$node_query_info = ProductMapper::connection_node_info( $_query_info );

		// Build edges and nodes.
		$edges = array();
		$nodes = array();
		foreach ( $posts as $post ) {
			$wc_product = wc_get_product( $post->ID );
			if ( ! $wc_product instanceof \WC_Product ) {
				continue;
			}

			$product = ProductMapper::from_wc_product( $wc_product, $node_query_info );

			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $product->id );
			$edge->node   = $product;

			$edges[] = $edge;
			$nodes[] = $product;
		}

		$page_info = new PageInfo();
		// Relay semantics for backward pagination (`last`, `before`): the
		// returned window ends just before `$before`, so items after the
		// window exist whenever `$before` was supplied — not whenever
		// `$after` was. `has_previous_page` in the backward case is driven
		// by the "did we fetch limit+1?" sentinel (`$has_extra`).
		$page_info->has_next_page     = null !== $last ? ( null !== $before ) : $has_extra;
		$page_info->has_previous_page = null !== $last ? $has_extra : ( null !== $after );
		$page_info->start_cursor      = ! empty( $edges ) ? $edges[0]->cursor : null;
		$page_info->end_cursor        = ! empty( $edges ) ? $edges[ count( $edges ) - 1 ]->cursor : null;

		$connection              = new Connection();
		$connection->edges       = $edges;
		$connection->nodes       = $nodes;
		$connection->page_info   = $page_info;
		$connection->total_count = $total_count;

		return $connection;
	}
}
