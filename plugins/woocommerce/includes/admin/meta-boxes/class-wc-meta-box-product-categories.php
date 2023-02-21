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
		$parsed_args = wp_parse_args( $args, $defaults );
		$tax_name    = esc_attr( $parsed_args['taxonomy'] );
		$taxonomy    = get_taxonomy( $parsed_args['taxonomy'] );
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
		<?php } ?>
		<div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
			<?php if ( current_user_can( $taxonomy->cap->edit_terms ) ) : ?>
				<div id="<?php echo $tax_name; ?>-adder" class="wp-hidden-children">
					<a id="<?php echo $tax_name; ?>-add-toggle" href="#<?php echo $tax_name; ?>-add" class="hide-if-no-js taxonomy-add-new">
						<?php
						/* translators: %s: Add New taxonomy label. */
						printf( __( '+ %s' ), $taxonomy->labels->add_new_item );
						?>
					</a>
					<p id="<?php echo $tax_name; ?>-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="new<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
						<input type="text" name="new<?php echo $tax_name; ?>" id="new<?php echo $tax_name; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $taxonomy->labels->new_item_name ); ?>" aria-required="true" />
						<label class="screen-reader-text" for="new<?php echo $tax_name; ?>_parent">
							<?php echo $taxonomy->labels->parent_item_colon; ?>
						</label>
						<?php
						$parent_dropdown_args = array(
							'taxonomy'         => $tax_name,
							'hide_empty'       => 0,
							'name'             => 'new' . $tax_name . '_parent',
							'orderby'          => 'name',
							'hierarchical'     => 1,
							'show_option_none' => '&mdash; ' . $taxonomy->labels->parent_item . ' &mdash;',
						);

						/**
						 * Filters the arguments for the taxonomy parent dropdown on the Post Edit page.
						 *
						 * @since 4.4.0
						 *
						 * @param array $parent_dropdown_args {
						 *     Optional. Array of arguments to generate parent dropdown.
						 *
						 *     @type string   $taxonomy         Name of the taxonomy to retrieve.
						 *     @type bool     $hide_if_empty    True to skip generating markup if no
						 *                                      categories are found. Default 0.
						 *     @type string   $name             Value for the 'name' attribute
						 *                                      of the select element.
						 *                                      Default "new{$tax_name}_parent".
						 *     @type string   $orderby          Which column to use for ordering
						 *                                      terms. Default 'name'.
						 *     @type bool|int $hierarchical     Whether to traverse the taxonomy
						 *                                      hierarchy. Default 1.
						 *     @type string   $show_option_none Text to display for the "none" option.
						 *                                      Default "&mdash; {$parent} &mdash;",
						 *                                      where `$parent` is 'parent_item'
						 *                                      taxonomy label.
						 * }
						 */
						$parent_dropdown_args = apply_filters( 'post_edit_category_parent_dropdown_args', $parent_dropdown_args );

						wp_dropdown_categories( $parent_dropdown_args );
						?>
						<input type="button" id="<?php echo $tax_name; ?>-add-submit" data-wp-lists="add:<?php echo $tax_name; ?>checklist:<?php echo $tax_name; ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>" />
						<?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
						<span id="<?php echo $tax_name; ?>-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
