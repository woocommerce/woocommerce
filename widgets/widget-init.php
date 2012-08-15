<?php
/**
 * Widgets init
 *
 * Init the widgets.
 *
 * @author 		WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	1.6.4
 */

/**
 * woocommerce_register_widgets function.
 *
 * @access public
 * @return void
 */
function woocommerce_register_widgets() {
	register_widget('WooCommerce_Widget_Recent_Products');
	register_widget('WooCommerce_Widget_Featured_Products');
	register_widget('WooCommerce_Widget_Product_Categories');
	register_widget('WooCommerce_Widget_Product_Tag_Cloud');
	register_widget('WooCommerce_Widget_Cart');
	register_widget('WooCommerce_Widget_Layered_Nav');
	register_widget('WooCommerce_Widget_Price_Filter');
	register_widget('WooCommerce_Widget_Product_Search');
	register_widget('WooCommerce_Widget_Top_Rated_Products');
	register_widget('WooCommerce_Widget_Recent_Reviews');
	register_widget('WooCommerce_Widget_Recently_Viewed');
	register_widget('WooCommerce_Widget_Best_Sellers');
	register_widget('WooCommerce_Widget_On_Sale');
	register_widget('WooCommerce_Widget_Login');
	register_widget('WooCommerce_Widget_Random_Products');
}

add_action('widgets_init', 'woocommerce_register_widgets');

include_once('widget-cart.php');
include_once('widget-featured_products.php');
include_once('widget-layered_nav.php');
include_once('widget-price_filter.php');
include_once('widget-product_categories.php');
include_once('widget-product_search.php');
include_once('widget-product_tag_cloud.php');
include_once('widget-recent_products.php');
include_once('widget-top_rated_products.php');
include_once('widget-recent_reviews.php');
include_once('widget-recently_viewed.php');
include_once('widget-best_sellers.php');
include_once('widget-onsale.php');
include_once('widget-login.php');
include_once('widget-random_products.php');