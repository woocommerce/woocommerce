<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Twenty Fourteen support.
 *
 * @class   WC_Twenty_Fourteen
 * @since   3.3.0
 * @package WooCommerce/Classes
 */
class WC_Twenty_Fourteen {

	/**
	 * Theme init.
	 */
	public static function init() {
		// Remove default wrappers.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper' );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end' );

		// Add custom wrappers.
		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'output_content_wrapper' ) );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'output_content_wrapper_end' ) );

		// Declare theme support for features.
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'woocommerce', array(
			'shop_catalog_image_size' => array(
				'width'  => 105,
				'height' => 105,
				'crop'   => 1,
			),
			'shop_thumbnail_image_size' => array(
				'width'  => 60,
				'height' => 60,
				'crop'   => 1,
			),
			'shop_single_image_size' => array(
				'width'  => 228,
				'height' => 228,
				'crop'   => 0,
			),
		) );
	}

	/**
	 * Open wrappers.
	 */
	public static function output_content_wrapper() {
		echo '<div id="primary" class="content-area"><div id="content" role="main" class="site-content twentyfourteen"><div class="tfwc">';
	}

	/**
	 * Close wrappers.
	 */
	public static function output_content_wrapper_end() {
		echo '</div></div></div>';
		get_sidebar( 'content' );
	}
}

WC_Twenty_Fourteen::init();
