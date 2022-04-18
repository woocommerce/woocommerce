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
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Gets the required capability to access the reviews page and manage product reviews.
	 *
	 * @return string
	 */
	public static function get_capability() {

		/**
		 * Filters whether the current user can manage product reviews.
		 *
		 * @param string $capability The capability (defaults to `moderate_comments`).
		 */
		return apply_filters( 'woocommerce_manage_product_reviews_capability', 'moderate_comments' );
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
