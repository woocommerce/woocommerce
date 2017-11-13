<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Report_Downloads.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     3.3.0
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
	 * @param string $position
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
		if ( isset( $_GET['permission_id'] ) && is_numeric( $_GET['permission_id'] ) && $_GET['permission_id'] > 0 ) {
			$permission_id = $_GET['permission_id'];
			// Load the permission, order, etc. so we can render more information.
			$permission	= null;
			$product	= null;
			try {
				$permission	= new WC_Customer_Download( $permission_id );
				$product	= wc_get_product( $permission->product_id );
			} catch ( Exception $e ) {
				wp_die( sprintf( __( 'Permission #%d not found.', 'woocommerce' ), $permission_id ) );
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
	 * @param mixed $file
	 * @param WC_Product $product
	 * @param WC_Customer_Download $permission
	 */
	protected function output_report_title( $file, $product, $permission ) {
		// Output file information (if provided).
		if ( $file ) {
			echo '<p><strong>' . __( 'File:', 'woocommerce' ) . '</strong><br/>' . esc_html( $file->get_name() ) . '</p>';
		}

		// Output product information.
		echo '<p><strong>' . __( 'Product: ', 'woocommerce' ) . '</strong><br/>';
		edit_post_link( $product->get_formatted_name(), '', '', $product->get_id() );
		echo '</p>';

		// Output order information.
		echo '<p><strong>' . __( 'Order: ', 'woocommerce' ) . '</strong><br/>';
		edit_post_link( '#' . $permission->order_id, '', '', $permission->order_id );
		echo '</p>';
	}

	/**
	 * Get column value.
	 *
	 * @param mixed $item
	 * @param string $column_name
	 */
	public function column_default( $item, $column_name ) {

		$permission = null;
		$product = null;
		try {
			$permission	= new WC_Customer_Download( $item->permission_id );
			$product	= wc_get_product( $permission->product_id );
		} catch ( Exception $e ) {
			// Ok to continue rendering other information even if permission and/or product is not found.
		}

		switch ( $column_name ) {
			case 'timestamp' :
				echo esc_html( $item->timestamp );
				break;

			case 'product' :
				if ( empty( $product ) ) {
					break;
				}

				edit_post_link( $product->get_formatted_name(), '', '', $product->get_id() );

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
	 * @param int $current_page
	 * @param int $per_page
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		// Get downloads from database.
		$table = $wpdb->prefix . WC_Customer_Download_Log_Data_Store::get_table_name();

		$query_from = "FROM {$table} as downloads WHERE 1=1 ";

		// Apply filter by permission.
		if ( isset( $_GET['permission_id'] ) && is_numeric( $_GET['permission_id'] ) && $_GET['permission_id'] > 0 ) {

			// Ensure the permission and product exist.
			try {
				$permission	= new WC_Customer_Download( $_GET['permission_id'] );
				$product	= wc_get_product( $permission->product_id );
			} catch ( Exception $e ) {
				// Leave array empty since we can't find the permission.
				return;
			}

			// Add filter for permission id.
			$query_from .= $wpdb->prepare( "AND permission_id = %d", $_GET['permission_id'] );
		}

		$query_from = apply_filters( 'woocommerce_report_downloads_query_from', $query_from );

		$this->items     = $wpdb->get_results( $wpdb->prepare( "SELECT * {$query_from} ORDER BY timestamp DESC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page ) );
		$this->max_items = $wpdb->get_var( "SELECT COUNT( DISTINCT download_log_id ) {$query_from};" );
	}
}
