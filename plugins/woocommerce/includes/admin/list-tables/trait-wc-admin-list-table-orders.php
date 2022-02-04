<?php

trait WC_Admin_Table_Orders_Trait {

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
		$show_columns['order_date']       = __( 'Date', 'woocommerce' );
		$show_columns['order_status']     = __( 'Status', 'woocommerce' );
		$show_columns['billing_address']  = __( 'Billing', 'woocommerce' );
		$show_columns['shipping_address'] = __( 'Ship to', 'woocommerce' );
		$show_columns['order_total']      = __( 'Total', 'woocommerce' );
		$show_columns['wc_actions']       = __( 'Actions', 'woocommerce' );

		wp_enqueue_script( 'wc-orders' );

		return $show_columns;
	}

	/**
	 * Render individual columns.
	 *
	 * @param string $column Column ID to render.
	 * @param int    $post_id Post ID being shown.
	 */
	public function render_columns( $column, $post_id ) {
		$this->prepare_row_data( $post_id );

		if ( ! $this->object ) {
			return;
		}

		if ( is_callable( array( $this, 'render_' . $column . '_column' ) ) ) {
			$this->{"render_{$column}_column"}();
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

		/**
		 * Filter buyer name in list table orders.
		 *
		 * @since 3.7.0
		 * @param string   $buyer Buyer name.
		 * @param WC_Order $order Order data.
		 */
		$buyer = apply_filters( 'woocommerce_admin_order_buyer_name', $buyer, $this->object );

		if ( $this->object->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $this->object->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
		} else {
			echo '<a href="#" class="order-preview" data-order-id="' . absint( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'woocommerce' ) ) . '">' . esc_html( __( 'Preview', 'woocommerce' ) ) . '</a>';
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $this->object->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
		}
	}

	/**
	 * Render columm: order_status.
	 */
	protected function render_order_status_column() {
		$tooltip                 = '';
		$comment_count           = get_comment_count( $this->object->get_id() );
		$approved_comments_count = absint( $comment_count['approved'] );

		if ( $approved_comments_count ) {
			$latest_notes = wc_get_order_notes(
				array(
					'order_id' => $this->object->get_id(),
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
			printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), wp_kses_post( $tooltip ), esc_html( wc_get_order_status_name( $this->object->get_status() ) ) );
		} else {
			printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), esc_html( wc_get_order_status_name( $this->object->get_status() ) ) );
		}
	}

	/**
	 * Render columm: order_date.
	 */
	protected function render_order_date_column() {
		$order_timestamp = $this->object->get_date_created() ? $this->object->get_date_created()->getTimestamp() : '';

		if ( ! $order_timestamp ) {
			echo '&ndash;';
			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $order_timestamp > strtotime( '-1 day', time() ) && $order_timestamp <= time() ) {
			$show_date = sprintf(
			/* translators: %s: human-readable time difference */
				_x( '%s ago', '%s = human-readable time difference', 'woocommerce' ),
				human_time_diff( $this->object->get_date_created()->getTimestamp(), time() )
			);
		} else {
			$show_date = $this->object->get_date_created()->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', __( 'M j, Y', 'woocommerce' ) ) );
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
	 * Render columm: wc_actions.
	 */
	protected function render_wc_actions_column() {
		echo '<p>';

		do_action( 'woocommerce_admin_order_actions_start', $this->object );

		$actions = array();

		if ( $this->object->has_status( array( 'pending', 'on-hold' ) ) ) {
			$actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=processing&order_id=' . $this->object->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Processing', 'woocommerce' ),
				'action' => 'processing',
			);
		}

		if ( $this->object->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=completed&order_id=' . $this->object->get_id() ), 'woocommerce-mark-order-status' ),
				'name'   => __( 'Complete', 'woocommerce' ),
				'action' => 'complete',
			);
		}

		$actions = apply_filters( 'woocommerce_admin_order_actions', $actions, $this->object );

		echo wc_render_action_buttons( $actions ); // WPCS: XSS ok.

		do_action( 'woocommerce_admin_order_actions_end', $this->object );

		echo '</p>';
	}

	/**
	 * Render columm: billing_address.
	 */
	protected function render_billing_address_column() {
		$address = $this->object->get_formatted_billing_address();

		if ( $address ) {
			echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );

			if ( $this->object->get_payment_method() ) {
				/* translators: %s: payment method */
				echo '<span class="description">' . sprintf( __( 'via %s', 'woocommerce' ), esc_html( $this->object->get_payment_method_title() ) ) . '</span>'; // WPCS: XSS ok.
			}
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Render columm: shipping_address.
	 */
	protected function render_shipping_address_column() {
		$address = $this->object->get_formatted_shipping_address();

		if ( $address ) {
			echo '<a target="_blank" href="' . esc_url( $this->object->get_shipping_address_map_url() ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
			if ( $this->object->get_shipping_method() ) {
				/* translators: %s: shipping method */
				echo '<span class="description">' . sprintf( __( 'via %s', 'woocommerce' ), esc_html( $this->object->get_shipping_method() ) ) . '</span>'; // WPCS: XSS ok.
			}
		} else {
			echo '&ndash;';
		}
	}

}
