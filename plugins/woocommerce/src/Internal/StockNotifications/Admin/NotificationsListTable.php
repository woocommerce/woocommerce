<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;

/**
 * Notifications list table for Customer Stock Notifications.
 */
class NotificationsListTable extends \WP_List_Table {

	/**
	 * Page home URL.
	 *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=back_in_stock_notifications';

	/**
	 * Total view records.
	 *
	 * @var int
	 */
	public $total_items = 0;

	/**
	 * Total active records.
	 *
	 * @var int
	 */
	public $total_active_items = 0;

	/**
	 * Total inactive records.
	 *
	 * @var int
	 */
	public $total_inactive_items = 0;

	/**
	 * Total queued records.
	 *
	 * @var int
	 */
	public $total_queued_items = 0;

	/**
	 * Are there any notifications in the DB?.
	 *
	 * @var int
	 */
	public $has_stock_notifications = false;

	public $data_store;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $status, $page;

		$this->data_store = \WC_Data_Store::load( 'stock_notification' );

		$this->total_items             = $this->data_store->query( array( 'return' => 'count' ) );
		$this->has_stock_notifications = $this->total_items > 0 ? true : false;

// TODO: Check if this is needed.
// error_log( print_r( $this->total_items, true ) );
// $this->total_queued_items      = wc_get_container()->get( NotificationsDB::class )->query(
// 	array(
// 		'count'     => true,
// 		'is_queued' => 'on',
// 	)
// );
		$this->total_active_items   = $this->data_store->query(
			array(
				'return'    => 'count',
				'is_active' => 'on',
			)
		);

// $this->total_inactive_items = wc_get_container()->get( NotificationsDB::class )->query(
// 	array(
// 		'count'     => true,
// 		'is_active' => 'off',
// 	)
// );

