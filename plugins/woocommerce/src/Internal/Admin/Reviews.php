<?php
/**
 * Products > Reviews
 */

namespace Automattic\WooCommerce\Internal\Admin;

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
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_reviews_page' ] );
	}

	/**
	 * Gets the class instance.
	 *
	 * @return Reviews instance
	 */
	public static function get_instance(): ?Reviews {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
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
			'moderate_comments',
			static::MENU_SLUG,
			[ $this, 'render_reviews_list_table' ]
		);

		add_action( "load-{$this->reviews_page_hook}", [ $this, 'load_reviews_screen' ] );
	}

	/**
	 * Determines whether the current page is the reviews page.
	 *
	 * @return bool
	 */
	public function is_reviews_page() : bool {
		global $pagenow;

		return 'edit.php' === $pagenow && isset( $_GET['page'] ) && 'product-reviews' === $_GET['page'];
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
	public function maybe_display_reviews_bulk_action_notice() {

		$messages = $this->get_bulk_action_notice_messages();

		echo ! empty( $messages ) ? '<div id="moderated" class="updated"><p>' . implode( "<br/>\n", $messages ) . '</p></div>' : '';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Gets the applicable bulk action admin notice messages.
	 *
	 * @return array
	 */
	public function get_bulk_action_notice_messages() : array {

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
			$messages[] = sprintf( _n( '%s comment approved', '%s comments approved', $approved, 'woocommerce' ), $approved );
		}

		if ( $unapproved > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s comment unapproved', '%s comments unapproved', $unapproved, 'woocommerce' ), $unapproved );
		}

		if ( $spammed > 0 ) {
			$ids = isset( $_REQUEST['ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ids'] ) ) : 0;
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s comment marked as spam.', '%s comments marked as spam.', $spammed, 'woocommerce' ), $spammed ) . ' <a href="' . esc_url( wp_nonce_url( "edit-comments.php?doaction=undo&action=unspam&ids=$ids", 'bulk-comments' ) ) . '">' . __( 'Undo', 'woocommerce' ) . '</a><br />';
		}

		if ( $unspammed > 0 ) {
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s comment restored from the spam', '%s comments restored from the spam', $unspammed, 'woocommerce' ), $unspammed );
		}

		if ( $trashed > 0 ) {
			$ids = isset( $_REQUEST['ids'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ids'] ) ) : 0;
			/* translators: %s is an integer higher than 0 (1, 2, 3...) */
			$messages[] = sprintf( _n( '%s comment moved to the Trash.', '%s comments moved to the Trash.', $trashed, 'woocommerce' ), $trashed ) . ' <a href="' . esc_url( wp_nonce_url( "edit-comments.php?doaction=undo&action=untrash&ids=$ids", 'bulk-comments' ) ) . '">' . __( 'Undo', 'woocommerce' ) . '</a><br />';
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
