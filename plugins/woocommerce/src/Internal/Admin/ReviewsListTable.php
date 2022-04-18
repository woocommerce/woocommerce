<?php
/**
 * Product > Reviews
 */

namespace Automattic\WooCommerce\Internal\Admin;

use WC_Product;
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
	 * @var int
	 */
	private $current_reviews_rating = 0;

	/**
	 * Current product the reviews should be displayed for.
	 *
	 * @var WC_Product|null Product or null for all products.
	 */
	private $current_product_for_reviews;

	/**
	 * Constructor.
	 *
	 * @param array|string $args Array or string of arguments.
	 */
	public function __construct( $args = [] ) {
		parent::__construct(
			wp_parse_args(
				$args,
				[
					'plural'   => 'product-reviews',
					'singular' => 'product-review',
				]
			)
		);

		$this->current_user_can_moderate_reviews = current_user_can( 'moderate_comments' );
	}

	/**
	 * Prepares reviews for display.
	 *
	 * @return void
	 */
	public function prepare_items() {

		$this->set_review_status();
		$this->set_review_type();
		$this->current_reviews_rating = isset( $_REQUEST['review_rating'] ) ? absint( $_REQUEST['review_rating'] ) : 0;
		$this->set_review_product();

		$args = [
			'post_type' => 'product',
		];

		// Include the order & orderby arguments.
		$args = wp_parse_args( $this->get_sort_arguments(), $args );
		// Handle the review item types filter.
		$args = wp_parse_args( $this->get_filter_type_arguments(), $args );
		// Handle the reviews rating filter.
		$args = wp_parse_args( $this->get_filter_rating_arguments(), $args );
		// Handle the review product filter.
		$args = wp_parse_args( $this->get_filter_product_arguments(), $args );

		$comments = get_comments( $args );

		update_comment_cache( $comments );

		$this->items = $comments;
	}

	/**
	 * Sets the product to filter reviews by.
	 *
	 * @return void
	 */
	protected function set_review_product() {

		$product_id = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : null;
		$product = $product_id ? wc_get_product( $product_id ) : null;

		if ( $product instanceof WC_Product ) {
			$this->current_product_for_reviews = $product;
		}
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
	 * Builds the `type` argument based on the current request.
	 *
	 * @return array
	 */
	protected function get_filter_type_arguments() : array {

		$args      = [];
		$item_type = isset( $_REQUEST['review_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['review_type'] ) ) : 'all';

		if ( 'all' === $item_type ) {
			return $args;
		}

		$args['type'] = $item_type;

		return $args;
	}

	/**
	 * Builds the `meta_query` arguments based on the current request.
	 *
	 * @return array
	 */
	protected function get_filter_rating_arguments() : array {

		$args = [];

		if ( empty( $this->current_reviews_rating ) ) {
			return $args;
		}

		$args['meta_query'] = [
			[
				'key'     => 'rating',
				'value'   => (int) $this->current_reviews_rating,
				'compare' => '=',
				'type'    => 'NUMERIC',
			],
		];

		return $args;
	}

	/**
	 * Gets the `post_id` argument based on the current request.
	 *
	 * @return array
	 */
	public function get_filter_product_arguments() : array {

		$args = [];

		if ( $this->current_product_for_reviews instanceof WC_Product ) {
			$args['post_id'] = $this->current_product_for_reviews->get_id();
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
	 * Generate and display row actions links.
	 *
	 * @see WP_Comments_List_Table::handle_row_actions() for consistency.
	 *
	 * @global string $comment_status Status for the current listed comments.
	 *
	 * @param WP_Comment $item        The product review or reply in context.
	 * @param string     $column_name Current column name.
	 * @param string     $primary     Primary column name.
	 * @return string
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		global $comment_status;

		if ( $primary !== $column_name || ! $this->current_user_can_edit_review ) {
			return '';
		}

		$review_status = wp_get_comment_status( $item );
		$del_nonce     = esc_html( '_wpnonce=' . wp_create_nonce( "delete-comment_$item->comment_ID" ) );
		$approve_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "approve-comment_$item->comment_ID" ) );

		$url = add_query_arg(
			[
				'c' => urlencode( $item->comment_ID ),
			],
			admin_url( 'comment.php' )
		);

		$approve_url   = $url . "&action=approvecomment&$approve_nonce";
		$unapprove_url = $url . "&action=unapprovecomment&$approve_nonce";
		$spam_url      = $url . "&action=spamcomment&$del_nonce";
		$unspam_url    = $url . "&action=unspamcomment&$del_nonce";
		$trash_url     = $url . "&action=trashcomment&$del_nonce";
		$untrash_url   = $url . "&action=untrashcomment&$del_nonce";
		$delete_url    = $url . "&action=deletecomment&$del_nonce";

		$actions = [
			'approve'   => '',
			'unapprove' => '',
			'reply'     => '',
			'quickedit' => '',
			'edit'      => '',
			'spam'      => '',
			'unspam'    => '',
			'trash'     => '',
			'untrash'   => '',
			'delete'    => '',
		];

		if ( $comment_status && 'all' !== $comment_status ) {
			if ( 'approved' === $review_status ) {
				$actions['unapprove'] = sprintf(
					'<a href="%s" data-wp-lists="%s" class="vim-u vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
					esc_url( $unapprove_url ),
					esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}:e7e7d3:action=dim-comment&amp;new=unapproved" ),
					esc_attr__( 'Unapprove this review', 'woocommerce' ),
					esc_html__( 'Unapprove', 'woocommerce' )
				);
			} elseif ( 'unapproved' === $review_status ) {
				$actions['approve'] = sprintf(
					'<a href="%s" data-wp-lists="%s" class="vim-a vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
					esc_url( $approve_url ),
					esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}:e7e7d3:action=dim-comment&amp;new=approved" ),
					esc_attr__( 'Approve this review', 'woocommerce' ),
					esc_html__( 'Approve', 'woocommerce' )
				);
			}
		} else {
			$actions['approve'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-a aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $approve_url ),
				esc_attr( "dim:the-comment-list:comment-{$item->comment_ID}:unapproved:e7e7d3:e7e7d3:new=approved" ),
				esc_attr__( 'Approve this review', 'woocommerce' ),
				esc_html__( 'Approve', 'woocommerce' )
			);

			$actions['unapprove'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-u aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $unapprove_url ),
				esc_attr( "dim:the-comment-list:comment-{$item->comment_ID}:unapproved:e7e7d3:e7e7d3:new=unapproved" ),
				esc_attr__( 'Unapprove this review', 'woocommerce' ),
				esc_html__( 'Unapprove', 'woocommerce' )
			);
		}

		if ( 'spam' !== $review_status ) {
			$actions['spam'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-s vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $spam_url ),
				esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}::spam=1" ),
				esc_attr__( 'Mark this review as spam', 'woocommerce' ),
				/* translators: "Mark as spam" link. */
				esc_html_x( 'Spam', 'verb', 'woocommerce' )
			);
		} else {
			$actions['unspam'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-z vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $unspam_url ),
				esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}:66cc66:unspam=1" ),
				esc_attr__( 'Restore this review from the spam', 'woocommerce' ),
				esc_html_x( 'Not Spam', 'review', 'woocommerce' )
			);
		}

		if ( 'trash' === $review_status ) {
			$actions['untrash'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="vim-z vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $untrash_url ),
				esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}:66cc66:untrash=1" ),
				esc_attr__( 'Restore this review from the Trash', 'woocommerce' ),
				esc_html__( 'Restore', 'woocommerce' )
			);
		}

		if ( 'spam' === $review_status || 'trash' === $review_status || ! EMPTY_TRASH_DAYS ) {
			$actions['delete'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="delete vim-d vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $delete_url ),
				esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}::delete=1" ),
				esc_attr__( 'Delete this review permanently', 'woocommerce' ),
				esc_html__( 'Delete Permanently', 'woocommerce' )
			);
		} else {
			$actions['trash'] = sprintf(
				'<a href="%s" data-wp-lists="%s" class="delete vim-d vim-destructive aria-button-if-js" aria-label="%s">%s</a>',
				esc_url( $trash_url ),
				esc_attr( "delete:the-comment-list:comment-{$item->comment_ID}::trash=1" ),
				esc_attr__( 'Move this review to the Trash', 'woocommerce' ),
				esc_html_x( 'Trash', 'verb', 'woocommerce' )
			);
		}

		if ( 'spam' !== $review_status && 'trash' !== $review_status ) {
			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'action' => 'editcomment',
							'c'      => urlencode( $item->comment_ID ),
						],
						admin_url( 'comment.php' )
					)
				),
				esc_attr__( 'Edit this review', 'woocommerce' ),
				esc_html__( 'Edit', 'woocommerce' )
			);

			$format = '<button type="button" data-comment-id="%d" data-post-id="%d" data-action="%s" class="%s button-link" aria-expanded="false" aria-label="%s">%s</button>';

			$actions['quickedit'] = sprintf(
				$format,
				esc_attr( $item->comment_ID ),
				esc_attr( $item->comment_post_ID ),
				'edit',
				'vim-q comment-inline',
				esc_attr__( 'Quick edit this review inline', 'woocommerce' ),
				esc_html__( 'Quick Edit', 'woocommerce' )
			);

			$actions['reply'] = sprintf(
				$format,
				esc_attr( $item->comment_ID ),
				esc_attr( $item->comment_post_ID ),
				'replyto',
				'vim-r comment-inline',
				esc_attr__( 'Reply to this review', 'woocommerce' ),
				esc_html__( 'Reply', 'woocommerce' )
			);
		}

		/** This filter is documented in wp-admin/includes/dashboard.php */
		$actions = apply_filters( 'comment_row_actions', array_filter( $actions ), $item );

		$always_visible = 'excerpt' === get_user_setting( 'posts_list_mode', 'list' );

		$output = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

		$i = 0;

		foreach ( $actions as $action => $link ) {
			++$i;

			if ( ( ( 'approve' === $action || 'unapprove' === $action ) && 2 === $i ) || 1 === $i ) {
				$sep = '';
			} else {
				$sep = ' | ';
			}

			if ( ( 'reply' === $action || 'quickedit' === $action ) && ! wp_doing_ajax() ) {
				$action .= ' hide-if-no-js';
			} elseif ( ( 'untrash' === $action && 'trash' === $review_status ) || ( 'unspam' === $action && 'spam' === $review_status ) ) {
				if ( '1' === get_comment_meta( $item->comment_ID, '_wp_trash_meta_status', true ) ) {
					$action .= ' approve';
				} else {
					$action .= ' unapprove';
				}
			}

			$output .= "<span class='$action'>$sep$link</span>";
		}

		$output .= '</div>';
		$output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'woocommerce' ) . '</span></button>';

		return $output;
	}

	/**
	 * Gets the columns for the table.
	 *
	 * @return array Table columns and their headings.
	 */
	public function get_columns() {
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'type'     => _x( 'Type', 'review type', 'woocommerce' ),
			'author'   => __( 'Author', 'woocommerce' ),
			'rating'   => __( 'Rating', 'woocommerce' ),
			'comment'  => _x( 'Review', 'column name', 'woocommerce' ),
			'response' => __( 'Product', 'woocommerce' ),
			'date'     => _x( 'Submitted on', 'column name', 'woocommerce' ),
		];

		/**
		 * Filters the table columns.
		 *
		 * @param array $columns
		 */
		return apply_filters( 'woocommerce_product_reviews_table_columns', $columns );
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
		/**
		 * Fires when the default column output is displayed for a single row.
		 * This action can be used to render custom columns that have been added.
		 *
		 * @param string $column_name The custom column's name.
		 * @param string $comment_id  The review ID as a numeric string.
		 */
		do_action( 'woocommerce_product_reviews_table_custom_column', $column_name, $item->comment_ID );
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
			$this->review_rating_dropdown( $this->current_reviews_rating );
			$this->product_search( $this->current_product_for_reviews );

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
	 * @param string $current_type The current comment item type slug.
	 * @return void
	 */
	protected function review_type_dropdown( $current_type ) {

		$item_types = [
			'all'     => __( 'All types', 'woocommerce' ),
			'comment' => __( 'Replies', 'woocommerce' ),
			'review'  => __( 'Reviews', 'woocommerce' ),
		];

		?>
		<label class="screen-reader-text" for="filter-by-review-type"><?php esc_html_e( 'Filter by review type', 'woocommerce' ); ?></label>
		<select id="filter-by-review-type" name="review_type">
			<?php foreach ( $item_types as $type => $label ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, $current_type ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Displays a review rating drop-down for filtering reviews in the Product Reviews list table.
	 *
	 * @param int $current_rating Rating to display reviews for.
	 * @return void
	 */
	public function review_rating_dropdown( $current_rating ) {

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
				<?php

				$title = 0 === (int) $rating
					? $label
					: sprintf(
						/* translators: %s: Star rating (1-5). */
						__( '%s-star rating', 'woocommerce' ),
						$rating
					);

				?>
				<option value="<?php echo esc_attr( $rating ); ?>" <?php selected( $rating, (string) $current_rating ); ?> title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Processes the bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( ! $this->current_user_can_moderate_reviews ) {
			return;
		}

		if ( $this->current_action() ) {
			check_admin_referer( 'bulk-product-reviews' );

			$query_string = remove_query_arg( [ 'page', '_wpnonce' ], wp_unslash( ( $_SERVER['QUERY_STRING'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// Replace current nonce with bulk-comments nonce.
			$comments_nonce = wp_create_nonce( 'bulk-comments' );
			$query_string   = add_query_arg( '_wpnonce', $comments_nonce, $query_string );

			// Redirect to edit-comments.php, which will handle processing the action for us.
			wp_safe_redirect( esc_url_raw( admin_url( 'edit-comments.php?' . $query_string ) ) );
			exit;
		} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {

			wp_safe_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ] ) );
			exit;
		}
	}

	/**
	 * Displays a product search input for filtering reviews by product in the Product Reviews list table.
	 *
	 * @param WC_Product|null $current_product The current product (or null when displaying all reviews).
	 * @return void
	 */
	protected function product_search( $current_product ) {
		?>
		<label class="screen-reader-text" for="filter-by-product"><?php esc_html_e( 'Filter by product', 'woocommerce' ); ?></label>
		<select
			id="filter-by-product"
			class="wc-product-search"
			name="product_id"
			style="width: 200px;"
			data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>"
			data-action="woocommerce_json_search_products"
			data-allow_clear="true">
			<?php if ( $current_product instanceof WC_Product ) : ?>
				<option value="<?php echo esc_attr( $current_product->get_id() ); ?>" selected="selected"><?php echo esc_html( $current_product->get_formatted_name() ); ?></option>
			<?php endif; ?>
		</select>
		<?php
	}

}
