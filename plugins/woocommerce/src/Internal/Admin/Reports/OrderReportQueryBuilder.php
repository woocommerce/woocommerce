<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Reports;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Builds SQL clauses for legacy order reports.
 *
 * @internal
 */
class OrderReportQueryBuilder {

	/**
	 * Lazy cache for HPOS report schema details built during a single query build.
	 *
	 * @var array<string,mixed>|null
	 */
	private $report_schema_cache = null;

	/**
	 * Build the SQL clauses for a legacy order report query.
	 *
	 * @param array $args       Parsed report arguments.
	 * @param int   $start_date Start date as a Unix timestamp.
	 * @param int   $end_date   End date as a Unix timestamp.
	 *
	 * @return array<string,string> SQL clauses keyed by select/from/join/where/group_by/order_by/limit.
	 */
	public function build_query( array $args, int $start_date, int $end_date ): array {
		global $wpdb;

		$this->report_schema_cache = null;

		$data                = $args['data'] ?? array();
		$where               = $args['where'] ?? array();
		$where_meta          = $args['where_meta'] ?? array();
		$group_by            = $args['group_by'] ?? '';
		$order_by            = $args['order_by'] ?? '';
		$limit               = $args['limit'] ?? '';
		$filter_range        = $args['filter_range'] ?? false;
		$order_types         = $args['order_types'] ?? wc_get_order_types( 'reports' );
		$order_status        = $args['order_status'] ?? array();
		$parent_order_status = $args['parent_order_status'] ?? false;

		$select = array();
		$joins  = array();

		foreach ( $data as $raw_key => $value ) {
			$part = $this->build_data_select_and_joins( $raw_key, $value );
			if ( null === $part ) {
				continue;
			}
			$select[] = $part['select'];
			$joins    = array_merge( $joins, $part['joins'] );
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( is_array( $value ) ) {
					$joins = array_merge( $joins, $this->build_where_meta_joins( $value ) );
				}
			}
		}

		if ( ! empty( $parent_order_status ) ) {
			$joins = array_merge( $joins, $this->build_parent_orders_join() );
		}

		$is_hpos = OrderUtil::custom_orders_table_usage_is_enabled();

		$query           = array();
		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = $is_hpos
			? 'FROM ' . OrderUtil::get_table_for_orders() . ' AS orders'
			: "FROM {$wpdb->posts} AS posts";
		$query['join']   = implode( ' ', $joins );
		$query['where']  = $this->build_where_clause( $order_types, $order_status, $parent_order_status, (bool) $filter_range, $start_date, $end_date );

		if ( ! empty( $where_meta ) ) {
			$query['where'] .= $this->build_where_meta_predicates( $where_meta );
		}

		if ( ! empty( $where ) ) {
			$query['where'] .= $this->build_where_predicates( $where );
		}

		if ( $group_by ) {
			$group_by_clause   = $is_hpos ? $this->translate_legacy_sql_fragment_hpos( $group_by ) : $group_by;
			$query['group_by'] = "GROUP BY {$group_by_clause}";
		}

		if ( $order_by ) {
			$order_by_clause   = $is_hpos ? $this->translate_legacy_sql_fragment_hpos( $order_by ) : $order_by;
			$query['order_by'] = "ORDER BY {$order_by_clause}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		return $query;
	}

