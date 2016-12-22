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
		return esc_html( mysql2date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$log['timestamp']
		) );
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
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<div class="alignleft actions">';
				$this->source_dropdown();
				submit_button( __( 'Filter', 'woocommerce' ), '', 'filter-action', false );
			echo '</div>';
		}
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
			'source'    => array( 'source',    true ),
		);
	}

	/**
	 * Display source dropdown
	 *
	 * @global wpdb $wpdb
	 */
	protected function source_dropdown() {
		global $wpdb;

		$sources = $wpdb->get_col( "
			SELECT DISTINCT source
			FROM {$wpdb->prefix}woocommerce_log
			WHERE source != ''
			ORDER BY source ASC
		" );

		$source_count = count( $sources );

		if ( !$source_count ) {
			return;
		}

		$logger = wc_get_logger();
		$logger->debug( wc_print_r( $sources, 1 ) ) ;

		$selected_source = isset( $_REQUEST['source'] ) ? $_REQUEST['source'] : '';
		?>
			<label for="filter-by-source" class="screen-reader-text"><?php _e( 'Filter by source', 'woocommerce' ); ?></label>
			<select name="source" id="filter-by-source">
				<option<?php selected( $selected_source, '' ); ?> value=""><?php _e( 'All', 'woocommerce' ); ?></option>
				<?php
					foreach ( $sources as $s ) {
						$logger->debug($s);
						printf( '<option%1$s value="%2$s">%3$s</option>',
							selected( $selected_source, $s, false ),
							esc_attr( $s ),
							esc_html( $s )
						);
					}
				?>
			</select>
		<?php
	}

	/**
	 * Prepare table list items.
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = apply_filters( 'woocommerce_status_log_items_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$where_conditions = array();
		$where_values     = array();
		if ( ! empty( $_REQUEST['level'] ) && WC_Log_Levels::is_valid_level( $_REQUEST['level'] ) ) {
			$where_conditions[] = 'level >= %d';
			$where_values[]     = WC_Log_Levels::get_level_severity( $_REQUEST['level'] );
		}
		if ( ! empty( $_REQUEST['source'] ) ) {
			$where_conditions[] = 'source = %s';
			$where_values[]     = $_REQUEST['source'];
		}

		$valid_orders = array( 'log_id', 'level', 'source', 'timestamp' );
		if ( ! empty( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], $valid_orders ) ) {
			$order_by = $_REQUEST['orderby'];
		} else {
			$order_by = 'log_id';
		}
		$order_by = esc_sql( $order_by );

		if ( ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ) {
			$order_order = 'ASC';
		} else {
			$order_order = 'DESC';
		}

		$select = 'SELECT log_id, timestamp, level, message, source';
		$from = "FROM {$wpdb->prefix}woocommerce_log";
		$where = ! empty( $where_conditions )
			? $wpdb->prepare( 'WHERE 1 = 1 AND ' . implode( ' AND ', $where_conditions ), $where_values )
			: '';
		$order = "ORDER BY {$order_by} {$order_order}";
		$limit_offset = $wpdb->prepare( 'LIMIT %d OFFSET %d', $per_page, $offset );

		$query = "{$select} {$from} {$where} {$order} {$limit_offset}";
		$query_count = "SELECT COUNT(log_id) {$from} {$where}";

		$logger = wc_get_logger();
		$logger->debug( wc_print_r( $query, 1 ), array( 'source' => 'wc-logger' ) );
		$logger->debug( wc_print_r( $query_count, 1 ), array( 'source' => 'wc-logger' ) );

		$this->items = $wpdb->get_results( $query, ARRAY_A );
		$total_items = $wpdb->get_var( $query_count );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}
}
