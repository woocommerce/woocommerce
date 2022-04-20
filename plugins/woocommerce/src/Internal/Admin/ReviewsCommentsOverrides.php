<?php

namespace Automattic\WooCommerce\Internal\Admin;

use WP_Comment_Query;

/**
 * Tweaks the WordPress comments page to exclude reviews.
 */
class ReviewsCommentsOverrides {

	/**
	 * Class instance.
	 *
	 * @var ReviewsCommentsOverrides|null instance
	 */
	protected static $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'admin_notices', [ $this, 'display_notices' ] );

		add_filter( 'comments_list_table_query_args', [ $this, 'exclude_reviews_from_comments' ] );
	}

	/**
	 * Renders admin notices.
	 */
	public function display_notices() {
		$screen = get_current_screen();

		if ( empty( $screen ) || 'edit-comments' !== $screen->base ) {
			return;
		}

		$this->display_reviews_moved_notice();
	}

	/**
	 * Renders an admin notice informing the user that reviews were moved to a new page.
	 */
	protected function display_reviews_moved_notice() {
		$notice_name = 'product_reviews_moved';
		if ( get_user_meta( get_current_user_id(), 'dismissed_' . $notice_name . '_notice', true ) ) {
			return;
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg(
				[
					'wc-hide-notice' => urlencode( $notice_name ),
				]
			),
			'woocommerce_hide_notices_nonce',
			'_wc_notice_nonce'
		);
		?>

		<div class="notice notice-info">
			<p><strong><?php esc_html_e( 'Product reviews have moved!', 'woocommerce' ); ?></strong></p>
			<p><?php esc_html_e( 'Product reviews can now be managed from Products > Reviews.', 'woocommerce' ); ?></p>
			<p class="submit">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=product-reviews' ) ); ?>" class="button-primary"><?php esc_html_e( 'Visit new location', 'woocommerce' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></a>
			</p>
		</div>

		<?php
	}

	/**
	 * Gets the class instance.
	 *
	 * @return object instance
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Excludes product reviews from showing in the comments page.
	 *
	 * @param array $args {@see WP_Comment_Query} query args.
	 * @return array
	 */
	public function exclude_reviews_from_comments( $args ) {

		if ( ! empty( $args['post_type'] ) && 'any' !== $args['post_type'] ) {
			$post_types = (array) $args['post_type'];
		} else {
			$post_types = get_post_types();
		}

		$index = array_search( 'product', $post_types );

		if ( false !== $index ) {
			unset( $post_types[ $index ] );
		}

		$args['post_type'] = $post_types;

		return $args;
	}

}
