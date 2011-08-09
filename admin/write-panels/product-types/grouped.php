<?php
/**
 * Grouped Product Type
 * 
 * Functions specific to grouped products (for the write panels)
 *
 * @author 		Jigowatt
 * @category 	Admin Write Panel Product Types
 * @package 	JigoShop
 */
  
/**
 * Product Options
 * 
 * Product Options for the grouped product type
 *
 * @since 		1.0
 */
function grouped_product_type_options() {
	?>
	<div id="grouped_product_options">
		<?php
			_e('Grouped products have no specific options &mdash; you can add simple products to this grouped product by editing them and setting their <code>parent product</code> option.', 'jigoshop');
		?>
	</div>
	<?php
}
add_action('jigoshop_product_type_options_box', 'grouped_product_type_options');

/**
 * Product Type selector
 * 
 * Adds this product type to the product type selector in the product options meta box
 *
 * @since 		1.0
 *
 * @param 		string $product_type Passed the current product type so that if it keeps its selected state
 */
function grouped_product_type_selector( $product_type ) {
	
	echo '<option value="grouped" '; if ($product_type=='grouped') echo 'selected="selected"'; echo '>'.__('Grouped','jigoshop').'</option>';

}
add_action('product_type_selector', 'grouped_product_type_selector');