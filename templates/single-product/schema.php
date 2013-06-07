<?php
/**
 * Identifies the product type and outputs the 
 * correct Schema for the beginning of the 
 * product container.
 * 
 * @author: 	Seb's Studio
 * @package 	WooCommerce/Templates
 * @version 	2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $product;

/* If the product is not downloadable. */
if(!$product->is_downloadable()){
?>
	<div itemscope itemtype="http://schema.org/Product" id="product-<?php echo $post->ID; ?>" <?php post_class(); ?>>
<?php
}
/* If the product is downloadable, what type of downloadable product is it. */
else{
	if(get_post_meta($post->ID, '_download_type', true) == 'application'){
?>
		<div itemscope itemtype="http://schema.org/SoftwareApplication" id="product-<?php echo $post->ID; ?>" <?php post_class(); ?>>
<?php
	}
	else if(get_post_meta($post->ID, '_download_type', true) == 'music'){
?>
		<div itemscope itemtype="http://schema.org/MusicAlbum" id="product-<?php echo $post->ID; ?>" <?php post_class(); ?>>
<?php
	}
}
?>
