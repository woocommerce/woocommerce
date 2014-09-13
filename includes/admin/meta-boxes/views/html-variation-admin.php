<?php ( ! defined( 'ABSPATH' ) && exit ); // Exit if accessed directly ?>

<div class="woocommerce_variation wc-metabox closed">
	<h3>
		<button type="button" class="remove_variation button" rel="<?php echo esc_attr( $variation_id ); ?>"><?php _e( 'Remove', 'woocommerce' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong>#<?php echo esc_html( $variation_id ); ?> &mdash; </strong>
		<?php
			foreach ( $parent_data['attributes'] as $attribute ) {

				// Only deal with attributes that are variations
				if ( ! $attribute['is_variation'] ) {
					continue;
				}

				// Get current value for variation (if set)
				$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] : '';

				// Name will be something like attribute_pa_color
				echo '<select class="wc_variation_attribute" data-wc_attribute_name="' . sanitize_title( $attribute['name'] ) . '" placeholder="' . esc_attr( $attribute['placeholder'] ) . '" name="attribute_' . sanitize_title( $attribute['name'] ) . '[' . $loop . ']">';

				if ( $attribute['is_taxonomy'] ){
					foreach( $attribute['post_terms'] as $term ){
						if ( $term->slug != $variation_selected_value ){
							continue;
						}
						echo '<option selected value="' . $variation_selected_value . '">' . esc_attr( $term->name ) . '</option>';
					}
				} else {
					echo '<option selected value="' . $variation_selected_value . '">' . esc_attr( $option ) . '</option>';
				}
				// Dont Forget about `woocommerce_variation_option_name`

				echo '</select>';
			}
		?>
		<input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
		<input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="<?php echo $loop; ?>" />
	</h3>

	<?php include 'html-variation-admin-form.php'; ?>
</div>
