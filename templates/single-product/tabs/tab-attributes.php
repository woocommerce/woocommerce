<?php global $product;

if ($product->has_attributes() || $product->has_dimensions() || $product->has_weight()) : ?>
	<li><a href="#tab-attributes"><?php _e('Additional Information', 'woocommerce'); ?></a></li>
<?php endif; ?>