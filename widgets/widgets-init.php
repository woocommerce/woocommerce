<?php
/**
 * Widgets init
 * 
 * Init the widgets.
 *
 * @package		WooCommerce
 * @category	Widgets
 * @author		WooThemes
 */
 
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

function woocommerce_register_widgets() {
	register_widget('WooCommerce_Widget_Recent_Products');
	register_widget('WooCommerce_Widget_Featured_Products');
	register_widget('WooCommerce_Widget_Product_Categories');
	register_widget('WooCommerce_Widget_Tag_Cloud');
	register_widget('WooCommerce_Widget_Cart');
	register_widget('WooCommerce_Widget_Layered_Nav');
	register_widget('WooCommerce_Widget_Price_Filter');
	register_widget('WooCommerce_Widget_Product_Search');
	register_widget('WooCommerce_Widget_Top_Rated_Products');
	register_widget('WooCommerce_Widget_Recent_Reviews');
}
add_action('widgets_init', 'woocommerce_register_widgets');