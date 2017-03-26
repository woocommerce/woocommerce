<?php
/**
 * Order Notes
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
 * WC_Meta_Box_Order_Notes Class.
 */
class WC_Meta_Box_Order_Notes {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post;

		$args = array(
			'post_id'   => $post->ID,
			'orderby'   => 'comment_ID',
			'order'     => 'DESC',
			'approve'   => 'approve',
			'type'      => 'order_note'
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		echo '<ul class="order_notes">';

		if ( $notes ) {

			foreach( $notes as $note ) {

				$note_classes   = array( 'note' );
				$note_classes[] = get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? 'customer-note' : '';
				$note_classes[] = $note->comment_author === __( 'WooCommerce', 'woocommerce' ) ? 'system-note' : '';
				$note_classes   = apply_filters( 'woocommerce_order_note_class', array_filter( $note_classes ), $note );
				?>
				<li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>">
					<div class="note_content">
						<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
					</div>
					<p class="meta">
						<abbr class="exact-date" title="<?php echo $note->comment_date; ?>"><?php printf( __( 'added on %1$s at %2$s', 'woocommerce' ), date_i18n( wc_date_format(), strtotime( $note->comment_date ) ), date_i18n( wc_time_format(), strtotime( $note->comment_date ) ) ); ?></abbr>
						<?php if ( $note->comment_author !== __( 'WooCommerce', 'woocommerce' ) ) printf( ' ' . __( 'by %s', 'woocommerce' ), $note->comment_author ); ?>
						<a href="#" class="delete_note"><?php _e( 'Delete note', 'woocommerce' ); ?></a>
					</p>
				</li>
				<?php
			}

		} else {
			echo '<li>' . __( 'There are no notes yet.', 'woocommerce' ) . '</li>';
		}

		echo '</ul>';
		?>
		<div class="add_note">
			<h4><?php _e( 'Add note', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce' ) ); ?></h4>
			<p>
				<textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
			</p>
			<p>
				<select name="order_note_type" id="order_note_type">
					<option value=""><?php _e( 'Private note', 'woocommerce' ); ?></option>
					<option value="customer"><?php _e( 'Note to customer', 'woocommerce' ); ?></option>
				</select>
				<a href="#" class="add_note button"><?php _e( 'Add', 'woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}
}
