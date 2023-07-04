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
	 * array_contains
	 * Check if string contains word from array.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 **/
	function array_contains( $str, array $arr ) {
		foreach ( $arr as $a ) {
			if ( stripos( $str, $a ) !== false ) {
				return true;
			}
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

		global $post;
		$is_cart_related_block = $this->array_contains( $post->post_content, self::get_blocks() );

		if ( $is_cart_related_block || is_shop() || is_product() || is_product_category() ) {
			wp_enqueue_script( 'wc-cart-fragments' );
		} elseif ( ! wp_script_is( 'wc-cart-fragments-data', 'registered' ) ) {
			ob_start();

			woocommerce_mini_cart();

			$mini_cart = ob_get_clean();

			$data = array(
				'fragments' => apply_filters(
					'woocommerce_add_to_cart_fragments',
					array(
						'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
					)
				),
				'cart_hash' => WC()->cart->get_cart_hash(),
			);

			wp_register_script( 'wc-cart-fragments-data', false );
			wp_enqueue_script( 'wc-cart-fragments-data' );
			wp_add_inline_script(
				'wc-cart-fragments-data',
				'jQuery( function( $ ) {
					$.each( ' . wp_json_encode( $data['fragments'] ) . ', function( key, value ) {
						$( key ).replaceWith( value );
					});
				 } )
				'
			);
		}

		$hide_if_empty = empty( $instance['hide_if_empty'] ) ? 0 : 1;

		if ( ! isset( $instance['title'] ) ) {
			$instance['title'] = __( 'Cart', 'woocommerce' );
		}

		$this->widget_start( $args, $instance );

		if ( $hide_if_empty ) {
			echo '<div class="hide_cart_widget_if_empty">';
		}

		// Insert cart widget placeholder - code in woocommerce.js will update this on page load.
		echo '<div class="widget_shopping_cart_content"></div>';

		if ( $hide_if_empty ) {
			echo '</div>';
		}

		$this->widget_end( $args );
	}
}
