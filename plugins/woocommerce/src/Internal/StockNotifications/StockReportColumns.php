<?php
/**
 * Back in stock notification columns for the Analytics Stock report.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the Waiting and Days waiting columns to the Analytics Stock report.
 */
class StockReportColumns {

	/**
	 * Active notification stats for the current report page, keyed by product ID.
	 *
	 * @var array<int, array{active_signups: int, days_waiting: int}>
	 */
	private array $stats = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_analytics_orderby_enum_reports/stock', array( $this, 'add_orderby_options' ) );
		add_filter( 'posts_clauses', array( $this, 'add_orderby_clauses' ), 10, 2 );
		add_action( 'woocommerce_analytics_stock_report_products_loaded', array( $this, 'load_stats' ) );
		add_filter( 'woocommerce_report_stock_export_columns', array( $this, 'add_export_columns' ) );
		add_filter( 'woocommerce_report_stock_prepare_export_item', array( $this, 'add_export_values' ), 10, 2 );

		// Registered here, not on `rest_api_init`: the CSV export reads the report outside a REST request.
		register_rest_field(
			'report_stock',
			'active_signups',
			array(
				'get_callback' => fn( $item ) => $this->stats[ $item['id'] ]['active_signups'] ?? 0,
				'schema'       => array(
					'description' => __( 'Number of customers waiting for a back in stock notification.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
		register_rest_field(
			'report_stock',
			'days_waiting',
			array(
				'get_callback' => fn( $item ) => $this->stats[ $item['id'] ]['days_waiting'] ?? null,
				'schema'       => array(
					'description' => __( 'Days since the oldest active back in stock sign-up, or null when there are none.', 'woocommerce' ),
					'type'        => array( 'integer', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * Allow sorting the report by the new columns.
	 *
	 * @internal
	 *
	 * @param mixed $options The orderby enum.
	 * @return mixed
	 */
	public function add_orderby_options( $options ) {
		return is_array( $options ) ? array_merge( $options, array( 'active_signups', 'days_waiting' ) ) : $options;
	}

	/**
	 * Sort by the new columns.
	 *
	 * @internal
	 *
	 * @param mixed $clauses  The query clauses.
	 * @param mixed $wp_query The query.
	 * @return mixed
	 */
	public function add_orderby_clauses( $clauses, $wp_query ) {
		if ( ! is_array( $clauses ) || ! $wp_query instanceof \WP_Query ) {
			return $clauses;
		}

		global $wpdb;

		$orderby = $wp_query->get( 'orderby' );
		$order   = $wp_query->get( 'order' );
		$order   = is_string( $order ) && 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		if ( 'active_signups' === $orderby ) {
			$clauses['join']   .= $this->get_stats_join();
			$clauses['orderby'] = " COALESCE( sn.sn_count, 0 ) {$order}, {$wpdb->posts}.ID {$order} ";
		} elseif ( 'days_waiting' === $orderby ) {
			// More days waiting means an older sign-up, so the date sorts the other way. Rows without sign-ups always go last.
			$date_order         = 'ASC' === $order ? 'DESC' : 'ASC';
			$clauses['join']   .= $this->get_stats_join();
			$clauses['orderby'] = " sn.sn_oldest IS NULL, sn.sn_oldest {$date_order}, {$wpdb->posts}.ID {$order} ";
		}

		return $clauses;
	}

	/**
	 * Load the stats for a page of report products with one query.
	 *
	 * @internal
	 *
	 * @param mixed $products The products on this page.
	 */
	public function load_stats( $products ): void {
		global $wpdb;

		$this->stats = array();

		$product_ids = array_map( fn( $product ) => $product->get_id(), array_filter( (array) $products ) );
		if ( empty( $product_ids ) ) {
			return;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is a list of %d placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) AS active_signups, TIMESTAMPDIFF( DAY, MIN( date_created_gmt ), UTC_TIMESTAMP() ) AS days_waiting
				FROM {$wpdb->prefix}wc_stock_notifications
				WHERE status = %s AND product_id IN ( {$placeholders} )
				GROUP BY product_id",
				array_merge( array( NotificationStatus::ACTIVE ), $product_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( (array) $rows as $row ) {
			$this->stats[ (int) $row->product_id ] = array(
				'active_signups' => (int) $row->active_signups,
				'days_waiting'   => (int) $row->days_waiting,
			);
		}
	}

	/**
	 * Add the new columns to the CSV export.
	 *
	 * @internal
	 *
	 * @param mixed $columns Column labels keyed by ID.
	 * @return mixed
	 */
	public function add_export_columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		$columns['active_signups'] = _x( 'Waiting', 'number of customers waiting for stock', 'woocommerce' );
		$columns['days_waiting']   = __( 'Days waiting', 'woocommerce' );

		return $columns;
	}

	/**
	 * Add the new columns' values to a CSV export row.
	 *
	 * @internal
	 *
	 * @param mixed $export_item The export row.
	 * @param mixed $item        The report item.
	 * @return mixed
	 */
	public function add_export_values( $export_item, $item ) {
		if ( ! is_array( $export_item ) || ! is_array( $item ) ) {
			return $export_item;
		}

		$export_item['active_signups'] = $item['active_signups'] ?? 0;
		$export_item['days_waiting']   = $item['days_waiting'] ?? '';

		return $export_item;
	}

	/**
	 * Join the active notification count and oldest sign-up date per product, for sorting.
	 *
	 * @return string
	 */
	private function get_stats_join(): string {
		global $wpdb;

		$status = NotificationStatus::ACTIVE;

		return " LEFT JOIN (
			SELECT product_id, COUNT(*) AS sn_count, MIN( date_created_gmt ) AS sn_oldest
			FROM {$wpdb->prefix}wc_stock_notifications
			WHERE status = '{$status}'
			GROUP BY product_id
		) sn ON sn.product_id = {$wpdb->posts}.ID ";
	}
}
