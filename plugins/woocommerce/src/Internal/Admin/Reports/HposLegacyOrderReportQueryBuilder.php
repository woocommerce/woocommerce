<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Reports;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Builds HPOS-backed SQL clauses for the legacy admin order reports.
 *
 * Used by {@see WC_Admin_Report::get_order_report_data()} when HPOS is the
 * authoritative order store, so the same `data` / `where` / `where_meta`
 * descriptors that legacy reports already pass continue to work against
 * `wc_orders`, `wc_orders_meta` and `wc_order_operational_data` instead of
 * `{$wpdb->posts}` / `{$wpdb->postmeta}`.
 *
 * @internal
 *
 * @since 11.1.0
 */
class HposLegacyOrderReportQueryBuilder {

	/**
	 * Lazy cache for schema details (column mappings, gmt offset) used during one query build.
	 *
	 * @var array<string,mixed>|null
	 */
	private $report_schema = null;

	/**
	 * Lazy cache for the legacy `posts` column => HPOS column map used during one query build.
	 *
	 * @var array<string,string>|null
	 */
	private $column_map = null;

	/**
	 * Report date window for the current build as `[start, end)` Unix timestamps,
	 * or null when the query is not bounded by a caller-supplied range.
	 *
	 * @var array{0:int,1:int}|null
	 */
	private $report_range = null;

