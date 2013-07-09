<?php
/**
 * Admin Report
 *
 * Extended by reports to show charts and stats in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Reports
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Admin_Report Class
 */
class WC_Admin_Report {

	public $start_date;
	public $end_date;

	/**
	 * Get report totals such as order totals and discount amounts.
	 *
	 * Data example:
	 *
	 * '_order_total' => array(
	 * 		'type'     => 'meta',
	 *    	'function' => 'SUM',
	 *      'name'     => 'total_sales'
	 * )
	 *
	 * @param  array $args
	 * @return array of results
	 */
	public function get_order_report_data( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'data'         => array(),
			'where'        => array(),
			'where_meta'   => array(),
 			'query_type'   => 'get_row',
			'group_by'     => '',
			'order_by'     => '',
			'limit'        => '',
			'filter_range' => false,
			'nocache'      => false,
			'debug'        => false
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		if ( empty( $data ) )
			return false;

		$select = array();

		foreach ( $data as $key => $value ) {
			$distinct = '';

			if ( isset( $value['distinct'] ) )
				$distinct = 'DISTINCT';

			if ( $value['type'] == 'meta' )
				$get_key = "meta_{$key}.meta_value";
			elseif( $value['type'] == 'post_data' )
				$get_key = "posts.{$key}";
			elseif( $value['type'] == 'order_item_meta' )
				$get_key = "order_item_meta_{$key}.meta_value";
			elseif( $value['type'] == 'order_item' )
				$get_key = "order_items.{$key}";

			if ( $value['function'] )
				$get = "{$value['function']}({$distinct} {$get_key})";
			else
				$get = "{$distinct} {$get_key}";

			$select[] = "{$get} as {$value['name']}";
		}

		$query['select'] = "SELECT " . implode( ',', $select );
		$query['from']   = "FROM {$wpdb->posts} AS posts";

		// Joins
		$joins         = array();
		$joins['rel']  = "LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID";
		$joins['tax']  = "LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )";
		$joins['term'] = "LEFT JOIN {$wpdb->terms} AS term USING( term_id )";

		foreach ( $data as $key => $value ) {
			if ( $value['type'] == 'meta' ) {

				$joins["meta_{$key}"] = "LEFT JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";

			} elseif ( $value['type'] == 'order_item_meta' ) {

				$joins["order_items"] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_id";
				$joins["order_item_meta_{$key}"] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items.order_item_id = order_item_meta_{$key}.order_item_id";

			} elseif ( $value['type'] == 'order_item' ) {

				$joins["order_items"] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_id";

			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( isset( $value['type'] ) && $value['type'] == 'order_item_meta' ) {

					$joins["order_items"] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_id";
					$joins["order_item_meta_{$value['meta_key']}"] = "LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$value['meta_key']} ON order_items.order_item_id = order_item_meta_{$value['meta_key']}.order_item_id";

				} else {
					// If we have a where clause for meta, join the postmeta table
					$joins["meta_{$value['meta_key']}"] = "LEFT JOIN {$wpdb->postmeta} AS meta_{$value['meta_key']} ON posts.ID = meta_{$value['meta_key']}.post_id";
				}
			}
		}

		$query['join'] = implode( ' ', $joins );

		$query['where']  = "
			WHERE 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			";

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	post_date > '" . date('Y-m-d', $this->start_date ) . "'
				AND 	post_date < '" . date('Y-m-d', strtotime( '+1 DAY', $this->end_date ) ) . "'
			";
		}

		foreach ( $data as $key => $value ) {
			if ( $value['type'] == 'meta' ) {

				$query['where'] .= " AND meta_{$key}.meta_key = '{$key}'";

			} elseif ( $value['type'] == 'order_item_meta' ) {

				$query['where'] .= " AND order_items.order_item_type = '{$value['order_item_type']}'";
				$query['where'] .= " AND order_item_meta_{$key}.meta_key = '{$key}'";

			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				if ( strtolower( $value['operator'] ) == 'in' ) {
					if ( is_array( $value['meta_value'] ) )
						$value['meta_value'] = implode( "','", $value['meta_value'] );
					if ( ! empty( $value['meta_value'] ) )
						$where_value = "IN ('{$value['meta_value']}')";
				} else {
					$where_value = "{$value['operator']} '{$value['meta_value']}'";
				}

				if ( ! empty( $where_value ) ) {
					if ( isset( $value['type'] ) && $value['type'] == 'order_item_meta' ) {
						$query['where'] .= " AND order_item_meta_{$value['meta_key']}.meta_key   = '{$value['meta_key']}'";
						$query['where'] .= " AND order_item_meta_{$value['meta_key']}.meta_value {$where_value}";
					} else {
						$query['where'] .= " AND meta_{$value['meta_key']}.meta_key   = '{$value['meta_key']}'";
						$query['where'] .= " AND meta_{$value['meta_key']}.meta_value {$where_value}";
					}
				}
			}
		}

