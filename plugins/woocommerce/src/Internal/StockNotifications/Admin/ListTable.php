<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;

/**
 * Notifications list table for Customer Stock Notifications.
 */
class ListTable extends \WP_List_Table {

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
	 * Total pending records.
	 *
	 * @var int
	 */
	public $total_pending_items = 0;

	/**
	 * Total cancelled records.
	 *
	 * @var int
	 */
	public $total_cancelled_items = 0;

	/**
	 * Total sent records.
	 *
	 * @var int
	 */
	public $total_sent_items = 0;

	/**
	 * Has stock notifications.
	 *
	 * @var bool
	 */
	public $has_stock_notifications = false;

	/**
	 * Data store.
	 *
	 * @var StockNotificationsDataStore
	 */
	public $data_store;

	/**
	 * Eligibility service.
	 *
	 * @var EligibilityService
	 */
	public $eligibility_service;

	/**
	 * Init.
	 *
	 * @internal
	 *
	 * @param EligibilityService $eligibility_service Eligibility service.
	 */
	final public function init( EligibilityService $eligibility_service ) {
		$this->eligibility_service = $eligibility_service;
	}

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->data_store              = \WC_Data_Store::load( 'stock_notification' );
		$this->has_stock_notifications = $this->data_store->query( array( 'return' => 'count' ) ) > 0;

		parent::__construct(
			array(
				'singular' => 'woocommerce_stock_notification',
				'plural'   => 'woocommerce_stock_notifications',
			)
		);
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param Notification $notification The notification object.
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
	 * @param Notification $notification The notification object.
	 * @return void
	 */
	public function column_id( $notification ) {
		$actions = array(
			'edit'   => sprintf( '<a href="' . admin_url( NotificationsPage::PAGE_URL . '&notification_action=edit&notification_id=%d' ) . '">%s</a>', $notification->get_id(), __( 'Edit', 'woocommerce' ) ),
			'delete' => sprintf( '<a href="' . wp_nonce_url( admin_url( NotificationsPage::PAGE_URL . '&notification_action=delete&notification_id=%d' ), 'delete_customer_stock_notification' ) . '">%s</a>', $notification->get_id(), __( 'Delete', 'woocommerce' ) ),
		);

		$title = $notification->get_id();

		printf(
			'<a class="row-title" href="%s" aria-label="%s">#%s</a>%s',
			esc_url( admin_url( NotificationsPage::PAGE_URL . '&notification_action=edit&notification_id=' . $notification->get_id() ) ),
			/* translators: %s: Notification code */
			sprintf( esc_attr__( '&#8220;%s&#8221; (Edit)', 'woocommerce' ), esc_attr( $title ) ),
			esc_html( $title ),
			wp_kses_post( $this->row_actions( $actions ) )
		);
	}

