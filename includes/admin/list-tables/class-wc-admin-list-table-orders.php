<?php
/**
 * List tables: orders.
 *
 * @author   WooCommerce
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Admin_List_Table_Orders', false ) ) {
	new WC_Admin_List_Table_Orders();
	return;
}

if ( ! class_exists( 'WC_Admin_List_Table', false ) ) {
	include_once( 'abstract-class-wc-admin-list-table.php' );
}

/**
 * WC_Admin_List_Table_Orders Class.
 */
class WC_Admin_List_Table_Orders extends WC_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'shop_order';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
		add_action( 'admin_footer', array( $this, 'order_preview_template' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'parse_query', array( $this, 'search_custom_fields' ) );
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="woocommerce-BlankState">';
		echo '<h2 class="woocommerce-BlankState-message">' . esc_html__( 'When you receive a new order, it will appear here.', 'woocommerce' ) . '</h2>';
		echo '<a class="woocommerce-BlankState-cta button-primary button" target="_blank" href="https://docs.woocommerce.com/document/managing-orders/?utm_source=blankslate&utm_medium=product&utm_content=ordersdoc&utm_campaign=woocommerceplugin">' . esc_html__( 'Learn more about orders', 'woocommerce' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Define primary column.
	 *
	 * @return array
	 */
	protected function get_primary_column() {
		return 'order_number';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		return array();
	}

	/**
	 * Define hidden columns.
	 *
	 * @return array
	 */
	protected function define_hidden_columns() {
		return array(
			'shipping_address',
			'billing_address',
		);
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = array(
			'order_number' => 'ID',
			'order_total'  => 'order_total',
			'order_date'   => 'date',
		);
		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		$show_columns                     = array();
		$show_columns['cb']               = $columns['cb'];
		$show_columns['order_number']     = __( 'Order', 'woocommerce' );
		$show_columns['billing_address']  = __( 'Billing', 'woocommerce' );
		$show_columns['shipping_address'] = __( 'Ship to', 'woocommerce' );
		$show_columns['order_date']       = __( 'Date', 'woocommerce' );
		$show_columns['order_status']     = __( 'Status', 'woocommerce' );
		$show_columns['order_total']      = __( 'Total', 'woocommerce' );

		if ( has_action( 'woocommerce_admin_order_actions_start' ) || has_action( 'woocommerce_admin_order_actions_end' ) || has_filter( 'woocommerce_admin_order_actions' ) ) {
			$show_columns['order_actions'] = __( 'Actions', 'woocommerce' );
		}

		wp_enqueue_script( 'wc-orders' );

		return $show_columns;
	}

	/**
	 * Define bulk actions.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function define_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		$actions['mark_processing'] = __( 'Mark processing', 'woocommerce' );
		$actions['mark_on-hold']    = __( 'Mark on-hold', 'woocommerce' );
		$actions['mark_completed']  = __( 'Mark complete', 'woocommerce' );

		return $actions;
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_order global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_order;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$this->object = $the_order = wc_get_order( $post_id );
		}
	}

	/**
	 * Render columm: order_number.
	 */
	protected function render_order_number_column() {
		$buyer = '';

		if ( $this->object->get_billing_first_name() || $this->object->get_billing_last_name() ) {
			/* translators: 1: first name 2: last name */
			$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $this->object->get_billing_first_name(), $this->object->get_billing_last_name() ) );
		} elseif ( $this->object->get_billing_company() ) {
			$buyer = trim( $this->object->get_billing_company() );
		} elseif ( $this->object->get_customer_id() ) {
			$user  = get_user_by( 'id', $this->object->get_customer_id() );
			$buyer = ucwords( $user->display_name );
		}

		echo '<a href="#" class="order-preview" data-order-id="' . absint( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'woocommerce' ) ) . '">' . esc_html( __( 'Preview', 'woocommerce' ) ) . '</a>';
		echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $this->object->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
	}

	/**
	 * Render columm: order_status.
	 */
	protected function render_order_status_column() {
		$tooltip       = '';
		$comment_count = absint( get_comment_count( $this->object->get_id() )['approved'] );

		if ( $comment_count ) {
			$latest_notes = wc_get_order_notes( array(
				'order_id' => $this->object->get_id(),
				'limit'    => 1,
				'orderby'  => 'date_created_gmt',
			) );

			$latest_note = current( $latest_notes );

			if ( isset( $latest_note->content ) && 1 === $comment_count ) {
				$tooltip = wc_sanitize_tooltip( $latest_note->content );
			} elseif ( isset( $latest_note->content ) ) {
				/* translators: %d: notes count */
				$tooltip = wc_sanitize_tooltip( $latest_note->content . '<br/><small style="display:block">' . sprintf( _n( 'Plus %d other note', 'Plus %d other notes', ( $comment_count - 1 ), 'woocommerce' ), $comment_count - 1 ) . '</small>' );
			} else {
				/* translators: %d: notes count */
				$tooltip = wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $comment_count, 'woocommerce' ), $comment_count ) );
			}
		}

		if ( $tooltip ) {
			printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), wp_kses_post( $tooltip ), esc_html( wc_get_order_status_name( $this->object->get_status() ) ) );
		} else {
			printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), esc_html( wc_get_order_status_name( $this->object->get_status() ) ) );
		}
	}

	/**
	 * Render columm: order_date.
	 */
	protected function render_order_date_column() {
		$order_timestamp = $this->object->get_date_created()->getTimestamp();

		if ( $order_timestamp > strtotime( '-1 day', current_time( 'timestamp', true ) ) ) {
			$show_date = sprintf( _x( '%s ago', '%s = human-readable time difference', 'woocommerce' ), human_time_diff( $this->object->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) ) );
		} else {
			$show_date = $this->object->get_date_created()->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', get_option( 'date_format' ) ) );
		}
		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $this->object->get_date_created()->date( 'c' ) ),
			esc_html( $this->object->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Render columm: order_total.
	 */
	protected function render_order_total_column() {
		if ( $this->object->get_payment_method_title() ) {
			/* translators: %s: method */
			echo '<span class="tips" data-tip="' . esc_attr( sprintf( __( 'via %s', 'woocommerce' ), $this->object->get_payment_method_title() ) ) . '">' . wp_kses_post( $this->object->get_formatted_order_total() ) . '</span>';
		} else {
			echo wp_kses_post( $this->object->get_formatted_order_total() );
		}
	}

	/**
	 * Render columm: order_actions.
	 */
	protected function render_order_actions_column() {
		echo '<p>';

		do_action( 'woocommerce_admin_order_actions_start', $this->object );

		$actions = array();

		if ( $this->object->has_status( array( 'pending', 'on-hold' ) ) ) {
			$actions['processing'] = array(
				'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $this->object->get_id() ), 'woocommerce-mark-order-status' ),
				'name'      => __( 'Processing', 'woocommerce' ),
				'action'    => 'processing',
			);
		}

		if ( $this->object->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$actions['complete'] = array(
				'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $this->object->get_id() ), 'woocommerce-mark-order-status' ),
				'name'      => __( 'Complete', 'woocommerce' ),
				'action'    => 'complete',
			);
		}

		$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $this->object );

		foreach ( $actions as $action ) {
			printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
		}

		do_action( 'woocommerce_admin_order_actions_end', $this->object );

		echo '</p>';
	}

	/**
	 * Render columm: billing_address.
	 */
	protected function render_billing_address_column() {
		if ( $address = $this->object->get_formatted_billing_address() ) {
			echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Render columm: shipping_address.
	 */
	protected function render_shipping_address_column() {
		if ( $address = $this->object->get_formatted_shipping_address() ) {
			echo '<a target="_blank" href="' . esc_url( $this->object->get_shipping_address_map_url() ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Template for order preview.
	 *
	 * @since 3.3.0
	 */
	public function order_preview_template() {
		?>
		<script type="text/template" id="tmpl-wc-modal-view-order">
			<div class="wc-backbone-modal wc-order-preview">
				<div class="wc-backbone-modal-content">
					<section class="wc-backbone-modal-main" role="main">
						<header class="wc-backbone-modal-header">
							<h1><?php echo esc_html( sprintf( __( 'Order #%s', 'woocommerce' ), '{{ data.order_number }}' ) ); ?></h1>
							<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
							</button>
						</header>
						<article>
							{{{ data.item_html }}}

							<div class="wc-order-preview-addresses">
								<div class="wc-order-preview-address">
									<h2><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h2>
									{{{ data.formatted_billing_address }}}

									<# if ( data.data.billing.email ) { #>
										<strong><?php esc_html_e( 'Email', 'woocommerce' ); ?></strong>
										<a href="mailto:{{ data.data.billing.email }}">{{ data.data.billing.email }}</a>
									<# } #>

									<# if ( data.data.billing.phone ) { #>
										<strong><?php esc_html_e( 'Phone', 'woocommerce' ); ?></strong>
										<a href="tel:{{ data.data.billing.phone }}">{{ data.data.billing.phone }}</a>
									<# } #>

									<# if ( data.payment_via ) { #>
										<strong><?php esc_html_e( 'Payment via', 'woocommerce' ); ?></strong>
										{{ data.payment_via }}
									<# } #>
								</div>
								<# if ( data.needs_shipping ) { #>
									<div class="wc-order-preview-address">
										<h2><?php esc_html_e( 'Shipping details', 'woocommerce' ); ?></h2>
										<# if ( data.ship_to_billing ) { #>
											{{{ data.formatted_billing_address }}}
										<# } else { #>
											<a href="{{ data.shipping_address_map_url }}" target="_blank">{{{ data.formatted_shipping_address }}}</a>
										<# } #>

										<# if ( data.data.customer_note ) { #>
											<strong><?php esc_html_e( 'Note', 'woocommerce' ); ?></strong>
											{{ data.data.customer_note }}
										<# } #>

										<# if ( data.shipping_via ) { #>
											<strong><?php esc_html_e( 'Shipping method', 'woocommerce' ); ?></strong>
											{{ data.shipping_via }}
										<# } #>
									</div>
								<# } #>
							</div>
						</article>
						<footer>
							<div class="inner">
								<a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post.php?action=edit' ) ); ?>&post={{ data.data.id }}"><?php esc_html_e( 'Edit order', 'woocommerce' ); ?></a>
							</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="wc-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		// Bail out if this is not a status-changing action.
		if ( false === strpos( $action, 'mark_' ) ) {
			return $redirect_to;
		}

		$order_statuses = wc_get_order_statuses();
		$new_status     = substr( $action, 5 ); // Get the status name from action.
		$report_action  = 'marked_' . $new_status;

		// Sanity check: bail out if this is actually not a status, or is
		// not a registered status.
		if ( ! isset( $order_statuses[ 'wc-' . $new_status ] ) ) {
			return $redirect_to;
		}

		$changed = 0;
		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );
			$order->update_status( $new_status, __( 'Order status changed by bulk edit:', 'woocommerce' ), true );
			do_action( 'woocommerce_order_edit_status', $id, $new_status );
			$changed++;
		}

		$redirect_to = add_query_arg( array(
			'post_type'    => $this->list_table_type,
			$report_action => true,
			'changed'      => $changed,
			'ids'          => join( ',', $ids ),
		), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page.
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type ) {
			return;
		}

		$order_statuses = wc_get_order_statuses();

		// Check if any status changes happened.
		foreach ( $order_statuses as $slug => $name ) {
			if ( isset( $_REQUEST[ 'marked_' . str_replace( 'wc-', '', $slug ) ] ) ) {  // WPCS: input var ok.

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok.
				/* translators: %s: orders count */
				$message = sprintf( _n( '%d order status changed.', '%d order statuses changed.', $number, 'woocommerce' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
				break;
			}
		}
	}

	/**
	 * See if we should render search filters or not.
	 */
	public function restrict_manage_posts() {
		global $typenow;

		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
			$this->render_filters();
		}
	}

	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$user_string = '';
		$user_id     = '';

		if ( ! empty( $_GET['_customer_user'] ) ) { // WPCS: input var ok.
			$user_id     = absint( $_GET['_customer_user'] ); // WPCS: input var ok, sanitization ok.
			$user        = get_user_by( 'id', $user_id );
			/* translators: 1: user display name 2: user ID 3: user email */
			$user_string = sprintf(
				esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
				$user->display_name,
				absint( $user->ID ),
				$user->user_email
			);
		}
		?>
		<select class="wc-customer-search" name="_customer_user" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'woocommerce' ); ?>" data-allow_clear="true">
			<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo wp_kses_post( $user_string ); ?><option>
		</select>
		<?php
	}

	/**
	 * Handle any filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function request_query( $query_vars ) {
		global $typenow;

		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
			return $this->query_filters( $query_vars );
		}

		return $query_vars;
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		global $wp_post_statuses;

		// Filter the orders by the posted customer.
		if ( ! empty( $_GET['_customer_user'] ) ) { // WPCS: input var ok.
			// @codingStandardsIgnoreStart
			$query_vars['meta_query'] = array(
				array(
					'key'   => '_customer_user',
					'value' => (int) $_GET['_customer_user'], // WPCS: input var ok, sanitization ok.
					'compare' => '=',
				),
			);
			// @codingStandardsIgnoreEnd
		}

		// Sorting.
		if ( isset( $query_vars['orderby'] ) ) {
			if ( 'order_total' === $query_vars['orderby'] ) {
				// @codingStandardsIgnoreStart
				$query_vars = array_merge( $query_vars, array(
					'meta_key'  => '_order_total',
					'orderby'   => 'meta_value_num',
				) );
				// @codingStandardsIgnoreEnd
			}
		}

		// Status.
		if ( ! isset( $query_vars['post_status'] ) ) {
			$post_statuses = wc_get_order_statuses();

			foreach ( $post_statuses as $status => $value ) {
				if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
					unset( $post_statuses[ $status ] );
				}
			}

			$query_vars['post_status'] = array_keys( $post_statuses );
		}
		return $query_vars;
	}

	/**
	 * Change the label when searching orders.
	 *
	 * @param mixed $query Current search query.
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'shop_order' !== $typenow || ! get_query_var( 'shop_order_search' ) || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return $query;
		}

		return wc_clean( wp_unslash( $_GET['s'] ) ); // WPCS: input var ok, sanitization ok.
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars Array of query vars.
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'shop_order_search';
		return $public_query_vars;
	}

	/**
	 * Search custom fields as well as content.
	 *
	 * @param WP_Query $wp Query object.
	 */
	public function search_custom_fields( $wp ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'shop_order' !== $wp->query_vars['post_type'] || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return;
		}

		$post_ids = wc_order_search( wc_clean( wp_unslash( $_GET['s'] ) ) ); // WPCS: input var ok, sanitization ok.

		if ( ! empty( $post_ids ) ) {
			// Remove "s" - we don't want to search order name.
			unset( $wp->query_vars['s'] );

			// so we know we're doing this.
			$wp->query_vars['shop_order_search'] = true;

			// Search by found posts.
			$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
		}
	}
}

new WC_Admin_List_Table_Orders();
