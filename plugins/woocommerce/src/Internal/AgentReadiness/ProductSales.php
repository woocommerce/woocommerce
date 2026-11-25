<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Each product's net sales over the last DAYS days, read from the Analytics order
 * lookup tables, so Sales readiness can rank product problems by the sales they put
 * at stake. Variations count toward their parent product, and are also kept on their own.
 * Orders are counted as Analytics counts them, leaving out the statuses its settings exclude.
 *
 * @since 11.4.0
 */
class ProductSales {

	public const DAYS = 90;

	/**
	 * Below this many orders over DAYS, shares of sales are too noisy to rank by, so checks rank by product count.
	 */
	public const MIN_ORDERS = 20;

	/**
	 * Share of the store's orders that must be in Analytics for its sales to count as imported.
	 * New orders reach Analytics shortly after they are placed, so the last few may be missing.
	 * A store with fewer than MIN_ORDERS orders ranks by product count anyway, so it counts as imported.
	 */
	private const IMPORTED_SHARE = 0.9;

	/**
	 * How many best sellers one page of more_top_products() returns: as many as one scan checks.
	 */
	public const TOP_PRODUCTS = ProductScan::PRODUCT_LIMIT;

	/**
	 * The store's net sales, the number of orders, and whether Analytics holds the store's orders
	 * for the period, or null while Analytics is off. `since` is the period's start, for reading
	 * the same period again for some products with for_products(), and for the best sellers with
	 * more_top_products() when the scan ranks by sales. Sales per product stay in the database.
	 *
	 * @return array{days:int,since:string,orders:int,imported:bool,total:float}|null
	 */
	public function last_days(): ?array {
		global $wpdb;

		if ( 'yes' !== get_option( Analytics::TOGGLE_OPTION_NAME, 'yes' ) ) {
			return null;
		}

		// The lookup tables store dates in the site's time zone.
		$start    = new \DateTimeImmutable( '-' . self::DAYS . ' days', wp_timezone() );
		$since    = $start->format( 'Y-m-d H:i:s' );
		$excluded = self::excluded_statuses();
		$stats    = $wpdb->prefix . 'wc_order_stats';
		$not_in   = self::not_in( $excluded );

		// Count first so an Analytics import that commits between the two reads cannot make
		// the order total cover revenue that this request never saw.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table name from $wpdb, placeholders built for the excluded statuses; the scan runs this once, in the background.
		$orders = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT stats.order_id )
				FROM {$stats} AS stats
				WHERE stats.parent_id = 0 AND stats.date_created >= %s {$not_in}",
				array_merge( array( $since ), $excluded )
			)
		);
		// phpcs:enable
		$total = self::total( $since, $excluded );

		return array(
			'days'     => self::DAYS,
			'since'    => $since,
			'orders'   => $orders,
			'imported' => self::is_imported( $orders, self::store_orders( $start, $excluded ) ),
			'total'    => max( 0.0, $total ),
		);
	}

	/**
	 * Net sales of some products and their variations since a date from last_days(), so a scan
	 * in batches reads one batch's sales instead of keeping the whole store's.
	 *
	 * @param array<int, int> $product_ids Product ids.
	 * @param string          $since       Period start, as last_days() gave it.
	 * @return array{by_product:array<int,float>,by_variation:array<int,float>}
	 */
	public function for_products( array $product_ids, string $since ): array {
		return self::revenue( $since, self::excluded_statuses(), $product_ids );
	}

	/**
	 * The store's net sales since a date, refunds taken off.
	 *
	 * @param string             $since    Period start, in the site's time zone.
	 * @param array<int, string> $excluded Order statuses Analytics leaves out.
	 *
	 * @throws \RuntimeException When the sales query fails.
	 */
	private static function total( string $since, array $excluded ): float {
		global $wpdb;

		$lookup = $wpdb->prefix . 'wc_order_product_lookup';
		$stats  = $wpdb->prefix . 'wc_order_stats';
		$not_in = self::not_in( $excluded );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from $wpdb, placeholders built for the statuses; the scan reads this once, in the background.
		$wpdb->last_error = '';
		$total            = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( lookup.product_net_revenue )
				FROM {$lookup} AS lookup
				INNER JOIN {$stats} AS stats ON stats.order_id = lookup.order_id
				WHERE lookup.date_created >= %s {$not_in}",
				array_merge( array( $since ), $excluded )
			)
		);
		// phpcs:enable
		$error = self::db_error();
		if ( '' !== $error ) {
			throw new \RuntimeException( esc_html( 'Agent readiness sales query failed. ' . $error ) );
		}
		return (float) $total;
	}

	/**
	 * A page of published products by net sales, highest first. Offset 0 is the first page.
	 * A deleted or unpublished product is left out here; its revenue still counts in the total.
	 *
	 * @since 11.4.0
	 *
	 * @param string $since  Period start from last_days().
	 * @param int    $offset How many leading products to skip.
	 * @param int    $limit  How many to return.
	 * @return array<int, int>
	 *
	 * @throws \RuntimeException When the sales query fails.
	 */
	public function more_top_products( string $since, int $offset, int $limit ): array {
		if ( $offset < 0 || $limit < 1 ) {
			return array();
		}
		return self::top_products( $since, self::excluded_statuses(), $offset, $limit );
	}

	/**
	 * Ids of the published products with the most net sales since a date, highest first.
	 * A variation counts toward its parent. Products whose sales net to nothing or less are left out.
	 *
	 * @param string             $since    Period start, in the site's time zone.
	 * @param array<int, string> $excluded Order statuses Analytics leaves out.
	 * @param int                $offset   How many leading products to skip.
	 * @param int                $limit    How many to return.
	 * @return array<int, int>
	 *
	 * @throws \RuntimeException When the sales query fails.
	 */
	private static function top_products( string $since, array $excluded, int $offset = 0, int $limit = self::TOP_PRODUCTS ): array {
		global $wpdb;

		$lookup = $wpdb->prefix . 'wc_order_product_lookup';
		$stats  = $wpdb->prefix . 'wc_order_stats';
		$not_in = self::not_in( $excluded );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Table names from $wpdb, placeholders built for the statuses, limit and offset; the scan reads this in the background.
		$wpdb->last_error = '';
		$ids              = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT lookup.product_id
				FROM {$lookup} AS lookup
				INNER JOIN {$wpdb->posts} AS posts ON posts.ID = lookup.product_id AND posts.post_type = 'product' AND posts.post_status = 'publish'
				INNER JOIN {$stats} AS stats ON stats.order_id = lookup.order_id
				WHERE lookup.date_created >= %s {$not_in}
				GROUP BY lookup.product_id
				HAVING SUM( lookup.product_net_revenue ) > 0
				ORDER BY SUM( lookup.product_net_revenue ) DESC, lookup.product_id ASC
				LIMIT %d OFFSET %d",
				array_merge( array( $since ), $excluded, array( $limit, $offset ) )
			)
		);
		// phpcs:enable
		$error = self::db_error();
		if ( '' !== $error ) {
			throw new \RuntimeException( esc_html( 'Agent readiness sales query failed. ' . $error ) );
		}
		return array_map( 'intval', $ids );
	}

	/**
	 * Net sales by product and by variation since a date, for some products.
	 *
	 * @param string             $since       Period start, in the site's time zone.
	 * @param array<int, string> $excluded    Order statuses Analytics leaves out.
	 * @param array<int, int>    $product_ids Product ids.
	 * @return array{by_product:array<int,float>,by_variation:array<int,float>}
	 *
	 * @throws \RuntimeException When the sales query fails.
	 */
	private static function revenue( string $since, array $excluded, array $product_ids ): array {
		global $wpdb;

		$by_product   = array();
		$by_variation = array();
		if ( array() === $product_ids ) {
			return compact( 'by_product', 'by_variation' );
		}
		$lookup   = $wpdb->prefix . 'wc_order_product_lookup';
		$stats    = $wpdb->prefix . 'wc_order_stats';
		$not_in   = self::not_in( $excluded );
		$products = 'AND lookup.product_id IN ( ' . implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) ) . ' )';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from $wpdb, placeholders built for the statuses and product ids; the scan reads this in the background.
		$wpdb->last_error = '';
		$rows             = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT lookup.product_id, lookup.variation_id, SUM( lookup.product_net_revenue ) AS revenue
				FROM {$lookup} AS lookup
				INNER JOIN {$stats} AS stats ON stats.order_id = lookup.order_id
				WHERE lookup.date_created >= %s {$not_in} {$products}
				GROUP BY lookup.product_id, lookup.variation_id",
				array_merge( array( $since ), $excluded, array_map( 'intval', $product_ids ) )
			),
			ARRAY_A
		);
		// phpcs:enable
		$error = self::db_error();
		if ( ! is_array( $rows ) || '' !== $error ) {
			throw new \RuntimeException( esc_html( rtrim( 'Agent readiness sales query failed. ' . $error ) ) );
		}

		foreach ( $rows as $row ) {
			$product_id                = (int) $row['product_id'];
			$by_product[ $product_id ] = ( $by_product[ $product_id ] ?? 0.0 ) + (float) $row['revenue'];
			if ( (int) $row['variation_id'] > 0 ) {
				$by_variation[ (int) $row['variation_id'] ] = (float) $row['revenue'];
			}
		}
		return compact( 'by_product', 'by_variation' );
	}

	/**
	 * The status filter, with one placeholder per excluded status. Empty when a filter leaves
	 * nothing to exclude, since `NOT IN ()` is not valid SQL.
	 *
	 * @param array<int, string> $excluded Order statuses Analytics leaves out.
	 */
	private static function not_in( array $excluded ): string {
		return array() === $excluded ? '' : 'AND stats.status NOT IN ( ' . implode( ', ', array_fill( 0, count( $excluded ), '%s' ) ) . ' )';
	}

	/**
	 * Whether Analytics holds enough of the store's orders. A store with too few orders to rank by
	 * keeps the "too few orders" reason instead.
	 *
	 * @param int $in_analytics Orders in Analytics for the period.
	 * @param int $in_store     Orders the store holds for the period.
	 */
	private static function is_imported( int $in_analytics, int $in_store ): bool {
		return $in_store < self::MIN_ORDERS || $in_analytics >= self::IMPORTED_SHARE * $in_store;
	}

	/**
	 * How many orders the store holds since a date, with the statuses Analytics counts. Analytics
	 * holds fewer while its import of past orders hasn't run or finished. Child orders are left
	 * out (`parent` 0): a multivendor sub-order is not a separate order Analytics imports.
	 * WCPay test-mode orders are left out the same way OrdersScheduler skips them (meta
	 * `_wcpay_mode=test`). They are counted in a second query and subtracted, because the posts
	 * order store doesn't support `meta_query`. A custom `woocommerce_analytics_is_test_order`
	 * callback is not applied.
	 *
	 * @param \DateTimeImmutable $since    Start of the period.
	 * @param array<int, string> $excluded Statuses Analytics leaves out.
	 */
	private static function store_orders( \DateTimeImmutable $since, array $excluded ): int {
		$statuses = array_diff( array_keys( wc_get_order_statuses() ), $excluded, array( 'wc-checkout-draft' ) );
		if ( array() === $statuses ) {
			return 0;
		}
		$count = static function ( array $extra ) use ( $since, $statuses ): int {
			$orders = wc_get_orders(
				array(
					'type'         => 'shop_order',
					'parent'       => 0,
					'status'       => array_values( $statuses ),
					'date_created' => '>=' . $since->getTimestamp(),
					'limit'        => 1,
					'return'       => 'ids',
					'paginate'     => true,
				) + $extra
			);
			return is_object( $orders ) && isset( $orders->total ) ? (int) $orders->total : 0;
		};
		$all   = $count( array() );
		// Below MIN_ORDERS the import check passes anyway, so test orders can't change it.
		if ( $all < self::MIN_ORDERS ) {
			return $all;
		}
		return max(
			0,
			$all - $count(
				array(
					'meta_key'   => '_wcpay_mode', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One count for the import check.
					'meta_value' => 'test', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One count for the import check.
				)
			)
		);
	}

	/**
	 * The order statuses Analytics leaves out, as the stats table stores them: with and without the `wc-` prefix.
	 *
	 * @return array<int, string>
	 */
	private static function excluded_statuses(): array {
		$default  = Settings::get_default_excluded_order_statuses();
		$excluded = Settings::get_valid_order_statuses_or_default( get_option( 'woocommerce_excluded_report_order_statuses', $default ), $default );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Core's Analytics hook, documented in Admin\API\Reports\DataStore.
		$filtered = apply_filters( 'woocommerce_analytics_excluded_order_statuses', array_merge( array( 'auto-draft', 'trash' ), $excluded ) );
		$statuses = array();
		foreach ( is_array( $filtered ) ? $filtered : $excluded as $status ) {
			if ( is_string( $status ) && '' !== $status ) {
				// The status column holds 20 characters, so longer custom statuses are stored cut short.
				$status     = str_starts_with( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
				$statuses[] = substr( $status, 0, 20 );
				$statuses[] = substr( 'wc-' . $status, 0, 20 );
			}
		}
		return array_values( array_unique( $statuses ) );
	}

	/**
	 * The last database error, read fresh after a query.
	 *
	 * @phpstan-impure
	 */
	private static function db_error(): string {
		global $wpdb;
		return is_string( $wpdb->last_error ) ? $wpdb->last_error : '';
	}
}
