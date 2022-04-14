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
	}

	/**
	 * Renders the Reviews page.
	 *
	 * @return void
	 */
	public function render_reviews_list_table() {
		$this->reviews_list_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<?php $this->reviews_list_table->views(); ?>

			<form id="reviews-filter" method="get">

				<input type="hidden" name="page" value="product-reviews" />

				<?php $this->reviews_list_table->search_box( __( 'Search reviews', 'woocommerce' ), 'reviews' ); ?>

				<?php $this->reviews_list_table->display(); ?>
			</form>
		</div>
		<?php
	}

}
