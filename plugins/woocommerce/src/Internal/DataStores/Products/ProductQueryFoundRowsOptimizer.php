<?php
/**
 * ProductQueryFoundRowsOptimizer class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataStores\Products;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
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
final class ProductQueryFoundRowsOptimizer {

	/**
	 * Feature flag id registered in FeaturesController; the master on/off switch for this optimization.
	 */
	public const FEATURE_NAME = 'product_query_separate_count';

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
	 * Hook closures registered by attach(), as hook name => array( priority, closure ), so detach()
	 * can remove exactly what was added.
	 *
	 * @var array
	 */
	private array $hooks = array();

	/**
	 * Constructor.
	 *
	 * @param WP_Query $query The product query to compute the pagination total for.
	 */
	public function __construct( WP_Query $query ) {
		$this->query = $query;
	}

	/**
	 * Whether the optimization should run for this query: the feature flag must be on and the
	 * database server supported, with a filter as the final override.
	 *
	 * @return bool True when the separate COUNT should replace SQL_CALC_FOUND_ROWS.
	 */
	public function is_enabled(): bool {
		$enabled = FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) && self::is_supported();

		/**
		 * Filters whether the main product query computes its total with a separate COUNT query instead
		 * of MySQL's SQL_CALC_FOUND_ROWS. Return false to keep WordPress' default behaviour.
		 *
		 * @since 11.0.0
		 * @param bool     $enabled Whether to use a separate COUNT query. Defaults to the
		 *                          'product_query_separate_count' feature being enabled on a supported
		 *                          server (MySQL 8.0+); false on MariaDB / MySQL < 8.0.
		 * @param WP_Query $query   The product query.
		 */
		return (bool) apply_filters( 'woocommerce_product_query_use_separate_count_query', $enabled, $this->query );
	}

	/**
	 * Memoized is_supported() result for the current request. NULL until first computed.
	 *
	 * @var bool|null
	 */
	private static ?bool $is_supported = null;

	/**
	 * Whether the separate-COUNT rewrite is worth using on the live database.
	 *
	 * It only wins on MySQL 8.0+. MariaDB (all versions) and MySQL < 8.0 regress: the plain SELECT trades
	 * the single full scan for a non-covering index range scan (a heap fetch per matched row). Gate on the
	 * running server so those engines keep SQL_CALC_FOUND_ROWS.
	 *
	 * Uses wc_get_server_database_version() (a SELECT VERSION() probe) rather than db_server_info():
	 * behind a proxy such as ProxySQL or MaxScale the latter reports the proxy's handshake version, not
	 * the actual backend server. The result is memoized so that probe runs at most once per request,
	 * even when several product queries render on one page.
	 *
	 * @return bool True when the server is MySQL 8.0 or newer.
	 */
	public static function is_supported(): bool {
		if ( null === self::$is_supported ) {
			$db_version         = wc_get_server_database_version();
			self::$is_supported = false === stripos( (string) $db_version['string'], 'mariadb' )
				&& version_compare( (string) $db_version['number'], '8.0', '>=' );
		}
		return self::$is_supported;
	}

	/**
	 * Attach the filters that swap SQL_CALC_FOUND_ROWS for a separate COUNT on the product query.
	 * The clause capture runs at priority 999 so it sees the clauses after every other filter
	 * (price, layered nav, ordering) and the COUNT matches the main query exactly.
	 */
	public function attach(): void {
		$this->found_posts_clauses = null;
		$this->hooks               = array(
			'posts_clauses'     => array( 999, fn( $clauses, $wp_query ) => $this->capture_product_query_clauses( $clauses, $wp_query ) ),
			'posts_request'     => array( 10, fn( $request, $wp_query ) => $this->remove_product_query_found_rows( $request, $wp_query ) ),
			'found_posts_query' => array( 10, fn( $found_posts_query, $wp_query ) => $this->product_query_found_posts_query( $found_posts_query, $wp_query ) ),
		);
		foreach ( $this->hooks as $hook => list( $priority, $closure ) ) {
			add_filter( $hook, $closure, $priority, 2 );
		}
	}

	/**
	 * Detach the filters attached by attach(). Called once the product loop is done.
	 */
	public function detach(): void {
		foreach ( $this->hooks as $hook => list( $priority, $closure ) ) {
			remove_filter( $hook, $closure, $priority );
		}
		$this->hooks = array();
	}

	/**
	 * Capture the product query's final JOIN/WHERE clauses for the separate COUNT.
	 *
	 * @param array    $clauses  The product query clauses.
	 * @param WP_Query $wp_query The current product query.
	 * @return array The clauses, unchanged.
	 */
	private function capture_product_query_clauses( $clauses, $wp_query ) {
		if ( $wp_query === $this->query ) {
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
	 */
	private function remove_product_query_found_rows( $request, $wp_query ) {
		if ( null !== $this->found_posts_clauses && $wp_query === $this->query ) {
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
	 */
	private function product_query_found_posts_query( $found_posts_query, $wp_query ) {
		global $wpdb;

		if ( null !== $this->found_posts_clauses && $wp_query === $this->query ) {
			$join  = $this->found_posts_clauses['join'];
			$where = $this->found_posts_clauses['where'];

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $join and $where are the same WordPress-built clauses already used for the main query; they are not re-interpolated here.
			$found_posts_query = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) FROM {$wpdb->posts} {$join} WHERE 1=1 {$where}";

			$this->found_posts_clauses = null;
		}

		return $found_posts_query;
	}
}
