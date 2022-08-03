<?php
// phpcs:disable Generic.Commenting.Todo.TaskFound
/**
 * OrdersTableQuery class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * This class provides a `WP_Query`-like interface to custom order tables.
 */
class OrdersTableQuery {

	/**
	 * Values to ignore when parsing query arguments.
	 */
	public const SKIPPED_VALUES = array( '', array(), null );

	/**
	 * Names of all COT tables (orders, addresses, operational_data, meta) in the form 'table_id' => 'table name'.
	 *
	 * @var array
	 */
	private $tables = array();

	/**
	 * Column mappings for all COT tables.
	 *
	 * @var array
	 */
	private $mappings = array();

	/**
	 * Query vars after processing and sanitization.
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * Columns to be selected in the SELECT clause.
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * Array of table aliases and conditions used to compute the JOIN clause of the query.
	 *
	 * @var array
	 */
	private $join = array();

	/**
	 * Array of fields and conditions used to compute the WHERE clause of the query.
	 *
	 * @var array
	 */
	private $where = array();

	/**
	 * Field to be used in the GROUP BY clause of the query.
	 *
	 * @var array
	 */
	private $groupby = array();

	/**
	 * Array of fields used to compute the ORDER BY clause of the query.
	 *
	 * @var array
	 */
	private $orderby = array();

	/**
	 * Limits used to compute the LIMIT clause of the query.
	 *
	 * @var array
	 */
	private $limits = array();

	/**
	 * Results (order IDs) for the current query.
	 *
	 * @var array
	 */
	private $results = array();

	/**
	 * Final SQL query to run after processing of args.
	 *
	 * @var string
	 */
	private $sql = '';

	/**
	 * The number of pages (when pagination is enabled).
	 *
	 * @var int
	 */
	private $max_num_pages = 0;

	/**
	 * The number of orders found.
	 *
	 * @var int
	 */
	private $found_orders = 0;

	/**
	 * Meta query parser.
	 *
	 * @var OrdersTableMetaQuery
	 */
	private $meta_query = null;


	/**
	 * Sets up and runs the query after processing arguments.
	 *
	 * @param array $args Array of query vars.
	 */
	public function __construct( $args = array() ) {
		$datastore = wc_get_container()->get( OrdersTableDataStore::class );

		// TODO: maybe OrdersTableDataStore::get_all_table_names() could return these keys/indices instead.
		$this->tables   = array(
			'orders'           => $datastore::get_orders_table_name(),
			'addresses'        => $datastore::get_addresses_table_name(),
			'operational_data' => $datastore::get_operational_data_table_name(),
			'meta'             => $datastore::get_meta_table_name(),
		);
		$this->mappings = $datastore->get_all_order_column_mappings();

		$this->args = $args;

		// TODO: args to be implemented.
		unset( $this->args['type'], $this->args['customer_note'], $this->args['name'] );

		$this->build_query();
		$this->run_query();
	}

