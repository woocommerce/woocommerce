<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use WC_Order;
use WC_Order_Data_Store_Interface;
use WP_List_Table;

/**
 * Admin list table for orders as managed by the OrdersTableDataStore.
 */
class ListTable extends WP_List_Table {
	/**
	 * Sets up the admin list table for orders (specifically, for orders managed by the OrdersTableDataStore).
	 *
	 * @see WC_Admin_List_Table_Orders for the corresponding class used in relation to the traditional WP Post store.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'order',
				'plural'   => 'orders',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Performs setup work required before rendering the table.
	 *
	 * @return void
	 */
	public function setup(): void {
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'get_columns' ) );
		add_filter( 'set_screen_option_edit_orders_per_page', array( $this, 'set_items_per_page' ), 10, 3 );
		$this->items_per_page();
		set_screen_options();
	}

	/**
	 * Sets up an items-per-page control.
	 */
	private function items_per_page(): void {
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'edit_orders_per_page',
			)
		);
	}

	/**
	 * Saves the items-per-page setting.
	 *
	 * @param mixed  $default The default value.
	 * @param string $option  The option being configured.
	 * @param int    $value   The submitted option value.
	 *
	 * @return mixed
	 */
	public function set_items_per_page( $default, string $option, int $value ) {
		return 'edit_orders_per_page' === $option ? absint( $value ) : $default;
	}

	/**
	 * Render the table.
	 *
	 * @return void
	 */
	public function display() {
		$title   = esc_html__( 'Orders', 'woocommerce' );
		$add_new = esc_html__( 'Add New', 'woocommerce' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "
			<div class='wrap'>
				<h1 class='wp-heading-inline'>{$title}</h1>
				<a href='/to-implement' class='page-title-action'>{$add_new}</a>
		";

		$this->views();
		parent::display();

		echo '</div>';
	}
	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$status = sanitize_text_field( wp_unslash( $_REQUEST['status'] ?? '' ) );
		$search = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );

		$args = array(
			'limit' => $this->get_items_per_page( 'edit_orders_per_page' ),
			'page'  => $this->get_pagenum(),
		);

		// @todo Confirm this is the best way to query.
		//       An alternative is using `$this->data_store->get_orders()` however in the case of the order CPT store
		//       that method is considered deprecated (not sure if that carries over to the new COT store).
		$this->items = wc_get_orders( $args );
	}

	/**
	 * Get list columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'order'   => esc_html__( 'Order', 'woocommerce' ),
			'date'    => esc_html__( 'Date', 'woocommerce' ),
			'status'  => esc_html__( 'Status', 'woocommerce' ),
			'billing' => esc_html__( 'Billing', 'woocommerce' ),
			'ship_to' => esc_html__( 'Ship to', 'woocommerce' ),
			'total'   => esc_html__( 'Total', 'woocommerce' ),
			'actions' => esc_html__( 'Actions', 'woocommerce' ),
		);
	}

	/**
	 * Checklist column, used for selecting items for processing by a bulk action.
	 *
	 * @param WC_Order $item The order object for the current row.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', esc_attr( $this->_args['singular'] ), esc_attr( $item->get_id() ) );
	}

	/**
	 * Renders the order number, customer name and provides a preview link.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_order( WC_Order $order ): void {
		$buyer = '';

		if ( $order->get_billing_first_name() || $order->get_billing_last_name() ) {
			/* translators: 1: first name 2: last name */
			$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $order->get_billing_first_name(), $order->get_billing_last_name() ) );
		} elseif ( $order->get_billing_company() ) {
			$buyer = trim( $order->get_billing_company() );
		} elseif ( $order->get_customer_id() ) {
			$user  = get_user_by( 'id', $order->get_customer_id() );
			$buyer = ucwords( $user->display_name );
		}

		/**
		 * Filter buyer name in list table orders.
		 *
		 * @since 3.7.0
		 *
		 * @param string   $buyer Buyer name.
		 * @param WC_Order $order Order data.
		 */
		$buyer = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $order );

		if ( $order->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
		} else {
			echo '<a href="#" class="order-preview" data-order-id="' . absint( $order->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'woocommerce' ) ) . '">' . esc_html( __( 'Preview', 'woocommerce' ) ) . '</a>';
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
		}
	}

	/**
	 * Renders the order date.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_date( WC_Order $order ): void {
		$order_timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : '';

		if ( ! $order_timestamp ) {
			echo '&ndash;';
			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $order_timestamp > strtotime( '-1 day', time() ) && $order_timestamp <= time() ) {
			$show_date = sprintf(
			/* translators: %s: human-readable time difference */
				_x( '%s ago', '%s = human-readable time difference', 'woocommerce' ),
				human_time_diff( $order->get_date_created()->getTimestamp(), time() )
			);
		} else {
			$show_date = $order->get_date_created()->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'woocommerce' ) ) );
		}
		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $order->get_date_created()->date( 'c' ) ),
			esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Renders the order status.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_status( WC_Order $order ): void {
		$tooltip                 = '';
		$comment_count           = get_comment_count( $order->get_id() );
		$approved_comments_count = absint( $comment_count['approved'] );

		if ( $approved_comments_count ) {
			$latest_notes = wc_get_order_notes(
				array(
					'order_id' => $order->get_id(),
					'limit'    => 1,
					'orderby'  => 'date_created_gmt',
				)
			);

			$latest_note = current( $latest_notes );

			if ( isset( $latest_note->content ) && 1 === $approved_comments_count ) {
				$tooltip = wc_sanitize_tooltip( $latest_note->content );
			} elseif ( isset( $latest_note->content ) ) {
				/* translators: %d: notes count */
				$tooltip = wc_sanitize_tooltip( $latest_note->content . '<br/><small style="display:block">' . sprintf( _n( 'Plus %d other note', 'Plus %d other notes', ( $approved_comments_count - 1 ), 'woocommerce' ), $approved_comments_count - 1 ) . '</small>' );
			} else {
				/* translators: %d: notes count */
				$tooltip = wc_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $approved_comments_count, 'woocommerce' ), $approved_comments_count ) );
			}
		}

		if ( $tooltip ) {
			printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $order->get_status() ) ), wp_kses_post( $tooltip ), esc_html( wc_get_order_status_name( $order->get_status() ) ) );
		} else {
			printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $order->get_status() ) ), esc_html( wc_get_order_status_name( $order->get_status() ) ) );
		}
	}

	/**
	 * Renders order billing information.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_billing( WC_Order $order ): void {
		$address = $order->get_formatted_billing_address();

		if ( $address ) {
			echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );

			if ( $order->get_payment_method() ) {
				/* translators: %s: payment method */
				echo '<span class="description">' . sprintf( esc_html__( 'via %s', 'woocommerce' ), esc_html( $order->get_payment_method_title() ) ) . '</span>';
			}
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Renders order shipping information.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_ship_to( WC_Order $order ): void {
		$address = $order->get_formatted_shipping_address();

		if ( $address ) {
			echo '<a target="_blank" href="' . esc_url( $order->get_shipping_address_map_url() ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
			if ( $order->get_shipping_method() ) {
				/* translators: %s: shipping method */
				echo '<span class="description">' . sprintf( esc_html__( 'via %s', 'woocommerce' ), esc_html( $order->get_shipping_method() ) ) . '</span>';
			}
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Renders the order total.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_total( WC_Order $order ): void {
		if ( $order->get_payment_method_title() ) {
			/* translators: %s: method */
			echo '<span class="tips" data-tip="' . esc_attr( sprintf( __( 'via %s', 'woocommerce' ), $order->get_payment_method_title() ) ) . '">' . wp_kses_post( $order->get_formatted_order_total() ) . '</span>';
		} else {
			echo wp_kses_post( $order->get_formatted_order_total() );
		}
	}

	/**
	 * Renders order actions.
	 *
	 * @param WC_Order $order The order object for the current row.
	 *
	 * @return void
	 */
	public function column_actions( WC_Order $order ): void {
		echo '<p>';

		do_action( 'woocommerce_admin_order_actions_start', $order );

		$actions = array();

		if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			$actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Processing', 'woocommerce' ),
				'action' => 'processing',
			);
		}

		if ( $order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Complete', 'woocommerce' ),
				'action' => 'complete',
			);
		}

		$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $order );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wc_render_action_buttons( $actions );

		do_action( 'woocommerce_admin_order_actions_end', $order );

		echo '</p>';
	}
}
