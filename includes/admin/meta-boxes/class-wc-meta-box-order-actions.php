<?php
/**
 * Order Actions
 *
 * Functions for displaying the order actions meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Meta_Box_Order_Actions Class.
 */
class WC_Meta_Box_Order_Actions {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $theorder;

		// This is used by some callbacks attached to hooks such as woocommerce_order_actions which rely on the global to determine if actions should be displayed for certain orders.
		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		$order_actions = apply_filters(
			'woocommerce_order_actions',
			array(
				'send_order_details'              => __( 'Email invoice / order details to customer', 'woocommerce' ),
				'send_order_details_admin'        => __( 'Resend new order notification', 'woocommerce' ),
				'regenerate_download_permissions' => __( 'Regenerate download permissions', 'woocommerce' ),
			)
		);
		?>
		<ul class="order_actions submitbox">

			<?php do_action( 'woocommerce_order_actions_start', $post->ID ); ?>

			<li class="wide" id="actions">
				<select name="wc_order_action">
					<option value=""><?php esc_html_e( 'Choose an action...', 'woocommerce' ); ?></option>
					<?php foreach ( $order_actions as $action => $title ) { ?>
						<option value="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( $title ); ?></option>
					<?php } ?>
				</select>
				<button class="button wc-reload"><span><?php esc_html_e( 'Apply', 'woocommerce' ); ?></span></button>
			</li>

			<li class="wide">
				<div id="delete-action">
					<?php
					if ( current_user_can( 'delete_post', $post->ID ) ) {

						if ( ! EMPTY_TRASH_DAYS ) {
							$delete_text = __( 'Delete permanently', 'woocommerce' );
						} else {
							$delete_text = __( 'Move to Trash', 'woocommerce' );
						}
						?>
						<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a>
						<?php
					}
					?>
				</div>

				<button type="submit" class="button save_order button-primary" name="save" value="<?php echo 'auto-draft' === $post->post_status ? esc_attr__( 'Create', 'woocommerce' ) : esc_attr__( 'Update', 'woocommerce' ); ?>"><?php echo 'auto-draft' === $post->post_status ? esc_html__( 'Create', 'woocommerce' ) : esc_html__( 'Update', 'woocommerce' ); ?></button>
			</li>

			<?php do_action( 'woocommerce_order_actions_end', $post->ID ); ?>

		</ul>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post Object.
	 */
	public static function save( $post_id, $post ) {
		// Order data saved, now get it so we can manipulate status.
		$order = wc_get_order( $post_id );

		// Handle button actions.
		if ( ! empty( $_POST['wc_order_action'] ) ) { // @codingStandardsIgnoreLine

			$action = wc_clean( wp_unslash( $_POST['wc_order_action'] ) ); // @codingStandardsIgnoreLine

			if ( 'send_order_details' === $action ) {
				do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

				// Send the customer invoice email.
				WC()->payment_gateways();
				WC()->shipping();
				WC()->mailer()->customer_invoice( $order );

				// Note the event.
				$order->add_order_note( __( 'Order details manually sent to customer.', 'woocommerce' ), false, true );

				do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'send_order_details_admin' === $action ) {

				do_action( 'woocommerce_before_resend_order_emails', $order, 'new_order' );

				WC()->payment_gateways();
				WC()->shipping();
				WC()->mailer()->emails['WC_Email_New_Order']->trigger( $order->get_id(), $order );

				do_action( 'woocommerce_after_resend_order_email', $order, 'new_order' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'regenerate_download_permissions' === $action ) {

				$data_store = WC_Data_Store::load( 'customer-download' );
				$data_store->delete_by_order_id( $post_id );
				wc_downloadable_product_permissions( $post_id, true );

			} else {

				if ( ! did_action( 'woocommerce_order_action_' . sanitize_title( $action ) ) ) {
					do_action( 'woocommerce_order_action_' . sanitize_title( $action ), $order );
				}
			}
		}
	}

	/**
	 * Set the correct message ID.
	 *
	 * @param string $location Location.
	 * @since  2.3.0
	 * @static
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}
}
