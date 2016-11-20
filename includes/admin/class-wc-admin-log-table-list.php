<?php
/**
 * WooCommerce Log Table List
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_Admin_Log_Table_List extends WP_List_Table {

	/**
	 * Initialize the webhook table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'log', 'woocommerce' ),
			'plural'   => __( 'logs', 'woocommerce' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Get list views.
	 *
	 * @return array
	 */
	public function get_views() {

		$current = ! empty( $_REQUEST['level'] ) ? $_REQUEST['level'] : false;

		$views = array();

		$process_levels = array(
			array( 'query_arg' => false, 'label' => _x( 'All levels', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'emergency', 'label' => _x( 'Emergency', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'alert', 'label' => _x( 'Alert', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'critical', 'label' => _x( 'Critical', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'error', 'label' => _x( 'Error', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'warning', 'label' => _x( 'Warning', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'notice', 'label' => _x( 'Notice', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'info', 'label' => _x( 'Info', 'Log level', 'woocommerce' ) ),
			array( 'query_arg' => 'debug', 'label' => _x( 'Debug', 'Log level', 'woocommerce' ) ),
		);

		foreach ( $process_levels as $level ) {
			$url = esc_url( add_query_arg( 'level', $level['query_arg'] ) );
			$class = $current === $level['query_arg'] ? ' class="current"' : '';
			$views[] = sprintf( '<a href="%1$s"%2$s>%3$s</a>', $url, $class, esc_html( $level['label'] ) );
		}

		return $views;
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'        => '<input type="checkbox" />',
			'timestamp' => __( 'Timestamp', 'woocommerce' ),
			'level'     => __( 'Level', 'woocommerce' ),
			'message'   => __( 'Message', 'woocommerce' ),
			'tag'       => __( 'Tag', 'woocommerce' ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_cb( $log ) {
		return sprintf( '<input type="checkbox" name="log[]" value="%1$s" />', $log['log_id'] );
	}

	/**
	 * Timestamp column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_timestamp( $log ) {
		return esc_html( $log['timestamp'] );
	}

	/**
	 * Level column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_level( $log ) {
		$level_key = $log['level'];
		$levels    = array(
			'emergency' => _x( 'Emergency', 'Log level', 'woocommerce' ),
			'alert'     => _x( 'Alert', 'Log level', 'woocommerce' ),
			'critical'  => _x( 'Critical', 'Log level', 'woocommerce' ),
			'error'     => _x( 'Error', 'Log level', 'woocommerce' ),
			'warning'   => _x( 'Warning', 'Log level', 'woocommerce' ),
			'notice'    => _x( 'Notice', 'Log level', 'woocommerce' ),
			'info'      => _x( 'Info', 'Log level', 'woocommerce' ),
			'debug'     => _x( 'Debug', 'Log level', 'woocommerce' ),
		);

		if ( isset( $levels[ $level_key ] ) ) {
			return esc_html( $levels[ $level_key ] );
		} else {
			return '';
		}
	}

	/**
	 * Message column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_message( $log ) {
		return esc_html( $log['message'] );
	}

	/**
	 * Tag column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_tag( $log ) {
		return esc_html( $log['tag'] );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'timestamp' => array( 'timestamp', true ),
			'level'     => array( 'level', true ),
			'tag'       => array( 'tag', true ),
		);
	}

	/**
	 * Prepare table list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = apply_filters( 'woocommerce_status_log_items_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$level_filter = '';
		if ( ! empty( $_REQUEST['level'] ) ) {
			$level_filter = $wpdb->prepare( 'AND level = %s', array( $_REQUEST['level'] ) );
		}

		$search = '';
		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = "AND tag LIKE '%" . esc_sql( $wpdb->esc_like( wc_clean( $_REQUEST['s'] ) ) ) . "%' ";
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			switch ( $_REQUEST['orderby'] ) {

				// Level requires special case to order Emergency -> Debug
				case 'level':
					$order_by = "CASE level "
						. "WHEN 'emergency' THEN 8 "
						. "WHEN 'alert' THEN 7 "
						. "WHEN 'critical' THEN 6 "
						. "WHEN 'error' THEN 5 "
						. "WHEN 'warning' THEN 4 "
						. "WHEN 'notice' THEN 3 "
						. "WHEN 'info' THEN 2 "
						. "WHEN 'debug' THEN 1 "
						. "ELSE 0 "
						. "END";
					break;

				// Intentional cascade, these are valid values.
				case 'timestamp':
				case 'tag':
					$order_by = $_REQUEST['orderby'];
					break;

				default:
					$order_by = 'log_id';
					break;
			}
		} else {
			$order_by = 'log_id';
		}

		if ( ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ) {
			$order_order = 'ASC';
		} else {
			$order_order = 'DESC';
		}


		$logs = $wpdb->get_results(
			"SELECT log_id, timestamp, level, message, tag FROM {$wpdb->prefix}woocommerce_log WHERE 1 = 1 {$level_filter} {$search}" .
			$wpdb->prepare( "ORDER BY {$order_by} {$order_order} LIMIT %d OFFSET %d", $per_page, $offset ), ARRAY_A
		);

		$count = $wpdb->get_var( "SELECT COUNT(log_id) FROM {$wpdb->prefix}woocommerce_log WHERE 1 = 1 {$level_filter} {$search};" );

		$this->items = $logs;

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
