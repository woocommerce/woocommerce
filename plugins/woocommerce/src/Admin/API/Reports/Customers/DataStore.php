<?php
/**
 * Admin\API\Reports\Customers\DataStore class file.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;
use Automattic\WooCommerce\Admin\API\Reports\Cache as ReportsCache;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Admin\API\Reports\Customers\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @override ReportsDataStore::$table_name
	 *
	 * @var string
	 */
	protected static $table_name = 'wc_customer_lookup';

	/**
	 * Cache identifier.
	 *
	 * @override ReportsDataStore::$cache_key
	 *
	 * @var string
	 */
	protected $cache_key = 'customers';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @override ReportsDataStore::$column_types
	 *
	 * @var array
	 */
	protected $column_types = array(
		'id'              => 'intval',
		'user_id'         => 'intval',
		'orders_count'    => 'intval',
		'total_spend'     => 'floatval',
		'avg_order_value' => 'floatval',
	);

	/**
	 * Data store context used to pass to filters.
	 *
	 * @override ReportsDataStore::$context
	 *
	 * @var string
	 */
	protected $context = 'customers';

	/**
	 * Assign report columns once full table name has been assigned.
	 *
	 * @override ReportsDataStore::assign_report_columns()
	 */
	protected function assign_report_columns() {
		global $wpdb;
		$table_name           = self::get_db_table_name();
		$orders_count         = 'SUM( CASE WHEN parent_id = 0 THEN 1 ELSE 0 END )';
		$total_spend          = 'SUM( total_sales )';
		$this->report_columns = array(
			'id'               => "{$table_name}.customer_id as id",
			'user_id'          => 'user_id',
			'username'         => 'username',
			'name'             => "CONCAT_WS( ' ', first_name, last_name ) as name", // @xxx: What does this mean for RTL?
			'first_name'       => 'first_name',
			'last_name'        => 'last_name',
			'email'            => 'email',
			'country'          => 'country',
			'city'             => 'city',
			'state'            => 'state',
			'postcode'         => 'postcode',
			'date_registered'  => 'date_registered',
			// Use single quotes for string literals to ensure compatibility with sql_mode=ANSI_QUOTES.
			'date_last_active' => "IF( date_last_active <= '0000-00-00 00:00:00', NULL, date_last_active ) AS date_last_active",
			'date_last_order'  => "MAX( {$wpdb->prefix}wc_order_stats.date_created ) as date_last_order",
			'orders_count'     => "{$orders_count} as orders_count",
			'total_spend'      => "{$total_spend} as total_spend",
			'avg_order_value'  => "CASE WHEN {$orders_count} = 0 THEN NULL ELSE {$total_spend} / {$orders_count} END AS avg_order_value",
		);
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_new_customer', array( __CLASS__, 'update_registered_customer' ) );

		add_action( 'woocommerce_update_customer', array( __CLASS__, 'update_registered_customer' ) );
		add_action( 'profile_update', array( __CLASS__, 'update_registered_customer' ) );

		add_action( 'added_user_meta', array( __CLASS__, 'update_registered_customer_via_last_active' ), 10, 3 );
		add_action( 'updated_user_meta', array( __CLASS__, 'update_registered_customer_via_last_active' ), 10, 3 );

		add_action( 'delete_user', array( __CLASS__, 'delete_customer_by_user_id' ) );
		add_action( 'remove_user_from_blog', array( __CLASS__, 'delete_customer_by_user_id' ) );

		add_action( 'woocommerce_privacy_remove_order_personal_data', array( __CLASS__, 'anonymize_customer' ) );

		add_action( 'woocommerce_analytics_delete_order_stats', array( __CLASS__, 'sync_on_order_delete' ), 15, 2 );
	}

	/**
	 * Sync customers data after an order was deleted.
	 *
	 * When an order is deleted, the customer record is deleted from the
	 * table if the customer has no other orders.
	 *
	 * @param int $order_id Order ID.
	 * @param int $customer_id Customer ID.
	 */
	public static function sync_on_order_delete( $order_id, $customer_id ) {
		$customer_id = absint( $customer_id );

		if ( 0 === $customer_id ) {
			return;
		}

		// Calculate the amount of orders remaining for this customer.
		$order_count = self::get_order_count( $customer_id );

		if ( 0 === $order_count ) {
			self::delete_customer( $customer_id );
		}
	}

	/**
	 * Sync customers data after an order was updated.
	 *
	 * Only updates customer if it is the customers last order.
	 *
	 * @param int $post_id of order.
	 * @return true|-1
	 */
	public static function sync_order_customer( $post_id ) {
		global $wpdb;

		if ( ! OrderUtil::is_order( $post_id, array( 'shop_order', 'shop_order_refund' ) ) ) {
			return -1;
		}

		$order       = wc_get_order( $post_id );
		$customer_id = self::get_existing_customer_id_from_order( $order );
		if ( false === $customer_id ) {
			return -1;
		}
		$last_order = self::get_last_order( $customer_id );

		if ( ! $last_order || $order->get_id() !== $last_order->get_id() ) {
			return -1;
		}

		list($data, $format) = self::get_customer_order_data_and_format( $order );

		$result = $wpdb->update( self::get_db_table_name(), $data, array( 'customer_id' => $customer_id ), $format );

		/**
		 * Fires when a customer is updated.
		 *
		 * @param int $customer_id Customer ID.
		 * @since 4.0.0
		 */
		do_action( 'woocommerce_analytics_update_customer', $customer_id );

		return 1 === $result;
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters. Overridden here to allow multiple direction
	 * clauses.
	 *
	 * @since 10.5.0
	 * @param array $query_args Parameters supplied by the user.
	 * @return void
	 */
	protected function add_order_by_sql_params( $query_args ) {
		$order_by_clause = $this->normalize_order_by_clause(
			$query_args['orderby'] ?? 'date_registered',
			$query_args['order'] ?? 'desc'
		);
		$this->clear_sql_clause( 'order_by' );
		$this->add_sql_clause( 'order_by', $order_by_clause );
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * Handles both order_by and direction.
	 *
	 * @since 10.5.0
	 * @param string $order_by Sorting criterion.
	 * @param string $order Order direction.
	 * @return string
	 */
	protected function normalize_order_by_clause( $order_by, $order = 'desc' ) {
		$order_by        = esc_sql( $order_by );
		$order           = strtolower( $order ) === 'asc' ? 'ASC' : 'DESC';
		$order_by_clause = '';

		if ( 'location' === $order_by ) {
			$order_by_clause = "state {$order}, country {$order}";
		} else {
			$order_by_clause = "{$order_by} {$order}";
		}

		return $order_by_clause;
	}

	/**
	 * Fills WHERE clause of SQL request with date-related constraints.
	 *
	 * @override ReportsDataStore::add_time_period_sql_params()
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 */
	protected function add_time_period_sql_params( $query_args, $table_name ) {
		global $wpdb;

		$this->clear_sql_clause( array( 'where', 'where_time', 'having' ) );
		$date_param_mapping  = array(
			'registered'  => array(
				'clause' => 'where',
				'column' => $table_name . '.date_registered',
			),
			'order'       => array(
				'clause' => 'where',
				'column' => $wpdb->prefix . 'wc_order_stats.date_created',
			),
			'last_active' => array(
				'clause' => 'where',
				'column' => $table_name . '.date_last_active',
			),
			'last_order'  => array(
				'clause' => 'having',
				'column' => "MAX( {$wpdb->prefix}wc_order_stats.date_created )",
			),
		);
		$match_operator      = $this->get_match_operator( $query_args );
		$where_time_clauses  = array();
		$having_time_clauses = array();

		foreach ( $date_param_mapping as $query_param => $param_info ) {
			$subclauses  = array();
			$before_arg  = $query_param . '_before';
			$after_arg   = $query_param . '_after';
			$column_name = $param_info['column'];

			if ( ! empty( $query_args[ $before_arg ] ) ) {
				$datetime     = new \DateTime( $query_args[ $before_arg ] );
				$datetime_str = $datetime->format( TimeInterval::$sql_datetime_format );
				$subclauses[] = "{$column_name} <= '$datetime_str'";
			}

			if ( ! empty( $query_args[ $after_arg ] ) ) {
				$datetime     = new \DateTime( $query_args[ $after_arg ] );
				$datetime_str = $datetime->format( TimeInterval::$sql_datetime_format );
				$subclauses[] = "{$column_name} >= '$datetime_str'";
			}

			if ( $subclauses && ( 'where' === $param_info['clause'] ) ) {
				$where_time_clauses[] = '(' . implode( ' AND ', $subclauses ) . ')';
			}

			if ( $subclauses && ( 'having' === $param_info['clause'] ) ) {
				$having_time_clauses[] = '(' . implode( ' AND ', $subclauses ) . ')';
			}
		}

		if ( $where_time_clauses ) {
			$this->subquery->add_sql_clause( 'where_time', 'AND ' . implode( " {$match_operator} ", $where_time_clauses ) );
		}

		if ( $having_time_clauses ) {
			$this->subquery->add_sql_clause( 'having', 'AND ' . implode( " {$match_operator} ", $having_time_clauses ) );
		}
	}

	/**
	 * Updates the database query with parameters used for Customers report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 */
	protected function add_sql_query_params( $query_args ) {
		global $wpdb;
		$customer_lookup_table  = self::get_db_table_name();
		$order_stats_table_name = $wpdb->prefix . 'wc_order_stats';

		$this->add_time_period_sql_params( $query_args, $customer_lookup_table );
		$this->get_limit_sql_params( $query_args );
		$this->add_order_by_sql_params( $query_args );
		$this->subquery->add_sql_clause( 'left_join', "LEFT JOIN {$order_stats_table_name} ON {$customer_lookup_table}.customer_id = {$order_stats_table_name}.customer_id" );

		$match_operator = $this->get_match_operator( $query_args );
		$where_clauses  = array();
		$having_clauses = array();

		$exact_match_params = array(
			'name',
			'username',
			'email',
			'country',
		);

		foreach ( $exact_match_params as $exact_match_param ) {
			if ( ! empty( $query_args[ $exact_match_param . '_includes' ] ) ) {
				$exact_match_arguments         = $query_args[ $exact_match_param . '_includes' ];
				$exact_match_arguments_escaped = array_map( 'esc_sql', explode( ',', $exact_match_arguments ) );
				$included                      = implode( "','", $exact_match_arguments_escaped );
				// 'country_includes' is a list of country codes, the others will be a list of customer ids.
				$table_column    = 'country' === $exact_match_param ? $exact_match_param : 'customer_id';
				$where_clauses[] = "{$customer_lookup_table}.{$table_column} IN ('{$included}')";
			}

			if ( ! empty( $query_args[ $exact_match_param . '_excludes' ] ) ) {
				$exact_match_arguments         = $query_args[ $exact_match_param . '_excludes' ];
				$exact_match_arguments_escaped = array_map( 'esc_sql', explode( ',', $exact_match_arguments ) );
				$excluded                      = implode( "','", $exact_match_arguments_escaped );
				// 'country_includes' is a list of country codes, the others will be a list of customer ids.
				$table_column    = 'country' === $exact_match_param ? $exact_match_param : 'customer_id';
				$where_clauses[] = "{$customer_lookup_table}.{$table_column} NOT IN ('{$excluded}')";
			}
		}

		$search_params = array(
			'name',
			'username',
			'email',
			'all',
		);

		if ( ! empty( $query_args['search'] ) ) {
			$name_like = '%' . $wpdb->esc_like( $query_args['search'] ) . '%';

			if ( empty( $query_args['searchby'] ) || 'name' === $query_args['searchby'] || ! in_array( $query_args['searchby'], $search_params, true ) ) {
				$searchby = "CONCAT_WS( ' ', first_name, last_name )";
			} elseif ( 'all' === $query_args['searchby'] ) {
				$searchby = "CONCAT_WS( ' ', first_name, last_name, username, email )";
			} else {
				$searchby = $query_args['searchby'];
			}

			$where_clauses[] = $wpdb->prepare( "{$searchby} LIKE %s", $name_like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		$filter_empty_params = array(
			'email',
			'name',
			'country',
			'city',
			'state',
			'postcode',
		);

		if ( ! empty( $query_args['filter_empty'] ) ) {
			$fields_to_filter_by = array_intersect( $query_args['filter_empty'], $filter_empty_params );
			if ( in_array( 'name', $fields_to_filter_by, true ) ) {
				$fields_to_filter_by   = array_diff( $fields_to_filter_by, array( 'name' ) );
				$fields_to_filter_by[] = "CONCAT_WS( ' ', first_name, last_name )";
			}
			$fields_with_not_condition = array_map(
				function ( $field ) {
					return $field . ' <> \'\'';
				},
				$fields_to_filter_by
			);
			$where_clauses[]           = '(' . implode( ' AND ', $fields_with_not_condition ) . ')';
		}

		// Allow a list of customer IDs to be specified.
		if ( ! empty( $query_args['customers'] ) ) {
			$included_customers = $this->get_filtered_ids( $query_args, 'customers' );
			$where_clauses[]    = "{$customer_lookup_table}.customer_id IN ({$included_customers})";
		}

		// Allow a list of user IDs to be specified.
		if ( ! empty( $query_args['users'] ) ) {
			$included_users  = $this->get_filtered_ids( $query_args, 'users' );
			$where_clauses[] = "{$customer_lookup_table}.user_id IN ({$included_users})";
		}

		// Allow a list of locations to be specified (includes).
		if ( ! empty( $query_args['location_includes'] ) ) {
			$location_clause = $this->build_location_filter_clause( $query_args['location_includes'], true );
			if ( '' !== $location_clause ) {
				$where_clauses[] = $location_clause;
			}
		}

		// Allow a list of locations to be excluded.
		if ( ! empty( $query_args['location_excludes'] ) ) {
			$location_clause = $this->build_location_filter_clause( $query_args['location_excludes'], false );
			if ( '' !== $location_clause ) {
				$where_clauses[] = $location_clause;
			}
		}

		// Filter by user type.
		if ( ! empty( $query_args['user_type'] ) && 'all' !== $query_args['user_type'] ) {
			$user_type       = $query_args['user_type'];
			$where_clauses[] = "{$customer_lookup_table}.user_id IS " . ( 'registered' === $user_type ? 'NOT NULL' : 'NULL' );
		}

		$numeric_params = array(
			'orders_count'    => array(
				'column' => 'COUNT( order_id )',
				'format' => '%d',
			),
			'total_spend'     => array(
				'column' => 'SUM( total_sales )',
				'format' => '%f',
			),
			'avg_order_value' => array(
				'column' => '( SUM( total_sales ) / COUNT( order_id ) )',
				'format' => '%f',
			),
		);

		foreach ( $numeric_params as $numeric_param => $param_info ) {
			$subclauses = array();
			$min_param  = $numeric_param . '_min';
			$max_param  = $numeric_param . '_max';
			$or_equal   = isset( $query_args[ $min_param ] ) && isset( $query_args[ $max_param ] ) ? '=' : '';

			if ( isset( $query_args[ $min_param ] ) ) {
				$subclauses[] = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"{$param_info['column']} >{$or_equal} {$param_info['format']}",
					$query_args[ $min_param ]
				);
			}

			if ( isset( $query_args[ $max_param ] ) ) {
				$subclauses[] = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"{$param_info['column']} <{$or_equal} {$param_info['format']}",
					$query_args[ $max_param ]
				);
			}

			if ( $subclauses ) {
				$having_clauses[] = '(' . implode( ' AND ', $subclauses ) . ')';
			}
		}

		if ( $where_clauses ) {
			$preceding_match = empty( $this->get_sql_clause( 'where_time' ) ) ? ' AND ' : " {$match_operator} ";
			$this->subquery->add_sql_clause( 'where', $preceding_match . implode( " {$match_operator} ", $where_clauses ) );
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$this->subquery->add_sql_clause( 'left_join', "AND ( {$order_status_filter} )" );
		}

		if ( $having_clauses ) {
			$preceding_match = empty( $this->get_sql_clause( 'having' ) ) ? ' AND ' : " {$match_operator} ";
			$this->subquery->add_sql_clause( 'having', $preceding_match . implode( " {$match_operator} ", $having_clauses ) );
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
		$defaults                 = parent::get_default_query_vars();
		$defaults['orderby']      = 'date_registered';
		$defaults['order_before'] = TimeInterval::default_before();
		$defaults['order_after']  = TimeInterval::default_after();

		return $defaults;
	}

	/**
	 * Returns an existing customer ID for an order if one exists.
	 *
	 * @param object $order WC Order.
	 * @return int|bool
	 */
	public static function get_existing_customer_id_from_order( $order ) {
		global $wpdb;

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		$user_id = $order->get_customer_id();

		if ( 0 === $user_id ) {
			$customer_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT customer_id FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
					$order->get_id()
				)
			);

			if ( $customer_id ) {
				return $customer_id;
			}

			$email = $order->get_billing_email( 'edit' );

			if ( $email ) {
				return self::get_customer_id_by_email( $email );
			} else {
				return false;
			}
		} else {
			return self::get_customer_id_by_user_id( $user_id );
		}
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

		$selections       = $this->selected_columns( $query_args );
		$sql_query_params = $this->add_sql_query_params( $query_args );
		$count_query      = "SELECT COUNT(*) FROM (
				{$this->subquery->get_query_statement()}
			) as tt
			";
		$db_records_count = (int) $wpdb->get_var(
			$count_query // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		$params      = $this->get_limit_params( $query_args );
		$total_pages = (int) ceil( $db_records_count / $params['per_page'] );
		if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
			return $data;
		}

		$this->subquery->clear_sql_clause( 'select' );
		$this->subquery->add_sql_clause( 'select', $selections );
		// For aggregated fields, ensure deterministic ordering by including GROUP BY field.
		$order_by             = $this->get_sql_clause( 'order_by' );
		$aggregated_fields    = array( 'orders_count', 'total_spend', 'avg_order_value' );
		$has_aggregated_field = false;

		foreach ( $aggregated_fields as $field ) {
			if ( false !== strpos( $order_by, $field ) ) {
				$has_aggregated_field = true;
				break;
			}
		}

		if ( $has_aggregated_field ) {
			$customer_lookup_table = self::get_db_table_name();
			$this->subquery->add_sql_clause( 'order_by', $order_by . ", {$customer_lookup_table}.customer_id" );
		} else {
			$this->subquery->add_sql_clause( 'order_by', $order_by );
		}
		$this->subquery->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );

		$customer_data = $wpdb->get_results(
			$this->subquery->get_query_statement(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		if ( null === $customer_data ) {
			return $data;
		}

		$customer_data = array_map( array( $this, 'cast_numbers' ), $customer_data );
		$data          = (object) array(
			'data'    => $customer_data,
			'total'   => $db_records_count,
			'pages'   => $total_pages,
			'page_no' => (int) $query_args['page'],
		);

		return $data;
	}

	/**
	 * Get or create a customer from a given order.
	 *
	 * @param object $order WC Order.
	 * @return int|bool
	 */
	public static function get_or_create_customer_from_order( $order ) {
		if ( ! $order ) {
			return false;
		}

		global $wpdb;

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		$returning_customer_id = self::get_existing_customer_id_from_order( $order );

		if ( $returning_customer_id ) {
			return $returning_customer_id;
		}

		list($data, $format) = self::get_customer_order_data_and_format( $order );

		$result      = $wpdb->insert( self::get_db_table_name(), $data, $format );
		$customer_id = $wpdb->insert_id;

		/**
		 * Fires when a new report customer is created.
		 *
		 * @param int $customer_id Customer ID.
		 * @since 4.0.0
		 */
		do_action( 'woocommerce_analytics_new_customer', $customer_id );

		return $result ? $customer_id : false;
	}

	/**
	 * Returns a data object and format object of the customers data coming from the order.
	 *
	 * @param object      $order         WC_Order where we get customer info from.
	 * @param object|null $customer_user WC_Customer registered customer WP user.
	 * @return array ($data, $format)
	 */
	public static function get_customer_order_data_and_format( $order, $customer_user = null ) {
		$data   = array(
			'first_name'       => $order->get_customer_first_name(),
			'last_name'        => $order->get_customer_last_name(),
			'email'            => $order->get_billing_email( 'edit' ),
			'city'             => $order->get_billing_city( 'edit' ),
			'state'            => $order->get_billing_state( 'edit' ),
			'postcode'         => $order->get_billing_postcode( 'edit' ),
			'country'          => $order->get_billing_country( 'edit' ),
			'date_last_active' => gmdate( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
		);
		$format = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		// Add registered customer data.
		if ( 0 !== $order->get_user_id() ) {
			$user_id = $order->get_user_id();
			if ( is_null( $customer_user ) ) {
				$customer_user = new \WC_Customer( $user_id );
			}

			// Set email as customer email instead of Order Billing Email if we have a customer.
			$data['email'] = $customer_user->get_email( 'edit' );

			// Adding other relevant customer data.
			$data['user_id']         = $user_id;
			$data['username']        = $customer_user->get_username( 'edit' );
			$data['date_registered'] = $customer_user->get_date_created( 'edit' ) ? $customer_user->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format ) : null;
			$format[]                = '%d';
			$format[]                = '%s';
			$format[]                = '%s';
		}
		return array( $data, $format );
	}

	/**
	 * Retrieve a guest ID (when user_id is null) by email.
	 *
	 * @param string $email Email address.
	 * @return false|array Customer array if found, boolean false if not.
	 */
	public static function get_guest_id_by_email( $email ) {
		global $wpdb;

		$table_name  = self::get_db_table_name();
		$customer_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT customer_id FROM {$table_name} WHERE email = %s AND user_id IS NULL LIMIT 1",
				$email
			)
		);

		return $customer_id ? (int) $customer_id : false;
	}

	/**
	 * Retrieve a customer ID by email address, regardless of user registration status.
	 * Prioritizes registered customers over guest customers when both exist.
	 *
	 * @param string $email Email address.
	 * @return false|int Customer ID if found, boolean false if not.
	 */
	public static function get_customer_id_by_email( $email ) {
		global $wpdb;

		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		$table_name  = self::get_db_table_name();
		$customer_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT customer_id FROM {$table_name} WHERE email = %s ORDER BY user_id IS NOT NULL DESC LIMIT 1",
				$email
			)
		);

		return $customer_id ? (int) $customer_id : false;
	}

	/**
	 * Retrieve a registered customer row id by user_id.
	 *
	 * @param string|int $user_id User ID.
	 * @return false|int Customer ID if found, boolean false if not.
	 */
	public static function get_customer_id_by_user_id( $user_id ) {
		global $wpdb;

		$table_name  = self::get_db_table_name();
		$customer_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT customer_id FROM {$table_name} WHERE user_id = %d LIMIT 1",
				$user_id
			)
		);

		return $customer_id ? (int) $customer_id : false;
	}

	/**
	 * Retrieve the last order made by a customer.
	 *
	 * @param int $customer_id Customer ID.
	 * @return object WC_Order|false.
	 */
	public static function get_last_order( $customer_id ) {
		global $wpdb;
		$orders_table = $wpdb->prefix . 'wc_order_stats';

		$last_order = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT order_id, date_created_gmt FROM {$orders_table}
				WHERE customer_id = %d
				ORDER BY date_created_gmt DESC, order_id DESC LIMIT 1",
				// phpcs:enable
				$customer_id
			)
		);
		if ( ! $last_order ) {
			return false;
		}
		return wc_get_order( absint( $last_order ) );
	}

	/**
	 * Retrieve the oldest orders made by a customer.
	 *
	 * @param int $customer_id Customer ID.
	 * @return array Orders.
	 */
	public static function get_oldest_orders( $customer_id ) {
		global $wpdb;
		$orders_table                = $wpdb->prefix . 'wc_order_stats';
		$excluded_statuses           = array_map( array( __CLASS__, 'normalize_order_status' ), self::get_excluded_report_order_statuses() );
		$excluded_statuses_condition = '';
		if ( ! empty( $excluded_statuses ) ) {
			$excluded_statuses_str       = implode( "','", $excluded_statuses );
			$excluded_statuses_condition = "AND status NOT IN ('{$excluded_statuses_str}')";
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT order_id, date_created FROM {$orders_table} WHERE customer_id = %d {$excluded_statuses_condition} ORDER BY date_created, order_id ASC LIMIT 2",
				$customer_id
			)
		);
	}

	/**
	 * Retrieve the amount of orders made by a customer.
	 *
	 * @param int $customer_id Customer ID.
	 * @return int|null Amount of orders for customer or null on failure.
	 */
	public static function get_order_count( $customer_id ) {
		global $wpdb;
		$customer_id = absint( $customer_id );

		if ( 0 === $customer_id ) {
			return null;
		}

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT( order_id ) FROM {$wpdb->prefix}wc_order_stats WHERE customer_id = %d",
				$customer_id
			)
		);

		if ( is_null( $result ) ) {
			return null;
		}

		return (int) $result;
	}

	/**
	 * Update the database with customer data.
	 *
	 * @param int $user_id WP User ID to update customer data for.
	 * @return int|bool|null Number or rows modified or false on failure.
	 */
	public static function update_registered_customer( $user_id ) {
		global $wpdb;

		$customer = new \WC_Customer( $user_id );

		if ( ! self::is_valid_customer( $user_id ) ) {
			return false;
		}

		$first_name = $customer->get_first_name();
		$last_name  = $customer->get_last_name();

		if ( empty( $first_name ) ) {
			$first_name = $customer->get_billing_first_name();
		}
		if ( empty( $last_name ) ) {
			$last_name = $customer->get_billing_last_name();
		}

		$last_active = $customer->get_meta( 'wc_last_active', true, 'edit' );
		$data        = array(
			'user_id'          => $user_id,
			'username'         => $customer->get_username( 'edit' ),
			'first_name'       => $first_name,
			'last_name'        => $last_name,
			'email'            => $customer->get_email( 'edit' ),
			'city'             => $customer->get_billing_city( 'edit' ),
			'state'            => $customer->get_billing_state( 'edit' ),
			'postcode'         => $customer->get_billing_postcode( 'edit' ),
			'country'          => $customer->get_billing_country( 'edit' ),
			'date_registered'  => $customer->get_date_created( 'edit' ) ? $customer->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format ) : null,
			'date_last_active' => $last_active ? gmdate( 'Y-m-d H:i:s', $last_active ) : null,
		);
		$format      = array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$customer_id = self::get_customer_id_by_user_id( $user_id );

		if ( $customer_id ) {
			// Preserve customer_id for existing user_id.
			$data['customer_id'] = $customer_id;
			$format[]            = '%d';
		}

		$results = $wpdb->replace( self::get_db_table_name(), $data, $format );

		/**
		 * Fires when customser's reports are updated.
		 *
		 * @param int $customer_id Customer ID.
		 * @since 4.0.0
		 */
		do_action( 'woocommerce_analytics_update_customer', $customer_id );

		ReportsCache::invalidate();

		return $results;
	}

	/**
	 * Update the database if the "last active" meta value was changed.
	 * Function expects to be hooked into the `added_user_meta` and `updated_user_meta` actions.
	 *
	 * @param int    $meta_id ID of updated metadata entry.
	 * @param int    $user_id ID of the user being updated.
	 * @param string $meta_key Meta key being updated.
	 */
	public static function update_registered_customer_via_last_active( $meta_id, $user_id, $meta_key ) {
		if ( 'wc_last_active' === $meta_key ) {
			self::update_registered_customer( $user_id );
		}
	}

	/**
	 * Check if a user ID is a valid customer or other user role with past orders.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	protected static function is_valid_customer( $user_id ) {
		$user = new \WP_User( $user_id );

		if ( (int) $user_id !== $user->ID ) {
			return false;
		}

		/**
		 * Filter the customer roles, used to check if the user is a customer.
		 *
		 * @param array List of customer roles.
		 * @since 4.0.0
		 */
		$customer_roles = (array) apply_filters( 'woocommerce_analytics_customer_roles', array( 'customer' ) );

		if ( empty( $user->roles ) || empty( array_intersect( $user->roles, $customer_roles ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a customer lookup row.
	 *
	 * @param int $customer_id Customer ID.
	 */
	public static function delete_customer( $customer_id ) {
		global $wpdb;

		$customer_id = (int) $customer_id;
		$num_deleted = $wpdb->delete( self::get_db_table_name(), array( 'customer_id' => $customer_id ) );

		if ( $num_deleted ) {
			/**
			 * Fires when a customer is deleted.
			 *
			 * @param int $order_id Order ID.
			 * @since 4.0.0
			 */
			do_action( 'woocommerce_analytics_delete_customer', $customer_id );

			ReportsCache::invalidate();
		}
	}

	/**
	 * Delete a customer lookup row by WordPress User ID.
	 *
	 * @param int $user_id WordPress User ID.
	 */
	public static function delete_customer_by_user_id( $user_id ) {
		global $wpdb;

		if ( (int) $user_id < 1 || doing_action( 'wp_uninitialize_site' ) ) {
			// Skip the deletion.
			return;
		}

		$user_id     = (int) $user_id;
		$num_deleted = $wpdb->delete( self::get_db_table_name(), array( 'user_id' => $user_id ) );

		if ( $num_deleted ) {
			ReportsCache::invalidate();
		}
	}

	/**
	 * Anonymize the customer data for a single order.
	 *
	 * @internal
	 * @param int|WC_Order $order Order instance or ID.
	 * @return void
	 */
	public static function anonymize_customer( $order ) {
		global $wpdb;

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		$customer_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT customer_id FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d", $order->get_id() )
		);

		if ( ! $customer_id ) {
			return;
		}

		// Long form query because $wpdb->update rejects [deleted].
		$deleted_text = __( '[deleted]', 'woocommerce' );
		$updated      = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}wc_customer_lookup
					SET
						user_id = NULL,
						username = %s,
						first_name = %s,
						last_name = %s,
						email = %s,
						country = '',
						postcode = %s,
						city = %s,
						state = %s
					WHERE
						customer_id = %d",
				array(
					$deleted_text,
					$deleted_text,
					$deleted_text,
					'deleted@site.invalid',
					$deleted_text,
					$deleted_text,
					$deleted_text,
					$customer_id,
				)
			)
		);
		// If the customer row was anonymized, flush the cache.
		if ( $updated ) {
			ReportsCache::invalidate();
		}
	}

	/**
	 * Build location filter SQL clause for includes or excludes.
	 *
	 * @since 10.5.0
	 * @param string $locations_string Comma-separated list of locations (e.g., "US:CA,US:NY,GB").
	 * @param bool   $is_include       True for IN clause, false for NOT IN clause.
	 * @return string SQL WHERE clause condition.
	 */
	protected function build_location_filter_clause( $locations_string, $is_include = true ) {
		$customer_lookup_table = self::get_db_table_name();
		$locations_array       = explode( ',', $locations_string );
		$country_state_pairs   = array();
		$countries             = array();

		foreach ( $locations_array as $location ) {
			$location = trim( $location );
			if ( empty( $location ) ) {
				continue;
			}

			if ( false !== strpos( $location, ':' ) ) {
				$parts = explode( ':', $location );
				if ( 2 === count( $parts ) ) {
					$country_state_pairs[] = array(
						'country' => esc_sql( $parts[0] ),
						'state'   => esc_sql( $parts[1] ),
					);
				}
			} else {
				$countries[] = esc_sql( $location );
			}
		}

		$conditions = array();

		// Build country:state pair conditions.
		if ( ! empty( $country_state_pairs ) ) {
			$pair_conditions = array();
			foreach ( $country_state_pairs as $pair ) {
				if ( $is_include ) {
					$pair_conditions[] = "({$customer_lookup_table}.country = '{$pair['country']}' AND {$customer_lookup_table}.state = '{$pair['state']}')";
				} else {
					$pair_conditions[] = "({$customer_lookup_table}.country != '{$pair['country']}' OR {$customer_lookup_table}.state != '{$pair['state']}')";
				}
			}
			$pair_connector = $is_include ? ' OR ' : ' AND ';
			$conditions[]   = '(' . implode( $pair_connector, $pair_conditions ) . ')';
		}

		// Build country-only conditions.
		if ( ! empty( $countries ) ) {
			$operator     = $is_include ? 'IN' : 'NOT IN';
			$conditions[] = "{$customer_lookup_table}.country {$operator} ('" . implode( "','", $countries ) . "')";
		}

		if ( empty( $conditions ) ) {
			return '';
		}

		// Combine conditions with OR for includes, AND for excludes.
		$connector = $is_include ? ' OR ' : ' AND ';

		return '(' . implode( $connector, $conditions ) . ')';
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		$table_name     = self::get_db_table_name();
		$this->subquery = new SqlQuery( $this->context . '_subquery' );
		$this->subquery->add_sql_clause( 'from', $table_name );
		$this->subquery->add_sql_clause( 'select', "{$table_name}.customer_id" );
		$this->subquery->add_sql_clause( 'group_by', "{$table_name}.customer_id" );
	}
}
