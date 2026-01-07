<?php
/**
 * The template for displaying last product review widget on the WordPress dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/dashboard-widget-last-reviews.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

/** @var $entries stdClass{ product_id:int, comment_id:string }[] */
if ( $entries ) {
	echo '<ul>';
	foreach ($entries as $comment ) {
		$product = wc_get_product( $comment->product_id );
		$comment = get_comment( $comment->comment_id );
		if ( $product && $comment && current_user_can( 'read_product', $product->get_id() ) ) {
			echo '<li>';

			echo get_avatar( $comment->comment_author_email, '32' );

			$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

			/* translators: %s: rating */
			echo '<div class="star-rating"><span style="width:' . esc_attr( $rating * 20 ) . '%">' . sprintf( esc_html__( '%s out of 5', 'woocommerce' ), esc_html( $rating ) ) . '</span></div>';

			/**
			 * Filters product title to display in the last reviews.
			 *
			 * @since 2.1.0
			 *
			 * @param string      $product_title The product title.
			 * @param \WP_Comment $comment       The comment.
			 */
			$product_title = apply_filters( 'woocommerce_admin_dashboard_recent_reviews', $product->get_title(), $comment );

			/* translators: %s: review author */
			echo '<h4 class="meta"><a href="' . esc_url( get_permalink( $product->get_id() ) ) . '#comment-' . esc_attr( absint( $comment->comment_ID ) ) . '">' . esc_html( $product_title ) . '</a> ' . sprintf( esc_html__( 'reviewed by %s', 'woocommerce' ), esc_html( $comment->comment_author ) ) . '</h4>';
			echo '<blockquote>' . wp_kses_data( $comment->comment_content ) . '</blockquote></li>';
		}
	}
	echo '</ul>';
} else {
	echo '<p>' . esc_html__( 'There are no product reviews yet.', 'woocommerce' ) . '</p>';
}

?>
