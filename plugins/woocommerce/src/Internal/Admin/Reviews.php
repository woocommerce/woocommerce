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

		add_action( 'init', [ $this, 'override_wp_count_comments' ] );
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
	 * Overrides the WordPress comments count functions.
	 *
	 * This is necessary to prevent product reviews to be counted as regular post comments.
	 */
	public function override_wp_count_comments() {

		add_filter( 'wp_count_comments', [ $this, 'count_comments' ], 10, 2 );
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

	/**
	 * Retrieves the total comment counts for the whole site or a single post.
	 *
	 * This method overrides the default count_comments from WordPress to not consider product reviews as comments.
	 *
	 * @param array $count   This param is not used, but added given that count_comments is a callback to a filter.
	 * @param int   $post_id Optional. Restrict the comment counts to the given post. Default 0, which indicates that
	 *                       comment counts for the whole site will be retrieved.
	 * @return stdClass {
	 *     The number of comments keyed by their status.
	 *
	 *     @type int $approved       The number of approved comments.
	 *     @type int $moderated      The number of comments awaiting moderation (a.k.a. pending).
	 *     @type int $spam           The number of spam comments.
	 *     @type int $trash          The number of trashed comments.
	 *     @type int $post-trashed   The number of comments for posts that are in the trash.
	 *     @type int $total_comments The total number of non-trashed comments, including spam.
	 *     @type int $all            The total number of pending or approved comments.
	 * }
	 */
	public function count_comments( $count, $post_id = 0 ) {

		$post_id = (int) $post_id;

		/**
		 * Filters the comments count for a given post or the whole site.
		 *
		 * @param array|stdClass $count   An empty array or an object containing comment counts.
		 * @param int            $post_id The post ID. Can be 0 to represent the whole site.
		 */
		$filtered = apply_filters( 'woocommerce_product_reviews_count_comments', array(), $post_id );
		if ( ! empty( $filtered ) ) {
			return $filtered;
		}

		$count = wp_cache_get( "comments-{$post_id}", 'counts' );
		if ( false !== $count ) {
			return $count;
		}

		$stats              = $this->get_comment_count( $post_id );
		$stats['moderated'] = $stats['awaiting_moderation'];
		unset( $stats['awaiting_moderation'] );

		$stats_object = (object) $stats;
		wp_cache_set( "comments-{$post_id}", $stats_object, 'counts' );

		return $stats_object;
	}

	/**
	 * Retrieves the total comment counts for the whole site or a single post.
	 *
	 * This method overrides the default get_comment_count from WordPress to not consider product reviews as comments.
	 *
	 * @param int $post_id Optional. Restrict the comment counts to the given post. Default 0, which indicates that
	 *                     comment counts for the whole site will be retrieved.
	 * @return int[] {
	 *     The number of comments keyed by their status.
	 *
	 *     @type int $approved            The number of approved comments.
	 *     @type int $awaiting_moderation The number of comments awaiting moderation (a.k.a. pending).
	 *     @type int $spam                The number of spam comments.
	 *     @type int $trash               The number of trashed comments.
	 *     @type int $post-trashed        The number of comments for posts that are in the trash.
	 *     @type int $total_comments      The total number of non-trashed comments, including spam.
	 *     @type int $all                 The total number of pending or approved comments.
	 * }
	 */
	protected function get_comment_count( $post_id = 0 ) {
		global $wpdb;

		$post_id = (int) $post_id;

		$where = '';
		if ( $post_id > 0 ) {
			$where = $wpdb->prepare( $where . ' AND comment_post_ID = %d', $post_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$sql = "
			SELECT comment_approved, COUNT( * ) AS total
			FROM {$wpdb->comments}
			WHERE comment_type <> 'review' {$where}
			GROUP BY comment_approved
		";

		$totals = (array) $wpdb->get_results( $wpdb->prepare( $sql ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$comment_count = array(
			'approved'            => 0,
			'awaiting_moderation' => 0,
			'spam'                => 0,
			'trash'               => 0,
			'post-trashed'        => 0,
			'total_comments'      => 0,
			'all'                 => 0,
		);

		foreach ( $totals as $row ) {
			switch ( $row['comment_approved'] ) {
				case 'trash':
					$comment_count['trash'] = $row['total'];
					break;
				case 'post-trashed':
					$comment_count['post-trashed'] = $row['total'];
					break;
				case 'spam':
					$comment_count['spam']            = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					break;
				case '1':
					$comment_count['approved']        = $row['total'];
					$comment_count['total_comments'] += $row['total'];
					$comment_count['all']            += $row['total'];
					break;
				case '0':
					$comment_count['awaiting_moderation'] = $row['total'];
					$comment_count['total_comments']     += $row['total'];
					$comment_count['all']                += $row['total'];
					break;
				default:
					break;
			}
		}

		return array_map( 'intval', $comment_count );
	}

}
