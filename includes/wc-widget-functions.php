<?php
/**
 * WooCommerce Widget Functions
 *
 * Widget related functions and widget registration.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include widget classes.
include_once( 'abstracts/abstract-wc-widget.php' );
include_once( 'widgets/class-wc-widget-cart.php' );
include_once( 'widgets/class-wc-widget-layered-nav-filters.php' );
include_once( 'widgets/class-wc-widget-layered-nav.php' );
include_once( 'widgets/class-wc-widget-price-filter.php' );
include_once( 'widgets/class-wc-widget-product-categories.php' );
include_once( 'widgets/class-wc-widget-product-search.php' );
include_once( 'widgets/class-wc-widget-product-tag-cloud.php' );
include_once( 'widgets/class-wc-widget-products.php' );
include_once( 'widgets/class-wc-widget-recent-reviews.php' );
include_once( 'widgets/class-wc-widget-recently-viewed.php' );
include_once( 'widgets/class-wc-widget-top-rated-products.php' );

/**
 * Register Widgets.
 *
 * @since 2.3.0
 */
function wc_register_widgets() {
	register_widget( 'WC_Widget_Cart' );
	register_widget( 'WC_Widget_Layered_Nav_Filters' );
	register_widget( 'WC_Widget_Layered_Nav' );
	register_widget( 'WC_Widget_Price_Filter' );
	register_widget( 'WC_Widget_Product_Categories' );
	register_widget( 'WC_Widget_Product_Search' );
	register_widget( 'WC_Widget_Product_Tag_Cloud' );
	register_widget( 'WC_Widget_Products' );
	register_widget( 'WC_Widget_Recent_Reviews' );
	register_widget( 'WC_Widget_Recently_Viewed' );
	register_widget( 'WC_Widget_Top_Rated_Products' );
}
add_action( 'widgets_init', 'wc_register_widgets' );
