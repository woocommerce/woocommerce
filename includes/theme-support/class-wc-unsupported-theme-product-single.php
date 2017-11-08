<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single product pages support for themes that don't declare WooCommerce support.
 *
 * @class   WC_Unsupported_Theme_Product_Single
 * @since   3.3.0
 * @version 3.3.0
 * @package WooCommerce/Classes
 */
class WC_Unsupported_Theme_Product_Single {

	/**
	 * Theme enhancements init.
	 */
	public static function init() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_filter( 'template_include', array( 'WC_Template_Loader', 'template_loader' ) );
		remove_filter( 'comments_template', array( 'WC_Template_Loader', 'comments_template_loader' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'style' ) );
		add_filter( 'the_content', array( __CLASS__, 'product_info_top' ) );
		add_filter( 'the_content', array( __CLASS__, 'product_info_bottom' ), 99 );
	}

	/**
	 * Style adjustments to normalize things across themes and make elements look better
	 * when inserted into the post content.
	 */
	public static function style() {
		ob_start();
		?>
		.wc-content-top {
			margin-bottom: 2em;
		}

		.wc-content-top input[name="quantity"] {
			margin-right: 1em;
		}

		.wc-content-top .price {
			font-size: 1.5em;
			color: #77a464;
		}

		.wc-content-top .reset_variations {
			margin-left: 1em;
		}

		.wc-content-top .woocommerce-variation-price {
			margin-bottom: 1em;
		}

		/* Normalize the button and input to the same height and font. */
		.wc-content-top input[name="quantity"],
		.wc-content-top .single_add_to_cart_button {
			float: left;
			font-size: 1em;
			height: 2em;
			line-height: 2em;
			padding-top: 0;
			padding-bottom: 0;
		}

		.wc-content-bottom .sku_wrapper {
			display: block;
			margin-bottom: 1em;
		}

		.wc-content-bottom li.product .button {
			font-size: .75em;
		}
		<?php
		$additional_styling = ob_get_clean();
        wp_add_inline_style( 'woocommerce-general', $additional_styling );
	}

	/**
	 * Add WC notices, the price and the cart at the top of the post content.
	 *
	 * @since 3.3.0
	 * @param string $content
	 * @return string
	 */
	public static function product_info_top( $content ) {
		ob_start();
		?>
		<div class="wc-content-top">
			<?php
			do_action( 'woocommerce_before_single_product' );
			woocommerce_template_single_price();
			woocommerce_template_single_add_to_cart();
			?>
		</div>
		<?php
		$top = ob_get_clean();
		return $top . $content;
	}

	/**
	 * Add attributes/dimensions and related products at the bottom of the content.
	 *
	 * @since 3.3.0
	 * @param string $content
	 * @return string
	 */
	public static function product_info_bottom( $content ) {
		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return $content;
		}

		$show_attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' ) || apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() );

		ob_start();
		?>
		<div class="wc-content-bottom">
			<?php
			if ( $show_attributes ):
				woocommerce_product_additional_information_tab();
			endif;
			woocommerce_template_single_meta();
			woocommerce_upsell_display();
			woocommerce_output_related_products();
			?>
		</div>
		<?php
		return $content . ob_get_clean();
	}
}

WC_Unsupported_Theme_Product_Single::init();