		parent::__construct(
			array(
				'singular' => 'woocommerce_stock_notification',
				'plural'   => 'woocommerce_stock_notifications',
			)
		);
	}

	/**
	 * This is a default column renderer
	 *
	 * @param $notification - row (key, value array)
	 * @param $column_name - string (key)
	 * @return string
	 */
	public function column_default( $notification, $column_name ) {
	
		$notification_data = $notification->get_data();

		if ( isset( $notification_data[ $column_name ] ) ) {

			echo wp_kses_post( $notification_data[ $column_name ] );

		} else {

			/**
			 * Fires in each custom column in the Back In Stock list table.
			 *
			 * This hook only fires if the current column_name is not set inside the $notification's keys.
			 *
			 * @param string $column_name The name of the column to display.
			 * @param array  $notification
			 */
			do_action( 'manage_bis_notifications_custom_column', $column_name, $notification );
		}
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_cb( $notification ) {
		?><label class="screen-reader-text" for="cb-select-<?php echo absint( $notification->get_id() ); ?>">
		<?php
			/* translators: %s: Notification code */
			printf( esc_html__( 'Select %s', 'woocommerce' ), esc_html( $notification->get_id() ) );
		?>
		</label>
		<input id="cb-select-<?php echo absint( $notification->get_id() ); ?>" type="checkbox" name="notification[]" value="<?php echo absint( $notification->get_id() ); ?>" />
		<?php
	}

	/**
	 * Handles the title column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_id( $notification ) {
		$actions = array(
			'edit'   => sprintf( '<a href="' . admin_url( 'admin.php?page=bis_notifications&section=edit&notification=%d' ) . '">%s</a>', $notification->get_id(), __( 'Edit', 'woocommerce' ) ),
			'delete' => sprintf( '<a href="' . wp_nonce_url( admin_url( 'admin.php?page=bis_notifications&section=delete&notification=%d' ), 'delete_notification' ) . '">%s</a>', $notification->get_id(), __( 'Delete', 'woocommerce' ) ),
		);

		$title = $notification->get_id();

		printf(
			'<a class="row-title" href="%s" aria-label="%s">#%s</a>%s',
			esc_url( admin_url( 'admin.php?page=bis_notifications&section=edit&notification=' . $notification->get_id() ) ),
			/* translators: %s: Notification code */
			sprintf( esc_attr__( '&#8220;%s&#8221; (Edit)', 'woocommerce' ), esc_attr( $title ) ),
			esc_html( $title ),
			wp_kses_post( $this->row_actions( $actions ) )
		);
	}

	/**
	 * Handles the status column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_status( $notification ) {
		// Build tooltip.
		$tooltip = '';

// TODO: Add statuses.
// if ( ! $notification->is_verified() && $notification->is_pending() ) {
// 	$status  = 'cancelled';
// 	$label   = __( 'Pending', 'woocommerce' );
// 	$tooltip = __( 'Awaiting verification', 'woocommerce' );
// } elseif ( $notification->is_queued() ) {
// 	$status = 'on-hold';
// 	$label  = __( 'Queued', 'woocommerce' );
// } elseif ( ! $notification->is_active() ) {
// 	$status = 'cancelled';
// 	$label  = __( 'Inactive', 'woocommerce' );
// } else {
// 	$status = 'completed';
// 	$label  = __( 'Active', 'woocommerce' );
// }

		$status = 'completed';
		$label  = __( 'Active', 'woocommerce' );

		if ( ! empty( $tooltip ) ) {
			printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $status ) ), wp_kses_post( $tooltip ), esc_html( $label ) );
		} else {
			printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $status ) ), esc_html( $label ) );
		}
	}

	/**
	 * Handles the redeemed user column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_user( $notification ) {
		if ( $notification->get_user_id() ) {
			$user = get_user_by( 'id', $notification->get_user_id() );
		}

		if ( isset( $user ) && $user ) {
			printf( '<a href="%s" target="_blank">%s</a>', esc_url( get_edit_user_link( $user->ID ) ), esc_html( $user->display_name ) );
		} else {
			echo esc_html( $notification->get_user_email() );
		}
	}

	/**
	 * Handles the product column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_product( $notification ) {
		$product = $notification->get_product();
		if ( is_a( $product, 'WC_Product' ) ) {

			// TODO: Make this a function?
			$name                     = $product->get_name();
			$formatted_variation_list = $this->get_product_formatted_variation_list( true );

			if ( $formatted_variation_list ) {
				/* translators: product name, identifier */
				$name .= '<span class="description">' . $formatted_variation_list . '</span>';
			}

			echo wp_kses_post(
				sprintf(
					'<a target="_blank" href="' . admin_url( 'post.php?post=%d&action=edit' ) . '">%s</a>',
					$product->get_parent_id() ? absint( $product->get_parent_id() ) : absint( $product->get_id() ),
					wp_kses_post( $name )
				)
			);
		} else {
			echo '&mdash;';
		}
	}

	/**
	 * Handles the product SKU output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_sku( $notification ) {
		$product = $notification->get_product();
		$sku     = false;

		if ( is_a( $product, 'WC_Product' ) ) {
			$sku = $product->get_sku();
		}

		if ( $sku ) {
			echo wp_kses_post( $sku );
		} else {
			echo '&mdash;';
		}
	}

	/**
	 * Handles the notification date column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_date_subscribed( $notification ) {
		$date_created = $notification->get_date_created();
		if ( ! $date_created ) {
			$t_time = __( 'Unpublished', 'woocommerce' );
			$h_time = $t_time;
		} else {
			$t_time = date_i18n( _x( 'Y/m/d g:i:s a', 'list table date hover format', 'woocommerce' ), $date_created );
			$h_time = date_i18n( wc_date_format(), $date_created );
		}

		echo '<span title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</span>';
	}

	/**
	 * Handles the waiting since column output.
	 *
	 * @param WC_BIS_Notification_Data $notification The notification object.
	 * @return void
	 */
	public function column_waiting_since( $notification ) {
// TODO: Is this needed?
// if ( empty( $notification->get_subscribe_date() ) || $notification->is_delivered() || ! $notification->is_active() ) {
// 	$t_time    = __( '&mdash;', 'woocommerce' );
// 	$h_time    = $t_time;
// 	$time_diff = 0;
// } else {
// 	$t_time    = date_i18n( _x( 'Y/m/d g:i:s a', 'list table date hover format', 'woocommerce' ), $notification->get_subscribe_date() );
// 	$time_diff = time() - $notification->get_subscribe_date();

// 	if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
// 		/* translators: %s: human time diff */
// 		$h_time = wp_kses_post( human_time_diff( $notification->get_subscribe_date() ) );
// 	} else {
// 		$h_time = date_i18n( wc_date_format(), $notification->get_subscribe_date() );
// 	}
// }

// echo '<span title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</span>';
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @return void
	 */
	public function no_items() {
		?>
		<p class="main">
			<?php esc_html_e( 'No Notifications found', 'woocommerce' ); ?>
		</p>
		<?php
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 */
	public function get_columns() {

		$columns                    = array();
		$columns['cb']              = '<input type="checkbox" />';
		$columns['id']              = _x( 'ID', 'column_name', 'woocommerce' );
		$columns['status']          = _x( 'Status', 'column_name', 'woocommerce' );
		$columns['user']            = _x( 'User/Email', 'column_name', 'woocommerce' );
		$columns['product']         = _x( 'Product', 'column_name', 'woocommerce' );
		$columns['sku']             = _x( 'SKU', 'column_name', 'woocommerce' );
		$columns['date_subscribed'] = _x( 'Signed Up', 'column_name', 'woocommerce' );
		$columns['waiting_since']   = _x( 'Waiting', 'column_name', 'woocommerce' );

		// TODO: Maybe remove filter?
		/**
		 * Filters the columns displayed in the Back In Stock list table.
		 *
		 * @since 9.9.0
		 *
		 * @param array $columns An associative array of column headings.
		 */
		return apply_filters( 'manage_bis_notifications_columns', $columns );
	}

	/**
	 * Return sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'date_subscribed' => array( 'subscribe_date', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions            = array();
		$actions['enable']  = __( 'Activate', 'woocommerce' );
		$actions['disable'] = __( 'Deactivate', 'woocommerce' );
		$actions['delete']  = __( 'Delete permanently', 'woocommerce' );
		return $actions;
	}

	/**
	 * Query the DB and attach items.
	 *
	 * @return void
	 */
	public function prepare_items() {

		$per_page = (int) get_user_meta( get_current_user_id(), 'stock_notifications_per_page', true ) ?: 10;

		// Table columns.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$has_filters           = false;

		// TODO: Create bulk actions.
		// Process actions.
		//$this->process_bulk_action();
		//$this->process_clear_queue_action();

		// Setup params.
		$paged   = isset( $_REQUEST['paged'] ) ? max( 0, (int) wp_unslash( $_REQUEST['paged'] ) - 1 ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$orderby = ( isset( $_REQUEST['orderby'] ) && in_array( wp_unslash( $_REQUEST['orderby'] ), array_keys( $this->get_sortable_columns() ), true ) ) ? wc_clean( wp_unslash( $_REQUEST['orderby'] ) ) : 'id'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = ( isset( $_REQUEST['order'] ) && in_array( wp_unslash( $_REQUEST['order'] ), array( 'asc', 'desc' ), true ) ) ? wc_clean( wp_unslash( $_REQUEST['order'] ) ) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Query args.
		$query_args = array(
			'order_by' => array( $orderby => $order ),
			'limit'    => $per_page,
			'offset'   => $paged * $per_page,
		);

		// Search.
		if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['search'] = wc_clean( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Views.
		if ( ! empty( $_REQUEST['status'] ) && 'active_bis_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['is_active'] = 'on';
		} elseif ( ! empty( $_REQUEST['status'] ) && 'inactive_bis_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['is_active'] = 'off';
		} elseif ( ! empty( $_REQUEST['status'] ) && 'queued_bis_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['is_queued'] = 'on';
		}

		// Filters.
		if ( ! empty( $_GET['m'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter = absint( wp_unslash( $_GET['m'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$month  = substr( (string) $filter, 4, 6 );
			$year   = substr( (string) $filter, 0, 4 ); // This will break at year 10.000 AC :).

			if ( $filter ) {
				$start_date               = strtotime( "{$year}-{$month}-01" );
				$query_args['start_date'] = $start_date;

				$end_date               = strtotime( '+1 month', $start_date );
				$query_args['end_date'] = $end_date;
			}

			$has_filters = true;
		}

		if ( ! empty( $_GET['bis_product_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter                   = absint( wp_unslash( $_GET['bis_product_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['product_id'] = array( $filter );
			$has_filters              = true;
		}

		if ( ! empty( $_GET['bis_customer_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter                = absint( wp_unslash( $_GET['bis_customer_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['user_id'] = array( $filter );
			$has_filters           = true;
		}

		// Only show existing products.
		$query_args['product_exists'] = true;

		$this->items = $this->data_store->query( $query_args );

		// Count total items.
		$query_args['return'] = 'count';
		unset( $query_args['limit'] );
		unset( $query_args['offset'] );
		$this->total_items = $this->data_store->query( $query_args );

		// If has filter, re-calc the views numbers.
		if ( $has_filters ) {
			// Count active.
			$query_args['is_active']  = 'on';
			$this->total_active_items = $this->data_store->query( $query_args );
			// Count inactive.
			$query_args['is_active']    = 'off';
			$this->total_inactive_items = $this->data_store->query( $query_args );
			// Count queued.
			$query_args['is_active']  = null;
			$query_args['is_queued']  = 'on';
			$this->total_queued_items = $this->data_store->query( $query_args );
		}

		// Configure pagination.
		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items, // Total items defined above.
				'per_page'    => $per_page, // Per page constant defined at top of method.
				'total_pages' => ceil( $this->total_items / $per_page ), // Calculate pages count.
			)
		);
	}

		/**
	 * Display table extra nav.
	 *
	 * @param string $which top|bottom.
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which && ! is_singular() ) {
			?>
			<div class="alignleft actions">
				<?php
				$this->render_filters();
				submit_button( __( 'Filter', 'woocommerce' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
				if ( 0 < $this->total_queued_items ) {
					?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'bis_clear_queued_items' => 1 ), admin_url( 'admin.php?page=bis_notifications' ) ), 'wc_bis_abort_all_queued_notifications' ) ); ?>" class="button"><?php esc_html_e( 'Abort Queued', 'woocommerce' ); ?></a>
					<?php
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * Display table filters.
	 *
	 * @return void
	 */
	protected function render_filters() {
		//$this->display_months_dropdown();
		$this->display_customer_dropdown();
		$this->display_product_dropdown();
	}

	/**
	 * Display product filter.
	 *
	 * @return void
	 */
	protected function display_product_dropdown() {
		$product_string = '';
		$product_id     = '';

		if ( ! empty( $_GET['bis_product_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product_id = wc_clean( wp_unslash( $_GET['bis_product_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product    = wc_get_product( absint( $product_id ) );

			if ( $product ) {
				$product_string = sprintf(
					/* translators: 1: product title 2: product ID */
					esc_html__( '%1$s (#%2$s)', 'woocommerce' ),
					$product->get_parent_id() ? $product->get_name() : $product->get_title(),
					absint( $product->get_id() )
				);
			}
		}
		?>
		<select class="wc-product-search" name="bis_product_filter" data-placeholder="<?php esc_attr_e( 'Select product&hellip;', 'woocommerce' ); ?>" data-allow_clear="true" id="bis_product_filter">
			<?php if ( $product_string && $product_id ) { ?>
				<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo wp_kses_post( htmlspecialchars( $product_string, ENT_COMPAT ) ); ?><option>
			<?php } ?>
		</select>
		<?php
	}

	/**
	 * Display customer filter.
	 *
	 * @return void
	 */
	protected function display_customer_dropdown() {
		$user_string = '';
		$user_id     = '';

		if ( ! empty( $_GET['bis_customer_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$user_id = wc_clean( wp_unslash( $_GET['bis_customer_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$user    = get_user_by( 'id', absint( $user_id ) );

			if ( $user ) {
				$user_string = sprintf(
					/* translators: 1: user display name 2: user ID 3: user email */
					esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
					$user->display_name,
					absint( $user->ID ),
					$user->user_email
				);
			}
		}
		?>
		<select class="wc-customer-search" name="bis_customer_filter" data-placeholder="<?php esc_attr_e( 'Select customer&hellip;', 'woocommerce' ); ?>" data-allow_clear="true" id="bis_customer_filter">
			<?php if ( $user_string && $user_id ) { ?>
				<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo wp_kses_post( htmlspecialchars( $user_string, ENT_COMPAT ) ); ?><option>
			<?php } ?>
		</select>
		<?php
	}

	/**
	 * Display dates dropdown filter.
	 *
	 * @return void
	 */
	protected function display_months_dropdown() {
		global $wp_locale;

		$months      = WC_BIS()->db->notifications->get_distinct_dates();
		$month_count = count( $months );

		if ( ! $month_count || ( 1 === $month_count && 0 === (int) $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) wp_unslash( $_GET['m'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php esc_html_e( 'Filter by date', 'woocommerce' ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php esc_html_e( 'All dates', 'woocommerce' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 === (int) $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf(
					"<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: %1$s: month %2$s: year */
					sprintf( esc_html__( '%1$s %2$d', 'woocommerce' ), esc_html( $wp_locale->get_month( $month ) ), esc_html( $year ) )
				);
			}
			?>
		</select>
		<?php
	}
}