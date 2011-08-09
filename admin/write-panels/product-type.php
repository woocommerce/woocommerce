<?php
/**
 * Product Type
 * 
 * Function for displaying the product type meta (specific) meta boxes
 *
 * @author 		Jigowatt
 * @category 	Admin Write Panels
 * @package 	JigoShop
 */

foreach(glob( dirname(__FILE__)."/product-types/*.php" ) as $filename) include_once($filename);

/**
 * Product type meta box
 * 
 * Display the product type meta box which contains a hook for product types to hook into and show their options
 *
 * @since 		1.0
 */
function jigoshop_product_type_options_box() {

	global $post;
	?>
	<div id="simple_product_options" class="panel jigoshop_options_panel">
		<?php
			_e('Simple products have no specific options.', 'jigoshop');
		?>
	</div>
	<?php 
	do_action('jigoshop_product_type_options_box');
}