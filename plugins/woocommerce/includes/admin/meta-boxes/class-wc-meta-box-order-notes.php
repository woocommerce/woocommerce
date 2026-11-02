<?php
/**
 * Order Notes
 *
 * @package WooCommerce\Admin\Meta Boxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Meta_Box_Order_Notes Class.
 */
class WC_Meta_Box_Order_Notes {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post|WC_Order $post Post or order object.
	 */
	public static function output( $post ) {
		if ( $post instanceof WC_Order ) {
			$order_id = $post->get_id();
		} else {
			$order_id = $post->ID;
		}

		$pagination = self::get_page( (int) $order_id );
		$notes      = $pagination['notes'];

		$private_button_label  = __( 'Add private note', 'woocommerce' );
		$customer_button_label = __( 'Send note to customer →', 'woocommerce' );
		$email_settings_url    = admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_email_customer_note' );
		?>
		<div class="add_note">
			<p>
				<label for="add_order_note"><?php esc_html_e( 'Add note', 'woocommerce' ); ?></label>
				<textarea name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
			</p>
			<div class="order_note_visibility">
				<label for="order_note_type"><?php esc_html_e( 'Visibility', 'woocommerce' ); ?></label>
				<select name="order_note_type" id="order_note_type">
					<option value="" data-button-label="<?php echo esc_attr( $private_button_label ); ?>"><?php esc_html_e( 'Private note', 'woocommerce' ); ?></option>
					<option value="customer" data-button-label="<?php echo esc_attr( $customer_button_label ); ?>"><?php esc_html_e( 'Public note to customer', 'woocommerce' ); ?></option>
				</select>
				<p class="add_note_email_settings" hidden>
					<?php
					$email_template_link = sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s <span aria-hidden="true">&#8599;</span><span class="screen-reader-text">%3$s</span></a>',
						esc_url( $email_settings_url ),
						esc_html__( 'email template', 'woocommerce' ),
						esc_html__( '(opens in a new tab)', 'woocommerce' )
					);
					printf(
						/* translators: %s: link to the customer note email template settings */
						esc_html__( 'Preview or edit %s', 'woocommerce' ),
						$email_template_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above.
					);
					?>
				</p>
			</div>
			<p class="add_note_actions">
				<button type="button" class="add_note button"><?php echo esc_html( $private_button_label ); ?></button>
			</p>
		</div>
		<div class="order-notes-list">
			<?php
			include __DIR__ . '/views/html-order-notes.php';
			include __DIR__ . '/views/html-order-notes-pagination.php';
			?>
		</div>
		<p class="order-notes-error" role="alert" hidden>
			<?php esc_html_e( 'Could not load order notes.', 'woocommerce' ); ?>
			<button type="button" class="button-link order-notes-retry"><?php esc_html_e( 'Try again', 'woocommerce' ); ?></button>
		</p>
		<?php
	}

	/**
	 * Get a page of notes and its pagination details.
	 *
	 * @since 11.3.0
	 * @param int $order_id Order ID.
	 * @param int $page     Requested page.
	 * @return array
	 */
	public static function get_page( int $order_id, int $page = 1 ): array {
		$total = 0;
		if ( $order_id > 0 ) {
			remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10 );
			$total = (int) get_comments(
				array(
					'post_id' => $order_id,
					'type'    => 'order_note',
					'status'  => 'approve',
					'count'   => true,
				)
			);

			add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );
		}

		$total_pages = max( 1, (int) ceil( $total / 10 ) );
		$page        = max( 1, min( $page, $total_pages ) );
		$notes       = $total > 0 ? wc_get_order_notes(
			array(
				'order_id' => $order_id,
				'limit'    => 10,
				'page'     => $page,
			)
		) : array();

		return compact( 'notes', 'total', 'page', 'total_pages' );
	}

	/**
	 * Render a page of notes for an authenticated AJAX request.
	 *
	 * @since 11.3.0
	 */
	public static function get_order_notes(): void {
		check_ajax_referer( 'get-order-notes', 'security' );

		$order_id = isset( $_POST['order_id'] ) && is_scalar( $_POST['order_id'] ) ? absint( wp_unslash( (string) $_POST['order_id'] ) ) : 0;
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce maps this per-order meta capability.
		if ( ! $order_id || ! current_user_can( 'edit_shop_order', $order_id ) ) {
			wp_send_json_error( null, 403 );
		}

		if ( ! wc_get_order( $order_id ) ) {
			wp_send_json_error( null, 404 );
		}

		$page       = isset( $_POST['page'] ) && is_scalar( $_POST['page'] ) ? absint( wp_unslash( (string) $_POST['page'] ) ) : 1;
		$pagination = self::get_page( $order_id, $page );
		$notes      = $pagination['notes'];

		ob_start();
		include __DIR__ . '/views/html-order-notes.php';
		include __DIR__ . '/views/html-order-notes-pagination.php';
		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}
}
