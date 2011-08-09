<?php
/**
 * Virtual Product Type
 * 
 * Functions specific to virtual products (for the write panels)
 *
 * @author 		Jigowatt
 * @category 	Admin Write Panel Product Types
 * @package 	JigoShop
 */
  
/**
 * Product Options
 * 
 * Product Options for the virtual product type
 *
 * @since 		1.0
 */
function virtual_product_type_options() {
	?>
	<div id="virtual_product_options">
		<?php
			_e('Virtual products have no specific options.', 'jigoshop');
		?>
	</div>
	<?php
}
add_action('jigoshop_product_type_options_box', 'virtual_product_type_options');

/**
 * Product Type selector
 * 
 * Adds this product type to the product type selector in the product options meta box
 *
 * @since 		1.0
 *
 * @param 		string $product_type Passed the current product type so that if it keeps its selected state
 */
function virtual_product_type_selector( $product_type ) {
	
	echo '<option value="virtual" '; if ($product_type=='virtual') echo 'selected="selected"'; echo '>'.__('Virtual','jigoshop').'</option>';

}
add_action('product_type_selector', 'virtual_product_type_selector');