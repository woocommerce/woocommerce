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

		// Subtitle for permission if set
		if ( isset( $_GET['permission_id'] ) && is_numeric( $_GET['permission_id'] ) && $_GET['permission_id'] > 0 ) {
			// Load the permission, order, etc. so we can render more information
			$permission	= null;
			$product	= null;
			try {
				$permission	= new WC_Customer_Download( $_GET['permission_id'] );
				$product	= wc_get_product( $permission->product_id );
			} catch ( Exception $e ) {
				echo '<p>Permission #' . esc_html( $_GET['permission_id'] ) . ' not found.</p>';
			}

			if ( ! empty( $permission ) && ! empty( $product ) ) {
				echo '<h2>';

				// File information
				if ( $file = $product->get_file( $permission->get_download_id() ) ) {
					echo esc_html( $file->get_name() ) . ' for ';
				}

				// Product information
				$product_description = '';
				if ( $sku = $product->get_sku() ) {
					$product_description .= $sku . ' - ';
				}
				$product_description .= $product->get_name();
				echo edit_post_link( $product_description, '', '', $product->get_id() );

				// Order information
				echo ' in Order ';
				echo edit_post_link( '#' . $permission->order_id, '', '', $permission->order_id );

				echo '</h2>';
			}
		}

		echo '<div id="poststuff" class="woocommerce-reports-wide">';
		$this->display();
		echo '</div>';
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
			// Ok to continue rendering other information even if permission and/or product is not found
		}

		switch ( $column_name ) {
			case 'timestamp' :
				echo esc_html( $item->timestamp );
				break;

			case 'product' :
				if ( empty( $product ) ) {
					break;
				}

				$product_description = '';
				if ( $sku = $product->get_sku() ) {
					$product_description .= $sku . ' - ';
				}
				$product_description .= $product->get_name();

				echo edit_post_link( $product_description, '', '', $product->get_id() );

				break;

			case 'order' :
				if ( ! empty( $permission ) ) {
					echo edit_post_link( '#' . $permission->order_id, '', '', $permission->order_id );
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

						echo '<a href="' . get_edit_user_link( $item->user_id ) . '">' . esc_html( $user_description ) . '</a>';
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
			'timestamp'			=> __( 'Download time', 'woocommerce' ),
			'product'			=> __( 'Product', 'woocommerce' ),
			'order'				=> __( 'Order', 'woocommerce' ),
			'user'				=> __( 'User', 'woocommerce' ),
			'user_ip_address'	=> __( 'User IP Address', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Prepare download list items.
	 */
	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$current_page          = absint( $this->get_pagenum() );
		$per_page              = apply_filters( 'woocommerce_admin_downloads_report_downloads_per_page', 20 );

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
		_e( 'No downloads found.', 'woocommerce' );
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

		// Get downloads from database
		$table = $wpdb->prefix . 'woocommerce_downloadable_product_download_log';

		$query_from = "FROM {$table} as downloads WHERE 1=1 ";

		// Apply filter by permission
		if ( isset( $_GET['permission_id'] ) && is_numeric( $_GET['permission_id'] ) && $_GET['permission_id'] > 0 ) {

			// Ensure the permission and product exist
			try {
				$permission	= new WC_Customer_Download( $_GET['permission_id'] );
				$product	= wc_get_product( $permission->product_id );
			} catch ( Exception $e ) {
				// Return empty array since we can't find the permission
				return array();
			}

			// Add filter for permission id
			$query_from .= $wpdb->prepare( "AND permission_id = %d", $_GET['permission_id'] );
		}

		$query_from = apply_filters( 'woocommerce_report_downloads_query_from', $query_from );

		$this->items     = $wpdb->get_results( $wpdb->prepare( "SELECT * {$query_from} ORDER BY timestamp DESC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page ) );
		$this->max_items = $wpdb->get_var( "SELECT COUNT( DISTINCT download_log_id ) {$query_from};" );
	}
}
