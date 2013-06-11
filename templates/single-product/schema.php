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

$schema = "Product"; // Default schema for product.

/* If the product is downloadable, what type of downloadable product is it. */
if($product->is_downloadable()){
	$download_type = get_post_meta($post->ID, '_download_type', true);
	if($download_type == 'application'){
		$schema = "SoftwareApplication";
	}
	else if($download_type == 'music'){
		$schema = "MusicAlbum";
	}
	// Not sure if this last bit is needed so I disabled it for now.
	/*else if($download_type == 'default' || $download_type == ''){
		$schema = "Product";
	}*/
}
?>
<div itemscope itemtype="http://schema.org/<?php echo $schema; ?>" id="product-<?php echo $post->ID; ?>" <?php post_class(); ?>>
