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
			__( 'Reviews', 'woocommerce' ),
			'moderate_comments',
			static::MENU_SLUG,
			[ $this, 'render_reviews_list_table' ]
		);

		add_action( "load-{$this->reviews_page_hook}", [ $this, 'load_reviews_screen' ] );
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
	}

}