	/**
	 * Remaps some legacy and `WP_Query` specific query vars to vars available in the customer order table scheme.
	 *
	 * @return void
	 */
	private function maybe_remap_args(): void {
		$mapping = array(
			// WP_Query legacy.
			'post_date'           => 'date_created_gmt',
			'post_modified'       => 'date_modified_gmt',
			'post_status'         => 'status',
			'_date_completed'     => 'date_completed_gmt',
			'_date_paid'          => 'date_paid_gmt',
			'paged'               => 'page',
			'post_parent'         => 'parent_order_id',
			'post_parent__in'     => 'parent_order_id',
			'post_parent__not_in' => 'parent_exclude',
			'post__not_in'        => 'exclude',
			'posts_per_page'      => 'limit',
			'p'                   => 'id',
			'post__in'            => 'id',
			'post_type'           => 'type',
			'fields'              => 'return',

			'customer_user'       => 'customer_id',
			'order_currency'      => 'currency',
			'order_version'       => 'woocommerce_version',
			'cart_discount'       => 'discount_total_amount',
			'cart_discount_tax'   => 'discount_tax_amount',
			'order_shipping'      => 'shipping_total_amount',
			'order_shipping_tax'  => 'shipping_tax_amount',
			'order_tax'           => 'tax_amount',

			// Translate from WC_Order_Query to table structure.
			'version'             => 'woocommerce_version',
			'date_created'        => 'date_created_gmt',
			'date_modified'       => 'date_updated_gmt',
			'date_completed'      => 'date_completed_gmt',
			'date_paid'           => 'date_paid_gmt',
			'discount_total'      => 'discount_total_amount',
			'discount_tax'        => 'discount_tax_amount',
			'shipping_total'      => 'shipping_total_amount',
			'shipping_tax'        => 'shipping_tax_amount',
			'cart_tax'            => 'tax_amount',
			'total'               => 'total_amount',
			'customer_ip_address' => 'ip_address',
			'customer_user_agent' => 'user_agent',
			'parent'              => 'parent_order_id',
		);

		foreach ( $mapping as $query_key => $table_field ) {
			if ( isset( $this->args[ $query_key ] ) ) {
				$this->args[ $table_field ] = $this->args[ $query_key ];
				unset( $this->args[ $query_key ] );
			}
		}

		// meta_query.
		$this->args['meta_query'] = ( $this->arg_isset( 'meta_query' ) && is_array( $this->args['meta_query'] ) ) ? $this->args['meta_query'] : array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		$shortcut_meta_query = array();
		foreach ( array( 'key', 'value', 'compare', 'type', 'compare_key', 'type_key' ) as $key ) {
			if ( $this->arg_isset( "meta_{$key}" ) ) {
				$shortcut_meta_query[ $key ] = $this->args[ "meta_{$key}" ];
			}
		}

		if ( ! empty( $shortcut_meta_query ) ) {
			if ( ! empty( $this->args['meta_query'] ) ) {
				$this->args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					$shortcut_meta_query,
					$this->args['meta_query'],
				);
			} else {
				$this->args['meta_query'] = array( $shortcut_meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}
		}
	}

	/**
	 * Sanitizes the 'status' query var.
	 *
	 * @return void
	 */
	private function sanitize_status(): void {
		// Sanitize status.
		$valid_statuses = array_keys( wc_get_order_statuses() );

		if ( empty( $this->args['status'] ) || 'any' === $this->args['status'] ) {
			$this->args['status'] = $valid_statuses;
		} elseif ( 'all' === $this->args['status'] ) {
			$this->args['status'] = array();
		} else {
			$this->args['status'] = is_array( $this->args['status'] ) ? $this->args['status'] : array( $this->args['status'] );

			foreach ( $this->args['status'] as &$status ) {
				$status = in_array( 'wc-' . $status, $valid_statuses, true ) ? 'wc-' . $status : $status;
			}

			$this->args['status'] = array_unique( array_filter( $this->args['status'] ) );
		}
	}

	/**
	 * Parses and sanitizes the 'orderby' query var.
	 *
	 * @return void
	 */
	private function sanitize_order_orderby(): void {
		// Allowed keys.
		// TODO: rand, meta keys, etc.
		$allowed_keys = array( 'ID', 'id', 'type', 'date', 'modified', 'parent' );

		// Translate $orderby to a valid field.
		$mapping = array(
			'ID'            => "{$this->tables['orders']}.id",
			'id'            => "{$this->tables['orders']}.id",
			'type'          => "{$this->tables['orders']}.type",
			'date'          => "{$this->tables['orders']}.date_created_gmt",
			'date_created'  => "{$this->tables['orders']}.date_created_gmt",
			'modified'      => "{$this->tables['orders']}.date_updated_gmt",
			'date_modified' => "{$this->tables['orders']}.date_updated_gmt",
			'parent'        => "{$this->tables['orders']}.parent_order_id",
			'total'         => "{$this->tables['orders']}.total_amount",
			'order_total'   => "{$this->tables['orders']}.total_amount",
		);

		$order   = $this->args['order'] ?? '';
		$orderby = $this->args['orderby'] ?? '';

		if ( 'none' === $orderby ) {
			return;
		}

		if ( is_string( $orderby ) ) {
			$orderby = array( $orderby => $order );
		}

		$this->args['orderby'] = array();
		foreach ( $orderby as $order_key => $order ) {
			if ( isset( $mapping[ $order_key ] ) ) {
				$this->args['orderby'][ $mapping[ $order_key ] ] = $this->sanitize_order( $order );
			}
		}
	}

