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

		$args = array( 'order_id' => $order_id );

		if ( 0 !== $order_id ) {
			$notes = wc_get_order_notes( $args );
		} else {
			$notes = array();
		}

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
		<?php
		include __DIR__ . '/views/html-order-notes.php';
	}
}