	/**
	 * Handles the status column output.
	 *
	 * @param Notification $notification The notification object.
	 * @return void
	 */
	public function column_status( $notification ) {

		if ( $notification->get_status() === NotificationStatus::PENDING ) {
			$status = 'cancelled';
			$label  = _x( 'Pending', 'stock notification status', 'woocommerce' );
		} elseif ( $notification->get_status() === NotificationStatus::CANCELLED ) {
			$status = 'cancelled';
			$label  = _x( 'Cancelled', 'stock notification status', 'woocommerce' );
		} elseif ( $notification->get_status() === NotificationStatus::SENT ) {
			$status = 'cancelled';
			$label  = _x( 'Sent', 'stock notification status', 'woocommerce' );
		} else {
			$status = 'completed';
			$label  = _x( 'Active', 'stock notification status', 'woocommerce' );
		}

		printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $status ) ), esc_html( $label ) );
	}

	/**
	 * Handles the redeemed user column output.
	 *
	 * @param Notification $notification The notification object.
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
	 * @param Notification $notification The notification object.
	 * @return void
	 */
	public function column_product( $notification ) {
		$product = $notification->get_product();

		if ( ! is_a( $product, 'WC_Product' ) ) {
			echo '&mdash;';
			return;
		}

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
				$name
			)
		);
	}

	/**
	 * Handles the product SKU output.
	 *
	 * @param Notification $notification The notification object.
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
	 * @param Notification $notification The notification object.
	 * @return void
	 */
	public function column_date_created_gmt( $notification ) {
		$date_created = $notification->get_date_created();

		if ( ! $date_created ) {
			$t_time = __( '&mdash;', 'woocommerce' );
			$h_time = $t_time;
		} else {
			$date_created = $date_created->getTimestamp();
			$t_time       = date_i18n( _x( 'Y/m/d g:i:s a', 'list table date hover format', 'woocommerce' ), $date_created );
			$h_time       = date_i18n( wc_date_format(), $date_created );
		}

		echo '<span title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</span>';
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

		$columns                     = array();
		$columns['cb']               = '<input type="checkbox" />';
		$columns['id']               = _x( 'Notification', 'column_name', 'woocommerce' );
		$columns['status']           = _x( 'Status', 'column_name', 'woocommerce' );
		$columns['user']             = _x( 'User/Email', 'column_name', 'woocommerce' );
		$columns['product']          = _x( 'Product', 'column_name', 'woocommerce' );
		$columns['sku']              = _x( 'SKU', 'column_name', 'woocommerce' );
		$columns['date_created_gmt'] = _x( 'Signed Up', 'column_name', 'woocommerce' );

		return $columns;
	}

	/**
	 * Return sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'      => array( 'id', true ),
			'product' => array( 'product_id', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Returns bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions           = array();
		$actions['enable'] = __( 'Activate', 'woocommerce' );
		$actions['cancel'] = __( 'Cancel', 'woocommerce' );
		$actions['delete'] = __( 'Delete permanently', 'woocommerce' );
		return $actions;
	}

	/**
	 * Query the DB and attach items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = (int) get_user_meta( get_current_user_id(), 'stock_notifications_per_page', true );
		$per_page = $per_page > 0 ? $per_page : 10;

		// Table columns.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

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
			$query_args['user_email'] = wc_clean( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Views.
		if ( ! empty( $_REQUEST['status'] ) && 'active_customer_stock_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['status'] = NotificationStatus::ACTIVE;
		} elseif ( ! empty( $_REQUEST['status'] ) && 'sent_customer_stock_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['status'] = NotificationStatus::SENT;
		} elseif ( ! empty( $_REQUEST['status'] ) && 'cancelled_customer_stock_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['status'] = NotificationStatus::CANCELLED;
		} elseif ( ! empty( $_REQUEST['status'] ) && 'pending_customer_stock_notifications' === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['status'] = NotificationStatus::PENDING;
		}

		// Filters.
		if ( ! empty( $_GET['m'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter = absint( wp_unslash( $_GET['m'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$month  = substr( (string) $filter, 4, 6 );
			$year   = substr( (string) $filter, 0, 4 ); // This will break at year 10.000 AC :).

			$start_timestamp          = mktime( 0, 0, 0, (int) $month, 1, (int) $year );
			$query_args['start_date'] = gmdate( 'Y-m-d H:i:s', $start_timestamp );

			$end_timestamp          = mktime( 0, 0, 0, (int) $month + 1, 1, (int) $year );
			$query_args['end_date'] = gmdate( 'Y-m-d H:i:s', $end_timestamp );
		}

		if ( ! empty( $_GET['customer_stock_notifications_product_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter  = absint( wp_unslash( $_GET['customer_stock_notifications_product_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product = wc_get_product( $filter );
			if ( $product instanceof \WC_Product ) {
				$target_ids               = $this->eligibility_service->get_target_product_ids( $product );
				$query_args['product_id'] = $target_ids;
			} else {
				NotificationsPage::add_notice( __( 'Invalid product selected.', 'woocommerce' ), 'error' );
			}
		}

		if ( ! empty( $_GET['customer_stock_notifications_customer_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter                = absint( wp_unslash( $_GET['customer_stock_notifications_customer_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$query_args['user_id'] = array( $filter );
		}

		$query_args['return'] = 'objects';
		$this->items          = $this->data_store->query( $query_args );

		// Count total items.
		$query_args['return'] = 'count';
		unset( $query_args['limit'] );
		unset( $query_args['offset'] );
		$this->total_items = $this->data_store->query( $query_args );

		// Count active.
		$query_args['status']     = NotificationStatus::ACTIVE;
		$this->total_active_items = $this->data_store->query( $query_args );

		// Count sent.
		$query_args['status']   = NotificationStatus::SENT;
		$this->total_sent_items = $this->data_store->query( $query_args );

		// Count cancelled.
		$query_args['status']        = NotificationStatus::CANCELLED;
		$this->total_cancelled_items = $this->data_store->query( $query_args );

		// Count pending.
		$query_args['status']      = NotificationStatus::PENDING;
		$this->total_pending_items = $this->data_store->query( $query_args );

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
		$this->display_months_dropdown();
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

		if ( ! empty( $_GET['customer_stock_notifications_product_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product_id = wc_clean( wp_unslash( $_GET['customer_stock_notifications_product_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		<select class="wc-product-search" name="customer_stock_notifications_product_filter" data-placeholder="<?php esc_attr_e( 'Select product&hellip;', 'woocommerce' ); ?>" data-allow_clear="true" id="customer_stock_notifications_product_filter">
			<?php if ( $product_string && $product_id ) { ?>
				<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo wp_kses_post( htmlspecialchars( $product_string, ENT_COMPAT ) ); ?></option>
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

		if ( ! empty( $_GET['customer_stock_notifications_customer_filter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$user_id = wc_clean( wp_unslash( $_GET['customer_stock_notifications_customer_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		<select class="wc-customer-search" name="customer_stock_notifications_customer_filter" data-placeholder="<?php esc_attr_e( 'Select customer&hellip;', 'woocommerce' ); ?>" data-allow_clear="true" id="customer_stock_notifications_customer_filter">
			<?php if ( $user_string && $user_id ) { ?>
				<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo wp_kses_post( htmlspecialchars( $user_string, ENT_COMPAT ) ); ?></option>
			<?php } ?>
		</select>
		<?php
	}

	/**
	 * Items of the `subsubsub` status menu.
	 *
	 * @return array
	 */
	protected function get_views() {
		$status_links = array();

		// All view.
		$class          = ! empty( $_REQUEST['status'] ) && 'all_customer_stock_notifications' === $_REQUEST['status'] ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$all_inner_html = sprintf(
			/* translators: %s: Notifications count */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$this->total_items,
				'notifications_status',
				'woocommerce'
			),
			number_format_i18n( $this->total_items )
		);

		$status_links['all'] = $this->get_link( array( 'status' => 'all_customer_stock_notifications' ), $all_inner_html, $class );

		// Active view.
		$class             = ! empty( $_REQUEST['status'] ) && 'active_customer_stock_notifications' === $_REQUEST['status'] ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_inner_html = sprintf(
			/* translators: %s: Notifications count */
			_nx(
				'Active <span class="count">(%s)</span>',
				'Active <span class="count">(%s)</span>',
				$this->total_active_items,
				'notifications_status',
				'woocommerce'
			),
			number_format_i18n( $this->total_active_items )
		);

		$status_links['active'] = $this->get_link( array( 'status' => 'active_customer_stock_notifications' ), $active_inner_html, $class );

		// Sent view.
		$class           = ! empty( $_REQUEST['status'] ) && 'sent_customer_stock_notifications' === $_REQUEST['status'] ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sent_inner_html = sprintf(
			/* translators: %s: Notifications count */
			_nx(
				'Sent <span class="count">(%s)</span>',
				'Sent <span class="count">(%s)</span>',
				$this->total_sent_items,
				'notifications_status',
				'woocommerce'
			),
			number_format_i18n( $this->total_sent_items )
		);

		$status_links['sent'] = $this->get_link( array( 'status' => 'sent_customer_stock_notifications' ), $sent_inner_html, $class );

		// Cancelled view.
		$class                = ! empty( $_REQUEST['status'] ) && 'cancelled_customer_stock_notifications' === $_REQUEST['status'] ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$cancelled_inner_html = sprintf(
			/* translators: %s: Notifications count */
			_nx(
				'Cancelled <span class="count">(%s)</span>',
				'Cancelled <span class="count">(%s)</span>',
				$this->total_cancelled_items,
				'notifications_status',
				'woocommerce'
			),
			number_format_i18n( $this->total_cancelled_items )
		);

		$status_links['cancelled'] = $this->get_link( array( 'status' => 'cancelled_customer_stock_notifications' ), $cancelled_inner_html, $class );

		// Pending view.
		$class              = ! empty( $_REQUEST['status'] ) && 'pending_customer_stock_notifications' === $_REQUEST['status'] ? 'current' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pending_inner_html = sprintf(
			/* translators: %s: Notifications count */
			_nx(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>',
				$this->total_pending_items,
				'notifications_status',
				'woocommerce'
			),
			number_format_i18n( $this->total_pending_items )
		);

		$status_links['pending'] = $this->get_link( array( 'status' => 'pending_customer_stock_notifications' ), $pending_inner_html, $class );

		return $status_links;
	}

	/**
	 * Construct a link string from args.
	 *
	 * @param array  $args Arguments for the link.
	 * @param string $label Link label.
	 * @param string $css_class CSS class.
	 * @return string
	 */
	protected function get_link( $args, $label, $css_class = '' ) {
		$url = add_query_arg( $args );

		$class_html   = '';
		$aria_current = '';
		if ( ! empty( $css_class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $css_class )
			);

			if ( 'current' === $css_class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * Display dates dropdown filter.
	 *
	 * @return void
	 */
	protected function display_months_dropdown() {
		global $wp_locale;

		$months = $this->data_store->get_distinct_dates();

		if ( ! is_array( $months ) ) {
			return;
		}

		$month_count = count( $months );

		if ( $month_count < 1 ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) wp_unslash( $_GET['m'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php esc_html_e( 'Filter by date', 'woocommerce' ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php esc_html_e( 'All dates', 'woocommerce' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 === (int) $arc_row->year || 0 === (int) $arc_row->month ) {
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

	/**
	 * Process actions.
	 */
	public function process_actions(): void {
		$this->process_delete_action();
		$this->process_bulk_action();
	}

	/**
	 * Process delete action.
	 *
	 * @return void
	 */
	public function process_delete_action(): void {

		$action = isset( $_GET['notification_action'] ) ? wc_clean( wp_unslash( $_GET['notification_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'delete' !== $action ) {
			return;
		}

		$notification_id = isset( $_GET['notification_id'] ) ? absint( $_GET['notification_id'] ) : 0;

		if ( ! $notification_id ) {
			return;
		}

		check_admin_referer( 'delete_customer_stock_notification' );

		try {

			$notification = Factory::get_notification( $notification_id );
			$this->data_store->delete( $notification );

			$notice_message = __( 'Notification deleted.', 'woocommerce' );
			NotificationsPage::add_notice( $notice_message, 'success' );

		} catch ( \Exception $e ) {

			$notice_message = __( 'Notification not found.', 'woocommerce' );
			NotificationsPage::add_notice( $notice_message, 'error' );
		}

		wp_safe_redirect( admin_url( NotificationsPage::PAGE_URL ) );
		exit();
	}

	/**
	 * Process bulk actions.
	 *
	 * @return void
	 */
	private function process_bulk_action() {
		if ( ! $this->current_action() ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$notifications = isset( $_GET['notification'] ) && is_array( $_GET['notification'] ) ? array_map( 'absint', $_GET['notification'] ) : array();

		if ( empty( $notifications ) ) {
			return;
		}

		$redirect_url = NotificationsPage::PAGE_URL;

		if ( 'enable' === $this->current_action() ) {
			foreach ( $notifications as $id ) {

				$notification = Factory::get_notification( $id );
				$notification->set_status( NotificationStatus::ACTIVE );
				$this->data_store->update( $notification );

			}
			$notice_message = sprintf(
				/* translators: %s: Notifications count */
				_nx(
					'%s notification updated.',
					'%s notifications updated.',
					count( $notifications ),
					'notifications_status',
					'woocommerce'
				),
				count( $notifications )
			);

			NotificationsPage::add_notice( $notice_message, 'success' );

		} elseif ( 'cancel' === $this->current_action() ) {
			foreach ( $notifications as $id ) {
				$notification = Factory::get_notification( $id );
				$notification->set_status( NotificationStatus::CANCELLED );
				$this->data_store->update( $notification );
			}

			$notice_message = sprintf(
				/* translators: %s: Notifications count */
				_nx(
					'%s notification updated.',
					'%s notifications updated.',
					count( $notifications ),
					'notifications_status',
					'woocommerce'
				),
				count( $notifications )
			);

			NotificationsPage::add_notice( $notice_message, 'success' );

		} elseif ( 'delete' === $this->current_action() ) {
			foreach ( $notifications as $id ) {
				$notification = Factory::get_notification( $id );
				$this->data_store->delete( $notification );
			}

			$notice_message = sprintf(
				/* translators: %s: Notifications count */
				_nx(
					'%s notification deleted.',
					'%s notifications deleted.',
					count( $notifications ),
					'notifications_status',
					'woocommerce'
				),
				count( $notifications )
			);

			NotificationsPage::add_notice( $notice_message, 'success' );
		}

		wp_safe_redirect( $redirect_url );
		exit();
	}
}