	/**
	 * Makes sure the order in an ORDER BY statement is either 'ASC' o 'DESC'.
	 *
	 * @param string $order The unsanitized order.
	 * @return string The sanitized order.
	 */
	private function sanitize_order( string $order ): string {
		$order = strtoupper( $order );

		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
	}

	/**
	 * Builds the final SQL query to be run.
	 *
	 * @return void
	 */
	private function build_query(): void {
		$this->maybe_remap_args();

		// Build query.
		$this->process_orders_table_query_args();
		$this->process_operational_data_table_query_args();
		$this->process_addresses_table_query_args();

		// Meta queries.
		if ( ! empty( $this->args['meta_query'] ) ) {
			$this->meta_query = new OrdersTableMetaQuery( $this );

			$sql = $this->meta_query->get_sql_clauses();

			$this->join  = $sql['join'] ? array_merge( $this->join, $sql['join'] ) : $this->join;
			$this->where = $sql['where'] ? array_merge( $this->where, array( $sql['where'] ) ) : $this->where;

			if ( $sql['join'] ) {
				$this->groupby[] = "{$this->tables['orders']}.id";
			}
		}

		$this->process_orderby();
		$this->process_limit();

		$orders_table = $this->tables['orders'];

		// DISTINCT.
		$distinct = '';

		// SELECT [fields].
		$this->fields = "{$orders_table}.id";
		$fields       = $this->fields;

		// SQL_CALC_FOUND_ROWS.
		if ( ( ! $this->arg_isset( 'no_found_rows' ) || ! $this->args['no_found_rows'] ) && $this->limits ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		} else {
			$found_rows = '';
		}

		// JOIN.
		$join = implode( ' ', $this->join );

		// WHERE.
		$where = '1=1';
		foreach ( $this->where as $_where ) {
			$where .= " AND ({$_where})";
		}

		// ORDER BY.
		$orderby = $this->orderby ? ( 'ORDER BY ' . implode( ', ', $this->orderby ) ) : '';

		// LIMITS.
		$limits = $this->limits ? 'LIMIT ' . implode( ',', $this->limits ) : '';

		// GROUP BY.
		$groupby = $this->groupby ? 'GROUP BY ' . implode( ', ', (array) $this->groupby ) : '';

		$this->sql = "SELECT $found_rows $distinct $fields FROM $orders_table $join WHERE $where $groupby $orderby $limits";
	}

