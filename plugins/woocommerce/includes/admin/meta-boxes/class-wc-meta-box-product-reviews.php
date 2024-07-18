<?php
/**
 * Product Reviews
 *
 * Functions for displaying product reviews data meta box.
 *
 * @package WooCommerce\Admin\Meta Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Meta_Box_Product_Reviews
 */
class WC_Meta_Box_Product_Reviews {

	/**
	 * Output the metabox.
	 *
	 * @param object $comment Comment being shown.
	 */
	public static function output( $comment ) {
		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		$current = get_comment_meta( $comment->comment_ID, 'rating', true );
		?>
		<select name="rating" id="rating">
			<?php
			for ( $rating = 1; $rating <= 5; $rating ++ ) {
				printf( '<option value="%1$s"%2$s>%1$s</option>', $rating, selected( $current, $rating, false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</select>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param mixed $data Data to save.
	 * @return mixed
	 */
	public static function save( $data ) {
		// Not allowed, return regular value without updating meta.
		if ( ! isset( $_POST['woocommerce_meta_nonce'], $_POST['rating'] ) || ! wp_verify_nonce( wp_unslash( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return $data;
		}

		if ( $_POST['rating'] > 5 || $_POST['rating'] < 0 ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return $data;
		}

		$comment_id = $data['comment_ID'];

		update_comment_meta( $comment_id, 'rating', intval( wp_unslash( $_POST['rating'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Return regular value after updating.
		return $data;
	}
}
