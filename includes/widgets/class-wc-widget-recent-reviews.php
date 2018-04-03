<?php
/**
 * Recent Reviews Widget.
 *
 * @package WooCommerce/Widgets
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget recent reviews class.
 */
class WC_Widget_Recent_Reviews extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_recent_reviews';
		$this->widget_description = __( 'Display a list of recent reviews from your store.', 'woocommerce' );
		$this->widget_id          = 'woocommerce_recent_reviews';
		$this->widget_name        = __( 'Recent Product Reviews', 'woocommerce' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Recent reviews', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' ),
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of reviews to show', 'woocommerce' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		global $comments, $comment;

		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		$number   = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];
		$comments = get_comments(
			array(
				'number'      => $number,
				'status'      => 'approve',
				'post_status' => 'publish',
				'post_type'   => 'product',
				'parent'      => 0,
			)
		); // WPCS: override ok.

		if ( $comments ) {
			$this->widget_start( $args, $instance );

			echo '<ul class="product_list_widget">';

			foreach ( (array) $comments as $comment ) {

				$_product = wc_get_product( $comment->comment_post_ID );

				$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

				$rating_html = wc_get_rating_html( $rating );

				echo '<li><a href="' . esc_url( get_comment_link( $comment->comment_ID ) ) . '">';

				echo $_product->get_image() . wp_kses_post( $_product->get_name() ) . '</a>'; // WPCS: XSS ok.

				echo $rating_html; // WPCS: XSS ok.

				/* translators: %s: review author */
				echo '<span class="reviewer">' . sprintf( esc_html__( 'by %s', 'woocommerce' ), get_comment_author() ) . '</span>'; // WPCS: XSS ok.

				echo '</li>';
			}

			echo '</ul>';

			$this->widget_end( $args );
		}

		$content = ob_get_clean();

		echo $content; // WPCS: XSS ok.

		$this->cache_widget( $args, $content );
	}
}
