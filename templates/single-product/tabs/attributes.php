<?php
/**
 * Attributes Tab
 */
 
global $woocommerce, $post, $_product;
?>
<div class="panel" id="tab-attributes">

	<?php $heading = apply_filters('woocommerce_product_additional_information_heading', __('Additional Information', 'woothemes')); ?>
	
	<h2><?php echo $heading; ?></h2>
	
	<?php $_product->list_attributes(); ?>

</div>