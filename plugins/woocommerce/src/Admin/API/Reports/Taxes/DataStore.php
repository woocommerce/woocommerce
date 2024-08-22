<?php
/**
 * API\Reports\Taxes\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;

/**
 * API\Reports\Taxes\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @override ReportsDataStore::$table_name
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_order_tax_lookup';

	/**
	 * Cache identifier.
	 *
	 * @override ReportsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'taxes';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override ReportsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'tax_rate_id'  => 'intval',
		'name'         => 'strval',
		'tax_rate'     => 'floatval',
		'country'      => 'strval',
		'state'        => 'strval',
		'priority'     => 'intval',
		'total_tax'    => 'floatval',
		'order_tax'    => 'floatval',
		'shipping_tax' => 'floatval',
		'orders_count' => 'intval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override ReportsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'taxes';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override ReportsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		global $wpdb;
		$table_name = self::get_db_table_name();

		// Using wp_woocommerce_tax_rates table limits the result to only the existing tax rates and
		// omits the historical records which differs from the purpose of wp_wc_order_tax_lookup table.
		// So in order to get the same data present in wp_woocommerce_tax_rates without breaking the
		// API contract the values are now retrieved from wp_woocommerce_order_items and wp_woocommerce_order_itemmeta.
		// And given that country, state and priority are not separate columns within the woocommerce_order_items,
		// a split to order_item_name column value is required to separate those values. This is not ideal,
		// but given this query is paginated and cached, then it is not a big deal. There is always room for
		// improvements here.
		$this->report_columns = array(
			'tax_rate_id'  => "{$table_name}.tax_rate_id",
			'name'         => "SUBSTRING_INDEX(SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',-2), '-', 1) as name",
			'tax_rate'     => "CAST({$wpdb->prefix}woocommerce_order_itemmeta.meta_value AS DECIMAL(7,4)) as tax_rate",
			'country'      => "SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',1) as country",
			'state'        => "SUBSTRING_INDEX(SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',-3), '-', 1) as state",
			'priority'     => "SUBSTRING_INDEX({$wpdb->prefix}woocommerce_order_items.order_item_name,'-',-1) as priority",
			'total_tax'    => 'SUM(total_tax) as total_tax',
			'order_tax'    => 'SUM(order_tax) as order_tax',
			'shipping_tax' => 'SUM(shipping_tax) as shipping_tax',
			'orders_count' => "COUNT( DISTINCT ( CASE WHEN parent_id = 0 THEN {$table_name}.order_id END ) ) as orders_count",
		);
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_analytics_delete_order_stats', array( __CLASS__, 'sync_on_order_delete' ), 15 );
	}

	/**
	 * Fills FROM clause of SQL request based on user supplied parameters.
	 *
	 * @param array  $query_args          Query arguments supplied by the user.
	 * @param string $order_status_filter Order status subquery.
	 */
	protected function add_from_sql_params( $query_args, $order_status_filter ) {
		global $wpdb;
		$table_name = self::get_db_table_name();

		if ( $order_status_filter ) {
			$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id" );
		}

		$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_order_items ON {$table_name}.order_id = {$wpdb->prefix}woocommerce_order_items.order_id AND {$wpdb->prefix}woocommerce_order_items.order_item_type = 'tax'" );
		$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id AND {$wpdb->prefix}woocommerce_order_itemmeta.meta_key = 'rate_percent'" );
	}

	/**
	 * Updates the database query with parameters used for Taxes report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function add_sql_query_params( $query_args ) {
		global $wpdb;

		$order_tax_lookup_table = self::get_db_table_name();

		$this->add_time_period_sql_params( $query_args, $order_tax_lookup_table );
		$this->get_limit_sql_params( $query_args );
		$this->add_order_by_sql_params( $query_args );
		$order_status_filter = $this->get_status_subquery( $query_args );
		$this->add_from_sql_params( $query_args, $order_status_filter );

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$allowed_taxes = self::get_filtered_ids( $query_args, 'taxes' );
			$this->subquery->add_sql_clause( 'where', "AND {$order_tax_lookup_table}.tax_rate_id IN ({$allowed_taxes})" );
		}

		if ( $order_status_filter ) {
			$this->subquery->add_sql_clause( 'where', "AND ( {$order_status_filter} )" );
		}
	}

	/**
	 * Get the default query arguments to be used by get_data().
	 * These defaults are only partially applied when used via REST API, as that has its own defaults.
	 *
	 * @override ReportsDataStore::get_default_query_vars()
	 *
	 * @return array Query parameters.
	 */
	public function get_default_query_vars() {
		$defaults            = parent::get_default_query_vars();
		$defaults['orderby'] = 'tax_rate_id';
		$defaults['taxes']   = array();

		return $defaults;
	}

	/**
	 * Returns the report data based on normalized parameters.
	 * Will be called by `get_data` if there is no data in cache.
	 *
	 * @override ReportsDataStore::get_noncached_data()
	 *
	 * @see get_data
	 * @param array $query_args Query parameters.
	 * @return stdClass|WP_Error Data object `{ totals: *, intervals: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_data( $query_args ) {
		global $wpdb;

		$this->initialize_queries();

		$data = (object) array(
			'data'    => array(),
			'total'   => 0,
			'pages'   => 0,
			'page_no' => 0,
		);

		$this->add_sql_query_params( $query_args );
		$params = $this->get_limit_params( $query_args );

		if ( isset( $query_args['taxes'] ) && is_array( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$total_results = count( $query_args['taxes'] );
			$total_pages   = (int) ceil( $total_results / $params['per_page'] );
		} else {
			$db_records_count = (int) $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- cache ok, DB call ok, unprepared SQL ok.
				"SELECT COUNT(*) FROM ( {$this->subquery->get_query_statement()} ) AS tt"
			);

			$total_results = $db_records_count;
			$total_pages   = (int) ceil( $db_records_count / $params['per_page'] );

			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}
		}

		$this->subquery->clear_sql_clause( 'select' );
		$this->subquery->add_sql_clause( 'select', $this->selected_columns( $query_args ) );
		$this->subquery->add_sql_clause( 'group_by', ", {$wpdb->prefix}woocommerce_order_items.order_item_name, {$wpdb->prefix}woocommerce_order_itemmeta.meta_value" );
		$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
		$this->subquery->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );

		$taxes_query = $this->subquery->get_query_statement();

		$tax_data = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- cache ok, DB call ok, unprepared SQL ok.
			$taxes_query,
			ARRAY_A
		);

		if ( null === $tax_data ) {
			return $data;
		}

		$tax_data = array_map( array( $this, 'cast_numbers' ), $tax_data );
		$data     = (object) array(
			'data'    => $tax_data,
			'total'   => $total_results,
			'pages'   => $total_pages,
			'page_no' => (int) $query_args['page'],
		);

		return $data;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @override ReportsDataStore::normalize_order_by()
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		global $wpdb;

		if ( 'tax_code' === $order_by ) {
			return "{$wpdb->prefix}woocommerce_order_items.order_item_name";
		} elseif ( 'rate' === $order_by ) {
			return 'tax_rate';
		}

		return $order_by;
	}

	/**
	 * Create or update an entry in the wc_order_tax_lookup table for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return int|bool Returns -1 if order won't be processed, or a boolean indicating processing success.
	 */
	public static function sync_order_taxes( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return -1;
		}

		$tax_items   = $order->get_items( 'tax' );
		$num_updated = 0;

		foreach ( $tax_items as $tax_item ) {
			$result = $wpdb->replace(
				self::get_db_table_name(),
				array(
					'order_id'     => $order->get_id(),
					'date_created' => $order->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format ),
					'tax_rate_id'  => $tax_item->get_rate_id(),
					'shipping_tax' => $tax_item->get_shipping_tax_total(),
					'order_tax'    => $tax_item->get_tax_total(),
					'total_tax'    => (float) $tax_item->get_tax_total() + (float) $tax_item->get_shipping_tax_total(),
				),
				array(
					'%d',
					'%s',
					'%d',
					'%f',
					'%f',
					'%f',
				)
			);

			/**
			 * Fires when tax's reports are updated.
			 *
			 * @param int $tax_rate_id Tax Rate ID.
			 * @param int $order_id    Order ID.
			 */
			do_action( 'woocommerce_analytics_update_tax', $tax_item->get_rate_id(), $order->get_id() );

			// Sum the rows affected. Using REPLACE can affect 2 rows if the row already exists.
			$num_updated += 2 === intval( $result ) ? 1 : intval( $result );
		}

		return ( count( $tax_items ) === $num_updated );
	}

	/**
	 * Clean taxes data when an order is deleted.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function sync_on_order_delete( $order_id ) {
		global $wpdb;

		$wpdb->delete( self::get_db_table_name(), array( 'order_id' => $order_id ) );

		/**
		 * Fires when tax's reports are removed from database.
		 *
		 * @param int $tax_rate_id Tax Rate ID.
		 * @param int $order_id    Order ID.
		 */
		do_action( 'woocommerce_analytics_delete_tax', 0, $order_id );

		ReportsCache::invalidate();
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		$this->subquery = new SqlQuery( $this->context . '_subquery' );
		$this->subquery->add_sql_clause( 'select', self::get_db_table_name() . '.tax_rate_id' );
		$this->subquery->add_sql_clause( 'from', self::get_db_table_name() );
		$this->subquery->add_sql_clause( 'group_by', self::get_db_table_name() . '.tax_rate_id' );
	}
}
