<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Each product's net sales over the last DAYS days, read from the Analytics order
 * lookup tables, so Sales readiness can rank product problems by the sales they put
 * at stake. Variations count toward their parent product. Orders are counted as
 * Analytics counts them, leaving out the statuses its settings exclude.
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
	 * Net sales by product id, their total and the number of orders, or null while Analytics is off.
	 *
	 * @return array{days:int,orders:int,total:float,by_product:array<int,float>}|null
	 */
	public function last_days(): ?array {
		global $wpdb;

		if ( 'yes' !== get_option( Analytics::TOGGLE_OPTION_NAME, 'yes' ) ) {
			return null;
		}

		// The lookup tables store dates in the site's time zone.
		$since    = ( new \DateTimeImmutable( '-' . self::DAYS . ' days', wp_timezone() ) )->format( 'Y-m-d H:i:s' );
		$excluded = self::excluded_statuses();
		// A filter may leave nothing to exclude, and `NOT IN ()` is not valid SQL.
		$not_in = array() === $excluded ? '' : 'AND stats.status NOT IN ( ' . implode( ', ', array_fill( 0, count( $excluded ), '%s' ) ) . ' )';
		$lookup = $wpdb->prefix . 'wc_order_product_lookup';
		$stats  = $wpdb->prefix . 'wc_order_stats';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names from $wpdb, placeholders built for the excluded statuses; the scan runs this once, in the background.
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT lookup.product_id, SUM( lookup.product_net_revenue ) AS revenue
				FROM {$lookup} AS lookup
				INNER JOIN {$stats} AS stats ON stats.order_id = lookup.order_id
				WHERE lookup.date_created >= %s {$not_in}
				GROUP BY lookup.product_id",
				array_merge( array( $since ), $excluded )
			),
			ARRAY_A
		);
		$orders = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT stats.order_id )
				FROM {$stats} AS stats
				WHERE stats.parent_id = 0 AND stats.date_created >= %s {$not_in}",
				array_merge( array( $since ), $excluded )
			)
		);
		// phpcs:enable

		$by_product = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$by_product[ (int) $row['product_id'] ] = (float) $row['revenue'];
		}
		return array(
			'days'       => self::DAYS,
			'orders'     => $orders,
			'total'      => max( 0.0, array_sum( $by_product ) ),
			'by_product' => $by_product,
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
}
