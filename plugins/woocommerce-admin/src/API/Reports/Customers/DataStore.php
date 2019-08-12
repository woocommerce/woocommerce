<?php
/**
 * Admin\API\Reports\Customers\DataStore class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\API\Reports\DataStore as ReportsDataStore;
use \Automattic\WooCommerce\Admin\API\Reports\DataStoreInterface;
use \Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * Admin\API\Reports\Customers\DataStore.
 */
class DataStore extends ReportsDataStore implements DataStoreInterface {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	const TABLE_NAME = 'wc_customer_lookup';

	/**
	 * Mapping columns to data type to return correct response types.
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
	 * SQL columns to select in the db query and their mapping to SQL code.
	 *
	 * @var array
	 */
	protected $report_columns = array(
		'id'               => 'customer_id as id',
		'user_id'          => 'user_id',
		'username'         => 'username',
		'name'             => "CONCAT_WS( ' ', first_name, last_name ) as name", // @todo What does this mean for RTL?
		'email'            => 'email',
		'country'          => 'country',
		'city'             => 'city',
		'state'            => 'state',
		'postcode'         => 'postcode',
		'date_registered'  => 'date_registered',
		'date_last_active' => 'IF( date_last_active <= "0000-00-00 00:00:00", NULL, date_last_active ) AS date_last_active',
		'orders_count'     => 'SUM( CASE WHEN parent_id = 0 THEN 1 ELSE 0 END ) as orders_count',
		'total_spend'      => 'SUM( gross_total ) as total_spend',
		'avg_order_value'  => '( SUM( gross_total ) / COUNT( order_id ) ) as avg_order_value',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		// Initialize some report columns that need disambiguation.
		$this->report_columns['id']              = $wpdb->prefix . self::TABLE_NAME . '.customer_id as id';
		$this->report_columns['date_last_order'] = "MAX( {$wpdb->prefix}wc_order_stats.date_created ) as date_last_order";
	}

	/**
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		add_action( 'woocommerce_new_customer', array( __CLASS__, 'update_registered_customer' ) );
		add_action( 'woocommerce_update_customer', array( __CLASS__, 'update_registered_customer' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'update_registered_customer' ) );
		add_action( 'updated_user_meta', array( __CLASS__, 'update_registered_customer_via_last_active' ), 10, 3 );
	}

	/**
	 * Trigger a customer update if their "last active" meta value was changed.
	 * Function expects to be hooked into the `updated_user_meta` action.
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
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'name' === $order_by ) {
			return "CONCAT_WS( ' ', first_name, last_name )";
		}

		return $order_by;
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_order_by_sql_params( $query_args ) {
		$sql_query['order_by_clause'] = '';

		if ( isset( $query_args['orderby'] ) ) {
			$sql_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}

		if ( isset( $query_args['order'] ) ) {
			$sql_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$sql_query['order_by_clause'] .= ' DESC';
		}

		return $sql_query;
	}

	/**
	 * Fills WHERE clause of SQL request with date-related constraints.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return array
	 */
	protected function get_time_period_sql_params( $query_args, $table_name ) {
		global $wpdb;

		$sql_query           = array(
			'where_time_clause' => '',
			'where_clause'      => '',
			'having_clause'     => '',
		);
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
			$sql_query['where_time_clause'] = ' AND ' . implode( " {$match_operator} ", $where_time_clauses );
		}

		if ( $having_time_clauses ) {
			$sql_query['having_clause'] = ' AND ' . implode( " {$match_operator} ", $having_time_clauses );
		}

