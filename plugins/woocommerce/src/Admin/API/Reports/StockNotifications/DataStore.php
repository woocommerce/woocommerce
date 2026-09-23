<?php
/**
 * API\Reports\StockNotifications\DataStore class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\API\Reports\StockNotifications;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

/**
 * Stock notifications report, one row per product.
 *
 * Metrics, per product:
 * - signups: rows created in the period, excluding pending ones. Pending rows are unconfirmed
 *   double opt-in and get purged by DataRetentionController, so counting them would shrink history.
 *   Cancelled rows still count on their creation date.
 * - customers: distinct emails behind `signups`.
 * - active_signups: rows currently active, whatever the period. Includes products already back in
 *   stock that the processor has not handled yet.
 * - days_waiting: days since the oldest active sign-up was created, 0 when there is none.
 *
 * The product is the one the customer signed up for, so a variation is its own row.
 *
 * The table stores UTC dates while the base class assumes site-time columns, so the date handling
 * below converts at query time. Intervals use the current UTC offset, which mis-buckets the hour
 * around a DST switch. Accepted for sign-up counts; the fix would be local-time columns, like wc_order_stats.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Stands in for the period condition in report columns until the query period is known.
	 */
	protected const PERIOD_PLACEHOLDER = '{period}';

	/**
	 * Table used to get the data.
	 *
	 * @override ReportsDataStore::$table_name
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_stock_notifications';

	/**
	 * Cache identifier.
	 *
	 * @override ReportsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'stock_notifications';

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override ReportsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'stock_notifications';

	/**
	 * Date field name.
	 *
	 * @override ReportsDataStore::$date_column_name
	 *
	 * @var string
	 */
	protected $date_column_name = 'date_created_gmt';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override ReportsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'product_id'     => 'intval',
		'signups'        => 'intval',
		'customers'      => 'intval',
		'active_signups' => 'intval',
		'days_waiting'   => 'intval',
	);

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * The row set also holds active sign-ups from outside the period, so `signups` and `customers`
	 * repeat the period check inside their CASE.
	 *
	 * @override ReportsDataStore::assign_report_columns()
	 *
	 * @return void
	 */
	protected function assign_report_columns() {
		global $wpdb;

		$table_name = self::get_db_table_name();
		$period     = self::PERIOD_PLACEHOLDER;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and placeholder are internal.
		$this->report_columns = array(
			'product_id'     => "{$table_name}.product_id",
			'signups'        => $wpdb->prepare( "SUM( CASE WHEN {$period} AND {$table_name}.status <> %s THEN 1 ELSE 0 END ) AS signups", NotificationStatus::PENDING ),
			'customers'      => $wpdb->prepare( "COUNT( DISTINCT CASE WHEN {$period} AND {$table_name}.status <> %s THEN {$table_name}.user_email END ) AS customers", NotificationStatus::PENDING ),
			'active_signups' => $wpdb->prepare( "SUM( CASE WHEN {$table_name}.status = %s THEN 1 ELSE 0 END ) AS active_signups", NotificationStatus::ACTIVE ),
			'days_waiting'   => $wpdb->prepare( "COALESCE( DATEDIFF( UTC_TIMESTAMP(), MIN( CASE WHEN {$table_name}.status = %s THEN {$table_name}.date_created_gmt END ) ), 0 ) AS days_waiting", NotificationStatus::ACTIVE ),
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Returns the selected columns, with the query period filled in.
	 *
	 * @override ReportsDataStore::selected_columns()
	 *
	 * @param array $query_args User-supplied options.
	 * @return string
	 */
	protected function selected_columns( $query_args ) {
		return str_replace( self::PERIOD_PLACEHOLDER, $this->get_period_condition( $query_args ), parent::selected_columns( $query_args ) );
	}

	/**
	 * Returns the SQL condition matching rows created in the query period.
	 *
	 * @param array $query_args Query parameters.
	 * @return string
	 */
	protected function get_period_condition( $query_args ) {
		global $wpdb;

		$column     = self::get_db_table_name() . '.`' . $this->date_column_name . '`';
		$conditions = array();

		if ( ! empty( $query_args['after'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- column name is internal.
			$conditions[] = $wpdb->prepare( "{$column} >= %s", $this->get_utc_datetime_string( $query_args['after'] ) );
		}
		if ( ! empty( $query_args['before'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- column name is internal.
			$conditions[] = $wpdb->prepare( "{$column} <= %s", $this->get_utc_datetime_string( $query_args['before'] ) );
		}

		return $conditions ? '( ' . implode( ' AND ', $conditions ) . ' )' : '1=1';
	}

	/**
	 * Formats a site-time date as a UTC SQL datetime string.
	 *
	 * @param \DateTime $datetime Date in the site timezone.
	 * @return string
	 */
	protected function get_utc_datetime_string( $datetime ) {
		$utc = clone $datetime;
		$utc->setTimezone( new \DateTimeZone( 'UTC' ) );
		return $utc->format( TimeInterval::$sql_datetime_format );
	}

	/**
	 * Returns SQL that shifts a UTC date column to site time, using the current UTC offset.
	 *
	 * @param string $table_name Table name.
	 * @param string $column     Date column name.
	 * @return string
	 */
	protected function get_local_datetime_sql( $table_name, $column ) {
		$offset = (int) wc_timezone_offset();
		return "DATE_ADD( {$table_name}.`{$column}`, INTERVAL {$offset} SECOND )";
	}

	/**
	 * Returns the interval SELECT expression for a UTC date column, bucketed in site time.
	 *
	 * Reuses TimeInterval::db_datetime_format() so the ids stay in sync with TimeInterval::time_interval_id().
	 *
	 * @param string $interval   Time interval, e.g. day, week, month.
	 * @param string $table_name Table name.
	 * @param string $column     Date column name.
	 * @return string
	 */
	protected function get_interval_sql( $interval, $table_name, $column ) {
		return str_replace(
			"{$table_name}.`{$column}`",
			$this->get_local_datetime_sql( $table_name, $column ),
			TimeInterval::db_datetime_format( $interval, $table_name, $column )
		);
	}

	/**
	 * Fills the date WHERE clauses, converting the site-time period to UTC.
	 *
	 * @override ReportsDataStore::add_time_period_sql_params()
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return void
	 */
	protected function add_time_period_sql_params( $query_args, $table_name ) {
		foreach ( array( 'after', 'before' ) as $key ) {
			if ( isset( $query_args[ $key ] ) && $query_args[ $key ] instanceof \DateTime ) {
				$query_args[ $key ] = clone $query_args[ $key ];
				$query_args[ $key ]->setTimezone( new \DateTimeZone( 'UTC' ) );
			}
		}

		parent::add_time_period_sql_params( $query_args, $table_name );
	}

	/**
	 * Fills the interval SELECT and date WHERE clauses, bucketing UTC dates in site time.
	 *
	 * @override ReportsDataStore::add_intervals_sql_params()
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return void
	 */
	protected function add_intervals_sql_params( $query_args, $table_name ) {
		$this->clear_sql_clause( array( 'from', 'where_time', 'where' ) );
		$this->add_time_period_sql_params( $query_args, $table_name );

		if ( ! empty( $query_args['interval'] ) ) {
			$this->clear_sql_clause( 'select' );
			$this->add_sql_clause( 'select', $this->get_interval_sql( $query_args['interval'], $table_name, $this->date_column_name ) );
		}
	}

	/**
	 * Updates the interval query for pagination, converting the page's date range to UTC.
	 *
	 * The parent writes site-time bounds into the interval query when ordering by date.
	 * `adj_after` and `adj_before` stay in site time for fill_in_missing_intervals().
	 *
	 * @override ReportsDataStore::update_intervals_sql_params()
	 *
	 * @param array  $query_args              Query arguments.
	 * @param int    $db_interval_count       Database interval count.
	 * @param int    $expected_interval_count Expected interval count on the output.
	 * @param string $table_name              Name of the db table relevant for the date constraint.
	 * @return void
	 */
	protected function update_intervals_sql_params( &$query_args, $db_interval_count, $expected_interval_count, $table_name ) {
		global $wpdb;

		parent::update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );

		if ( $db_interval_count === $expected_interval_count || 'date' !== strtolower( $query_args['orderby'] ) ) {
			return;
		}

		$column = "{$table_name}.`{$this->date_column_name}`";
		$this->interval_query->clear_sql_clause( 'where_time' );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- column name is internal.
		$this->interval_query->add_sql_clause( 'where_time', $wpdb->prepare( "AND {$column} <= %s", $this->get_utc_datetime_string( $query_args['adj_before'] ) ) );
		$this->interval_query->add_sql_clause( 'where_time', $wpdb->prepare( "AND {$column} >= %s", $this->get_utc_datetime_string( $query_args['adj_after'] ) ) );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Returns the cache key, including a version bumped on every stock notification write.
	 *
	 * @override ReportsDataStore::get_cache_key()
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return parent::get_cache_key( $params ) . '_' . (int) get_option( StockNotificationsDataStore::REPORTS_VERSION_OPTION, 0 );
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
		$defaults                     = parent::get_default_query_vars();
		$defaults['per_page']         = 25;
		$defaults['orderby']          = 'active_signups';
		$defaults['order']            = 'desc';
		$defaults['product_includes'] = array();
		$defaults['extended_info']    = false;

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
	 * @return \stdClass|\WP_Error Data object `{ data: array, total: int, pages: int, page_no: int }`, or error.
	 */
	public function get_noncached_data( $query_args ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		$this->initialize_queries();

		$data = (object) array(
			'data'    => array(),
			'total'   => 0,
			'pages'   => 0,
			'page_no' => 0,
		);

		$limit_params = $this->get_limit_sql_params( $query_args );
		$period       = $this->get_period_condition( $query_args );

		// Products with a sign-up in the period, or anyone still waiting.
		$this->subquery->add_sql_clause(
			'where',
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name and period condition are internal.
				"AND ( ( {$period} AND {$table_name}.status <> %s ) OR {$table_name}.status = %s )",
				NotificationStatus::PENDING,
				NotificationStatus::ACTIVE
			)
		);

		$included_products = $this->get_filtered_ids( $query_args, 'product_includes' );
		if ( $included_products ) {
			$this->subquery->add_sql_clause( 'where', "AND {$table_name}.product_id IN ({$included_products})" );
		}

		$this->subquery->add_sql_clause( 'select', "{$table_name}.product_id" );
		$count_query = "SELECT COUNT(*) FROM ( {$this->subquery->get_query_statement()} ) AS tt";

		$total_results = (int) $wpdb->get_var(
			$count_query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built from internal clauses.
		);
		$total_pages   = (int) ceil( $total_results / $limit_params['per_page'] );
		if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
			return $data;
		}

		$this->subquery->clear_sql_clause( 'select' );
		$this->subquery->add_sql_clause( 'select', $this->selected_columns( $query_args ) );
		$this->add_order_by_clause( $query_args, $this->subquery );
		$this->add_orderby_order_clause( $query_args, $this->subquery );
		if ( 'product_id' !== $query_args['orderby'] ) {
			// Stable paging when the sort column ties.
			$this->subquery->add_sql_clause( 'order_by', ", {$table_name}.product_id ASC" );
		}
		$this->subquery->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );

		$rows = $wpdb->get_results(
			$this->subquery->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built from internal clauses.
			ARRAY_A
		);
		if ( null === $rows ) {
			return $data;
		}

		$this->include_extended_info( $rows, $query_args );

		return (object) array(
			'data'    => array_map( array( $this, 'cast_numbers' ), $rows ),
			'total'   => $total_results,
			'pages'   => $total_pages,
			'page_no' => (int) $query_args['page'],
		);
	}

	/**
	 * Adds product name, permalink and edit link to each row, loading all products at once.
	 *
	 * @param array $rows       Report rows.
	 * @param array $query_args Query parameters.
	 * @return void
	 */
	protected function include_extended_info( &$rows, $query_args ) {
		$products = array();
		if ( $query_args['extended_info'] && $rows ) {
			$found = wc_get_products(
				array(
					'include' => array_map( 'intval', array_column( $rows, 'product_id' ) ),
					'type'    => array_merge( array_keys( wc_get_product_types() ), array( ProductType::VARIATION ) ),
					'limit'   => -1,
				)
			);
			foreach ( is_array( $found ) ? $found : array() as $product ) {
				$products[ $product->get_id() ] = $product;
			}
		}

		foreach ( $rows as $idx => $row ) {
			$extended_info = new \ArrayObject();
			if ( $query_args['extended_info'] ) {
				$product = $products[ (int) $row['product_id'] ] ?? null;
				if ( $product ) {
					$edit_id       = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
					$extended_info = array(
						'name'      => $product->get_name(),
						'permalink' => $product->get_permalink(),
						'edit_url'  => (string) get_edit_post_link( $edit_id, 'raw' ),
					);
				} else {
					$extended_info = array(
						/* translators: %s is product name */
						'name'      => sprintf( __( '%s (Deleted)', 'woocommerce' ), '#' . $row['product_id'] ),
						'permalink' => '',
						'edit_url'  => '',
					);
				}
			}
			$rows[ $idx ]['extended_info'] = $extended_info;
		}
	}

	/**
	 * Initialize query objects.
	 *
	 * @return void
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		$this->subquery = new SqlQuery( $this->context . '_subquery' );
		$this->subquery->add_sql_clause( 'from', self::get_db_table_name() );
		$this->subquery->add_sql_clause( 'group_by', self::get_db_table_name() . '.product_id' );
	}
}