	/**
	 * Generates a properly escaped and sanitized WHERE condition for a given field.
	 *
	 * @param string $table    The table the field belongs to.
	 * @param string $field    The field or column name.
	 * @param string $operator The operator to use in the condition. Defaults to '=' or 'IN' depending on $value.
	 * @param mixed  $value    The value.
	 * @param string $type     The column type as specified in {@see OrdersTableDataStore} column mappings.
	 * @return string The resulting WHERE condition.
	 */
	public function where( string $table, string $field, string $operator, $value, string $type ): string {
		global $wpdb;

		$db_util  = wc_get_container()->get( DatabaseUtil::class );
		$operator = strtoupper( '' !== $operator ? $operator : '=' );

		try {
			$format = $db_util->get_wpdb_format_for_type( $type );
		} catch ( \Exception $e ) {
			$format = '%s';
		}

		// = and != can be shorthands for IN and NOT in for array values.
		if ( is_array( $value ) && '=' === $operator ) {
			$operator = 'IN';
		} elseif ( is_array( $value ) && '!=' === $operator ) {
			$operator = 'NOT IN';
		}

		if ( ! in_array( $operator, array( '=', '!=', 'IN', 'NOT IN' ), true ) ) {
			return false;
		}

		if ( is_array( $value ) ) {
			$value = array_map( array( $db_util, 'format_object_value_for_db' ), $value, array_fill( 0, count( $value ), $type ) );
		} else {
			$value = $db_util->format_object_value_for_db( $value, $type );
		}

		if ( is_array( $value ) ) {
			$placeholder = array_fill( 0, count( $value ), $format );
			$placeholder = '(' . implode( ',', $placeholder ) . ')';
		} else {
			$placeholder = $format;
		}

		$sql = $wpdb->prepare( "{$table}.{$field} {$operator} {$placeholder}", $value ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return $sql;
	}

	/**
	 * Processes fields related to the orders table.
	 *
	 * @return void
	 */
	private function process_orders_table_query_args(): void {
		$this->sanitize_status();

		$fields = array_filter(
			array(
				'id',
				'status',
				'type',
				'currency',
				'tax_amount',
				'customer_id',
				'billing_email',
				'total_amount',
				'parent_order_id',
				'payment_method',
				'payment_method_title',
				'transaction_id',
				'ip_address',
				'user_agent',
			),
			array( $this, 'arg_isset' )
		);

		foreach ( $fields as $arg_key ) {
			$this->where[] = $this->where( $this->tables['orders'], $arg_key, '=', $this->args[ $arg_key ], $this->mappings['orders'][ $arg_key ]['type'] );
		}

		if ( $this->arg_isset( 'parent_exclude' ) ) {
			$this->where[] = $this->where( $this->tables['orders'], 'parent_order_id', '!=', $this->args['parent_exclude'], 'int' );
		}

		if ( $this->arg_isset( 'exclude' ) ) {
			$this->where[] = $this->where( $this->tables['orders'], 'id', '!=', $this->args['exclude'], 'int' );
		}

		// 'customer' is a very special field.
		if ( $this->arg_isset( 'customer' ) ) {
			$customer_query = $this->generate_customer_query( $this->args['customer'] );

			if ( $customer_query ) {
				$this->where[] = $customer_query;
			}
		}
	}

	/**
	 * Generate SQL conditions for the 'customer' query.
	 *
	 * @param array  $values   List of customer ids or emails.
	 * @param string $relation 'OR' or 'AND' relation used to build the customer query.
	 * @return string SQL to be used in a WHERE clause.
	 */
	private function generate_customer_query( $values, string $relation = 'OR' ): string {
		$values = is_array( $values ) ? $values : array( $values );
		$ids    = array();
		$emails = array();

		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				$sql      = $this->generate_customer_query( $value, 'AND' );
				$pieces[] = $sql ? '(' . $sql . ')' : '';
			} elseif ( is_numeric( $value ) ) {
				$ids[] = absint( $value );
			} elseif ( is_string( $value ) && is_email( $value ) ) {
				$emails[] = sanitize_email( $value );
			}
		}

		if ( $ids ) {
			$pieces[] = $this->where( $this->tables['orders'], 'customer_id', '=', $ids, 'int' );
		}

		if ( $emails ) {
			$pieces[] = $this->where( $this->tables['orders'], 'billing_email', '=', $emails, 'string' );
		}

