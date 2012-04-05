<?php global $product;

if ( get_option('woocommerce_enable_dimension_product_attributes') == 'yes' ) {
	if ( $product->has_attributes() || $product->has_dimensions() || $product->has_weight() ) {
		?>
		<li><a href="#tab-attributes"><?php _e('Additional Information', 'woocommerce'); ?></a></li>
		<?php
	}
}
?>