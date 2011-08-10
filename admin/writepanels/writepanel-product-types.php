<?php
/**
 * Product Type
 * 
 * Function for displaying the product type meta (specific) meta boxes
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */

include_once('writepanel-product-type-downloadable.php');
include_once('writepanel-product-type-variable.php');

/**
 * Product type meta box
 * 
 * Display the product type meta box which contains a hook for product types to hook into and show their options
 *
 * @since 		1.0
 */
function woocommerce_product_type_options_box() {

	global $post;
	?>
	<div id="simple_product_options" class="panel woocommerce_options_panel">
		<?php
			_e('Simple products have no specific options.', 'woothemes');
		?>
	</div>
	<?php 
	do_action('woocommerce_product_type_options_box');
}

/**
 * Virtual Product Type - Product Options
 * 
 * Product Options for the virtual product type
 */
function virtual_product_type_options() {
	?>
	<div id="virtual_product_options">
		<?php
			_e('Virtual products have no specific options.', 'woothemes');
		?>
	</div>
	<?php
}
add_action('woocommerce_product_type_options_box', 'virtual_product_type_options');

/**
 * Grouped Product Type - Product Options
 * 
 * Product Options for the grouped product type
 *
 * @since 		1.0
 */
function grouped_product_type_options() {
	?>
	<div id="grouped_product_options">
		<?php
			_e('Grouped products have no specific options &mdash; you can add simple products to this grouped product by editing them and setting their <code>parent product</code> option.', 'woothemes');
		?>
	</div>
	<?php
}
add_action('woocommerce_product_type_options_box', 'grouped_product_type_options');


/**
 * Product Type selectors
 * 
 * Adds a product type to the product type selector in the product options meta box
 */
add_action('product_type_selector', 'virtual_product_type_selector');
add_action('product_type_selector', 'grouped_product_type_selector');

function virtual_product_type_selector( $product_type ) {
	echo '<option value="virtual" '; if ($product_type=='virtual') echo 'selected="selected"'; echo '>'.__('Virtual', 'woothemes').'</option>';
}

function grouped_product_type_selector( $product_type ) {
	echo '<option value="grouped" '; if ($product_type=='grouped') echo 'selected="selected"'; echo '>'.__('Grouped', 'woothemes').'</option>';
}
