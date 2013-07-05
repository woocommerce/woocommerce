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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Cat_Dropdown_Walker extends Walker {

	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id', 'slug' => 'slug' );

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $cat Category data object.
	 * @param int $depth Depth of category in reference to parents.
	 * @param array $args
	 * @param int $current_object_id
	 */
	function start_el( &$output, $cat, $depth, $args, $current_object_id = 0 ) {

		if ( ! empty( $args['hierarchical'] ) )
			$pad = str_repeat('&nbsp;', $depth * 3);
		else
			$pad = '';

		$cat_name = apply_filters( 'list_product_cats', $cat->name, $cat );

		$value = isset( $args['value'] ) && $args['value'] == 'id' ? $cat->term_id : $cat->slug;

		$output .= "\t<option class=\"level-$depth\" value=\"" . $value . "\"";

		if ( $value == $args['selected'] || ( is_array( $args['selected'] ) && in_array( $value, $args['selected'] ) ) )
			$output .= ' selected="selected"';

		$output .= '>';

		$output .= $pad . __( $cat_name, 'woocommerce' );

		if ( ! empty( $args['show_count'] ) )
			$output .= '&nbsp;(' . $cat->count . ')';

		$output .= "</option>\n";
	}
}