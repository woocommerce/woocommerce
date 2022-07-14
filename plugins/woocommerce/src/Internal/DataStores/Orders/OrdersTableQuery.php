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
	private const SKIPPED_VALUES = array( '', array(), null );

	/**
	 * Query vars set by the user.
	 *
	 * @var array
	 */
	private $original_args = array();

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

		$this->original_args = $args;
		$this->args          = $args;

		$this->remap_args();
		$this->validate_args();
		$this->build_query();
		$this->run_query();
	}

	/**
	 * Remaps some legacy and `WP_Query` specific query vars to vars available in the customer order table scheme.
	 *
	 * @return void
	 */
	private function remap_args(): void {
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
	}

	/**
	 * Validates query vars.
	 *
	 * @return void
	 */
	private function validate_args(): void {
		// Order statuses.
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

		// 'order' and 'orderby' vars.
		$this->args['order'] = $this->sanitize_order( $this->args['order'] ?? '' );
		$this->sanitize_orderby( $this->args['orderby'] ?? 'date' );

		// TODO: 'type', 'customer'.
		// TODO: Keys from $this->internal_meta_keys (i.e. _key)
		// TODO: meta_query
		// TODO: customer_note, name.
		unset( $this->args['type'], $this->args['customer'], $this->args['meta_query'], $this->args['customer_note'], $this->args['name'] );
	}

	/**
	 * Parses and sanitizes the 'orderby' query var.
	 *
	 * @return void
	 */
	private function sanitize_orderby(): void {
		// Allowed keys.
		// TODO: rand, metakeys, etc.
		$allowed_keys = array( 'ID', 'id', 'type', 'date', 'modified', 'parent' );

		// Translate $orderby to a valid field.
		$mapping = array(
			'ID'       => 'orders.id',
			'id'       => 'orders.id',
			'type'     => 'orders.type',
			'date'     => 'orders.date_created_gmt',
			'modified' => 'orders.date_updated_gmt',
			'parent'   => 'orders.parent_order_id',
		);

		$order   = $this->args['order'];
		$orderby = $this->args['orderby'];

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
		$order = strtoupper( is_string( $order ) ? $order : '' );

		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
	}

	/**
	 * Builds the final SQL query to be run.
	 *
	 * @return void
	 */
	private function build_query(): void {
		$this->parse_core_fields();
		$this->parse_orderby();
		$this->parse_limit();

		// SELECT [fields].
		$this->fields = 'orders.id';
		$fields       = $this->fields;

		// SQL_CALC_FOUND_ROWS.
		if ( ( ! $this->arg_isset( 'no_found_rows' ) || ! $this->args['no_found_rows'] ) && $this->limits ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		} else {
			$found_rows = '';
		}

		// JOIN.
		$join = '';
		foreach ( $this->join as $alias => $_join ) {
			$join .= " LEFT JOIN ${_join['table']} {$alias} ON ({$_join['on']})";
		}

		// WHERE.
		$where = '1=1';
		foreach ( $this->where as $_where ) {
			$condition = $this->get_where_condition( $_where['table'], $_where['field'], $_where['value'], $_where['type'] ?? false, $_where['operator'] ?? false );

			if ( ! $condition ) {
				continue;
			}

			$where .= " AND ({$condition})";
		}

		// ORDER BY.
		$orderby = 'ORDER BY ' . $this->orderby;

		// LIMITS.
		$limits = $this->limits ? 'LIMIT ' . implode( ',', $this->limits ) : '';

		$distinct = '';
		$groupby  = '';

		$orders_table = $this->tables['orders'];

		$this->sql = "SELECT $found_rows $distinct $fields FROM $orders_table orders $join WHERE $where $groupby $orderby $limits";
	}

	/**
	 * Generates a properly escaped and sanitized WHERE condition for a given field.
	 *
	 * @param string $table    The table the field belongs to.
	 * @param string $field    The field or column name.
	 * @param mixed  $value    The value.
	 * @param string $type     The column type as specified in {@see OrdersTableDataStore} column mappings.
	 * @param string $operator The operator to use in the condition. Defaults to '=' or 'IN' depending on $value.
	 * @return string The resulting WHERE condition.
	 */
	private function get_where_condition( string $table, string $field, $value, string $type, string $operator = '' ): string {
		global $wpdb;

		$db_util  = wc_get_container()->get( DatabaseUtil::class );
		$operator = strtoupper( $operator ? $operator : '=' );

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
			$placeholder = array_fill( 0, count( $value ), $format );
			$placeholder = '(' . implode( ',', $placeholder ) . ')';
		} else {
			$placeholder = $format;
		}

		$sql = $wpdb->prepare( "{$table}.{$field} {$operator} {$placeholder}", $value ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return $sql;
	}

	/**
	 * Processes query vars for all supported core fields from custom order tables.
	 *
	 * @return void
	 */
	private function parse_core_fields(): void {
		global $wpdb;

		// Orders.
		foreach ( array( 'id', 'status', 'type', 'currency', 'tax_amount', 'customer_id', 'billing_email', 'total_amount', 'parent_order_id', 'payment_method', 'payment_method_title', 'transaction_id', 'ip_address', 'user_agent' ) as $arg_key ) {
			if ( ! $this->arg_isset( $arg_key ) ) {
				continue;
			}

			$this->where[] = array(
				'table' => 'orders',
				'field' => $arg_key,
				'value' => $this->args[ $arg_key ],
				'type'  => $this->mappings['orders'][ $arg_key ]['type'],
			);
		}

		if ( $this->arg_isset( 'parent_exclude' ) ) {
			$this->where[] = array(
				'table'    => 'orders',
				'field'    => 'id',
				'operator' => '!=',
				'value'    => $this->args['parent_exclude'],
				'type'     => 'int',
			);
		}

		if ( $this->arg_isset( 'exclude' ) ) {
			$this->where[] = array(
				'table'    => 'orders',
				'field'    => 'id',
				'operator' => '!=',
				'value'    => $this->args['exclude'],
				'type'     => 'int',
			);
		}

		// Operational data.
		foreach ( array( 'created_via', 'woocommerce_version', 'prices_include_tax', 'order_key', 'discount_total_amount', 'discount_tax_amount', 'shipping_total_amount', 'shipping_tax_amount' ) as $arg_key ) {
			if ( ! $this->arg_isset( $arg_key ) ) {
				continue;
			}

			if ( ! isset( $this->join['operational_data'] ) ) {
				$this->join['operational_data'] = array(
					'table' => $this->tables['operational_data'],
					'on'    => 'orders.id = operational_data.order_id',
				);
			}

			$this->where[] = array(
				'table' => 'operational_data',
				'field' => $arg_key,
				'value' => $this->args[ $arg_key ],
				'type'  => $this->mappings['operational_data'][ $arg_key ]['type'],
			);
		}

		// Process billing & shipping address fields.
		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone' ) as $field_name ) {
				if ( ! $this->arg_isset( "{$address_type}_{$field_name}" ) ) {
					continue;
				}

				if ( ! isset( $this->join[ "{$address_type}_address" ] ) ) {
					$this->join[ "{$address_type}_address" ] = array(
						'table' => $this->tables['addresses'],
						'on'    => $wpdb->prepare( "orders.id = {$address_type}_address.order_id AND {$address_type}_address.address_type = %s", $address_type ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					);
				}

				$this->where[] = array(
					'table' => "{$address_type}_address",
					'field' => $field_name,
					'value' => $this->args[ "{$address_type}_{$field_name}" ],
					'type'  => $this->mappings[ "{$address_type}_address" ][ $field_name ]['type'],
				);
			}
		}
	}

	/**
	 * Generates the ORDER BY clause.
	 *
	 * @return void
	 */
	private function parse_orderby(): void {
		$orderby = $this->args['orderby'];

		if ( 'none' === $orderby ) {
			$this->orderby = '';
			return;
		}

		$orderby_array = array();
		foreach ( $this->args['orderby'] as $_orderby => $order ) {
			$orderby_array[] = "{$_orderby} {$order}";
		}

		$this->orderby = implode( ', ', $orderby_array );
	}

	/**
	 * Generates the limits to be used in the LIMIT clause.
	 *
	 * @return void
	 */
	private function parse_limit(): void {
		$paginate = ( $this->arg_isset( 'paginate' ) ? (bool) $this->args['paginate'] : false );
		$limit    = ( $this->arg_isset( 'limit' ) ? absint( $this->args['limit'] ) : false );
		$page     = ( $this->arg_isset( 'page' ) ? absint( $this->args['page'] ) : 1 );
		$offset   = ( $this->arg_isset( 'offset' ) ? absint( $this->args['offset'] ) : false );

		if ( ! $paginate ) {
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
	private function arg_isset( string $arg_key ): bool {
		return ( isset( $this->args[ $arg_key ] ) && ! in_array( $this->args[ $arg_key ], self::SKIPPED_VALUES, true ) );
	}

	/**
	 * Runs the SQL query.
	 *
	 * @return void
	 */
	private function run_query() {
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

}
