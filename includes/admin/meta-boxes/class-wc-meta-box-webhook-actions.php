<?php
/**
 * Webhook Actions
 *
 * Display the webhook actions meta box.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Meta Boxes
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Webhook_Actions Class
 */
class WC_Meta_Box_Webhook_Actions {

	/**
	 * Get date i18n.
	 *
	 * @param  string $date
	 *
	 * @return string
	 */
	protected static function get_date_i18n( $date ) {
		return date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $date ) );
	}

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		?>
		<style>
			#poststuff #woocommerce-webhook-actions .inside { padding: 0; margin: 0; }
		</style>

			<?php if ( '0000-00-00 00:00:00' != $post->post_modified_gmt ) : ?>
				<ul class="order_actions submitbox">
					<?php if ( '0000-00-00 00:00:00' == $post->post_date_gmt ) : ?>
						<li class="wide">
							<strong><?php _e( 'Created at' ); ?>:</strong> <?php echo self::get_date_i18n( $post->post_modified_gmt ); ?>
						</li>
					<?php else : ?>
						<li class="wide">
							<strong><?php _e( 'Created at' ); ?>:</strong> <?php echo self::get_date_i18n( $post->post_date_gmt ); ?>
						</li>
						<li class="wide">
							<strong><?php _e( 'Updated at' ); ?>:</strong> <?php echo self::get_date_i18n( $post->post_modified_gmt ); ?>
						</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>

			<div class="submitbox" id="submitpost">
				<div id="major-publishing-actions">
					<?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
						<div id="delete-action">
							<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo ( ! EMPTY_TRASH_DAYS ) ? __( 'Delete Permanently', 'woocommerce' ) : __( 'Move to Trash', 'woocommerce' ); ?></a></div>
					<?php endif; ?>

					<div id="publishing-action">
						<span class="spinner"></span>
						<input type="submit" class="button button-primary button-large" name="save" id="publish" accesskey="p" value="<?php _e( 'Save Webhook', 'woocommerce' ); ?>" data-tip="<?php _e( 'Save/update the Webhook', 'woocommerce' ); ?>" />
					</div>
					<div class="clear"></div>
				</div>
			</div>
		<?php
	}
}