	/**
	 * Resolve a single `$data` entry into a SELECT fragment plus the JOINs it requires.
	 *
	 * @param string $raw_key Original key from the `$data` array.
	 * @param array  $value   The `$data` row.
	 *
	 * @return array{select: string, joins: array<string,string>}|null Null when the row has no resolvable type.
	 */
	private function build_data_select_and_joins( $raw_key, $value ) {
		$key       = sanitize_key( $raw_key );
		$distinct  = isset( $value['distinct'] ) ? 'DISTINCT' : '';
		$join_type = $value['join_type'] ?? 'INNER';
		$type      = $value['type'] ?? '';
		$get_key   = '';
		$joins     = array();

		switch ( $type ) {
			case 'meta':
				list( $get_key, $joins ) = $this->resolve_meta_select( $raw_key, $key, $join_type );
				break;
			case 'parent_meta':
				list( $get_key, $joins ) = $this->resolve_parent_meta_select( $raw_key, $key, $join_type );
				break;
			case 'post_data':
				$get_key = OrderUtil::custom_orders_table_usage_is_enabled()
					? $this->translate_post_column_hpos( $key )
					: "posts.{$key}";
				break;
			case 'order_item_meta':
				$get_key = "order_item_meta_{$key}.meta_value";
				$joins   = $this->build_order_item_meta_joins( $key, $raw_key, $value['order_item_type'] ?? '', $join_type );
				break;
			case 'order_item':
				$get_key = "order_items.{$key}";
				$joins   = $this->build_order_items_join( $join_type );
				break;
		}

		if ( '' === $get_key ) {
			return null;
		}

		$expr = $value['function']
			? "{$value['function']}({$distinct} {$get_key})"
			: "{$distinct} {$get_key}";

		return array(
			'select' => "{$expr} as {$value['name']}",
			'joins'  => $joins,
		);
	}

	/**
	 * Resolve a `type=meta` SELECT.
	 *
	 * @param string $raw_key   Meta key.
	 * @param string $key       Sanitized version of the meta key.
	 * @param string $join_type Join type.
	 *
	 * @return array{0: string, 1: array<string,string>} SELECT fragment and JOINs.
	 */
	private function resolve_meta_select( $raw_key, $key, $join_type ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$schema = $this->get_report_schema();
			if ( isset( $schema['order_column'][ $raw_key ] ) ) {
				return array( $schema['order_column'][ $raw_key ], array() );
			}
			if ( isset( $schema['op_data_column'][ $raw_key ] ) ) {
				return array( $schema['op_data_column'][ $raw_key ], $this->build_op_data_join() );
			}
		}

