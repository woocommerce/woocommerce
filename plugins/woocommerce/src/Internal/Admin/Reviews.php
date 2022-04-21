<?php
/**
 * Products > Reviews
 */

namespace Automattic\WooCommerce\Internal\Admin;

use WP_Screen;

/**
 * Handles backend logic for the Reviews component.
 */
class Reviews {

	/**
	 * Admin page identifier.
	 */
	const MENU_SLUG = 'product-reviews';

	/**
	 * Class instance.
	 *
	 * @var Reviews|null instance
	 */
	protected static $instance;

	/**
	 * Reviews page hook name.
	 *
	 * @var string|null
	 */
	protected $reviews_page_hook;

	/**
	 * Reviews list table instance.
	 *
	 * @var ReviewsListTable|null
	 */
	protected $reviews_list_table;

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'admin_menu', [ $this, 'add_reviews_page' ] );

		add_filter( 'parent_file', [ $this, 'edit_review_parent_file' ] );

		add_filter( 'gettext', [ $this, 'edit_comments_screen_text' ], 10, 2 );

		add_action( 'admin_notices', [ $this, 'display_notices' ] );
	}

	/**
	 * Gets the class instance.
	 *
	 * @return Reviews instance
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Gets the required capability to access the reviews page and manage product reviews.
	 *
	 * @param string $context The context for which the capability is needed.
	 * @return string
	 */
	public static function get_capability( $context = 'view' ) {

		/**
		 * Filters whether the current user can manage product reviews.
		 *
		 * @param string $capability The capability (defaults to `moderate_comments`).
		 * @param string $context    The context for which the capability is needed.
		 */
		return apply_filters( 'woocommerce_product_reviews_page_capability', 'moderate_comments', $context );
	}

	/**
	 * Registers the Product Reviews submenu page.
	 *
	 * @return void
	 */
	public function add_reviews_page() {

		$this->reviews_page_hook = add_submenu_page(
			'edit.php?post_type=product',
			__( 'Reviews', 'woocommerce' ),
			__( 'Reviews', 'woocommerce' ) . $this->get_pending_count_bubble(),
			static::get_capability(),
			static::MENU_SLUG,
			[ $this, 'render_reviews_list_table' ]
		);

		add_action( "load-{$this->reviews_page_hook}", [ $this, 'load_reviews_screen' ] );
	}

	/**
	 * Determines whether the current page is the reviews page.
	 *
	 * @global WP_Screen $current_screen
	 *
	 * @return bool
	 */
	public function is_reviews_page() : bool {
		global $current_screen;

		return isset( $current_screen->base ) && 'product_page_' . static::MENU_SLUG === $current_screen->base;
	}

	/**
	 * Displays notices on the Reviews page.
	 *
	 * @return void
	 */
	public function display_notices() {

		if ( $this->is_reviews_page() ) {
			$this->maybe_display_reviews_bulk_action_notice();
		}
	}

	/**
	 * May display the bulk action admin notice.
	 *
	 * @return void
	 */
	protected function maybe_display_reviews_bulk_action_notice() {

		$messages = $this->get_bulk_action_notice_messages();

		echo ! empty( $messages ) ? '<div id="moderated" class="updated"><p>' . implode( "<br/>\n", $messages ) . '</p></div>' : '';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Gets the applicable bulk action admin notice messages.
	 *
	 * @return array
	 */
	protected function get_bulk_action_notice_messages() : array {

		$approved   = isset( $_REQUEST['approved'] ) ? (int) $_REQUEST['approved'] : 0;
		$unapproved = isset( $_REQUEST['unapproved'] ) ? (int) $_REQUEST['unapproved'] : 0;
		$deleted    = isset( $_REQUEST['deleted'] ) ? (int) $_REQUEST['deleted'] : 0;
		$trashed    = isset( $_REQUEST['trashed'] ) ? (int) $_REQUEST['trashed'] : 0;
		$untrashed  = isset( $_REQUEST['untrashed'] ) ? (int) $_REQUEST['untrashed'] : 0;
		$spammed    = isset( $_REQUEST['spammed'] ) ? (int) $_REQUEST['spammed'] : 0;
		$unspammed  = isset( $_REQUEST['unspammed'] ) ? (int) $_REQUEST['unspammed'] : 0;

		$messages = [];

		if ( $approved > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review approved', '%s reviews approved', $approved, 'woocommerce' ), $approved );
		}

		if ( $unapproved > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review unapproved', '%s reviews unapproved', $unapproved, 'woocommerce' ), $unapproved );
		}

		if ( $spammed > 0 ) {
			$ids = isset( $_REQUEST['ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ids'] ) ) : 0;
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review marked as spam.', '%s reviews marked as spam.', $spammed, 'woocommerce' ), $spammed ) . ' <a href="' . esc_url( wp_nonce_url( "edit-comments.php?doaction=undo&action=unspam&ids=$ids", 'bulk-comments' ) ) . '">' . __( 'Undo', 'woocommerce' ) . '</a><br />';
		}

		if ( $unspammed > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review restored from the spam', '%s reviews restored from the spam', $unspammed, 'woocommerce' ), $unspammed );
		}

		if ( $trashed > 0 ) {
			$ids = isset( $_REQUEST['ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ids'] ) ) : 0;
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review moved to the Trash.', '%s reviews moved to the Trash.', $trashed, 'woocommerce' ), $trashed ) . ' <a href="' . esc_url( wp_nonce_url( "edit-comments.php?doaction=undo&action=untrash&ids=$ids", 'bulk-comments' ) ) . '">' . __( 'Undo', 'woocommerce' ) . '</a><br />';
		}

		if ( $untrashed > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review restored from the Trash', '%s reviews restored from the Trash', $untrashed, 'woocommerce' ), $untrashed );
		}

		if ( $deleted > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s review permanently deleted', '%s reviews permanently deleted', $deleted, 'woocommerce' ), $deleted );
		}

		return $messages;
	}

	/**
	 * Counts the number of pending product reviews/replies, and returns the notification bubble if there's more than zero.
	 *
	 * @return string Empty string if there are no pending reviews, or bubble HTML if there are.
	 */
	protected function get_pending_count_bubble() : string {
		$count = (int) get_comments(
			[
				'type__in'  => [ 'review', 'comment' ],
				'status'    => '0',
				'post_type' => 'product',
				'count'     => true,
			]
		);

		if ( empty( $count ) ) {
			return '';
		}

		return ' <span class="awaiting-mod count-' . esc_attr( $count ) . '"><span class="pending-count">' . esc_html( $count ) . '</span></span>';
	}

	/**
	 * Highlights Product -> Reviews admin menu item when editing a review or a reply to a review.
	 *
	 * @global string $submenu_file
	 *
	 * @param string $parent_file Parent menu item.
	 * @return string
	 */
	public function edit_review_parent_file( $parent_file ) {
		global $submenu_file, $current_screen;

		if ( isset( $current_screen->id, $_GET['c'] ) && 'comment' === $current_screen->id ) {

			$comment_id = absint( $_GET['c'] );
			$comment = get_comment( $comment_id );

			if ( $comment->comment_parent > 0 ) {
				$comment = get_comment( $comment->comment_parent );
			}

			if ( $comment && 'product' === get_post_type( $comment->comment_post_ID ) ) {
				$parent_file  = 'edit.php?post_type=product';
				$submenu_file = 'product-reviews'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		return $parent_file;
	}

	/**
	 * Replaces Edit/Moderate Comment title/headline with Edit Review, when editing/moderating a review.
	 *
	 * @param  string $translation Translated text.
	 * @param  string $text        Text to translate.
	 * @return string              Translated text.
	 */
	public function edit_comments_screen_text( $translation, $text ) {
		global $comment;

		// Bail out if not a text we should replace.
		if ( ! in_array( $text, [ 'Edit Comment', 'Moderate Comment' ], true ) ) {
			return $translation;
		}

		// Try to get comment from query params when not in context already.
		if ( ! $comment && isset( $_GET['action'], $_GET['c'] ) && 'editcomment' === $_GET['action'] ) {
			$comment_id = absint( $_GET['c'] );
			$comment    = get_comment( $comment_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$is_reply = false;

		if ( isset( $comment->comment_parent ) && $comment->comment_parent > 0 ) {
			$is_reply = true;
			$comment = get_comment( $comment->comment_parent ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// Only replace the translated text if we are editing a comment left on a product (ie. a review).
		if ( isset( $comment->comment_post_ID ) && 'product' === get_post_type( $comment->comment_post_ID ) ) {
			if ( 'Edit Comment' === $text ) {
				$translation = $is_reply
					? __( 'Edit Review Reply', 'woocommerce' )
					: __( 'Edit Review', 'woocommerce' );
			} elseif ( 'Moderate Comment' === $text ) {
				$translation = $is_reply
					? __( 'Moderate Review Reply', 'woocommerce' )
					: __( 'Moderate Review', 'woocommerce' );
			}
		}

		return $translation;
	}

	/**
	 * Initializes the list table.
	 *
	 * @return void
	 */
	public function load_reviews_screen() {
		$this->reviews_list_table = new ReviewsListTable( [ 'screen' => $this->reviews_page_hook ] );
		$this->reviews_list_table->process_bulk_action();
	}

	/**
	 * Renders the Reviews page.
	 *
	 * @return void
	 */
	public function render_reviews_list_table() {

		$this->reviews_list_table->prepare_items();

		ob_start();

		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<?php $this->reviews_list_table->views(); ?>

			<form id="reviews-filter" method="get">
				<?php $page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : static::MENU_SLUG; ?>

				<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
				<input type="hidden" name="post_type" value="product" />

				<?php $this->reviews_list_table->search_box( __( 'Search reviews', 'woocommerce' ), 'reviews' ); ?>

				<?php $this->reviews_list_table->display(); ?>
			</form>
		</div>
		<?php

		/**
		 * Filters the contents of the product reviews list table output.
		 *
		 * @param string           $output             The HTML output of the list table.
		 * @param ReviewsListTable $reviews_list_table The reviews list table instance.
		 */
		echo apply_filters( 'woocommerce_product_reviews_list_table', ob_get_clean(), $this->reviews_list_table ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

}
