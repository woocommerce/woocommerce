<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Pagination;

/**
 * WP_Query ID-cursor pagination helper.
 *
 * Implements cursor-based pagination on the posts ID column by hooking
 * `posts_where` and reading two custom query vars:
 *
 * - `wc_api_after_id`  — emit `AND ID > X` in the SQL WHERE clause.
 * - `wc_api_before_id` — emit `AND ID < X`.
 *
 * Resolvers set whichever of those vars they need on their WP_Query args
 * and call {@see self::ensure_registered()} once before running the query.
 * The filter registers itself lazily on first use and short-circuits for
 * any query that doesn't set these vars, so it's safe to leave in place
 * for the rest of the request.
 */
class IdCursorFilter {

	/**
	 * Query var for the exclusive lower-bound ID (`ID > X`).
	 */
	public const AFTER_ID = 'wc_api_after_id';

	/**
	 * Query var for the exclusive upper-bound ID (`ID < X`).
	 */
	public const BEFORE_ID = 'wc_api_before_id';

	/**
	 * Whether the posts_where hook is currently registered for this request.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Register the posts_where filter on first call; no-op thereafter.
	 *
	 * The filter is a no-op for queries that don't set the cursor query
	 * vars, so leaving it registered for the remainder of the request is
	 * harmless — and it means resolvers never need to clean up after
	 * themselves, which is how the previous add/remove dance leaked.
	 */
	public static function ensure_registered(): void {
		if ( self::$registered ) {
			return;
		}
		add_filter( 'posts_where', array( self::class, 'apply' ), 10, 2 );
		self::$registered = true;
	}

	/**
	 * Filter callback for `posts_where`. Appends cursor conditions when the
	 * corresponding query vars are set on the WP_Query; returns the input
	 * clause unchanged otherwise.
	 *
	 * @param string    $where SQL WHERE clause being built.
	 * @param \WP_Query $query The WP_Query being prepared.
	 * @return string The modified WHERE clause.
	 */
	public static function apply( string $where, \WP_Query $query ): string {
		$after  = (int) $query->get( self::AFTER_ID );
		$before = (int) $query->get( self::BEFORE_ID );

		if ( $after <= 0 && $before <= 0 ) {
			return $where;
		}

		global $wpdb;
		if ( $after > 0 ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after );
		}
		if ( $before > 0 ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID < %d", $before );
		}
		return $where;
	}
}
