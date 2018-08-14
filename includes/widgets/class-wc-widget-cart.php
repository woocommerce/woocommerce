<?php
/**
 * Shopping Cart Widget.
 *
 * Displays shopping cart widget.
 *
 * @package WooCommerce/Widgets
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
			$this->enqueue_ajax_script();
		}

		parent::__construct();
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

	/**
	 * This method provides the JS script which will execute on addition of a new widget.
	 *
	 * @todo 1. In this function there is a redundency of code, which needs to be fixed.
	 *       2. Also sort out a better way to fix the raw JS code block. May be using `wc_enqueue_js()`.
	 *
	 * @return void
	 */
	private function enqueue_ajax_script() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-cart-fragments', WC()->plugin_url() . '/assets/js/frontend/cart-fragments' . $suffix . '.js', array( 'jquery', 'js-cookie' ), WC_VERSION, true );

		wp_localize_script( 'wc-cart-fragments', 'wc_cart_fragments_params',
			array(
				'ajax_url'      => WC()->ajax_url(),
				'wc_ajax_url'   => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'cart_hash_key' => apply_filters( 'woocommerce_cart_hash_key', 'wc_cart_hash_' . md5( get_current_blog_id() . '_' . get_site_url( get_current_blog_id(), '/' ) . get_template() ) ),
				'fragment_name' => apply_filters( 'woocommerce_cart_fragment_name', 'wc_fragments_' . md5( get_current_blog_id() . '_' . get_site_url( get_current_blog_id(), '/' ) . get_template() ) ),
			)
		);

		wp_enqueue_script( 'wc-cart-fragments' );

		?>
		<script type="text/javascript">
			(function( $ ) {
				$(function() {
					'use strict';
					$(document).on('widget-added', function() {
						refresh_cart_fragment();
					});
				});
			})(jQuery);
		</script>
		<?php
	}
}