		if ( ! empty( $where ) ) {
			foreach ( $where as $value ) {
				if ( strtolower( $value['operator'] ) == 'in' ) {
					if ( is_array( $value['value'] ) )
						$value['value'] = implode( "','", $value['value'] );
					if ( ! empty( $value['value'] ) )
						$where_value = "IN ('{$value['value']}')";
				} else {
					$where_value = "{$value['operator']} '{$value['value']}'";
				}

				if ( ! empty( $where_value ) )
					$query['where'] .= " AND {$value['key']} {$where_value}";
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

		$query      = implode( ' ', $query );
		$query_hash = md5( $query_type . $query );

		if ( $debug )
			var_dump( $query );

		if ( $debug || $nocache || ( false === ( $result = get_transient( 'wc_report_' . $query_hash ) ) ) ) {
			$result = apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( $query ), $data );

			if ( $filter_range ) {
				if ( date('Y-m-d', strtotime( $this->end_date ) ) == date('Y-m-d', current_time( 'timestamp' ) ) ) {
					$expiration = 60 * 60 * 1; // 1 hour
				} else {
					$expiration = 60 * 60 * 24; // 24 hour
				}
			} else {
				$expiration = 60 * 60 * 24; // 24 hour
			}

			set_transient( 'wc_report_' . $query_hash, $result, $expiration );
		}

		return $result;
	}

	/**
	 * Put data with post_date's into an array of times
	 *
	 * @param  array $data array of your data
	 * @param  string $date_key key for the 'date' field. e.g. 'post_date'
	 * @param  string $data_key key for the data you are charting
	 * @param  int $interval
	 * @param  string $start_date
	 * @param  string $group_by
	 * @return string
	 */
	public function prepare_chart_data( $data, $date_key, $data_key, $interval, $start_date, $group_by ) {
		$prepared_data = array();

		// Ensure all days (or months) have values first in this range
		for ( $i = 0; $i <= $interval; $i ++ ) {
			switch ( $group_by ) {
				case 'day' :
					$time = strtotime( date( 'Ymd', strtotime( "+{$i} DAY", $start_date ) ) ) * 1000;
				break;
				case 'month' :
					$time = strtotime( date( 'Ym', strtotime( "+{$i} MONTH", $start_date ) ) . '01' ) * 1000;
				break;
			}

			if ( ! isset( $prepared_data[ $time ] ) )
				$prepared_data[ $time ] = array( esc_js( $time ), 0 );
		}

		foreach ( $data as $d ) {
			switch ( $group_by ) {
				case 'day' :
					$time = strtotime( date( 'Ymd', strtotime( $d->$date_key ) ) ) * 1000;
				break;
				case 'month' :
					$time = strtotime( date( 'Ym', strtotime( $d->$date_key ) ) . '01' ) * 1000;
				break;
			}

			if ( ! isset( $prepared_data[ $time ] ) )
				continue;

			if ( $data_key )
				$prepared_data[ $time ][1] += $d->$data_key;
			else
				$prepared_data[ $time ][1] ++;
		}

		return $prepared_data;
	}

	/**
	 * Prepares a sparkline to show sales in the last X days
	 *
	 * @param  int $id
	 * @param  int $days
	 */
	public function sales_sparkline( $id, $days, $type ) {
		$meta_key = $type == 'sales' ? '_line_total' : '_qty';

		$data = $this->get_order_report_data( array(
			'data' => array(
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id'
				),
				$meta_key => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_value'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'post_date',
					'value'    => date( 'Y-m-d', strtotime( 'midnight -7 days', current_time( 'timestamp' ) ) ),
					'operator' => '>'
				),
				array(
					'key'      => 'order_item_meta__product_id.meta_value',
					'value'    => $id,
					'operator' => '='
				)
			),
			'group_by'     => 'YEAR(post_date), MONTH(post_date), DAY(post_date)',
			'query_type'   => 'get_results',
			'filter_range' => false
		) );

		$total = 0;
		foreach ( $data as $d )
			$total += $d->order_item_value;

		if ( $type == 'sales' ) {
			$tooltip = sprintf( __( 'Sold %s worth in the last %d days', 'woocommerce' ), strip_tags( woocommerce_price( $total ) ), $days );
		} else {
			$tooltip = sprintf( _n( 'Sold 1 time in the last %d days', 'Sold %d times in the last %d days', $total, 'woocommerce' ), $total, $days );
		}

		$sparkline_data = array_values( $this->prepare_chart_data( $data, 'post_date', 'order_item_value', $days - 1, strtotime( 'midnight -' . ( $days - 1 ) . ' days', current_time( 'timestamp' ) ), 'day' ) );

		return '<span class="wc_sparkline ' . ( $type == 'sales' ? 'lines' : 'bars' ) . ' tips" data-color="#777" data-tip="' . $tooltip . '" data-barwidth="' . 60*60*16*1000 . '" data-sparkline="' . esc_attr( json_encode( $sparkline_data ) ) . '"></span>';
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		return array();
	}

	/**
	 * [get_chart_widgets description]
	 * @return array
	 */
	public function get_chart_widgets() {
		return array();
	}

	/**
	 * Output the report
	 */
	public function output_report() {}
}