		return $pieces ? implode( " $relation ", $pieces ) : '';
	}

	/**
	 * Processes fields related to the operational data table.
	 *
	 * @return void
	 */
	private function process_operational_data_table_query_args(): void {
		$fields = array_filter(
			array(
				'created_via',
				'woocommerce_version',
				'prices_include_tax',
				'order_key',
				'discount_total_amount',
				'discount_tax_amount',
				'shipping_total_amount',
				'shipping_tax_amount',
			),
			array( $this, 'arg_isset' )
		);

		if ( ! $fields ) {
			return;
		}

		$this->join[] = "INNER JOIN {$this->tables['operational_data']} ON ( {$this->tables['orders']}.id = {$this->tables['operational_data']}.order_id )";

		foreach ( $fields as $arg_key ) {
			$this->where[] = $this->where( $this->tables['operational_data'], $arg_key, '=', $this->args[ $arg_key ], $this->mappings['operational_data'][ $arg_key ]['type'] );
		}
	}

	/**
	 * Processes fields related to the addresses table.
	 *
	 * @return void
	 */
	private function process_addresses_table_query_args(): void {
		global $wpdb;

		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			$fields = array_filter(
				array(
					$address_type . '_first_name',
					$address_type . '_last_name',
					$address_type . '_company',
					$address_type . '_address_1',
					$address_type . '_address_2',
					$address_type . '_city',
					$address_type . '_state',
					$address_type . '_postcode',
					$address_type . '_country',
					$address_type . '_phone',
				),
				array( $this, 'arg_isset' )
			);

			if ( ! $fields ) {
				continue;
			}

			$this->join[] = $wpdb->prepare(
				"INNER JOIN {$this->tables['addresses']} AS {$address_type} ON ( {$this->tables['orders']}.id = {$address_type}.order_id AND {$address_type}.address_type = %s )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$address_type
			);

			foreach ( $fields as $arg_key ) {
				$column_name = str_replace( "{$address_type}_", '', $arg_key );

				$this->where[] = $this->where(
					$address_type,
					$column_name,
					'=',
					$this->args[ $arg_key ],
					$this->mappings[ "{$address_type}_address" ][ $column_name ]['type']
				);
			}
		}
	}

	/**
	 * Generates the ORDER BY clause.
	 *
	 * @return void
	 */
	private function process_orderby(): void {
		// 'order' and 'orderby' vars.
		$this->args['order'] = $this->sanitize_order( $this->args['order'] ?? '' );
		$this->sanitize_order_orderby();

		$orderby = $this->args['orderby'];

		if ( 'none' === $orderby ) {
			$this->orderby = '';
			return;
		}

		$orderby_array = array();
		foreach ( $this->args['orderby'] as $_orderby => $order ) {
			$orderby_array[] = "{$_orderby} {$order}";
		}

		$this->orderby = $orderby_array;
	}

	/**
	 * Generates the limits to be used in the LIMIT clause.
	 *
	 * @return void
	 */
	private function process_limit(): void {
		$limit  = ( $this->arg_isset( 'limit' ) ? absint( $this->args['limit'] ) : false );
		$page   = ( $this->arg_isset( 'page' ) ? absint( $this->args['page'] ) : 1 );
		$offset = ( $this->arg_isset( 'offset' ) ? absint( $this->args['offset'] ) : false );

		if ( ! $limit ) {
			return;
		}

		$this->limits = array( $offset ? $offset : absint( ( $page - 1 ) * $limit ), $limit );
	}

	/**
	 * Checks if a query var is set (i.e. not one of the "skipped values").
	 *
	 * @param string $arg_key Query var.
	 * @return bool TRUE if query var is set.
	 */
	public function arg_isset( string $arg_key ): bool {
		return ( isset( $this->args[ $arg_key ] ) && ! in_array( $this->args[ $arg_key ], self::SKIPPED_VALUES, true ) );
	}

	/**
	 * Runs the SQL query.
	 *
	 * @return void
	 */
	private function run_query(): void {
		global $wpdb;

		// Run query.
		$this->orders = array_map( 'absint', $wpdb->get_col( $this->sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Set max_num_pages and found_orders if necessary.
		if ( ( $this->arg_isset( 'no_found_rows' ) && ! $this->args['no_found_rows'] ) || empty( $this->orders ) ) {
			return;
		}

		if ( $this->limits ) {
			$this->found_orders  = absint( $wpdb->get_var( 'SELECT FOUND_ROWS()' ) );
			$this->max_num_pages = (int) ceil( $this->found_orders / $this->args['limit'] );
		} else {
			$this->found_orders = count( $this->orders );
		}
	}

	/**
	 * Make some private available for backwards compatibility.
	 *
	 * @param string $name Property to get.
	 * @return mixed
	 */
	public function __get( string $name ) {
		switch ( $name ) {
			case 'found_orders':
			case 'found_posts':
				return $this->found_orders;
			case 'max_num_pages':
				return $this->max_num_pages;
			case 'posts':
			case 'orders':
				return $this->results;
			default:
				break;
		}
	}

	/**
	 * Returns the value of one of the query arguments.
	 *
	 * @param string $arg_name Query var.
	 * @return mixed
	 */
	public function get( string $arg_name ) {
		return $this->args[ $arg_name ] ?? null;
	}

	/**
	 * Returns the name of one of the OrdersTableDatastore tables.
	 *
	 * @param string $table_id Table identifier. One of 'orders', 'operational_data', 'addresses', 'meta'.
	 * @return string The prefixed table name.
	 * @throws \Exception When table ID is not found.
	 */
	public function get_table_name( string $table_id = '' ): string {
		if ( ! isset( $this->tables[ $table_id ] ) ) {
			// Translators: %s is a table identifier.
			throw new \Exception( sprintf( __( 'Invalid table id: %s.', 'woocommerce' ), $table_id ) );
		}

		return $this->tables[ $table_id ];
	}

}
