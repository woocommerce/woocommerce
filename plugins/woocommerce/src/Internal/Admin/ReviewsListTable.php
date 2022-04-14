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
	private $current_user_can_edit = false;

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
		$the_comment_class = (string) wp_get_comment_status( $comment->comment_ID );
		$the_comment_class = implode( ' ', get_comment_class( $the_comment_class, $comment->comment_ID, $comment->comment_post_ID ) );
		// Sets the post for the product in context.
		$post = get_post( $comment->comment_post_ID ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->current_user_can_edit = current_user_can( 'edit_comment', $comment->comment_ID );

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
	 * @param WP_Comment $item Review or reply being rendered.
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

		if ( $this->current_user_can_edit ) :

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
	 * @param object|array $item Review or reply being rendered.
	 */
	protected function column_response( $item ) {
		// @TODO Implement in MWC-5337 {agibson 2022-04-12}
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
	 * Returns an array of supported statuses and their labels.
	 *
	 * @return array
	 */
	protected function get_status_filters() : array {
		return [
			/* translators: %s: Number of reviews. */
			'all'       => _nx_noop(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				'product reviews',
				'woocommerce'
			),
			/* translators: %s: Number of reviews. */
			'moderated' => _nx_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>',
				'product reviews',
				'woocommerce'
			),
			/* translators: %s: Number of reviews. */
			'approved'  => _nx_noop(
				'Approved <span class="count">(%s)</span>',
				'Approved <span class="count">(%s)</span>',
				'product reviews',
				'woocommerce'
			),
			/* translators: %s: Number of reviews. */
			'spam'      => _nx_noop(
				'Spam <span class="count">(%s)</span>',
				'Spam <span class="count">(%s)</span>',
				'product reviews',
				'woocommerce'
			),
			/* translators: %s: Number of reviews. */
			'trash'     => _nx_noop(
				'Trash <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>',
				'product reviews',
				'woocommerce'
			),
		];
	}

	/**
	 * Renders the available status filters.
	 *
	 * @see WP_Comments_List_Table::get_views() for consistency.
	 */
	protected function get_views() {
		global $post_id, $comment_status, $comment_type;

		$status_links = [];

		$status_labels = $this->get_status_filters();

		if ( ! EMPTY_TRASH_DAYS ) {
			unset( $status_labels['trash'] );
		}

		$link = add_query_arg(
			[
				'post_type' => 'product',
				'page'      => Reviews::MENU_SLUG,
			],
			admin_url( 'edit.php' )
		);

		if ( ! empty( $comment_type ) && 'all' !== $comment_type ) {
			$link = add_query_arg( 'comment_type', urlencode( $comment_type ), $link );
		}
		if ( ! empty( $post_id ) ) {
			$link = add_query_arg( 'p', absint( $post_id ), $link );
		}

		foreach ( $status_labels as $status => $label ) {
			$current_link_attributes = '';

			if ( $status === $comment_status ) {
				$current_link_attributes = ' class="current" aria-current="page"';
			}

			$link = add_query_arg( 'comment_status', urlencode( $status ), $link );

			$number_reviews_for_status = $this->get_review_count( $status, (int) $post_id );

			$count_html = sprintf(
				'<span class="%s-count">%s</span>',
				( 'moderated' === $status ) ? 'pending' : $status,
				number_format_i18n( $number_reviews_for_status )
			);

			$status_links[ $status ] = '<a href="' . esc_url( $link ) . '"' . $current_link_attributes . '>' . sprintf( translate_nooped_plural( $label, $number_reviews_for_status ), $count_html ) . '</a>';
		}

		/** This filter is documented in wp-admin/includes/class-wp-comments-list-table.php */
		return apply_filters( 'comment_status_links', $status_links );
	}

	/**
	 * Returns the number of reviews (including review replies) for a given status.
	 *
	 * @param string $status     Status key from {@see ReviewsListTable::get_status_filters()}.
	 * @param int    $product_id ID of the product if we're filtering by product in this request. Otherwise `0` for
	 *                           no product filter.
	 * @return int
	 */
	protected function get_review_count( string $status, int $product_id ) : int {
		return (int) get_comments(
			[
				'type__in'  => [ 'review', 'comment' ],
				'status'    => $this->convert_status_to_query_value( $status ),
				'post_type' => 'product',
				'post_id'   => $product_id,
				'count'     => true,
			]
		);
	}

	/**
	 * Converts a status key into its equivalent `comment_approved` database column value.
	 *
	 * @param string $status Status key from {@see ReviewsListTable::get_status_filters()}.
	 * @return string
	 */
	protected function convert_status_to_query_value( string $status ) : string {
		// These keys exactly match the database column.
		if ( in_array( $status, [ 'spam', 'trash' ], true ) ) {
			return $status;
		}

		switch ( $status ) {
			case 'moderated':
				return '0';
			case 'approved':
				return '1';
			default:
				return 'all';
		}
	}

}
