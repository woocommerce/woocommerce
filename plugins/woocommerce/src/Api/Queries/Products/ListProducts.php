<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Queries\Products;

use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\Unroll;
use Automattic\WooCommerce\Api\Enums\Products\ProductType;
use Automattic\WooCommerce\Api\InputTypes\Products\ProductFilterInput;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\Edge;
use Automattic\WooCommerce\Api\Pagination\PageInfo;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;
use Automattic\WooCommerce\Api\Interfaces\Product;
use Automattic\WooCommerce\Api\Utils\Products\ProductMapper;

/**
 * Query to list products with cursor-based pagination.
 *
 * Demonstrates: #[Unroll] on parameter, enum as direct param, multiple capabilities.
 */
#[Name( 'Products' )]
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
	 * @return Connection
	 */
	#[ConnectionOf( Product::class )]
	public function execute(
		PaginationParams $pagination,
		#[Unroll]
		ProductFilterInput $filters,
		#[Description( 'Filter by product type.' )]
		?ProductType $product_type = null,
	): Connection {
		$first  = $pagination->first;
		$last   = $pagination->last;
		$after  = $pagination->after;
		$before = $pagination->before;
		$limit  = $first ?? $last ?? 10;

		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit + 1,
			'orderby'        => 'ID',
			'order'          => null !== $last ? 'DESC' : 'ASC',
			'post_status'    => $filters->status?->value ?? 'any',
		);

		// Product type filter via taxonomy.
		if ( null !== $product_type ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $product_type->value,
				),
			);
		}

		// Stock status filter via meta.
		if ( null !== $filters->stock_status ) {
			$wc_status = match ( $filters->stock_status->value ) {
				1 => 'instock',
				2 => 'outofstock',
				3 => 'onbackorder',
			};
			$query_args['meta_query'] = array(
				array(
					'key'   => '_stock_status',
					'value' => $wc_status,
				),
			);
		}

		// Search filter.
		if ( null !== $filters->search ) {
			$query_args['s'] = $filters->search;
		}

		// Cursor-based filtering.
		$where_filter = null;
		if ( null !== $after ) {
			$after_id         = (int) base64_decode( $after, true );
			add_filter(
				'posts_where',
				$where_filter = static function ( string $where ) use ( $after_id ): string {
					global $wpdb;
					return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after_id );
				}
			);
		}

		if ( null !== $before ) {
			$before_id        = (int) base64_decode( $before, true );
			add_filter(
				'posts_where',
				$where_filter = static function ( string $where ) use ( $before_id ): string {
					global $wpdb;
					return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID < %d", $before_id );
				}
			);
		}

		// Total count query.
		$count_args  = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'post_status'    => $filters->status?->value ?? 'any',
		);
		$count_query = new \WP_Query( $count_args );
		$total_count = $count_query->found_posts;

		// Main query.
		$query = new \WP_Query( $query_args );

		if ( null !== $where_filter ) {
			remove_filter( 'posts_where', $where_filter );
		}

		$posts = $query->posts;

		// Determine pagination.
		$has_extra = count( $posts ) > $limit;
		if ( $has_extra ) {
			$posts = array_slice( $posts, 0, $limit );
		}

		if ( null !== $last ) {
			$posts = array_reverse( $posts );
		}

		// Build edges and nodes.
		$edges = array();
		$nodes = array();
		foreach ( $posts as $post ) {
			$wc_product = wc_get_product( $post->ID );
			if ( ! $wc_product instanceof \WC_Product ) {
				continue;
			}

			$product = ProductMapper::from_wc_product( $wc_product );

			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $product->id );
			$edge->node   = $product;

			$edges[] = $edge;
			$nodes[] = $product;
		}

		$page_info                    = new PageInfo();
		$page_info->has_next_page     = null !== $last ? ( null !== $after ) : $has_extra;
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
