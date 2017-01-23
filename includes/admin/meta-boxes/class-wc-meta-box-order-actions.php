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
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Order_Actions Class.
 */
class WC_Meta_Box_Order_Actions {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $theorder;

		// This is used by some callbacks attached to hooks such as woocommerce_order_actions which rely on the global to determine if actions should be displayed for certain orders.
		if ( ! is_object( $theorder ) ) {
			$theorder = wc_get_order( $post->ID );
		}

		$order_type_object = get_post_type_object( $post->post_type );
		?>
		<ul class="order_actions submitbox">

			<?php do_action( 'woocommerce_order_actions_start', $post->ID ); ?>

			<li class="wide" id="actions">
				<select name="wc_order_action">
					<option value=""><?php _e( 'Actions', 'woocommerce' ); ?></option>
					<optgroup label="<?php esc_attr_e( 'Resend order emails', 'woocommerce' ); ?>">
						<?php
						$mailer           = WC()->mailer();
						$available_emails = apply_filters( 'woocommerce_resend_order_emails_available', array( 'new_order', 'cancelled_order', 'customer_processing_order', 'customer_completed_order', 'customer_invoice', 'customer_refunded_order' ) );
						$mails            = $mailer->get_emails();

						if ( ! empty( $mails ) ) {
							foreach ( $mails as $mail ) {
								if ( in_array( $mail->id, $available_emails ) && 'no' !== $mail->enabled ) {
									echo '<option value="send_email_'. esc_attr( $mail->id ) .'">' . esc_html( $mail->title ) . '</option>';
								}
							}
						}
						?>
					</optgroup>

					<option value="regenerate_download_permissions"><?php _e( 'Regenerate download permissions', 'woocommerce' ); ?></option>

					<?php foreach( apply_filters( 'woocommerce_order_actions', array() ) as $action => $title ) { ?>
						<option value="<?php echo $action; ?>"><?php echo $title; ?></option>
					<?php } ?>
				</select>

				<button class="button wc-reload" title="<?php esc_attr_e( 'Apply', 'woocommerce' ); ?>"><span><?php _e( 'Apply', 'woocommerce' ); ?></span></button>
			</li>

			<li class="wide">
				<div id="delete-action"><?php

					if ( current_user_can( 'delete_post', $post->ID ) ) {

						if ( ! EMPTY_TRASH_DAYS ) {
							$delete_text = __( 'Delete Permanently', 'woocommerce' );
						} else {
							$delete_text = __( 'Move to Trash', 'woocommerce' );
						}
						?><a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo $delete_text; ?></a><?php
					}
				?></div>

				<input type="submit" class="button save_order button-primary tips" name="save" value="<?php printf( __( 'Save %s', 'woocommerce' ), $order_type_object->labels->singular_name ); ?>" data-tip="<?php printf( __( 'Save/update the %s', 'woocommerce' ), $order_type_object->labels->singular_name ); ?>" />
			</li>

			<?php do_action( 'woocommerce_order_actions_end', $post->ID ); ?>

		</ul>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		// Order data saved, now get it so we can manipulate status
		$order = wc_get_order( $post_id );

		// Handle button actions
		if ( ! empty( $_POST['wc_order_action'] ) ) {

			$action = wc_clean( $_POST['wc_order_action'] );

			if ( strstr( $action, 'send_email_' ) ) {

				// Switch back to the site locale.
				if ( function_exists( 'switch_to_locale' ) ) {
					switch_to_locale( get_locale() );
				}

				do_action( 'woocommerce_before_resend_order_emails', $order );

				// Ensure gateways are loaded in case they need to insert data into the emails.
				WC()->payment_gateways();
				WC()->shipping();

				// Load mailer.
				$mailer = WC()->mailer();
				$email_to_send = str_replace( 'send_email_', '', $action );
				$mails = $mailer->get_emails();

				if ( ! empty( $mails ) ) {
					foreach ( $mails as $mail ) {
						if ( $mail->id == $email_to_send ) {
							$mail->trigger( $order->id );
							$order->add_order_note( sprintf( __( '%s email notification manually sent.', 'woocommerce' ), $mail->title ), false, true );
						}
					}
				}

				do_action( 'woocommerce_after_resend_order_email', $order, $email_to_send );

				// Restore user locale.
				if ( function_exists( 'restore_current_locale' ) ) {
					restore_current_locale();
				}

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'regenerate_download_permissions' === $action ) {

				delete_post_meta( $post_id, '_download_permissions_granted' );
				$wpdb->delete( 
					$wpdb->prefix . 'woocommerce_downloadable_product_permissions',
					array( 'order_id' => $post_id ),
					array( '%d' )
				);
				wc_downloadable_product_permissions( $post_id );

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
	 * @param string $location
	 *
	 * @since  2.3.0
	 *
	 * @static
	 *
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}

}
