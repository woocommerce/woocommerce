<?php
/**
 * Attributes Tab
 */
 
global $woocommerce, $post, $product;

if ($product->has_attributes() || $product->has_dimensions() || $product->has_weight()) : ?>
	<div class="panel" id="tab-attributes">
	
		<?php $heading = apply_filters('woocommerce_product_additional_information_heading', __('Additional Information', 'woocommerce')); ?>
		
		<h2><?php echo $heading; ?></h2>
		
		<?php $product->list_attributes(); ?>
	
	</div>
<?php endif; ?>