		return $sql_query;
	}

	/**
	 * Updates the database query with parameters used for Customers report: categories and order status.
	 *
	 * @param array $query_args Query arguments supplied by the user.
	 * @return array            Array of parameters used for SQL query.
	 */
	protected function get_sql_query_params( $query_args ) {
		global $wpdb;
		$customer_lookup_table  = $wpdb->prefix . self::TABLE_NAME;
		$order_stats_table_name = $wpdb->prefix . 'wc_order_stats';

		$sql_query_params                = $this->get_time_period_sql_params( $query_args, $customer_lookup_table );
		$sql_query_params                = array_merge( $sql_query_params, $this->get_limit_sql_params( $query_args ) );
		$sql_query_params                = array_merge( $sql_query_params, $this->get_order_by_sql_params( $query_args ) );
		$sql_query_params['from_clause'] = " LEFT JOIN {$order_stats_table_name} ON {$customer_lookup_table}.customer_id = {$order_stats_table_name}.customer_id";

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
		);

		if ( ! empty( $query_args['search'] ) ) {
			$name_like = '%' . $wpdb->esc_like( $query_args['search'] ) . '%';

			if ( empty( $query_args['searchby'] ) || 'name' === $query_args['searchby'] || ! in_array( $query_args['searchby'], $search_params, true ) ) {
				$searchby = "CONCAT_WS( ' ', first_name, last_name )";
			} else {
				$searchby = $query_args['searchby'];
			}

			$where_clauses[] = $wpdb->prepare( "{$searchby} LIKE %s", $name_like ); // WPCS: unprepared SQL ok.
		}

		// Allow a list of customer IDs to be specified.
		if ( ! empty( $query_args['customers'] ) ) {
			$included_customers = implode( ',', array_map( 'intval', $query_args['customers'] ) );
			$where_clauses[]    = "{$customer_lookup_table}.customer_id IN ({$included_customers})";
		}

		$numeric_params = array(
			'orders_count'    => array(
				'column' => 'COUNT( order_id )',
				'format' => '%d',
			),
			'total_spend'     => array(
				'column' => 'SUM( gross_total )',
				'format' => '%f',
			),
			'avg_order_value' => array(
				'column' => '( SUM( gross_total ) / COUNT( order_id ) )',
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
					"{$param_info['column']} >{$or_equal} {$param_info['format']}",
					$query_args[ $min_param ]
				); // WPCS: unprepared SQL ok, PreparedSQLPlaceholders replacement count ok.
			}

			if ( isset( $query_args[ $max_param ] ) ) {
				$subclauses[] = $wpdb->prepare(
					"{$param_info['column']} <{$or_equal} {$param_info['format']}",
					$query_args[ $max_param ]
				); // WPCS: unprepared SQL ok, PreparedSQLPlaceholders replacement count ok.
			}

			if ( $subclauses ) {
				$having_clauses[] = '(' . implode( ' AND ', $subclauses ) . ')';
			}
		}

		if ( $where_clauses ) {
			$preceding_match                  = empty( $sql_query_params['where_time_clause'] ) ? ' AND ' : " {$match_operator} ";
			$sql_query_params['where_clause'] = $preceding_match . implode( " {$match_operator} ", $where_clauses );
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$sql_query_params['from_clause'] .= " AND ( {$order_status_filter} )";
		}

		if ( $having_clauses ) {
			$preceding_match                    = empty( $sql_query_params['having_clause'] ) ? ' AND ' : " {$match_operator} ";
			$sql_query_params['having_clause'] .= $preceding_match . implode( " {$match_operator} ", $having_clauses );
		}

		return $sql_query_params;
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$customers_table_name   = $wpdb->prefix . self::TABLE_NAME;
		$order_stats_table_name = $wpdb->prefix . 'wc_order_stats';

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'     => get_option( 'posts_per_page' ),
			'page'         => 1,
			'order'        => 'DESC',
			'orderby'      => 'date_registered',
			'order_before' => TimeInterval::default_before(),
			'order_after'  => TimeInterval::default_after(),
			'fields'       => '*',
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		$cache_key = $this->get_cache_key( $query_args );
		$data      = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $data ) {
			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$selections       = $this->selected_columns( $query_args );
			$sql_query_params = $this->get_sql_query_params( $query_args );

			$db_records_count = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM (
					SELECT {$customers_table_name}.customer_id
					FROM
						{$customers_table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						{$customers_table_name}.customer_id
					HAVING
						1=1
						{$sql_query_params['having_clause']}
				) as tt
				"
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

			$total_pages = (int) ceil( $db_records_count / $sql_query_params['per_page'] );
			if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
				return $data;
			}
			$customer_data = $wpdb->get_results(
				"SELECT
						{$selections}
					FROM
						{$customers_table_name}
						{$sql_query_params['from_clause']}
					WHERE
						1=1
						{$sql_query_params['where_time_clause']}
						{$sql_query_params['where_clause']}
					GROUP BY
						{$customers_table_name}.customer_id
					HAVING
						1=1
						{$sql_query_params['having_clause']}
					ORDER BY
						{$sql_query_params['order_by_clause']}
					{$sql_query_params['limit']}
					",
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

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

			wp_cache_set( $cache_key, $data, $this->cache_group );
		}

		return $data;
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
				return self::get_guest_id_by_email( $email );
			} else {
				return false;
			}
		} else {
			return self::get_customer_id_by_user_id( $user_id );
		}
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

		$data   = array(
			'first_name'       => $order->get_customer_first_name(),
			'last_name'        => $order->get_customer_last_name(),
			'email'            => $order->get_billing_email( 'edit' ),
			'city'             => $order->get_billing_city( 'edit' ),
			'state'            => $order->get_billing_state( 'edit' ),
			'postcode'         => $order->get_billing_postcode( 'edit' ),
			'country'          => $order->get_billing_country( 'edit' ),
			'date_last_active' => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
		);
		$format = array(
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
			$user_id                 = $order->get_user_id();
			$customer                = new \WC_Customer( $user_id );
			$data['user_id']         = $user_id;
			$data['username']        = $customer->get_username( 'edit' );
			$data['date_registered'] = $customer->get_date_created( 'edit' ) ? $customer->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format ) : null;
			$format[]                = '%d';
			$format[]                = '%s';
			$format[]                = '%s';
		}

		$result      = $wpdb->insert( $wpdb->prefix . self::TABLE_NAME, $data, $format );
		$customer_id = $wpdb->insert_id;

		/**
		 * Fires when a new report customer is created.
		 *
		 * @param int $customer_id Customer ID.
		 */
		do_action( 'woocommerce_reports_new_customer', $customer_id );

		return $result ? $customer_id : false;
	}

	/**
	 * Retrieve a guest ID (when user_id is null) by email.
	 *
	 * @param string $email Email address.
	 * @return false|array Customer array if found, boolean false if not.
	 */
	public static function get_guest_id_by_email( $email ) {
		global $wpdb;

		$table_name  = $wpdb->prefix . self::TABLE_NAME;
		$customer_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT customer_id FROM {$table_name} WHERE email = %s AND user_id IS NULL LIMIT 1",
				$email
			)
		); // WPCS: unprepared SQL ok.

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

		$table_name  = $wpdb->prefix . self::TABLE_NAME;
		$customer_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT customer_id FROM {$table_name} WHERE user_id = %d LIMIT 1",
				$user_id
			)
		); // WPCS: unprepared SQL ok.

		return $customer_id ? (int) $customer_id : false;
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
				"SELECT order_id, date_created FROM {$orders_table} WHERE customer_id = %d {$excluded_statuses_condition} ORDER BY date_created, order_id ASC LIMIT 2",
				$customer_id
			)
		); // WPCS: unprepared SQL ok.
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

		$last_order = $customer->get_last_order();

		if ( ! $last_order ) {
			$first_name = get_user_meta( $user_id, 'first_name', true );
			$last_name  = get_user_meta( $user_id, 'last_name', true );
		} else {
			$first_name = $last_order->get_customer_first_name();
			$last_name  = $last_order->get_customer_last_name();
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
			'date_registered'  => $customer->get_date_created( 'edit' )->date( TimeInterval::$sql_datetime_format ),
			'date_last_active' => $last_active ? date( 'Y-m-d H:i:s', $last_active ) : null,
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
		);

		$customer_id = self::get_customer_id_by_user_id( $user_id );

		if ( $customer_id ) {
			// Preserve customer_id for existing user_id.
			$data['customer_id'] = $customer_id;
			$format[]            = '%d';
		}

		$results = $wpdb->replace( $wpdb->prefix . self::TABLE_NAME, $data, $format );

		/**
		 * Fires when customser's reports are updated.
		 *
		 * @param int $customer_id Customer ID.
		 */
		do_action( 'woocommerce_reports_update_customer', $customer_id );
		return $results;
	}

	/**
	 * Check if a user ID is a valid customer or other user role with past orders.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	protected static function is_valid_customer( $user_id ) {
		$customer = new \WC_Customer( $user_id );

		if ( absint( $customer->get_id() ) !== absint( $user_id ) ) {
			return false;
		}

		$customer_roles = (array) apply_filters( 'woocommerce_admin_customer_roles', array( 'customer' ) );
		if ( $customer->get_order_count() < 1 && ! in_array( $customer->get_role(), $customer_roles, true ) ) {
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
		$table_name  = $wpdb->prefix . self::TABLE_NAME;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM ${table_name} WHERE customer_id = %d",
				$customer_id
			)
		);

		/**
		 * Fires when a customer is deleted.
		 *
		 * @param int $order_id Order ID.
		 */
		do_action( 'woocommerce_reports_delete_customer', $customer_id );
	}

	/**
	 * Returns string to be used as cache key for the data.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	protected function get_cache_key( $params ) {
		return 'woocommerce_' . self::TABLE_NAME . '_' . md5( wp_json_encode( $params ) );
	}

}
