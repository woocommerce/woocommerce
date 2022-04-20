<?php
/**
 * WooCommerce Log Table List
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce\Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WC_Admin_Log_Table_List extends WP_List_Table {

	/**
	 * Initialize the log table list.
	 */
	public function __construct() {
		add_action( 'admin_footer', array( $this, 'inline_edit' ) );

		parent::__construct(
			array(
				'singular' => 'log',
				'plural'   => 'logs',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Display level dropdown
	 *
	 * @global wpdb $wpdb
	 */
	public function level_dropdown() {

		$levels = array(
			array(
				'value' => WC_Log_Levels::EMERGENCY,
				'label' => __( 'Emergency', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::ALERT,
				'label' => __( 'Alert', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::CRITICAL,
				'label' => __( 'Critical', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::ERROR,
				'label' => __( 'Error', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::WARNING,
				'label' => __( 'Warning', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::NOTICE,
				'label' => __( 'Notice', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::INFO,
				'label' => __( 'Info', 'woocommerce' ),
			),
			array(
				'value' => WC_Log_Levels::DEBUG,
				'label' => __( 'Debug', 'woocommerce' ),
			),
		);

		$selected_level = isset( $_REQUEST['level'] ) ? $_REQUEST['level'] : '';
		?>
			<label for="filter-by-level" class="screen-reader-text"><?php esc_html_e( 'Filter by level', 'woocommerce' ); ?></label>
			<select name="level" id="filter-by-level">
				<option<?php selected( $selected_level, '' ); ?> value=""><?php esc_html_e( 'All levels', 'woocommerce' ); ?></option>
				<?php
				foreach ( $levels as $l ) {
					printf(
						'<option%1$s value="%2$s">%3$s</option>',
						selected( $selected_level, $l['value'], false ),
						esc_attr( $l['value'] ),
						esc_html( $l['label'] )
					);
				}
				?>
			</select>
		<?php
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
			'source'    => __( 'Source', 'woocommerce' ),
			'details'    => __( 'Details', 'woocommerce' ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_cb( $log ) {
		return sprintf( '<input type="checkbox" name="log[]" value="%1$s" />', esc_attr( $log['log_id'] ) );
	}

	/**
	 * Timestamp column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function column_timestamp( $log ) {
		return esc_html(
			mysql2date(
				'Y-m-d H:i:s',
				$log['timestamp']
			)
		);
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
			'alert'     => __( 'Alert', 'woocommerce' ),
			'critical'  => __( 'Critical', 'woocommerce' ),
			'error'     => __( 'Error', 'woocommerce' ),
			'warning'   => __( 'Warning', 'woocommerce' ),
			'notice'    => __( 'Notice', 'woocommerce' ),
			'info'      => __( 'Info', 'woocommerce' ),
			'debug'     => __( 'Debug', 'woocommerce' ),
		);

		if ( ! isset( $levels[ $level_key ] ) ) {
			return '';
		}

		$level       = $levels[ $level_key ];
		$level_class = sanitize_html_class( 'log-level--' . $level_key );
		return '<span class="log-level ' . $level_class . '">' . esc_html( $level ) . '</span>';
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

	public function column_details( $log ) {
		?>
		<button>View Details</button>
		<?php
	}

	/**
	 * Context column.
	 *
	 * @param  array $log
	 * @return string
	 */
	public function render_log( $log ) {
		// Note that this unserializing "safe" data from the database, not user input.
		$context = unserialize( $log['context'] );
		$context = $this->arrayify( $context );

		// Exclude unneeded values.
		$exclude = array( '*default_data', '*data_store', 'xdebug_message' );
		$exclude = apply_filters( 'woocommerce_log_exlude_error_fields', $exclude );

		$context = $this->array_to_string( $context, $exclude );

		echo '<p style="font-weight: bold">Error Details</p><textarea class="widefat" rows="20" style="width:100%;white-space:pre;white-space:pre-wrap;">' . print_r( $context, 1 ) . '</textarea>';
	}

	public function arrayify( $input ) {
		$out   = array();
		$input = is_object( $input ) ? (array) $input : $input;
		if ( is_array( $input ) ) {

			$out = array_map( function( $value ) {

				if ( is_array( $value ) || is_object( $value ) ) {
					return $this->arrayify( $value );
				} else {
					return $value;
				}

				return '';
			}, $input );
		}

		return $out;
	}

	public function array_to_string( $input, $exclude = array(), $depth = 0 ) {
		$out   = '';
		$input = is_object( $input )  ? (array) $input : $input;
		if ( is_array( $input ) ) {

			foreach ( $input as $key => $val ) {

				$key = strip_tags( (string) $key );

				if ( in_array( (string) $key, $exclude ) ) {
					continue;
				}

				$tabs = '';
				for ( $i = 0; $i < $depth; $i++ ) {
					$tabs .= '&#09;';
				}

				$out .= $tabs . '[' . $key . '] => ';

				if ( is_array( $val ) || is_object( $val ) ) {
					$new_depth = $depth + 1;
					$out .= '[&#010;' . $this->array_to_string( $val, $exclude, $new_depth ) . $tabs . ']';
				} else {
					$out .= "'" . $val . "'";
				}
				$out .= '&#013;&#010;';
			}
		}
		$out .= '';
		return $out;
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
				$this->level_dropdown();
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
			'level'     => array( 'level', true ),
			'source'    => array( 'source', true ),
		);
	}

	/**
	 * Display source dropdown
	 *
	 * @global wpdb $wpdb
	 */
	protected function source_dropdown() {
		global $wpdb;

		$sources = $wpdb->get_col(
			"SELECT DISTINCT source
			FROM {$wpdb->prefix}woocommerce_log
			WHERE source != ''
			ORDER BY source ASC"
		);

		if ( ! empty( $sources ) ) {
			$selected_source = isset( $_REQUEST['source'] ) ? $_REQUEST['source'] : '';
			?>
				<label for="filter-by-source" class="screen-reader-text"><?php esc_html_e( 'Filter by source', 'woocommerce' ); ?></label>
				<select name="source" id="filter-by-source">
					<option<?php selected( $selected_source, '' ); ?> value=""><?php esc_html_e( 'All sources', 'woocommerce' ); ?></option>
					<?php
					foreach ( $sources as $s ) {
						printf(
							'<option%1$s value="%2$s">%3$s</option>',
							selected( $selected_source, $s, false ),
							esc_attr( $s ),
							esc_html( $s )
						);
					}
					?>
				</select>
			<?php
		}
	}

	/**
	 * Outputs the hidden row displayed when inline editing
	 */
	public function inline_edit() {
		?>
		<table>
			<tbody id="inlineedit">
				<tr id="inline-edit" class="quick-edit-row">
					<td>// The TABLE and TR will display none by default.
					</td>
				</tr>
			</tbody>
		</table>
		<script>
			(function($) {
				$( '#the-list' ).on( 'click', '.editinline', function() {
					$( this ).attr( 'aria-expanded', 'true' );
					console.log( 'clicked' );

					var logId = $( this ).attr( 'data-id' );

				});
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Generates the table rows.
	 *
	 * @since 3.1.0
	 */
	public function display_rows() {
		foreach ( $this->items as $item ) {
			$this->single_row( $item );
			$this->single_row_log( $item );
		}
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item
	 */
	public function single_row_log( $item ) {
		// Maintains alternating row background colors.
		echo '<tr style="display: none"><td></td></tr>';
		echo '<tr><td colspan="' . intval( $this->get_column_count() ) . '">';
		$this->render_log( $item );
		echo '</td></tr>';
	}

	/**
	 * Prepare table list items.
	 *
	 * @global wpdb $wpdb
	 */
	public function prepare_items() {
		global $wpdb;

		$this->prepare_column_headers();

		$per_page = $this->get_items_per_page( 'woocommerce_status_log_items_per_page', 10 );

		$where  = $this->get_items_query_where();
		$order  = $this->get_items_query_order();
		$limit  = $this->get_items_query_limit();
		$offset = $this->get_items_query_offset();

		$query_items = "
			SELECT log_id, timestamp, level, message, source, context
			FROM {$wpdb->prefix}woocommerce_log
			{$where} {$order} {$limit} {$offset}
		";

		$this->items = $wpdb->get_results( $query_items, ARRAY_A );

		$query_count = "SELECT COUNT(log_id) FROM {$wpdb->prefix}woocommerce_log {$where}";
		$total_items = $wpdb->get_var( $query_count );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Get prepared LIMIT clause for items query
	 *
	 * @global wpdb $wpdb
	 *
	 * @return string Prepared LIMIT clause for items query.
	 */
	protected function get_items_query_limit() {
		global $wpdb;

		$per_page = $this->get_items_per_page( 'woocommerce_status_log_items_per_page', 10 );
		return $wpdb->prepare( 'LIMIT %d', $per_page );
	}

	/**
	 * Get prepared OFFSET clause for items query
	 *
	 * @global wpdb $wpdb
	 *
	 * @return string Prepared OFFSET clause for items query.
	 */
	protected function get_items_query_offset() {
		global $wpdb;

		$per_page     = $this->get_items_per_page( 'woocommerce_status_log_items_per_page', 10 );
		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		return $wpdb->prepare( 'OFFSET %d', $offset );
	}

	/**
	 * Get prepared ORDER BY clause for items query
	 *
	 * @return string Prepared ORDER BY clause for items query.
	 */
	protected function get_items_query_order() {
		$valid_orders = array( 'level', 'source', 'timestamp' );
		if ( ! empty( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], $valid_orders ) ) {
			$by = wc_clean( $_REQUEST['orderby'] );
		} else {
			$by = 'timestamp';
		}
		$by = esc_sql( $by );

		if ( ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		return "ORDER BY {$by} {$order}, log_id {$order}";
	}

	/**
	 * Get prepared WHERE clause for items query
	 *
	 * @global wpdb $wpdb
	 *
	 * @return string Prepared WHERE clause for items query.
	 */
	protected function get_items_query_where() {
		global $wpdb;

		$where_conditions = array();
		$where_values     = array();
		if ( ! empty( $_REQUEST['level'] ) && WC_Log_Levels::is_valid_level( $_REQUEST['level'] ) ) {
			$where_conditions[] = 'level >= %d';
			$where_values[]     = WC_Log_Levels::get_level_severity( $_REQUEST['level'] );
		}
		if ( ! empty( $_REQUEST['source'] ) ) {
			$where_conditions[] = 'source = %s';
			$where_values[]     = wc_clean( $_REQUEST['source'] );
		}
		if ( ! empty( $_REQUEST['s'] ) ) {
			$where_conditions[] = 'message like %s';
			$where_values[]     = '%' . $wpdb->esc_like( wc_clean( wp_unslash( $_REQUEST['s'] ) ) ) . '%';
		}

		if ( empty( $where_conditions ) ) {
			return '';
		}

		return $wpdb->prepare( 'WHERE 1 = 1 AND ' . implode( ' AND ', $where_conditions ), $where_values );
	}

	/**
	 * Set _column_headers property for table list
	 */
	protected function prepare_column_headers() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}
}