	/**
	 * Build the SQL clauses for an HPOS-backed legacy order report query.
	 *
	 * @since 11.1.0
	 *
	 * @param array $args       Parsed report arguments.
	 * @param int   $start_date Start date as a Unix timestamp.
	 * @param int   $end_date   End date as a Unix timestamp.
	 *
	 * @return array<string,string> SQL clauses keyed by select/from/join/where/group_by/order_by/limit.
	 */
	public function build_query( array $args, int $start_date, int $end_date ): array {
		// The class is resolved from the DI container as a shared instance: reset the
		// per-build caches so option-derived state can't leak between queries.
		$this->report_schema = null;
		$this->column_map    = null;
		// Only trust the range when the query is actually bounded by it: queries with
		// filter_range off (e.g. the sparklines) may still carry the report page's
		// selected dates, which can describe a different DST period than the rows
		// they fetch.
		$this->report_range = ( ! empty( $args['filter_range'] ) && $start_date > 0 && $end_date > 0 )
			? array( $start_date, (int) strtotime( '+1 DAY', $end_date ) )
			: null;

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

		// The CPT path builds joins from `( $data + $where )`, so a `where` row carrying a
		// `type` gets its join even without a matching `data` entry (rows shadowed by a
		// `data` key are skipped, matching the array-union semantics). Such rows only ever
		// contribute joins, never SELECT columns.
		foreach ( $where as $raw_key => $value ) {
			if ( is_array( $value ) && ! array_key_exists( $raw_key, $data ) ) {
				$joins = array_merge( $joins, $this->resolve_type_joins( $raw_key, $value ) );
			}
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

		$query           = array();
		$query['select'] = 'SELECT ' . implode( ',', $select );
		$query['from']   = 'FROM ' . OrderUtil::get_table_for_orders() . ' AS orders';
		$query['join']   = implode( ' ', $joins );
		$query['where']  = $this->build_where_clause( $order_types, $order_status, $parent_order_status, (bool) $filter_range, $start_date, $end_date );

		if ( ! empty( $where_meta ) ) {
			$query['where'] .= $this->build_where_meta_predicates( $where_meta );
		}

		if ( ! empty( $where ) ) {
			$query['where'] .= $this->build_where_predicates( $where );
		}

		if ( $group_by ) {
			$query['group_by'] = 'GROUP BY ' . $this->translate_legacy_sql_fragment( $group_by );
		}

		if ( $order_by ) {
			$query['order_by'] = 'ORDER BY ' . $this->translate_legacy_sql_fragment( $order_by );
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
				$get_key = $this->translate_post_column( $key );
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

		// Only bare selects get the legacy money format; inside an aggregate the raw
		// column must be used, as per-row rounding would accumulate error where the
		// CPT path sums the unrounded meta values.
		if ( ! $value['function'] && $this->is_money_meta_key( $type, $raw_key ) ) {
			$get_key = $this->format_money_column( $get_key );
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
	 * Resolve the JOINs required by a `data` or `where` row's `type`, without any SELECT part.
	 *
	 * @param string|int $raw_key Key of the row in its original array (a numeric index for `where` rows).
	 * @param array      $value   The row.
	 *
	 * @return array<string,string> JOINs keyed by alias.
	 */
	private function resolve_type_joins( $raw_key, array $value ): array {
		$raw_key   = (string) $raw_key;
		$key       = sanitize_key( $raw_key );
		$join_type = $value['join_type'] ?? 'INNER';

		switch ( $value['type'] ?? '' ) {
			case 'meta':
				return $this->resolve_meta_select( $raw_key, $key, $join_type )[1];
			case 'parent_meta':
				return $this->resolve_parent_meta_select( $raw_key, $key, $join_type )[1];
			case 'order_item_meta':
				return $this->build_order_item_meta_joins( $key, $raw_key, $value['order_item_type'] ?? '', $join_type );
			case 'order_item':
				return $this->build_order_items_join( $join_type );
		}

		return array();
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
		$schema = $this->get_report_schema();
		if ( isset( $schema['order_column'][ $raw_key ] ) ) {
			return array( $schema['order_column'][ $raw_key ], array() );
		}
		if ( isset( $schema['op_data_column'][ $raw_key ] ) ) {
			return array( $schema['op_data_column'][ $raw_key ], $this->build_op_data_join() );
		}
		if ( isset( $schema['address_column'][ $raw_key ] ) ) {
			list( $address_type, $column ) = $schema['address_column'][ $raw_key ];
			return array( "address_{$address_type}.{$column}", $this->build_address_join( $address_type ) );
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
		if ( isset( $schema['address_column'][ $raw_key ] ) ) {
			list( $address_type, $column ) = $schema['address_column'][ $raw_key ];
			$joins                         = array_merge(
				$this->build_parent_orders_join(),
				$this->build_address_join( $address_type, 'parent_orders', 'parent_address' )
			);
			return array( "parent_address_{$address_type}.{$column}", $joins );
		}

		return array(
			"parent_meta_{$key}.meta_value",
			$this->build_parent_meta_join( $key, $raw_key, $join_type ),
		);
	}

	/**
	 * Whether a data row resolves to a mapped HPOS money column.
	 *
	 * @param string $type    Row type.
	 * @param string $raw_key Original meta key.
	 *
	 * @return bool
	 */
	private function is_money_meta_key( $type, $raw_key ): bool {
		if ( 'meta' !== $type && 'parent_meta' !== $type ) {
			return false;
		}

		$schema = $this->get_report_schema();
		return isset( $schema['order_column'][ $raw_key ] ) || isset( $schema['op_data_column'][ $raw_key ] );
	}

	/**
	 * Wrap a mapped money column so bare SELECTs match the legacy meta format.
	 *
	 * HPOS money columns are DECIMAL(26,8), so selecting one raw yields e.g.
	 * '50.00000000' where the CPT meta stores '50.00'. Rounding to the store's
	 * price decimals restores the legacy format. Never applied inside aggregate
	 * functions: several money metas are stored at full precision, so per-row
	 * rounding would drift from the CPT sums.
	 *
	 * @param string $column Qualified column reference.
	 *
	 * @return string SQL expression.
	 */
	private function format_money_column( string $column ): string {
		return "ROUND({$column}, " . wc_get_price_decimals() . ')';
	}

	/**
	 * Build the JOIN onto `wc_order_addresses` for one address type.
	 *
	 * @param string $address_type 'billing' or 'shipping'.
	 * @param string $order_alias  Orders-table alias to join from.
	 * @param string $alias_prefix Prefix for the address-table alias.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_address_join( string $address_type, string $order_alias = 'orders', string $alias_prefix = 'address' ): array {
		$alias = "{$alias_prefix}_{$address_type}";
		return array(
			$alias => 'LEFT JOIN ' . OrdersTableDataStore::get_addresses_table_name() . " AS {$alias} ON ( {$order_alias}.id = {$alias}.order_id AND {$alias}.address_type = '{$address_type}' )",
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
			$alias = "order_item_meta_{$key}";
			return array(
				'order_items' => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON orders.id = order_items.order_id",
				$alias        => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS {$alias} ON order_items.order_item_id = {$alias}.order_item_id",
			);
		}

		$schema = $this->get_report_schema();
		if ( ! is_array( $meta_key ) && isset( $schema['where_meta_column'][ $meta_key ] ) ) {
			return array();
		}
		if ( ! is_array( $meta_key ) && isset( $schema['address_column'][ $meta_key ] ) ) {
			return $this->build_address_join( $schema['address_column'][ $meta_key ][0] );
		}

		$alias = "meta_{$key}";
		return array( $alias => "{$join_type} JOIN " . OrderUtil::get_table_for_order_meta() . " AS {$alias} ON orders.id = {$alias}.order_id" );
	}

	/**
	 * Build the JOIN required to look up parent order data.
	 *
	 * @return array<string,string> JOIN keyed by alias.
	 */
	private function build_parent_orders_join() {
		return array(
			'parent_orders' => 'LEFT JOIN ' . OrderUtil::get_table_for_orders() . ' AS parent_orders ON orders.parent_order_id = parent_orders.id',
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
		$alias = "meta_{$key}";
		return array(
			$alias => "{$join_type} JOIN " . OrderUtil::get_table_for_order_meta() . " AS {$alias} ON ( orders.id = {$alias}.order_id AND {$alias}.meta_key = '{$raw_key}' )",
		);
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
		$alias = "parent_meta_{$key}";
		return array(
			$alias => "{$join_type} JOIN " . OrderUtil::get_table_for_order_meta() . " AS {$alias} ON (orders.parent_order_id = {$alias}.order_id) AND ({$alias}.meta_key = '{$raw_key}')",
		);
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
		return array(
			'order_items' => "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON orders.id = order_items.order_id",
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
		$items_join = "{$join_type} JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON (orders.id = order_items.order_id)";

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
		$clause = "
			WHERE 	orders.type 	IN ( '" . implode( "','", $order_types ) . "' )
			";

		if ( ! empty( $order_status ) ) {
			$clause .= "
				AND 	orders.status 	IN ( 'wc-" . implode( "','wc-", $order_status ) . "')
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
		$statuses_in = "'wc-" . implode( "','wc-", $parent_order_status ) . "'";

		if ( ! empty( $order_status ) ) {
			return " AND ( parent_orders.status IN ( {$statuses_in} ) OR parent_orders.id IS NULL ) ";
		}

		return " AND parent_orders.status IN ( {$statuses_in} ) ";
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
		$start_gmt = $this->local_timestamp_to_gmt( (int) $start_date );
		$end_gmt   = $this->local_timestamp_to_gmt( (int) strtotime( '+1 DAY', $end_date ) );

		return "
			AND 	orders.date_created_gmt >= '{$start_gmt}'
			AND 	orders.date_created_gmt < '{$end_gmt}'
		";
	}

	/**
	 * Convert a WordPress "local" timestamp (one that reads as site-local wall-clock time
	 * when formatted with {@see gmdate()}) into the equivalent GMT datetime string.
	 *
	 * Uses the site timezone via {@see wp_timezone()} so the offset is resolved for that
	 * specific date. On timezones that observe DST this yields the correct UTC instant for
	 * the boundary even when the report range spans a different offset period than "now",
	 * unlike a single cached `gmt_offset`.
	 *
	 * @param int $local_ts Local timestamp to convert.
	 *
	 * @return string GMT datetime in `Y-m-d H:i:s` format.
	 */
	private function local_timestamp_to_gmt( int $local_ts ): string {
		return (string) $this->local_datetime_to_gmt( gmdate( 'Y-m-d H:i:s', $local_ts ) );
	}

	/**
	 * Convert a site-local date or datetime string into the equivalent GMT datetime string.
	 *
	 * Only plain `Y-m-d`, `Y-m-d H:i` and `Y-m-d H:i:s` values are converted; anything else
	 * (including relative formats) returns null so callers can fall back to per-row SQL
	 * conversion instead of guessing at the caller's intent.
	 *
	 * @param string $local_datetime Local date or datetime string.
	 *
	 * @return string|null GMT datetime in `Y-m-d H:i:s` format, or null when not convertible.
	 */
	private function local_datetime_to_gmt( string $local_datetime ): ?string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $local_datetime ) ) {
			return null;
		}

		try {
			$local = new \DateTimeImmutable( $local_datetime, wp_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}

		return $local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Build the WHERE fragment for caller-supplied `where_meta` predicates.
	 *
	 * @param array $where_meta The original `where_meta` argument.
	 *
	 * @return string WHERE fragment.
	 */
	private function build_where_meta_predicates( $where_meta ) {
		$schema   = $this->get_report_schema();
		$relation = $where_meta['relation'] ?? 'AND';

		$clause = ' AND (';
		$first  = true;

		foreach ( $where_meta as $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			$where_value = $this->prepare_predicate_value( $value, 'meta_value' );
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
			} elseif ( ! is_array( $meta_key ) && isset( $schema['where_meta_column'][ $meta_key ] ) ) {
				$clause .= ' ( ' . $schema['where_meta_column'][ $meta_key ] . " {$where_value} )";
			} elseif ( ! is_array( $meta_key ) && isset( $schema['address_column'][ $meta_key ] ) ) {
				list( $address_type, $column ) = $schema['address_column'][ $meta_key ];
				$clause                       .= " ( address_{$address_type}.{$column} {$where_value} )";
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
		$clause = '';

		foreach ( $where as $value ) {
			$where_value = $this->prepare_predicate_value( $value, 'value' );
			if ( '' === $where_value ) {
				continue;
			}

			$predicate = $this->build_gmt_date_predicate( $value );
			if ( null === $predicate ) {
				$predicate = $this->translate_legacy_sql_fragment( $value['key'] ) . " {$where_value}";
			}

			$clause .= ' AND ' . $predicate;
		}

		return $clause;
	}

	/**
	 * Build a sargable predicate on `orders.date_created_gmt` for a plain `post_date` where row.
	 *
	 * Translating `post_date` wraps the column in the per-row local-time expression, which
	 * stops MySQL from using the date index. For the common shape — a bare `post_date` key,
	 * a scalar comparison operator and a date/datetime value (e.g. the sales sparkline's
	 * only date bound, `post_date > 'Y-m-d'`) — the boundary is converted to UTC in PHP
	 * instead and compared against the raw GMT column, matching what
	 * {@see self::build_filter_range_where_clause()} does for `filter_range` bounds.
	 *
	 * @param array $value A `where` row.
	 *
	 * @return string|null Predicate SQL, or null when the row is not a plain post_date comparison.
	 */
	private function build_gmt_date_predicate( array $value ): ?string {
		$key = trim( (string) ( $value['key'] ?? '' ) );
		if ( 'post_date' !== $key && 'posts.post_date' !== $key ) {
			return null;
		}

		$rhs = $value['value'] ?? '';
		if ( ! in_array( $value['operator'], array( '=', '!=', '<', '<=', '>', '>=' ), true ) || ! is_string( $rhs ) ) {
			return null;
		}

		$gmt = $this->local_datetime_to_gmt( $rhs );
		if ( null === $gmt ) {
			return null;
		}

		global $wpdb;
		return "orders.date_created_gmt {$value['operator']} " . $wpdb->prepare( '%s', $gmt );
	}

	/**
	 * Prepare the right-hand side of a predicate (e.g. `= '5'` or `IN ('a','b')`).
	 *
	 * Used by both the `where` (`value` / `operator`) and `where_meta`
	 * (`meta_value` / `operator`) builders.
	 *
	 * @param array  $value     A `where` or `where_meta` row.
	 * @param string $value_key The array key holding the RHS value (`value` or `meta_value`).
	 *
	 * @return string SQL fragment, or empty string when no predicate is emitted.
	 */
	private function prepare_predicate_value( array $value, string $value_key ): string {
		global $wpdb;
		$op_lc = strtolower( $value['operator'] );
		$rhs   = $value[ $value_key ] ?? '';

		if ( 'in' === $op_lc || 'not in' === $op_lc ) {
			if ( ! empty( $rhs ) && ! is_array( $rhs ) ) {
				$rhs = (array) $rhs;
			}
			if ( empty( $rhs ) ) {
				return '';
			}
			$formats = implode( ', ', array_fill( 0, count( $rhs ), '%s' ) );
			return $value['operator'] . ' (' . $wpdb->prepare( $formats, $rhs ) . ')'; // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return $value['operator'] . ' ' . $wpdb->prepare( '%s', $rhs );
	}

	/**
	 * Get HPOS schema details used by the report SQL builder (column mappings and gmt offset).
	 *
	 * @return array<string,mixed>
	 */
	private function get_report_schema(): array {
		if ( null !== $this->report_schema ) {
			return $this->report_schema;
		}

		$this->report_schema = array(
			'gmt_offset'        => (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ),
			'order_column'      => array(
				'_order_total' => 'orders.total_amount',
				'_order_tax'   => 'orders.tax_amount',
				// On a refund row, wc_orders.total_amount stores the negative refund total.
				// Legacy callers expect the positive `_refund_amount` meta value, so it
				// is intentionally not mapped here.
			),
			'op_data_column'    => array(
				'_order_shipping'     => 'op_data.shipping_total_amount',
				'_order_shipping_tax' => 'op_data.shipping_tax_amount',
			),
			'where_meta_column' => array(
				'_customer_user' => 'orders.customer_id',
			),
			'address_column'    => $this->build_address_column_map(),
		);

		return $this->report_schema;
	}

	/**
	 * Map legacy `_billing_*` / `_shipping_*` meta keys to `wc_order_addresses` columns.
	 *
	 * Under HPOS these fields live in the addresses table, not in order meta, so a
	 * meta join would return no rows for them.
	 *
	 * @return array<string,array{0:string,1:string}> Meta key => [address_type, column].
	 */
	private function build_address_column_map(): array {
		$fields = array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone' );
		$map    = array();

		foreach ( array( 'billing', 'shipping' ) as $address_type ) {
			foreach ( $fields as $field ) {
				if ( 'shipping' === $address_type && 'email' === $field ) {
					continue;
				}
				$map[ "_{$address_type}_{$field}" ] = array( $address_type, $field );
			}
		}

		return $map;
	}

	/**
	 * Map of bare legacy `posts` column names to their HPOS equivalents.
	 *
	 * Source of truth shared by {@see self::translate_post_column()} and
	 * {@see self::translate_legacy_sql_fragment()}.
	 *
	 * @return array<string,string>
	 */
	private function legacy_to_hpos_column_map(): array {
		if ( null !== $this->column_map ) {
			return $this->column_map;
		}

		$this->column_map = array(
			'ID'          => 'orders.id',
			'post_date'   => $this->hpos_local_date_expr(),
			'post_parent' => 'orders.parent_order_id',
			'post_status' => 'orders.status',
			'post_type'   => 'orders.type',
		);

		return $this->column_map;
	}

	/**
	 * Translate a `post_data` column key to an HPOS column reference.
	 *
	 * @param string $key Sanitized `post_data` key from the caller.
	 *
	 * @return string SQL fragment referencing the equivalent HPOS column.
	 */
	private function translate_post_column( string $key ): string {
		$map = $this->legacy_to_hpos_column_map();

		// `sanitize_key()` lowercases `ID` to `id`; treat both forms as the orders.id column.
		if ( 'id' === $key ) {
			return $map['ID'];
		}
		return $map[ $key ] ?? "orders.{$key}";
	}

	/**
	 * Translate legacy `posts.<col>` (and bare `ID` / `post_date`) references in an arbitrary SQL fragment.
	 *
	 * Matches are bounded by `\b` so tokens embedded in longer identifiers
	 * (e.g. `product_ID`, `posts.post_date_gmt`) are left untouched.
	 *
	 * @param string $fragment Caller-supplied SQL fragment.
	 *
	 * @return string Translated fragment safe to drop into an HPOS query.
	 */
	private function translate_legacy_sql_fragment( string $fragment ): string {
		$map = $this->legacy_to_hpos_column_map();

		// Qualified `posts.<col>` references first, then the bare tokens legacy
		// callers use unqualified. Callbacks avoid `$`/`\` replacement-string pitfalls.
		$fragment = (string) preg_replace_callback(
			'/\bposts\.(ID|post_date|post_parent|post_status|post_type)\b/',
			static function ( $matches ) use ( $map ) {
				return $map[ $matches[1] ];
			},
			$fragment
		);

		return (string) preg_replace_callback(
			'/\b(ID|post_date)\b/',
			static function ( $matches ) use ( $map ) {
				return $map[ $matches[1] ];
			},
			$fragment
		);
	}

	/**
	 * Build a MySQL expression that converts `orders.date_created_gmt` into site-local time.
	 *
	 * For sites configured with a named timezone (e.g. `Europe/Berlin`) the conversion is done
	 * per row with `CONVERT_TZ()` so DST is honoured and rows near midnight bucket into the
	 * correct day/month. `CONVERT_TZ()` returns NULL when the server's timezone tables are not
	 * loaded, so it falls back to {@see self::build_transition_fallback_expr()}. Sites using a
	 * manual UTC offset have no DST, so a fixed-offset shift is exact and is used directly.
	 *
	 * @return string SQL fragment that produces a local DATETIME.
	 */
	private function hpos_local_date_expr(): string {
		$timezone_string = get_option( 'timezone_string' );
		if ( '' === $timezone_string ) {
			$schema = $this->get_report_schema();
			return "DATE_ADD(orders.date_created_gmt, INTERVAL {$schema['gmt_offset']} SECOND)";
		}

		global $wpdb;
		$convert = $wpdb->prepare( 'CONVERT_TZ(orders.date_created_gmt, %s, %s)', '+00:00', $timezone_string );

		return "IFNULL({$convert}, {$this->build_transition_fallback_expr()})";
	}

	/**
	 * Build the fallback used when `CONVERT_TZ()` can't resolve the named timezone
	 * (MySQL timezone tables not loaded).
	 *
	 * A single shift by the current `gmt_offset` would be wrong for rows created in a
	 * different DST period, so the site timezone's DST transitions inside the report
	 * window are baked into a CASE expression and each row is shifted by the offset in
	 * effect when it was created. Without a caller-supplied range (e.g. the sparklines)
	 * the window covers the last year, which spans any window such callers use.
	 *
	 * @return string SQL fragment that produces a local DATETIME.
	 */
	private function build_transition_fallback_expr(): string {
		list( $window_start, $window_end ) = $this->report_range ?? array( time() - YEAR_IN_SECONDS, time() + DAY_IN_SECONDS );

		$transitions = wp_timezone()->getTransitions( $window_start, $window_end );
		if ( ! is_array( $transitions ) || array() === $transitions ) {
			$schema = $this->get_report_schema();
			return "DATE_ADD(orders.date_created_gmt, INTERVAL {$schema['gmt_offset']} SECOND)";
		}

		// The first entry describes the offset already in effect at the window start;
		// each later entry is a transition inside the window.
		$case  = '';
		$count = count( $transitions );
		for ( $i = 0; $i < $count - 1; $i++ ) {
			$boundary = gmdate( 'Y-m-d H:i:s', $transitions[ $i + 1 ]['ts'] );
			$offset   = (int) $transitions[ $i ]['offset'];
			$case    .= "WHEN orders.date_created_gmt < '{$boundary}' THEN DATE_ADD(orders.date_created_gmt, INTERVAL {$offset} SECOND) ";
		}

		$last_offset = (int) $transitions[ $count - 1 ]['offset'];
		$last_shift  = "DATE_ADD(orders.date_created_gmt, INTERVAL {$last_offset} SECOND)";

		if ( '' === $case ) {
			return $last_shift;
		}

		return "CASE {$case}ELSE {$last_shift} END";
	}
}
