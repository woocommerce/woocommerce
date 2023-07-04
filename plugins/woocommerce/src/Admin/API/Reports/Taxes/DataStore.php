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
	 * @var string
	 */
	protected static $table_name = 'wc_order_tax_lookup';

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'taxes';

	/**
	 * Mapping columns to data type to return correct response types.
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
	 * @var string
	 */
	protected $context = 'taxes';

	/**
	 * Assign report columns once full table name has been assigned.
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'tax_rate_id'  => "{$table_name}.tax_rate_id",
			'name'         => 'tax_rate_name as name',
			'tax_rate'     => 'tax_rate',
			'country'      => 'tax_rate_country as country',
			'state'        => 'tax_rate_state as state',
			'priority'     => 'tax_rate_priority as priority',
			'total_tax'    => 'SUM(total_tax) as total_tax',
			'order_tax'    => 'SUM(order_tax) as order_tax',
			'shipping_tax' => 'SUM(shipping_tax) as shipping_tax',
			'orders_count' => "COUNT( DISTINCT ( CASE WHEN total_tax >= 0 THEN {$table_name}.order_id END ) ) as orders_count",
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

		if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
			$this->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_tax_rates ON default_results.tax_rate_id = {$wpdb->prefix}woocommerce_tax_rates.tax_rate_id" );
		} else {
			$this->subquery->add_sql_clause( 'join', "JOIN {$wpdb->prefix}woocommerce_tax_rates ON {$table_name}.tax_rate_id = {$wpdb->prefix}woocommerce_tax_rates.tax_rate_id" );
		}
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
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = self::get_db_table_name();

		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page' => get_option( 'posts_per_page' ),
			'page'     => 1,
			'order'    => 'DESC',
			'orderby'  => 'tax_rate_id',
			'before'   => TimeInterval::default_before(),
			'after'    => TimeInterval::default_after(),
			'fields'   => '*',
			'taxes'    => array(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		/*
		 * We need to get the cache key here because
		 * parent::update_intervals_sql_params() modifies $query_args.
		 */
		$cache_key = $this->get_cache_key( $query_args );
		$data      = $this->get_cached_data( $cache_key );

		if ( false === $data ) {
			$this->initialize_queries();

			$data = (object) array(
				'data'    => array(),
				'total'   => 0,
				'pages'   => 0,
				'page_no' => 0,
			);

			$this->add_sql_query_params( $query_args );
			$params = $this->get_limit_params( $query_args );

			if ( isset( $query_args['taxes'] ) && ! empty( $query_args['taxes'] ) ) {
				$total_results = count( $query_args['taxes'] );
				$total_pages   = (int) ceil( $total_results / $params['per_page'] );

				$inner_selections = array( 'tax_rate_id', 'total_tax', 'order_tax', 'shipping_tax', 'orders_count' );
				$outer_selections = array( 'name', 'tax_rate', 'country', 'state', 'priority' );

				$selections      = $this->selected_columns( array( 'fields' => $inner_selections ) );
				$fields          = $this->get_fields( $query_args );
				$join_selections = $this->format_join_selections( $fields, array( 'tax_rate_id' ), $outer_selections );
				$ids_table       = $this->get_ids_table( $query_args['taxes'], 'tax_rate_id' );

				$this->subquery->clear_sql_clause( 'select' );
				$this->subquery->add_sql_clause( 'select', $this->selected_columns( array( 'fields' => $inner_selections ) ) );
				$this->add_sql_clause( 'select', $join_selections );
				$this->add_sql_clause( 'from', '(' );
				$this->add_sql_clause( 'from', $this->subquery->get_query_statement() );
				$this->add_sql_clause( 'from', ") AS {$table_name}" );
				$this->add_sql_clause(
					'right_join',
					"RIGHT JOIN ( {$ids_table} ) AS default_results
					ON default_results.tax_rate_id = {$table_name}.tax_rate_id"
				);

				$taxes_query = $this->get_query_statement();
			} else {
				$db_records_count = (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM (
						{$this->subquery->get_query_statement()}
					) AS tt"
				); // WPCS: cache ok, DB call ok, unprepared SQL ok.

				$total_results = $db_records_count;
				$total_pages   = (int) ceil( $db_records_count / $params['per_page'] );

				if ( $query_args['page'] < 1 || $query_args['page'] > $total_pages ) {
					return $data;
				}

				$this->subquery->clear_sql_clause( 'select' );
				$this->subquery->add_sql_clause( 'select', $this->selected_columns( $query_args ) );
				$this->subquery->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
				$taxes_query = $this->subquery->get_query_statement();
			}

			$tax_data = $wpdb->get_results(
				$taxes_query,
				ARRAY_A
			); // WPCS: cache ok, DB call ok, unprepared SQL ok.

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

			$this->set_cached_data( $cache_key, $data );
		}

		return $data;
	}

	/**
	 * Get report totals such as order totals and discount amounts.
	 *
	 * Data example:
	 *
	 * '_order_total' => array(
	 *     'type'     => 'meta',
	 *     'function' => 'SUM',
	 *     'name'     => 'total_sales'
	 * )
	 *
	 * @param  array $args arguments for the report.
	 * @return mixed depending on query_type
	 */
	public function get_order_report_data( $args = array() ) {
		global $wpdb;

		$default_args = array(
			'data'                => array(),
			'where'               => array(),
			'where_meta'          => array(),
			'query_type'          => 'get_row',
			'group_by'            => '',
			'order_by'            => '',
			'limit'               => '',
			'filter_range'        => false,
			'nocache'             => false,
			'debug'               => false,
			'order_types'         => wc_get_order_types( 'reports' ),
			'order_status'        => array( 'completed', 'processing', 'on-hold' ),
			'parent_order_status' => false,
		);
		$args         = apply_filters( 'woocommerce_reports_get_order_report_data_args', $args );
		$args         = wp_parse_args( $args, $default_args );

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args );

		if ( empty( $data ) ) {
			return '';
		}

		$order_status = apply_filters( 'woocommerce_reports_order_statuses', $order_status );

		$query  = array();
		$select = array();

		foreach ( $data as $raw_key => $value ) {
			$key      = sanitize_key( $raw_key );
			$distinct = '';

			if ( isset( $value['distinct'] ) ) {
				$distinct = 'DISTINCT';
			}

			switch ( $value['type'] ) {
				case 'meta':
					$get_key = "meta_{$key}.meta_value";
					break;
				case 'parent_meta':
					$get_key = "parent_meta_{$key}.meta_value";
					break;
				case 'post_data':
					$get_key = "posts.{$key}";
					break;
				case 'order_item_meta':
					$get_key = "order_item_meta_{$key}.meta_value";
					break;
				case 'order_item':
					$get_key = "order_items.{$key}";
					break;
			}

			if ( empty( $get_key ) ) {
				// Skip to the next foreach iteration else the query will be invalid.
				continue;
			}

			if ( $value['function'] ) {
				$get = "{$value['function']}({$distinct} {$get_key})";
			} else {
				$get = "{$distinct} {$get_key}";
			}

			$select[] = "{$get} as {$value['name']}";
		}

		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = "FROM {$wpdb->posts} AS posts";

		// Joins.
		$joins = array();

		foreach ( ( $data + $where ) as $raw_key => $value ) {
			$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
			$type      = isset( $value['type'] ) ? $value['type'] : false;
			$key       = sanitize_key( $raw_key );

			switch ( $type ) {
				case 'meta':
					$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON ( posts.ID = meta_{$key}.post_id AND meta_{$key}.meta_key = '{$raw_key}' )";
					break;
				case 'parent_meta':
					$joins[ "parent_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS parent_meta_{$key} ON (posts.post_parent = parent_meta_{$key}.post_id) AND (parent_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item_meta':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON (posts.ID = order_items.order_id)";

					if ( ! empty( $value['order_item_type'] ) ) {
						$joins['order_items'] .= " AND (order_items.order_item_type = '{$value['order_item_type']}')";
					}

					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON " .
						"(order_items.order_item_id = order_item_meta_{$key}.order_item_id) " .
						" AND (order_item_meta_{$key}.meta_key = '{$raw_key}')";
					break;
				case 'order_item':
					$joins['order_items'] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
					break;
			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$join_type = isset( $value['join_type'] ) ? $value['join_type'] : 'INNER';
				$type      = isset( $value['type'] ) ? $value['type'] : false;
				$key       = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( 'order_item_meta' === $type ) {

					$joins['order_items']              = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id";
					$joins[ "order_item_meta_{$key}" ] = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";

				} else {
					// If we have a where clause for meta, join the postmeta table.
					$joins[ "meta_{$key}" ] = "{$join_type} JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";
				}
			}
		}

		if ( ! empty( $parent_order_status ) ) {
			$joins['parent'] = "LEFT JOIN {$wpdb->posts} AS parent ON posts.post_parent = parent.ID";
		}

		$query['join'] = implode( ' ', $joins );

		$query['where'] = "
			WHERE 	posts.post_type 	IN ( '" . implode( "','", $order_types ) . "' )
			";

		if ( ! empty( $order_status ) ) {
			$query['where'] .= "
				AND 	posts.post_status 	IN ( 'wc-" . implode( "','wc-", $order_status ) . "')
			";
		}

		if ( ! empty( $parent_order_status ) ) {
			if ( ! empty( $order_status ) ) {
				$query['where'] .= " AND ( parent.post_status IN ( 'wc-" . implode( "','wc-", $parent_order_status ) . "') OR parent.ID IS NULL ) ";
			} else {
				$query['where'] .= " AND parent.post_status IN ( 'wc-" . implode( "','wc-", $parent_order_status ) . "') ";
			}
		}

		// phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
		// if ( $filter_range ) {
		// 	$query['where'] .= "
		// 		AND 	posts.post_date >= '" . date( 'Y-m-d H:i:s', $this->start_date ) . "'
		// 		AND 	posts.post_date < '" . date( 'Y-m-d H:i:s', strtotime( '+1 DAY', $this->end_date ) ) . "'
		// 	";
		// }
		// phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date

		if ( ! empty( $where_meta ) ) {

			$relation = isset( $where_meta['relation'] ) ? $where_meta['relation'] : 'AND';

			$query['where'] .= ' AND (';

			foreach ( $where_meta as $index => $value ) {

				if ( ! is_array( $value ) ) {
					continue;
				}

				$key = sanitize_key( is_array( $value['meta_key'] ) ? $value['meta_key'][0] . '_array' : $value['meta_key'] );

				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {

					if ( is_array( $value['meta_value'] ) ) {
						// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						$value['meta_value'] = implode( "','", $value['meta_value'] );
					}

					if ( ! empty( $value['meta_value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['meta_value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['meta_value']}'";
				}

				if ( ! empty( $where_value ) ) {
					if ( $index > 0 ) {
						$query['where'] .= ' ' . $relation;
					}

					if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( order_item_meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND order_item_meta_{$key}.meta_value {$where_value} )";
					} else {

						if ( is_array( $value['meta_key'] ) ) {
							$query['where'] .= " ( meta_{$key}.meta_key   IN ('" . implode( "','", $value['meta_key'] ) . "')";
						} else {
							$query['where'] .= " ( meta_{$key}.meta_key   = '{$value['meta_key']}'";
						}

						$query['where'] .= " AND meta_{$key}.meta_value {$where_value} )";
					}
				}
			}

			$query['where'] .= ')';
		}

		if ( ! empty( $where ) ) {

			foreach ( $where as $value ) {

				if ( strtolower( $value['operator'] ) === 'in' || strtolower( $value['operator'] ) === 'not in' ) {

					if ( is_array( $value['value'] ) ) {
						$value['value'] = implode( "','", $value['value'] );
					}

					if ( ! empty( $value['value'] ) ) {
						$where_value = "{$value['operator']} ('{$value['value']}')";
					}
				} else {
					$where_value = "{$value['operator']} '{$value['value']}'";
				}

				if ( ! empty( $where_value ) ) {
					$query['where'] .= " AND {$value['key']} {$where_value}";
				}
			}
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		$query = apply_filters( 'woocommerce_reports_get_order_report_query', $query );
		$query = implode( ' ', $query );

		if ( $debug ) {
			echo '<pre>';
			wc_print_r( $query );
			echo '</pre>';
		}

		if ( $debug || $nocache ) {
			// self::enable_big_selects();

			$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
		} else {
			$query_hash = md5( $query_type . $query );
			// $result     = $this->get_cached_query( $query_hash );
			$result     = null;
			if ( null === $result ) {
				// self::enable_big_selects();

				$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );
			}
			// $this->set_cached_query( $query_hash, $result );
		}

		return $result;
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		global $wpdb;

		if ( 'tax_code' === $order_by ) {
			return 'CONCAT_WS( "-", NULLIF(tax_rate_country, ""), NULLIF(tax_rate_state, ""), NULLIF(tax_rate_name, ""), NULLIF(tax_rate_priority, "") )';
		} elseif ( 'rate' === $order_by ) {
			return "CAST({$wpdb->prefix}woocommerce_tax_rates.tax_rate as DECIMAL(7,4))";
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
