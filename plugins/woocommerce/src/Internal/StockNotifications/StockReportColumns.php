<?php
/**
 * Back in stock notification columns for the Analytics Stock report.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the Waiting for stock column to the Analytics Stock report.
 */
class StockReportColumns {

	/**
	 * Active sign-up counts for the current report page, keyed by product ID.
	 *
	 * @var array<int, int>
	 */
	private array $counts = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_analytics_orderby_enum_reports/stock', array( $this, 'add_orderby_options' ) );
		add_filter( 'woocommerce_analytics_clauses_join_stock', array( $this, 'add_orderby_join' ), 10, 2 );
		add_filter( 'woocommerce_analytics_clauses_order_by_stock', array( $this, 'add_orderby_clauses' ), 10, 2 );
		add_action( 'woocommerce_analytics_stock_report_products_loaded', array( $this, 'load_counts' ), 10, 2 );
		add_filter( 'woocommerce_report_stock_export_columns', array( $this, 'add_export_columns' ) );
		add_filter( 'woocommerce_report_stock_prepare_export_item', array( $this, 'add_export_values' ), 10, 2 );

		// Registered here, not on `rest_api_init`: the CSV export reads the report outside a REST request.
		register_rest_field(
			'report_stock',
			'active_signups',
			array(
				'get_callback' => fn( $item ) => $this->counts[ $item['id'] ] ?? 0,
				'schema'       => array(
					'description' => __( 'Number of customers waiting for a back in stock notification.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * Allow sorting the report by the new column.
	 *
	 * @internal
	 *
	 * @param mixed $options The orderby enum.
	 * @return mixed
	 */
	public function add_orderby_options( $options ) {
		return is_array( $options ) ? array_merge( $options, array( 'active_signups' ) ) : $options;
	}

	/**
	 * Join the active sign-up counts when the stock report is sorted by them.
	 *
	 * @internal
	 *
	 * @param mixed $clauses  The JOIN clauses.
	 * @param mixed $wp_query The stock report query.
	 * @return mixed
	 */
	public function add_orderby_join( $clauses, $wp_query = null ) {
		if ( ! is_array( $clauses ) || ! $this->is_sorted_by_signups( $wp_query ) ) {
			return $clauses;
		}

		// Sign-up changes don't bust WP_Query's cached post IDs, so a cached order would go stale.
		$wp_query->set( 'cache_results', false );

		$clauses[] = $this->get_counts_join();

		return $clauses;
	}

	/**
	 * Sort the stock report by active sign-ups.
	 *
	 * @internal
	 *
	 * @param mixed $clauses  The ORDER BY clauses.
	 * @param mixed $wp_query The stock report query.
	 * @return mixed
	 */
	public function add_orderby_clauses( $clauses, $wp_query = null ) {
		if ( ! is_array( $clauses ) || ! $this->is_sorted_by_signups( $wp_query ) ) {
			return $clauses;
		}

		global $wpdb;

		$order = $wp_query->get( 'order' );
		$order = is_string( $order ) && 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		return array( " COALESCE( sn.sn_count, 0 ) {$order}, {$wpdb->posts}.ID {$order} " );
	}

	/**
	 * Load the active sign-up counts for a page of report products with one query.
	 *
	 * @internal
	 *
	 * @param mixed $request  The report request.
	 * @param mixed $products The products on this page.
	 */
	public function load_counts( $request, $products ): void {
		global $wpdb;

		$this->counts = array();

		$product_ids = array_map( fn( $product ) => $product->get_id(), array_filter( is_array( $products ) ? $products : array(), fn( $product ) => $product instanceof \WC_Product ) );
		if ( empty( $product_ids ) ) {
			return;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders is a list of %d placeholders.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, COUNT(*) AS active_signups
				FROM {$wpdb->prefix}wc_stock_notifications
				WHERE status = %s AND product_id IN ( {$placeholders} )
				GROUP BY product_id",
				array_merge( array( NotificationStatus::ACTIVE ), $product_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( (array) $rows as $row ) {
			$this->counts[ (int) $row->product_id ] = (int) $row->active_signups;
		}
	}

	/**
	 * Add the new column to the CSV export.
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

		$columns['active_signups'] = __( 'Waiting for stock', 'woocommerce' );

		return $columns;
	}

	/**
	 * Add the new column's value to a CSV export row.
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

		return $export_item;
	}

	/**
	 * Whether the stock report query is sorted by active sign-ups.
	 *
	 * @param mixed $wp_query The stock report query.
	 * @return bool
	 */
	private function is_sorted_by_signups( $wp_query ): bool {
		return $wp_query instanceof \WP_Query && 'active_signups' === $wp_query->get( 'orderby' );
	}

	/**
	 * Join the active sign-up count per product, for sorting.
	 *
	 * @return string
	 */
	private function get_counts_join(): string {
		global $wpdb;

		$status = NotificationStatus::ACTIVE;

		return " LEFT JOIN (
			SELECT product_id, COUNT(*) AS sn_count
			FROM {$wpdb->prefix}wc_stock_notifications
			WHERE status = '{$status}'
			GROUP BY product_id
		) sn ON sn.product_id = {$wpdb->posts}.ID ";
	}
}
