<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Queries\Coupons;

use Automattic\WooCommerce\Api\Attributes\ConnectionOf;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\Name;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Api\Pagination\Edge;
use Automattic\WooCommerce\Api\Pagination\IdCursorFilter;
use Automattic\WooCommerce\Api\Pagination\PageInfo;
use Automattic\WooCommerce\Api\Pagination\PaginationParams;
use Automattic\WooCommerce\Api\Types\Coupons\Coupon;
use Automattic\WooCommerce\Api\Utils\Coupons\CouponMapper;

#[Name( 'coupons' )]
#[Description( 'List coupons with cursor-based pagination.' )]
/**
 * Query to list coupons with cursor-based pagination.
 */
#[RequiredCapability( 'read_private_shop_coupons' )]
class ListCoupons {
	/**
	 * List coupons with optional filtering and pagination.
	 *
	 * @param PaginationParams $pagination The pagination parameters.
	 * @param ?CouponStatus    $status     Optional status filter.
	 * @return Connection
	 */
	#[ConnectionOf( Coupon::class )]
	public function execute(
		PaginationParams $pagination,
		#[Description( 'Filter by coupon status.' )]
		?CouponStatus $status = null,
	): Connection {
		$first  = $pagination->first;
		$last   = $pagination->last;
		$after  = $pagination->after;
		$before = $pagination->before;
		$limit  = $first ?? $last ?? PaginationParams::get_default_page_size();

		// Use WP_Query for the count and a filtered query for cursor-based
		// pagination. We only need `found_posts` (which comes from the
		// SQL_CALC_FOUND_ROWS query WP runs alongside the main SELECT), so
		// the main SELECT fetches only one row — posts_per_page => -1 would
		// materialize every ID just to throw it away.
		$count_args  = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_status'    => $status?->value ?? 'any',
		);
		$count_query = new \WP_Query( $count_args );
		$total_count = $count_query->found_posts;

		// Fetch posts with cursor filtering via post__in or meta_query workaround.
		// For simplicity, we use direct ID-based filtering.
		$posts_query_args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => $limit + 1,
			'orderby'        => 'ID',
			'order'          => null !== $last ? 'DESC' : 'ASC',
			'post_status'    => $status?->value ?? 'any',
		);

		if ( null !== $after ) {
			$posts_query_args[ IdCursorFilter::AFTER_ID ] = IdCursorFilter::decode_id_cursor( $after, 'after' );
		}
		if ( null !== $before ) {
			$posts_query_args[ IdCursorFilter::BEFORE_ID ] = IdCursorFilter::decode_id_cursor( $before, 'before' );
		}
		IdCursorFilter::ensure_registered();

		$query = new \WP_Query( $posts_query_args );
		$posts = $query->posts;

		// Determine pagination.
		$has_extra = count( $posts ) > $limit;
		if ( $has_extra ) {
			$posts = array_slice( $posts, 0, $limit );
		}

		// If we fetched in DESC order for $last, reverse to get ascending order.
		if ( null !== $last ) {
			$posts = array_reverse( $posts );
		}

		// Build edges and nodes.
		$edges = array();
		$nodes = array();
		foreach ( $posts as $post ) {
			$wc_coupon = new \WC_Coupon( $post->ID );
			$coupon    = CouponMapper::from_wc_coupon( $wc_coupon );

			$edge         = new Edge();
			$edge->cursor = base64_encode( (string) $coupon->id );
			$edge->node   = $coupon;

			$edges[] = $edge;
			$nodes[] = $coupon;
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
