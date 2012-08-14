<?php
/**
 * WC_Product_Cat_Dropdown_Walker class.
 *
 * @extends 	Walker
 * @class 		WC_Product_Cat_Dropdown_Walker
 * @version		1.6.4
 * @package		WooCommerce/Classes/Walkers
 * @author 		WooThemes
 */
class WC_Product_Cat_Dropdown_Walker extends Walker {

	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id', 'slug' => 'slug' );

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int $depth Depth of category in reference to parents.
	 * @param array $args
	 */
	function start_el(&$output, $cat, $depth, $args) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters( 'list_product_cats', $cat->name, $cat );
		$output .= "\t<option class=\"level-$depth\" value=\"" . $cat->slug . "\"";

		if ( $cat->slug == $args['selected'] )
			$output .= ' selected="selected"';

		$output .= '>';

		$output .= $pad . __( $cat_name, 'woocommerce' );

		if ( $args['show_count'] )
			$output .= '&nbsp;(' . $cat->count . ')';

		$output .= "</option>\n";
	}
}