		return array(
			"meta_{$key}.meta_value",
			$this->build_meta_join( $key, $raw_key, $join_type ),
		);
	}

	/**
	 * Resolve a `type=parent_meta` SELECT.
	 *
	 * @param string $raw_key   Meta key on the parent order.
	 * @param string $key       Sanitized version of the meta key.
	 * @param string $join_type Join type.
	 *
	 * @return array{0: string, 1: array<string,string>} SELECT fragment and JOINs.
	 */
	private function resolve_parent_meta_select( $raw_key, $key, $join_type ) {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$schema = $this->get_report_schema();
			if ( isset( $schema['order_column'][ $raw_key ] ) ) {
				$column = substr( $schema['order_column'][ $raw_key ], strlen( 'orders.' ) );
				return array( "parent_orders.{$column}", $this->build_parent_orders_join() );
			}
			if ( isset( $schema['op_data_column'][ $raw_key ] ) ) {
				$column = substr( $schema['op_data_column'][ $raw_key ], strlen( 'op_data.' ) );
				$joins  = array_merge( $this->build_parent_orders_join(), $this->build_parent_op_data_join() );
				return array( "parent_op_data.{$column}", $joins );
			}
		}

		return array(
			"parent_meta_{$key}.meta_value",
			$this->build_parent_meta_join( $key, $raw_key, $join_type ),
		);
	}

	/**
	 * Build the JOINs required to satisfy a `where_meta` predicate.
	 *
	 * @param array $value A `where_meta` row.
	 *
	 * @return array<string,string> JOINs keyed by alias.
	 */
	private function build_where_meta_joins( $value ) {
		$type      = $value['type'] ?? '';
		$join_type = $value['join_type'] ?? 'INNER';
		$meta_key  = $value['meta_key'];
		$key       = sanitize_key( is_array( $meta_key ) ? $meta_key[0] . '_array' : $meta_key );

		if ( 'order_item_meta' === $type ) {
			global $wpdb;
			$order_id_col = OrderUtil::custom_orders_table_usage_is_enabled() ? 'orders.id' : 'posts.ID';
			$alias        = "order_item_meta_{$key}";
			return array(
				'order_items' => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON {$order_id_col} = order_items.order_id",
				$alias        => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS {$alias} ON order_items.order_item_id = {$alias}.order_item_id",
			);
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$schema = $this->get_report_schema();
			if ( ! is_array( $meta_key ) && isset( $schema['where_meta_column'][ $meta_key ] ) ) {
				return array();
			}
			$alias = "meta_{$key}";
			return array( $alias => "{$join_type} JOIN " . OrderUtil::get_table_for_order_meta() . " AS {$alias} ON orders.id = {$alias}.order_id" );
		}

		$alias = "meta_{$key}";
		return array( $alias => "{$join_type} JOIN " . OrderUtil::get_table_for_order_meta() . " AS {$alias} ON posts.ID = {$alias}.post_id" );
	}

	/**
	 * Build the JOIN required to look up parent order data.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_parent_orders_join() {
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return array(
				'parent_orders' => 'LEFT JOIN ' . OrderUtil::get_table_for_orders() . ' AS parent_orders ON orders.parent_order_id = parent_orders.id',
			);
		}

		global $wpdb;
		return array(
			'parent' => "LEFT JOIN {$wpdb->posts} AS parent ON posts.post_parent = parent.ID",
		);
	}

	/**
	 * Build the JOIN required to look up the parent order's operational-data row.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_parent_op_data_join() {
		return array(
			'parent_op_data' => 'LEFT JOIN ' . OrdersTableDataStore::get_operational_data_table_name() . ' AS parent_op_data ON parent_orders.id = parent_op_data.order_id',
		);
	}

	/**
	 * Build the JOIN onto `wc_order_operational_data`.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_op_data_join() {
		return array(
			'op_data' => 'LEFT JOIN ' . OrdersTableDataStore::get_operational_data_table_name() . ' AS op_data ON orders.id = op_data.order_id',
		);
	}

	/**
	 * Build the JOIN onto the orders-meta table for an order meta lookup.
	 *
	 * @param string $key       Sanitized meta key for the alias.
	 * @param string $raw_key   Original meta key.
	 * @param string $join_type Join type.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_meta_join( $key, $raw_key, $join_type ) {
		$alias      = "meta_{$key}";
		$meta_table = OrderUtil::get_table_for_order_meta();

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return array( $alias => "{$join_type} JOIN {$meta_table} AS {$alias} ON ( orders.id = {$alias}.order_id AND {$alias}.meta_key = '{$raw_key}' )" );
		}

		return array( $alias => "{$join_type} JOIN {$meta_table} AS {$alias} ON ( posts.ID = {$alias}.post_id AND {$alias}.meta_key = '{$raw_key}' )" );
	}

	/**
	 * Build the JOIN onto the orders-meta table keyed on the parent order id.
	 *
	 * @param string $key       Sanitized meta key for the alias.
	 * @param string $raw_key   Original meta key.
	 * @param string $join_type Join type.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_parent_meta_join( $key, $raw_key, $join_type ) {
		$alias      = "parent_meta_{$key}";
		$meta_table = OrderUtil::get_table_for_order_meta();

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return array( $alias => "{$join_type} JOIN {$meta_table} AS {$alias} ON (orders.parent_order_id = {$alias}.order_id) AND ({$alias}.meta_key = '{$raw_key}')" );
		}

		return array( $alias => "{$join_type} JOIN {$meta_table} AS {$alias} ON (posts.post_parent = {$alias}.post_id) AND ({$alias}.meta_key = '{$raw_key}')" );
	}

	/**
	 * Build the JOIN onto `wp_woocommerce_order_items`.
	 *
	 * @param string $join_type Join type.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_order_items_join( $join_type ) {
		global $wpdb;
		$order_id_col = OrderUtil::custom_orders_table_usage_is_enabled() ? 'orders.id' : 'posts.ID';

		return array(
			'order_items' => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON {$order_id_col} = order_items.order_id",
		);
	}

	/**
	 * Build the JOIN pair used by `type=order_item_meta` data entries.
	 *
	 * @param string $key             Sanitized meta key for the itemmeta alias.
	 * @param string $raw_key         Original meta key.
	 * @param string $order_item_type Optional order-item type filter.
	 * @param string $join_type       Join type.
	 *
	 * @return array<string,string> JOINs keyed by alias.
	 */
	private function build_order_item_meta_joins( $key, $raw_key, $order_item_type, $join_type ) {
		global $wpdb;
		$order_id_col = OrderUtil::custom_orders_table_usage_is_enabled() ? 'orders.id' : 'posts.ID';
		$items_join   = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ({$order_id_col} = order_items.order_id)";

		if ( '' !== $order_item_type ) {
			$items_join .= " AND (order_items.order_item_type = '{$order_item_type}')";
		}

		$itemmeta_alias = "order_item_meta_{$key}";
		return array(
			'order_items'   => $items_join,
			$itemmeta_alias => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS {$itemmeta_alias} ON " .
				"(order_items.order_item_id = {$itemmeta_alias}.order_item_id) " .
				" AND ({$itemmeta_alias}.meta_key = '{$raw_key}')",
		);
	}

	/**
	 * Build the top-level WHERE clause.
	 *
	 * @param array       $order_types         Order types to restrict to.
	 * @param array|false $order_status        Order statuses without the `wc-` prefix.
	 * @param array|false $parent_order_status Parent order statuses without the `wc-` prefix.
	 * @param bool        $filter_range        Whether to bound the query by date.
	 * @param int         $start_date          Start date as a Unix timestamp.
	 * @param int         $end_date            End date as a Unix timestamp.
	 *
	 * @return string WHERE clause.
	 */
	private function build_where_clause( $order_types, $order_status, $parent_order_status, $filter_range, $start_date, $end_date ) {
		$is_hpos       = OrderUtil::custom_orders_table_usage_is_enabled();
		$type_column   = $is_hpos ? 'orders.type' : 'posts.post_type';
		$status_column = $is_hpos ? 'orders.status' : 'posts.post_status';

		$clause = "
			WHERE 	{$type_column} 	IN ( '" . implode( "','", $order_types ) . "' )
			";

		if ( ! empty( $order_status ) ) {
			$clause .= "
				AND 	{$status_column} 	IN ( 'wc-" . implode( "','wc-", $order_status ) . "')
			";
		}

		if ( ! empty( $parent_order_status ) ) {
			$clause .= $this->build_parent_order_status_where_clause( $parent_order_status, $order_status );
		}

		if ( $filter_range ) {
			$clause .= $this->build_filter_range_where_clause( $start_date, $end_date );
		}

		return $clause;
	}

	/**
	 * Build the WHERE fragment that filters by the parent order's status.
	 *
	 * @param array       $parent_order_status Status slugs without `wc-` prefix.
	 * @param array|false $order_status        If non-empty, parent-status NULL is allowed.
	 *
	 * @return string WHERE fragment.
	 */
	private function build_parent_order_status_where_clause( $parent_order_status, $order_status ) {
		$is_hpos        = OrderUtil::custom_orders_table_usage_is_enabled();
		$status_col     = $is_hpos ? 'parent_orders.status' : 'parent.post_status';
		$null_check_col = $is_hpos ? 'parent_orders.id' : 'parent.ID';
		$statuses_in    = "'wc-" . implode( "','wc-", $parent_order_status ) . "'";

		if ( ! empty( $order_status ) ) {
			return " AND ( {$status_col} IN ( {$statuses_in} ) OR {$null_check_col} IS NULL ) ";
		}

		return " AND {$status_col} IN ( {$statuses_in} ) ";
	}

	/**
	 * Build the WHERE fragment that bounds the query by report date range.
	 *
	 * @param int $start_date Start date as a Unix timestamp.
	 * @param int $end_date   End date as a Unix timestamp.
	 *
	 * @return string WHERE fragment.
	 */
	private function build_filter_range_where_clause( $start_date, $end_date ) {
		// phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$schema    = $this->get_report_schema();
			$start_gmt = $start_date - $schema['gmt_offset'];
			$end_gmt   = strtotime( '+1 DAY', $end_date ) - $schema['gmt_offset'];
			return "
				AND 	orders.date_created_gmt >= '" . gmdate( 'Y-m-d H:i:s', $start_gmt ) . "'
				AND 	orders.date_created_gmt < '" . gmdate( 'Y-m-d H:i:s', $end_gmt ) . "'
			";
		}

		return "
			AND 	posts.post_date >= '" . date( 'Y-m-d H:i:s', $start_date ) . "'
			AND 	posts.post_date < '" . date( 'Y-m-d H:i:s', strtotime( '+1 DAY', $end_date ) ) . "'
		";
		// phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date
	}

	/**
	 * Build the WHERE fragment for caller-supplied `where_meta` predicates.
	 *
	 * @param array $where_meta The original `where_meta` argument.
	 *
	 * @return string WHERE fragment.
	 */
	private function build_where_meta_predicates( $where_meta ) {
		$is_hpos  = OrderUtil::custom_orders_table_usage_is_enabled();
		$schema   = $is_hpos ? $this->get_report_schema() : array();
		$relation = $where_meta['relation'] ?? 'AND';

		$clause = ' AND (';
		$first  = true;

		foreach ( $where_meta as $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			$where_value = $this->prepare_where_meta_value( $value );
			if ( '' === $where_value ) {
				continue;
			}

			if ( ! $first ) {
				$clause .= ' ' . $relation;
			}
			$first = false;

			$meta_key = $value['meta_key'];
			$key      = sanitize_key( is_array( $meta_key ) ? $meta_key[0] . '_array' : $meta_key );

			if ( isset( $value['type'] ) && 'order_item_meta' === $value['type'] ) {
				if ( is_array( $meta_key ) ) {
					$clause .= " ( order_item_meta_{$key}.meta_key   IN ('" . implode( "','", $meta_key ) . "')";
				} else {
					$clause .= " ( order_item_meta_{$key}.meta_key   = '{$meta_key}'";
				}
				$clause .= " AND order_item_meta_{$key}.meta_value {$where_value} )";
			} elseif ( $is_hpos && ! is_array( $meta_key ) && isset( $schema['where_meta_column'][ $meta_key ] ) ) {
				$clause .= ' ( ' . $schema['where_meta_column'][ $meta_key ] . " {$where_value} )";
			} else {
				if ( is_array( $meta_key ) ) {
					$clause .= " ( meta_{$key}.meta_key   IN ('" . implode( "','", $meta_key ) . "')";
				} else {
					$clause .= " ( meta_{$key}.meta_key   = '{$meta_key}'";
				}
				$clause .= " AND meta_{$key}.meta_value {$where_value} )";
			}
		}

		return $clause . ')';
	}

	/**
	 * Build the WHERE fragment for caller-supplied `where` predicates.
	 *
	 * @param array $where Caller-supplied predicates.
	 *
	 * @return string WHERE fragment.
	 */
	private function build_where_predicates( $where ) {
		$is_hpos = OrderUtil::custom_orders_table_usage_is_enabled();
		$clause  = '';

		foreach ( $where as $value ) {
			$where_value = $this->prepare_where_value( $value );
			if ( '' === $where_value ) {
				continue;
			}
			$column  = $is_hpos ? $this->translate_legacy_sql_fragment_hpos( $value['key'] ) : $value['key'];
			$clause .= " AND {$column} {$where_value}";
		}

		return $clause;
	}

	/**
	 * Prepare the right-hand side of a `where_meta` predicate.
	 *
	 * @param array $value A `where_meta` row.
	 *
	 * @return string SQL fragment, or empty string when no predicate is emitted.
	 */
	private function prepare_where_meta_value( $value ) {
		global $wpdb;
		$op_lc = strtolower( $value['operator'] );

		if ( 'in' === $op_lc || 'not in' === $op_lc ) {
			if ( ! empty( $value['meta_value'] ) && ! is_array( $value['meta_value'] ) ) {
				$value['meta_value'] = (array) $value['meta_value']; // @phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			}
			if ( empty( $value['meta_value'] ) ) {
				return '';
			}
			$formats = implode( ', ', array_fill( 0, count( $value['meta_value'] ), '%s' ) );
			return $value['operator'] . ' (' . $wpdb->prepare( $formats, $value['meta_value'] ) . ')'; // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $value['operator'] . ' ' . $wpdb->prepare( '%s', $value['meta_value'] );
	}

	/**
	 * Prepare the right-hand side of a `where` predicate.
	 *
	 * @param array $value A `where` row.
	 *
	 * @return string SQL fragment, or empty string when no predicate is emitted.
	 */
	private function prepare_where_value( $value ) {
		global $wpdb;
		$op_lc = strtolower( $value['operator'] );

		if ( 'in' === $op_lc || 'not in' === $op_lc ) {
			if ( ! empty( $value['value'] ) && ! is_array( $value['value'] ) ) {
				$value['value'] = (array) $value['value'];
			}
			if ( empty( $value['value'] ) ) {
				return '';
			}
			$formats = implode( ', ', array_fill( 0, count( $value['value'] ), '%s' ) );
			return $value['operator'] . ' (' . $wpdb->prepare( $formats, $value['value'] ) . ')'; // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $value['operator'] . ' ' . $wpdb->prepare( '%s', $value['value'] );
	}

	/**
	 * Get HPOS-only schema details used by the report SQL builder.
	 *
	 * @return array<string,mixed>
	 */
	private function get_report_schema() {
		if ( null !== $this->report_schema_cache ) {
			return $this->report_schema_cache;
		}

		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->report_schema_cache = array();
			return $this->report_schema_cache;
		}

		$this->report_schema_cache = array(
			'gmt_offset'        => (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ),
			'order_column'      => array(
				'_order_total' => 'orders.total_amount',
				'_order_tax'   => 'orders.tax_amount',
				// On a refund row, wc_orders.total_amount stores the negative refund total.
				// Legacy callers expect the positive `_refund_amount` meta value.
			),
			'op_data_column'    => array(
				'_order_shipping'     => 'op_data.shipping_total_amount',
				'_order_shipping_tax' => 'op_data.shipping_tax_amount',
			),
			'where_meta_column' => array(
				'_customer_user' => 'orders.customer_id',
			),
		);

		return $this->report_schema_cache;
	}

	/**
	 * Translate a `post_data` column key to an HPOS column reference.
	 *
	 * @param string $key Sanitized `post_data` key from the caller.
	 *
	 * @return string SQL fragment referencing the equivalent HPOS column.
	 */
	private function translate_post_column_hpos( $key ) {
		switch ( $key ) {
			case 'id':
			case 'ID':
				return 'orders.id';
			case 'post_date':
				return $this->hpos_local_date_expr();
			case 'post_parent':
				return 'orders.parent_order_id';
			case 'post_status':
				return 'orders.status';
			case 'post_type':
				return 'orders.type';
			default:
				return "orders.{$key}";
		}
	}

	/**
	 * Translate legacy `posts.<col>` references in an arbitrary SQL fragment.
	 *
	 * @param string $fragment Caller-supplied SQL fragment.
	 *
	 * @return string Translated fragment safe to drop into an HPOS query.
	 */
	private function translate_legacy_sql_fragment_hpos( $fragment ) {
		$local_date_expr = $this->hpos_local_date_expr();

		$replacements = array(
			'posts.post_date'   => $local_date_expr,
			'posts.post_parent' => 'orders.parent_order_id',
			'posts.post_status' => 'orders.status',
			'posts.post_type'   => 'orders.type',
			'posts.ID'          => 'orders.id',
			'post_date'         => $local_date_expr,
			'ID'                => 'orders.id',
		);

		return strtr( $fragment, $replacements );
	}

	/**
	 * Build a MySQL expression that converts `orders.date_created_gmt` into site-local time.
	 *
	 * @return string SQL fragment that produces a local DATETIME.
	 */
	private function hpos_local_date_expr() {
		$schema = $this->get_report_schema();
		$offset = isset( $schema['gmt_offset'] ) ? (int) $schema['gmt_offset'] : 0;

		return "DATE_ADD(orders.date_created_gmt, INTERVAL {$offset} SECOND)";
	}
}
