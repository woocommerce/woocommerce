<?php
/**
 * Product > Reviews
 */

namespace Automattic\WooCommerce\Internal\Admin;

use WP_Comment;
use WP_Comments_List_Table;
use WP_List_Table;

/**
 * Handles the Product Reviews page.
 */
class ReviewsListTable extends WP_List_Table {

	/**
	 * Memoization flag to determine if the current user can edit the current review.
	 *
	 * @var bool
	 */
	private $current_user_can_edit_review = false;

	/**
	 * Prepares reviews for display.
	 *
	 * @return void
	 */
	public function prepare_items() {

		$comments = get_comments(
			[
				'post_type' => 'product',
			]
		);

		update_comment_cache( $comments );

		$this->items = $comments;
	}

	/**
	 * Render a single row HTML.
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 * @return void
	 */
	public function single_row( $item ) {
		global $post, $comment;

		// Overrides the comment global for properly rendering rows.
		$comment           = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$the_comment_class = wp_get_comment_status( $comment->comment_ID );
		$the_comment_class = implode( ' ', get_comment_class( $the_comment_class, $comment->comment_ID, $comment->comment_post_ID ) );
		// Sets the post for the product in context.
		$post = get_post( $comment->comment_post_ID ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->current_user_can_edit_review = current_user_can( 'edit_comment', $comment->comment_ID );

		?>
		<tr id="comment-<?php echo esc_attr( $comment->comment_ID ); ?>" class="<?php echo esc_attr( $the_comment_class ); ?>">
			<?php $this->single_row_columns( $comment ); ?>
		</tr>
		<?php
	}

	/**
	 * Returns the columns for the table.
	 *
	 * @return array Table columns and their headings.
	 */
	public function get_columns() {
		return [
			'cb'       => '<input type="checkbox" />',
			'type'     => _x( 'Type', 'review type', 'woocommerce' ),
			'author'   => __( 'Author', 'woocommerce' ),
			'rating'   => __( 'Rating', 'woocommerce' ),
			'comment'  => _x( 'Review', 'column name', 'woocommerce' ),
			'response' => __( 'Product', 'woocommerce' ),
			'date'     => _x( 'Submitted on', 'column name', 'woocommerce' ),
		];
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the primary colum.
	 */
	protected function get_primary_column_name() {
		return 'comment';
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_cb( $item ) {
		// @TODO Implement in MWC-5335 {agibson 2022-04-12}
	}

	/**
	 * Renders the review column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_comment( $item ) {
		// @TODO Implement in MWC-5339 {agibson 2022-04-12}
	}

	/**
	 * Renders the author column.
	 *
	 * @see WP_Comments_List_Table::column_author() for consistency.
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 */
	protected function column_author( $item ) {
		global $comment_status;

		$author_url = $this->get_item_author_url();
		$author_url_display = $this->get_item_author_url_for_display( $author_url );

		?>
		<strong><?php comment_author(); ?></strong><br />
		<?php

		if ( ! empty( $author_url ) ) :

			?>
			<a title="<?php echo esc_attr( $author_url ); ?>" href="<?php echo esc_attr( $author_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $author_url_display ); ?></a>
			<br>
			<?php

		endif;

		if ( $this->current_user_can_edit_review ) :

			if ( ! empty( $item->comment_author_email ) ) :
				/** This filter is documented in wp-includes/comment-template.php */
				$email = apply_filters( 'comment_email', $item->comment_author_email, $item );

				if ( ! empty( $email ) && '@' !== $email ) {
					printf( '<a href="%1$s">%2$s</a><br />', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				}
			endif;

			$link = add_query_arg(
				[
					's'    => get_comment_author_IP( $item->comment_ID ),
					'page' => Reviews::MENU_SLUG,
					'mode' => 'detail',
				],
				'admin.php'
			);

			if ( 'spam' === $comment_status ) :
				$link = add_query_arg( [ 'comment_status' => 'spam' ], $link );
			endif;

			?>
			<a href="<?php echo esc_url( $link ); ?>"><?php comment_author_IP( $item->comment_ID ); ?></a>
			<?php

		endif;
	}

	/**
	 * Gets the item author URL.
	 *
	 * @return string
	 */
	private function get_item_author_url() {

		$author_url = get_comment_author_url();
		$protocols = [ 'https://', 'http://' ];

		if ( in_array( $author_url, $protocols ) ) {
			$author_url = '';
		}

		return $author_url;
	}

	/**
	 * Gets the item author URL for display.
	 *
	 * @param string $author_url The review or reply author URL (raw).
	 * @return string
	 */
	private function get_item_author_url_for_display( $author_url ) {

		$author_url_display = untrailingslashit( preg_replace( '|^http(s)?://(www\.)?|i', '', $author_url ) );

		if ( strlen( $author_url_display ) > 50 ) {
			$author_url_display = wp_html_excerpt( $author_url_display, 49, '&hellip;' );
		}

		return $author_url_display;
	}

	/**
	 * Renders the "submitted on" column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_date( $item ) {
		// @TODO Implement in MWC-5338 {agibson 2022-04-12}
	}

	/**
	 * Renders the product column.
	 *
	 * @see WP_Comments_List_Table::column_response() for consistency.
	 *
	 * @return void
	 */
	protected function column_response() {
		$product_post = get_post();

		if ( ! $product_post ) {
			return;
		}

		?>
		<div class="response-links">
			<?php

			if ( current_user_can( 'edit_product', $product_post->ID ) ) :
				$post_link  = "<a href='" . get_edit_post_link( $product_post->ID ) . "' class='comments-edit-item-link'>";
				$post_link .= esc_html( get_the_title( $product_post->ID ) ) . '</a>';
			else :
				$post_link = esc_html( get_the_title( $product_post->ID ) );
			endif;

			echo $post_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$post_type_object = get_post_type_object( $product_post->post_type );

			?>
			<a href="<?php echo esc_url( get_permalink( $product_post->ID ) ); ?>" class="comments-view-item-link">
				<?php echo esc_html( $post_type_object->labels->view_item ); ?>
			</a>
			<span class="post-com-count-wrapper post-com-count-<?php echo esc_attr( $product_post->ID ); ?>">
				<?php $this->comments_bubble( $product_post->ID, get_pending_comments_num( $product_post->ID ) ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * Renders the type column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_type( $item ) {
		echo esc_html(
			'review' === $item->comment_type ?
			'&#9734;&nbsp;' . __( 'Review', 'woocommerce' ) :
			__( 'Reply', 'woocommerce' )
		);
	}

	/**
	 * Renders the rating column.
	 *
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_rating( $item ) {
		$rating = get_comment_meta( $item->comment_ID, 'rating', true );

		if ( ! empty( $rating ) && is_numeric( $rating ) ) {
			$rating = (int) $rating;
			$accessibility_label = sprintf(
				/* translators: 1: number representing a rating */
				__( '%1$s out of 5', 'woocommerce' ),
				$rating
			);
			$stars = str_repeat( '&#9733;', $rating );
			$stars .= str_repeat( '&#9734;', 5 - $rating );
			?>
			<span aria-label="<?php echo esc_attr( $accessibility_label ); ?>"><?php echo esc_html( $stars ); ?></span>
			<?php
		}
	}

	/**
	 * Renders any custom columns.
	 *
	 * @param object|array $item        Review or reply being rendered.
	 * @param string       $column_name Name of the column being rendered.
	 */
	protected function column_default( $item, $column_name ) {
		// @TODO Implement in MWC-5362 {agibson 2022-04-12}
	}

}
