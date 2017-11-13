<?php
/**
 * Download report.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Report_Downloads.
 */
class WC_Report_Downloads extends WP_List_Table {

	/**
	 * Max items.
	 *
	 * @var int
	 */
	protected $max_items;

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct( array(
			'singular'  => 'download',
			'plural'    => 'downloads',
			'ajax'      => false,
		) );
	}

	/**
	 * Don't need this.
	 *
	 * @param string $position Top or bottom.
	 */
	public function display_tablenav( $position ) {
		if ( 'top' !== $position ) {
			parent::display_tablenav( $position );
		}
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$this->prepare_items();

		// Subtitle for permission if set.
		if ( ! empty( $_GET['permission_id'] ) ) { // WPCS: input var ok.
			$permission_id = absint( $_GET['permission_id'] ); // WPCS: input var ok.

			// Load the permission, order, etc. so we can render more information.
			$permission	= null;
			$product	= null;
			try {
				$permission	= new WC_Customer_Download( $permission_id );
				$product	= wc_get_product( $permission->product_id );
			} catch ( Exception $e ) {
				wp_die( sprintf( esc_html__( 'Permission #%d not found.', 'woocommerce' ), esc_html( $permission_id ) ) );
			}

			if ( ! empty( $permission ) && ! empty( $product ) ) {
				// File information.
				$file = $product->get_file( $permission->get_download_id() );

				// Output the titles at the top of the report.
				$this->output_report_title( $file, $product, $permission );
			}
		}

		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		$this->display();
		echo '</div>';
	}

	/**
	 * Output the title at the top of the report.
	 *
	 * @param object               $file File to output.
	 * @param WC_Product           $product Product object.
	 * @param WC_Customer_Download $permission Download permission object.
	 */
	protected function output_report_title( $file, $product, $permission ) {
		// Output file information (if provided).
		if ( $file ) {
			echo '<p><strong>' . esc_html__( 'File:', 'woocommerce' ) . '</strong><br/>' . esc_html( $file->get_name() ) . '</p>';
		}

		// Output product information.
		echo '<p><strong>' . esc_html__( 'Product: ', 'woocommerce' ) . '</strong><br/>';
		edit_post_link( $product->get_formatted_name(), '', '', $product->get_id() );
		echo '</p>';

		// Output order information.
		echo '<p><strong>' . esc_html__( 'Order: ', 'woocommerce' ) . '</strong><br/>';
		edit_post_link( '#' . $permission->order_id, '', '', $permission->order_id );
		echo '</p>';
	}

	/**
	 * Get column value.
	 *
	 * @param mixed  $item Item being displayed.
	 * @param string $column_name Column name.
	 */
	public function column_default( $item, $column_name ) {

		$permission = null;
		$product = null;
		try {
			$permission	= new WC_Customer_Download( $item->permission_id );
			$product	= wc_get_product( $permission->product_id );
		} catch ( Exception $e ) {
			// Ok to continue rendering other information even if permission and/or product is not found.
			return;
		}

		switch ( $column_name ) {
			case 'timestamp' :
				echo esc_html( $item->timestamp );
				break;
			case 'product' :
				if ( ! empty( $product ) ) {
					edit_post_link( $product->get_formatted_name(), '', '', $product->get_id() );
				}
				break;
			case 'order' :
				if ( ! empty( $permission ) ) {
					edit_post_link( '#' . $permission->order_id, '', '', $permission->order_id );
				}
				break;
			case 'user' :
				if ( $item->user_id > 0 ) {
					$user = get_user_by( 'id', $item->user_id );

					if ( ! empty( $user ) ) {
						$user_description = $user->data->user_nicename;
						if ( empty( $user_description ) ) {
							$user_description = $user->data->user_email;
						}

						echo '<a href="' . esc_url( get_edit_user_link( $item->user_id ) ) . '">' . esc_html( $user_description ) . '</a>';
					}
				} else {
					esc_html_e( 'Guest', 'woocommerce' );
				}
				break;
			case 'user_ip_address' :
				echo esc_html( $item->user_ip_address );
				break;
		}
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'timestamp'			=> __( 'Timestamp', 'woocommerce' ),
			'product'			=> __( 'Product', 'woocommerce' ),
			'order'				=> __( 'Order', 'woocommerce' ),
			'user'				=> __( 'User', 'woocommerce' ),
			'user_ip_address'	=> __( 'IP Address', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Prepare download list items.
	 */
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$current_page          = absint( $this->get_pagenum() );
		// Allow filtering per_page value, but ensure it's at least 1.
		$per_page              = max( 1, apply_filters( 'woocommerce_admin_downloads_report_downloads_per_page', 20 ) );

		$this->get_items( $current_page, $per_page );

		/**
		 * Pagination.
		 */
		$this->set_pagination_args( array(
			'total_items' => $this->max_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $this->max_items / $per_page ),
		) );
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No customer downloads has been tracked yet.', 'woocommerce' );
	}

	/**
	 * Get downloads matching criteria.
	 *
	 * @param int $current_page Current viewed page.
	 * @param int $per_page How many results to show per page.
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		// Get downloads from database.
		$table = $wpdb->prefix . WC_Customer_Download_Log_Data_Store::get_table_name();

		$query_from = "FROM {$table} as downloads WHERE 1=1 ";

		// Apply filter by permission.
		if ( ! empty( $_GET['permission_id'] ) ) { // WPCS: input var ok.
			$permission_id = absint( $_GET['permission_id'] ); // WPCS: input var ok.

			// Ensure the permission and product exist.
			try {
				$permission	= new WC_Customer_Download( $permission_id );
				$product	= wc_get_product( $permission->product_id );
			} catch ( Exception $e ) {
				// Leave array empty since we can't find the permission.
				return;
			}

			// Add filter for permission id.
			$query_from .= $wpdb->prepare( 'AND permission_id = %d', $permission_id );
		}

		$query_from  = apply_filters( 'woocommerce_report_downloads_query_from', $query_from );
		$query_order = $wpdb->prepare( 'ORDER BY timestamp DESC LIMIT %d, %d;', ( $current_page - 1 ) * $per_page, $per_page );

		$this->items     = $wpdb->get_results( "SELECT * {$query_from} {$query_order}" ); // WPCS: cache ok, db call ok, unprepared SQL ok.
		$this->max_items = $wpdb->get_var( "SELECT COUNT( DISTINCT download_log_id ) {$query_from};" ); // WPCS: cache ok, db call ok, unprepared SQL ok.
	}
}
