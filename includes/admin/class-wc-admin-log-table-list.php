<?php
/**
 * WooCommerce Log Table List
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_Admin_Log_Table_List extends WP_List_Table {

	/**
	 * Initialize the log table list.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'log',  'woocommerce' ),
			'plural'   => __( 'logs', 'woocommerce' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Get list views.
	 *
	 * @return array Views.
	 */
	public function get_views() {

		$current = ! empty( $_REQUEST['level'] ) ? $_REQUEST['level'] : false;

		$views = array();

		$levels = array(
			array( 'query_arg' => false,       'label' => __( 'All levels', 'woocommerce' ) ),
			array( 'query_arg' => 'emergency', 'label' => __( 'Emergency',  'woocommerce' ) ),
			array( 'query_arg' => 'alert',     'label' => __( 'Alert',      'woocommerce' ) ),
			array( 'query_arg' => 'critical',  'label' => __( 'Critical',   'woocommerce' ) ),
			array( 'query_arg' => 'error',     'label' => __( 'Error',      'woocommerce' ) ),
			array( 'query_arg' => 'warning',   'label' => __( 'Warning',    'woocommerce' ) ),
			array( 'query_arg' => 'notice',    'label' => __( 'Notice',     'woocommerce' ) ),
			array( 'query_arg' => 'info',      'label' => __( 'Info',       'woocommerce' ) ),
			array( 'query_arg' => 'debug',     'label' => __( 'Debug',      'woocommerce' ) ),
		);

		foreach ( $levels as $level ) {
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
			'level'     => __( 'Level',     'woocommerce' ),
			'message'   => __( 'Message',   'woocommerce' ),
			'source'    => __( 'Source',       'woocommerce' ),
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
		return esc_html( mysql2date( 'c', $log['timestamp'] ) );
	}

	/**
	 * Level column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_level( $log ) {
		$level_key = WC_Log_Levels::get_severity_level( $log['level'] );
		$levels    = array(
			'emergency' => __( 'Emergency', 'woocommerce' ),
			'alert'     => __( 'Alert',     'woocommerce' ),
			'critical'  => __( 'Critical',  'woocommerce' ),
			'error'     => __( 'Error',     'woocommerce' ),
			'warning'   => __( 'Warning',   'woocommerce' ),
			'notice'    => __( 'Notice',    'woocommerce' ),
			'info'      => __( 'Info',      'woocommerce' ),
			'debug'     => __( 'Debug',     'woocommerce' ),
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
	 * Source column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_source( $log ) {
		return esc_html( $log['source'] );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'woocommerce' ),
		);
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'timestamp' => array( 'timestamp', true ),
			'level'     => array( 'level',     true ),
			'source'    => array( 'source',       true ),
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
		if ( ! empty( $_REQUEST['level'] ) && WC_Log_Levels::is_valid_level( $_REQUEST['level'] ) ) {
			$level_filter = $wpdb->prepare(
				'AND level >= %d',
				array( WC_Log_Levels::get_level_severity( $_REQUEST['level'] ) )
			);
		}

		$search = '';
		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = "AND source LIKE '%" . esc_sql( $wpdb->esc_like( wc_clean( $_REQUEST['s'] ) ) ) . "%' ";
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			switch ( $_REQUEST['orderby'] ) {

				// Intentional cascade, these are valid values.
				case 'timestamp':
				case 'source':
				case 'level':
					$order_by = $_REQUEST['orderby'];
					break;

				// Invalid $_REQUEST orderby, use default
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

		$this->items = $wpdb->get_results(
			"SELECT log_id, timestamp, level, message, source
				FROM {$wpdb->prefix}woocommerce_log
				WHERE 1 = 1 {$level_filter} {$search}
				ORDER BY {$order_by} {$order_order} " .
				$wpdb->prepare( "LIMIT %d OFFSET %d", $per_page, $offset ),
			ARRAY_A
		);

		$count = $wpdb->get_var( "SELECT COUNT(log_id) FROM {$wpdb->prefix}woocommerce_log WHERE 1 = 1 {$level_filter} {$search};" );

		// Set the pagination
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => $per_page,
			'total_pages' => ceil( $count / $per_page ),
		) );
	}
}
