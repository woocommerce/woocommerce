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

	public static $in_content_filter = false;

	/**
	 * Theme enhancements init.
	 */
	public static function init() {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_filter( 'template_include', array( 'WC_Template_Loader', 'template_loader' ) );
		remove_filter( 'comments_template', array( 'WC_Template_Loader', 'comments_template_loader' ) );

		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );

		add_filter( 'the_content', array( __CLASS__, 'inject_product' ) );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'remove_review_tab' ) );
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'remove_featured_image' ) );
	}

	/**
	 * Enqueue the unsupported theme stylesheet to normalize styles.
	 */
	public static function enqueue_styles() {
		wp_enqueue_style( 'woocommerce-unsupported-theme' );
	}

	/**
	 * Replace the content with the product shortcode.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function inject_product( $content ) {
		self::$in_content_filter = true;
		remove_filter( 'the_content', array( __CLASS__, 'inject_product' ) );
		$content = do_shortcode( '[product_page id="' . get_the_ID() . '"]' );
		self::$in_content_filter = false;

		return $content;
	}

	/**
	 * Prevent the featured image unless we're in the content processing the product shortcode.
	 *
	 * @param string $html
	 * @return string
	 */
	public static function remove_featured_image( $html ) {
		if ( self::$in_content_filter ) {
			return $html;
		}

		return '';
	}

	/**
	 * Remove the Review tab and just use the regular comment form.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function remove_review_tab( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	}
}

WC_Unsupported_Theme_Product_Single::init();
