<?php
/**
 * Shopping Cart Widget.
 *
 * Displays shopping cart widget.
 *
 * @package WooCommerce\Widgets
 * @version 2.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget cart class.
 */
class WC_Widget_Cart extends WC_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_shopping_cart';
		$this->widget_description = __( 'Display the customer shopping cart.', 'woocommerce' );
		$this->widget_id          = 'woocommerce_widget_cart';
		$this->widget_name        = __( 'Cart', 'woocommerce' );
		$this->settings           = array(
			'title'         => array(
				'type'  => 'text',
				'std'   => __( 'Cart', 'woocommerce' ),
				'label' => __( 'Title', 'woocommerce' ),
			),
			'hide_if_empty' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide if cart is empty', 'woocommerce' ),
			),
		);

		if ( is_customize_preview() ) {
			wp_enqueue_script( 'wc-cart-fragments' );
		}

		parent::__construct();
	}

	/**
	 * Get list of Woo blocks that update the cart.
	 *
	 * @return array;
	 */
	public static function get_blocks() {
		return array(
			'woocommerce/handpicked-products',
			'woocommerce/single-product',
			'woocommerce/product-collection',
			'woocommerce/add-to-cart-form',
			'woocommerce/product-top-rated',
			'woocommerce/product-tag',
			'woocommerce/products-by-attribute',
			'woocommerce/product-on-sale',
			'woocommerce/product-new',
			'woocommerce/product-category',
			'woocommerce/product-best-sellers',
			'woocommerce/all-products',
		);
	}

	/**
	 * Check if post has a block from the provided list of blocks.
	 *
	 * @param string[]                $blocks     Blocks to check for.
	 * @param int|string|WP_Post|null $post       Post to check.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 **/
	function has_block_from_list( $blocks, $post = null ) {
		if ( ! $post ) {
			$post = get_post();
		}

		if ( ! $post ) {
			return false;
		}

		if ( ! function_exists( 'parse_blocks' ) ) {
			return false;
		}

		$block_from_page = parse_blocks( $post->post_content );
		$block_name      = $block_from_page[0]['blockName'];

		if ( in_array( $block_name, $blocks, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( apply_filters( 'woocommerce_widget_cart_is_hidden', is_cart() || is_checkout() ) ) {
			return;
		}

		$hide_if_empty = empty( $instance['hide_if_empty'] ) ? 0 : 1;

		if ( ! isset( $instance['title'] ) ) {
			$instance['title'] = __( 'Cart', 'woocommerce' );
		}

		$this->widget_start( $args, $instance );

		if ( $hide_if_empty ) {
			echo '<div class="hide_cart_widget_if_empty">';
		}

		global $post;
		$is_cart_related_block = $this->has_block_from_list( self::get_blocks(), $post );

		if ( $is_cart_related_block || is_shop() || is_product() || is_product_category() ) {
			wp_enqueue_script( 'wc-cart-fragments' );
			// Insert cart widget placeholder - code in woocommerce.js will update this on page load.
			echo '<div class="widget_shopping_cart_content"></div>';
		} else {
			// Insert cart widget with the Mini-cart content.
			echo '<div class="widget_shopping_cart_content">' . woocommerce_mini_cart() . '</div>';
		}

		if ( $hide_if_empty ) {
			echo '</div>';
		}

		$this->widget_end( $args );
	}
}
