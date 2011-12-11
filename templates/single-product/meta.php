<?php
/**
 * Single Product Meta
 */

global $post, $product;
?>
<div class="product_meta">

	<?php if ($product->is_type('simple') && get_option('woocommerce_enable_sku')=='yes') : ?>
		<span itemprop="productID" class="sku"><?php _e('SKU:', 'woothemes'); ?> <?php echo $product->sku; ?>.</span>
	<?php endif; ?>
	
	<?php echo $product->get_categories( ', ', ' <span class="posted_in">'.__('Category:', 'woothemes').' ', '.</span>'); ?>
	
	<?php echo $product->get_tags( ', ', ' <span class="tagged_as">'.__('Tags:', 'woothemes').' ', '.</span>'); ?>

</div>