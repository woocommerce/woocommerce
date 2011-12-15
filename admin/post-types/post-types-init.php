<?php
/**
 * WooCommerce Post Types Init
 * 
 * Load post types panels
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */
global $typenow;

include_once( 'writepanels/writepanels-init.php' );	
		
if ($typenow=='post' && isset($_GET['post']) && !empty($_GET['post'])) :
	$typenow = $post->post_type;
elseif (empty($typenow) && !empty($_GET['post'])) :
    $post = get_post($_GET['post']);
    $typenow = $post->post_type;
endif;

if ( $typenow=='product' ) :
	include_once('product.php');
	include_once('writepanels/writepanel-product_data.php');
elseif ( $typenow=='shop_coupon' ) :
	include_once('shop_coupon.php');
	include_once('writepanels/writepanel-coupon_data.php');
elseif ( $typenow=='shop_order' ) :
	include_once('shop_order.php');
	include_once('writepanels/writepanel-order_data.php');
	include_once('writepanels/writepanel-order_notes.php');
endif;