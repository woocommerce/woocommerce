<?php
/**
 * ProductQuerySeparateCountQuery class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Products;

use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Computes the product-archive pagination total with a dedicated COUNT instead of MySQL's
 * SQL_CALC_FOUND_ROWS, which forces a full materialize+sort of the matched set just to count it
 * (roughly doubling archive cost on large catalogs; also deprecated since MySQL 8.0.17).
 *
 * Keeps no_found_rows = false but supplies the total by capturing the query's final JOIN/WHERE,
 * stripping SQL_CALC_FOUND_ROWS, and answering the found-rows lookup with COUNT( DISTINCT ID ) over the
 * same clauses (so price and layered-nav filters are reflected). WC_Query wires one instance per product
 * query and attaches/detaches it around the loop; identity against the captured query scopes the filters.
 */
class ProductQuerySeparateCountQuery {

	/**
	 * The product query whose pagination total this instance computes.
	 *
	 * @var WP_Query
	 */
	private WP_Query $query;

	/**
	 * Captured JOIN and WHERE clauses of the product query, used to build the separate COUNT that
	 * replaces SQL_CALC_FOUND_ROWS. NULL until the clauses have been captured.
	 *
	 * @var array|null
	 */
	private ?array $found_posts_clauses = null;

	/**
	 * Constructor.
	 *
	 * @param WP_Query $query The product query to compute the pagination total for.
	 */
	public function __construct( WP_Query $query ) {
		$this->query = $query;
	}

	/**
	 * Attach the filters that swap SQL_CALC_FOUND_ROWS for a separate COUNT on the product query.
	 */
	public function attach(): void {
		$this->found_posts_clauses = null;
		add_filter( 'posts_clauses', array( $this, 'capture_product_query_clauses' ), 999, 2 );
		add_filter( 'posts_request', array( $this, 'remove_product_query_found_rows' ), 10, 2 );
		add_filter( 'found_posts_query', array( $this, 'product_query_found_posts_query' ), 10, 2 );
	}

	/**
	 * Detach the filters attached by attach(). Called once the product loop is done.
	 */
	public function detach(): void {
		remove_filter( 'posts_clauses', array( $this, 'capture_product_query_clauses' ), 999 );
		remove_filter( 'posts_request', array( $this, 'remove_product_query_found_rows' ), 10 );
		remove_filter( 'found_posts_query', array( $this, 'product_query_found_posts_query' ), 10 );
	}

	/**
	 * Whether the query being filtered is the exact product query this instance was built for.
	 *
	 * @param mixed $wp_query The query passed by the current filter.
	 * @return bool
	 */
	private function is_target_query( $wp_query ): bool {
		return $wp_query instanceof WP_Query && $wp_query === $this->query;
	}

	/**
	 * Capture the product query's final JOIN/WHERE clauses for the separate COUNT. Runs at a late
	 * priority so it sees the clauses after every other filter (price, layered-nav, ordering), ensuring
	 * the COUNT matches exactly the rows the main query returns.
	 *
	 * @param array    $clauses  The product query clauses.
	 * @param WP_Query $wp_query The current product query.
	 * @return array The clauses, unchanged.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function capture_product_query_clauses( $clauses, $wp_query ) {
		if ( $this->is_target_query( $wp_query ) ) {
			$this->found_posts_clauses = array(
				'join'  => $clauses['join'],
				'where' => $clauses['where'],
			);
		}

		return $clauses;
	}

	/**
	 * Remove SQL_CALC_FOUND_ROWS from the product query request. The total row count is supplied
	 * separately by product_query_found_posts_query().
	 *
	 * @param string   $request  The complete SQL query.
	 * @param WP_Query $wp_query The current product query.
	 * @return string The query without SQL_CALC_FOUND_ROWS.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function remove_product_query_found_rows( $request, $wp_query ) {
		if ( is_array( $this->found_posts_clauses ) && $this->is_target_query( $wp_query ) ) {
			$stripped = preg_replace( '/^(\s*SELECT\s+)SQL_CALC_FOUND_ROWS\s+/i', '$1', $request, 1 );

			// Guard against a preg_replace error (null); keep the original request if so.
			if ( null !== $stripped ) {
				$request = $stripped;
			}
		}

		return $request;
	}

	/**
	 * Replace WordPress' FOUND_ROWS() lookup with a dedicated COUNT over the product query's JOIN/WHERE.
	 * COUNT( DISTINCT ID ) because the layered-nav attribute join can otherwise double-count rows (the
	 * main query's GROUP BY is intentionally kept).
	 *
	 * @param string   $found_posts_query The query used to retrieve the found post count.
	 * @param WP_Query $wp_query          The current product query.
	 * @return string The replacement COUNT query, or the original when not applicable.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function product_query_found_posts_query( $found_posts_query, $wp_query ) {
		global $wpdb;

		if ( is_array( $this->found_posts_clauses ) && $this->is_target_query( $wp_query ) ) {
			$join  = $this->found_posts_clauses['join'];
			$where = $this->found_posts_clauses['where'];

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $join and $where are the same WordPress-built clauses already used for the main query; they are not re-interpolated here.
			$found_posts_query = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) FROM {$wpdb->posts} {$join} WHERE 1=1 {$where}";

			$this->found_posts_clauses = null;
		}

		return $found_posts_query;
	}
}
