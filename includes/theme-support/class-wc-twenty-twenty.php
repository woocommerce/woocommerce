<?php
/**
 * Twenty Twenty support.
 *
 * @since   3.8.1
 * @package WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Twenty_Twenty class.
 */
class WC_Twenty_Twenty {

	/**
	 * Theme init.
	 */
	public static function init() {

		// Change WooCommerce wrappers.
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'output_content_wrapper' ), 10 );
		add_action( 'woocommerce_after_main_content', array( __CLASS__, 'output_content_wrapper_end' ), 10 );

		// This theme doesn't have a traditional sidebar.
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

		// Enqueue theme compatibility styles.
		add_filter( 'woocommerce_enqueue_styles', array( __CLASS__, 'enqueue_styles' ) );

		// Register theme features.
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 300,
				'single_image_width'    => 600,
			)
		);

	}

	/**
	 * Open the Twenty Twenty wrapper.
	 */
	public static function output_content_wrapper() {
		echo '<section id="primary" class="content-area">';
		echo '<main id="main" class="site-main">';
	}

	/**
	 * Close the Twenty Twenty wrapper.
	 */
	public static function output_content_wrapper_end() {
		echo '</main>';
		echo '</section>';
	}

	/**
	 * Enqueue CSS for this theme.
	 *
	 * @param  array $styles Array of registered styles.
	 * @return array
	 */
	public static function enqueue_styles( $styles ) {
		unset( $styles['woocommerce-general'] );

		$styles['woocommerce-general'] = array(
			'src'     => str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/css/twenty-twenty.css',
			'deps'    => '',
			'version' => WC_VERSION,
			'media'   => 'all',
			'has_rtl' => true,
		);

		return apply_filters( 'woocommerce_twenty_twenty_styles', $styles );
	}

}

WC_Twenty_Twenty::init();
