<?php
/**
 * Attributes Tab
 */
 
global $woocommerce, $post, $product;
?>
<div class="panel" id="tab-attributes">

	<?php $heading = apply_filters('woocommerce_product_additional_information_heading', __('Additional Information', 'woocommerce')); ?>
	
	<h2><?php echo $heading; ?></h2>
	
	<?php $product->list_attributes(); ?>

</div>