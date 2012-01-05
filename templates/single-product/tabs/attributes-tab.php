<?php global $product; ?>

<?php if ($product->has_attributes()) : ?>
	<li><a href="#tab-attributes"><?php _e('Additional Information', 'woocommerce'); ?></a></li>
<?php endif; ?>