<?php
/**
 * Product Images
 *
 * Display the product images meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce\Admin\Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Product_Images Class.
 */
class WC_Meta_Box_Product_Categories {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post, $box ) {
		$defaults = array( 'taxonomy' => 'category' );
		if ( ! isset( $box['args'] ) || ! is_array( $box['args'] ) ) {
			$args = array();
		} else {
			$args = $box['args'];
		}
		$parsed_args         = wp_parse_args( $args, $defaults );
		$tax_name            = esc_attr( $parsed_args['taxonomy'] );
		$selected_categories = wp_get_object_terms( $post->ID, 'product_cat' );
		?>
		<div id="taxonomy-<?php echo $tax_name; ?>-metabox"></div>
		<? foreach ( (array) $selected_categories as $term ) { ?>
			<input
				type="hidden"
				value="<?php echo $term->term_id; ?>"
				name="tax_input[<?php echo $tax_name; ?>][]"
				data-name="<?php echo $term->name; ?>"
			/>
		<?php
	}
}
}
