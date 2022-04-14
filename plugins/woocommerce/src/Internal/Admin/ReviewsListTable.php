<?php
/**
 * Product > Reviews
 */

namespace Automattic\WooCommerce\Internal\Admin;

use WP_Comment;
use WP_Comments_List_Table;
use WP_List_Table;
use WP_Post;

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
	 * Memoization flag to determine if the current user can moderate reviews.
	 *
	 * @var bool
	 */
	private $current_user_can_moderate_reviews;


	/**
	 * Current rating of reviews to display.
	 *
	 * @var string
	 */
	private $current_rating = '0';

	/**
	 * Constructor.
	 *
	 * @param array|string $args Array or string of arguments.
	 */
	public function __construct( $args = [] ) {
		parent::__construct( $args );

		$this->current_user_can_moderate_reviews = current_user_can( 'moderate_comments' );
	}

	/**
	 * Prepares reviews for display.
	 *
	 * @return void
	 */
	public function prepare_items() {

		$this->current_rating = (string) isset( $_REQUEST['rating'] ) ? wc_clean( wp_unslash( $_REQUEST['rating'] ) ) : 0;

		$this->set_review_status();
		$this->set_review_type();

		$args = [
			'post_type' => 'product',
		];

		// Include the order & orderby arguments.
		$args = wp_parse_args( $this->get_sort_arguments(), $args );

		// Handle the review item types filter.
		$args = wp_parse_args( $this->get_filter_type_arguments(), $args );

		$comments = get_comments( $args );

		update_comment_cache( $comments );

		$this->items = $comments;
	}

	/**
	 * Sets the `$comment_status` global based on the current request.
	 *
	 * @global string $comment_status
	 *
	 * @return void
	 */
	protected function set_review_status() {
		global $comment_status;

		$comment_status = sanitize_text_field( wp_unslash( $_REQUEST['comment_status'] ?? 'all' ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! in_array( $comment_status, [ 'all', 'moderated', 'approved', 'spam', 'trash' ], true ) ) {
			$comment_status = 'all'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Sets the `$comment_type` global based on the current request.
	 *
	 * @global string $comment_type
	 *
	 * @return void
	 */
	protected function set_review_type() {
		global $comment_type;

		$review_type = sanitize_text_field( wp_unslash( $_REQUEST['review_type'] ?? 'all' ) );

		if ( 'all' !== $review_type && ! empty( $review_type ) ) {
			$comment_type = $review_type; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Builds the `orderby` and `order` arguments based on the current request.
	 *
	 * @return array
	 */
	protected function get_sort_arguments() : array {
		$orderby = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ?? '' ) );
		$order   = sanitize_text_field( wp_unslash( $_REQUEST['order'] ?? '' ) );

		$args = [];

		if ( ! in_array( $orderby, $this->get_sortable_columns(), true ) ) {
			$orderby = 'comment_date_gmt';
		}

		// If ordering by "rating", then we need to adjust to sort by meta value.
		if ( 'rating' === $orderby ) {
			$orderby          = 'meta_value_num';
			$args['meta_key'] = 'rating';
		}

		if ( ! in_array( strtolower( $order ), [ 'asc', 'desc' ], true ) ) {
			$order = 'desc';
		}

		return wp_parse_args(
			[
				'orderby' => $orderby,
				'order'   => strtolower( $order ),
			],
			$args
		);
	}

	/**
	 * Builds the `comment_type` and `parent__in` arguments based on the current request.
	 *
	 * @return array
	 */
	protected function get_filter_type_arguments() : array {

		$args      = [];
		$item_type = isset( $_REQUEST['review_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['review_type'] ) ) : 'all';

		switch ( $item_type ) {

			case 'all':
				break;

			// Review replies.
			case 'comment':
				$parents = get_comments(
					[
						'type'   => 'review',
						'fields' => 'ids',
						'paged'  => -1,
					]
				);

				$args['comment_type'] = 'comment';
				$args['parent__in']   = ! empty( $parents ) ? (array) $parents : [ 0 ];

				break;

			// Reviews and other review types.
			case 'review':
			default:
				$args['comment_type'] = $item_type;
				$args['parent__in']   = [ 0 ];

				break;
		}

		return $args;
	}

	/**
	 * Render a single row HTML.
	 *
	 * @global WP_Post $post
	 * @global WP_Comment $comment
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 * @return void
	 */
	public function single_row( $item ) {
		global $post, $comment;

		// Overrides the comment global for properly rendering rows.
		$comment           = $item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$the_comment_class = (string) wp_get_comment_status( $comment->comment_ID );
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
	 * Gets the columns for the table.
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
	 * Gets a list of sortable columns.
	 *
	 * Key is the column ID and value is which database column we perform the sorting on.
	 * The `rating` column uses a unique key instead, as that requires sorting by meta value.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return [
			'author'   => 'comment_author',
			'response' => 'comment_post_ID',
			'date'     => 'comment_date_gmt',
			'type'     => 'comment_type',
			'rating'   => 'rating',
		];
	}

	/**
	 * Returns a list of available bulk actions.
	 *
	 * @global string $comment_status
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		global $comment_status;

		$actions = [];

		if ( in_array( $comment_status, [ 'all', 'approved' ], true ) ) {
			$actions['unapprove'] = __( 'Unapprove', 'woocommerce' );
		}

		if ( in_array( $comment_status, [ 'all', 'moderated' ], true ) ) {
			$actions['approve'] = __( 'Approve', 'woocommerce' );
		}

		if ( in_array( $comment_status, [ 'all', 'moderated', 'approved', 'trash' ], true ) ) {
			$actions['spam'] = _x( 'Mark as spam', 'review', 'woocommerce' );
		}

		if ( 'trash' === $comment_status ) {
			$actions['untrash'] = __( 'Restore', 'woocommerce' );
		} elseif ( 'spam' === $comment_status ) {
			$actions['unspam'] = _x( 'Not spam', 'review', 'woocommerce' );
		}

		if ( in_array( $comment_status, [ 'trash', 'spam' ], true ) || ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = __( 'Delete permanently', 'woocommerce' );
		} else {
			$actions['trash'] = __( 'Move to Trash', 'woocommerce' );
		}

		return $actions;
	}

	/**
	 * Outputs the text to display when there are no reviews to display.
	 *
	 * @global string $comment_status
	 *
	 * @see WP_List_Table::no_items()
	 */
	public function no_items() {
		global $comment_status;

		if ( 'moderated' === $comment_status ) {
			esc_html_e( 'No reviews awaiting moderation.', 'woocommerce' );
		} else {
			esc_html_e( 'No reviews found.', 'woocommerce' );
		}
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 */
	protected function column_cb( $item ) {
		if ( $this->current_user_can_edit_review ) {
			?>
			<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $item->comment_ID ); ?>"><?php esc_html_e( 'Select review', 'woocommerce' ); ?></label>
			<input
				id="cb-select-<?php echo esc_attr( $item->comment_ID ); ?>"
				type="checkbox"
				name="delete_comments[]"
				value="<?php echo esc_attr( $item->comment_ID ); ?>"
			/>
			<?php
		}
	}

	/**
	 * Renders the review column.
	 *
	 * @see WP_Comments_List_Table::column_comment() for consistency.
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 * @return void
	 */
	protected function column_comment( $item ) {
		$in_reply_to = $this->get_in_reply_to_review_text( $item );

		if ( $in_reply_to ) {
			echo $in_reply_to . '<br><br>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		printf(
			'%1$s%2$s%3$s',
			'<div class="comment-text">',
			get_comment_text( $item->comment_ID ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'</div>'
		);
	}

	/**
	 * Gets the in-reply-to-review text.
	 *
	 * @param WP_Comment $reply Reply to review.
	 * @return string
	 */
	private function get_in_reply_to_review_text( $reply ) {

		$review = $reply->comment_parent ? get_comment( $reply->comment_parent ) : null;

		if ( ! $review ) {
			return '';
		}

		$parent_review_link = esc_url( get_comment_link( $review ) );
		$review_author_name = get_comment_author( $review );

		return sprintf(
			/* translators: %s: Parent review link with review author name. */
			ent2ncr( __( 'In reply to %s.', 'woocommerce' ) ),
			'<a href="' . esc_url( $parent_review_link ) . '">' . esc_html( $review_author_name ) . '</a>'
		);
	}

	/**
	 * Renders the author column.
	 *
	 * @see WP_Comments_List_Table::column_author() for consistency.
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 * @return void
	 */
	protected function column_author( $item ) {
		global $comment_status;

		$author_url = $this->get_item_author_url();
		$author_url_display = $this->get_item_author_url_for_display( $author_url );

		if ( get_option( 'show_avatars' ) ) {
			$author_avatar = get_avatar( $item, 32, 'mystery' );
		} else {
			$author_avatar = '';
		}

		echo '<strong>' . $author_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		comment_author();
		echo '</strong><br>';

		if ( ! empty( $author_url ) ) :

			?>
			<a title="<?php echo esc_attr( $author_url ); ?>" href="<?php echo esc_url( $author_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $author_url_display ); ?></a>
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
					's'    => urlencode( get_comment_author_IP( $item->comment_ID ) ),
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
	private function get_item_author_url() : string {

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
	private function get_item_author_url_for_display( $author_url ) : string {

		$author_url_display = untrailingslashit( preg_replace( '|^http(s)?://(www\.)?|i', '', $author_url ) );

		if ( strlen( $author_url_display ) > 50 ) {
			$author_url_display = wp_html_excerpt( $author_url_display, 49, '&hellip;' );
		}

		return $author_url_display;
	}

	/**
	 * Renders the "submitted on" column.
	 *
	 * Note that the output is consistent with {@see WP_Comments_List_Table::column_date()}.
	 *
	 * @param WP_Comment $item Review or reply being rendered.
	 * @return void
	 */
	protected function column_date( $item ) {

		$submitted = sprintf(
			/* translators: 1 - Product review date, 2: Product review time. */
			__( '%1$s at %2$s', 'woocommerce' ),
			/* translators: Review date format. See https://www.php.net/manual/datetime.format.php */
			get_comment_date( __( 'Y/m/d', 'woocommerce' ), $item ),
			/* translators: Review time format. See https://www.php.net/manual/datetime.format.php */
			get_comment_date( __( 'g:i a', 'woocommerce' ), $item )
		);

		?>
		<div class="submitted-on">
			<?php

			if ( 'approved' === wp_get_comment_status( $item ) && ! empty( $item->comment_post_ID ) ) :
				printf(
					'<a href="%1$s">%2$s</a>',
					esc_url( get_comment_link( $item ) ),
					esc_html( $submitted )
				);
			else :
				echo esc_html( $submitted );
			endif;

			?>
		</div>
		<?php
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
				$post_link  = "<a href='" . esc_url( get_edit_post_link( $product_post->ID ) ) . "' class='comments-edit-item-link'>";
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
	 * @param WP_Comment $item Review or reply being rendered.
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
	 * @param WP_Comment $item Review or reply being rendered.
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
	 * @param WP_Comment $item        Review or reply being rendered.
	 * @param string     $column_name Name of the column being rendered.
	 */
	protected function column_default( $item, $column_name ) {
		// @TODO Implement in MWC-5362 {agibson 2022-04-12}
	}

	/**
	 * Renders the extra controls to be displayed between bulk actions and pagination.
	 *
	 * @global string $comment_status
	 * @global string $comment_type
	 *
	 * @param string $which Position (top or bottom).
	 */
	protected function extra_tablenav( $which ) {
		global $comment_status, $comment_type;

		echo '<div class="alignleft actions">';

		if ( 'top' === $which ) {

			ob_start();

			$this->review_type_dropdown( $comment_type );

			$this->rating_dropdown();

			$output = ob_get_clean();

			if ( ! empty( $output ) && $this->has_items() ) {

				echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				submit_button( __( 'Filter', 'woocommerce' ), '', 'filter_action', false, [ 'id' => 'post-query-submit' ] );
			}
		}

		if ( ( 'spam' === $comment_status || 'trash' === $comment_status ) && $this->has_items() && $this->current_user_can_moderate_reviews ) {

			wp_nonce_field( 'bulk-destroy', '_destroy_nonce' );

			$title = 'spam' === $comment_status
				? esc_attr__( 'Empty Spam', 'woocommerce' )
				: esc_attr__( 'Empty Trash', 'woocommerce' );

			submit_button( $title, 'apply', 'delete_all', false );
		}

		echo '</div>';
	}

	/**
	 * Displays a review type drop-down for filtering reviews in the Product Reviews list table.
	 *
	 * @see WP_Comments_List_Table::comment_type_dropdown() for consistency.
	 *
	 * @param string $item_type The current comment item type slug.
	 * @return void
	 */
	protected function review_type_dropdown( $item_type ) {

		$item_types = [
			'all'     => __( 'All types', 'woocommerce' ),
			'comment' => __( 'Replies', 'woocommerce' ),
			'review'  => __( 'Reviews', 'woocommerce' ),
		];

		?>
		<label class="screen-reader-text" for="filter-by-review-type"><?php esc_html_e( 'Filter by review type', 'woocommerce' ); ?></label>
		<select id="filter-by-review-type" name="review_type">
			<?php foreach ( $item_types as $type => $label ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, $item_type ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Displays a review rating drop-down for filtering reviews in the Product Reviews list table.
	 *
	 * @return void
	 */
	public function rating_dropdown() {

		$rating_options = [
			'0' => __( 'All ratings', 'woocommerce' ),
			'1' => '&#9733;',
			'2' => '&#9733;&#9733;',
			'3' => '&#9733;&#9733;&#9733;',
			'4' => '&#9733;&#9733;&#9733;&#9733;',
			'5' => '&#9733;&#9733;&#9733;&#9733;&#9733;',
		];

		?>
		<label class="screen-reader-text" for="filter-by-review-rating"><?php esc_html_e( 'Filter by review rating', 'woocommerce' ); ?></label>
		<select id="filter-by-review-rating" name="review_rating">
			<?php foreach ( $rating_options as $rating => $label ) : ?>
				<option value="<?php echo esc_attr( $rating ); ?>" <?php selected( $this->current_rating, $rating ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

}
