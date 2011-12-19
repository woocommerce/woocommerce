<?php global $product; ?>

<?php if ($product->has_attributes()) : ?>
	<li><a href="#tab-attributes"><?php _e('Additional Information', 'woothemes'); ?></a></li>
<?php endif; ?>