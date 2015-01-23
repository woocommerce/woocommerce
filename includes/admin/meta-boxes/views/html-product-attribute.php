<div data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" class="woocommerce_attribute wc-metabox closed <?php echo esc_attr( implode( ' ', $metabox_class ) ); ?>" rel="<?php echo $position; ?>">
	<h3>
		<button type="button" class="remove_row button"><?php _e( 'Remove', 'woocommerce' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<strong class="attribute_name"><?php echo esc_html( $attribute_label ); ?></strong>
	</h3>
	<div class="woocommerce_attribute_data wc-metabox-content">
		<table cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<td class="attribute_name">
						<label><?php _e( 'Name', 'woocommerce' ); ?>:</label>

						<?php if ( $attribute['is_taxonomy'] ) : ?>
							<strong><?php echo esc_html( $attribute_label ); ?></strong>
							<input type="hidden" name="attribute_names[<?php echo $i; ?>]" value="<?php echo esc_attr( $taxonomy ); ?>" />
						<?php else : ?>
							<input type="text" class="attribute_name" name="attribute_names[<?php echo $i; ?>]" value="<?php echo esc_attr( $attribute['name'] ); ?>" />
						<?php endif; ?>

						<input type="hidden" name="attribute_position[<?php echo $i; ?>]" class="attribute_position" value="<?php echo esc_attr( $position ); ?>" />
						<input type="hidden" name="attribute_is_taxonomy[<?php echo $i; ?>]" value="<?php echo $attribute['is_taxonomy'] ? 1 : 0; ?>" />
					</td>
					<td rowspan="3">
						<label><?php _e( 'Value(s)', 'woocommerce' ); ?>:</label>

						<?php if ( $attribute['is_taxonomy'] ) : ?>
							<?php if ( 'select' === $attribute_taxonomy->attribute_type ) : ?>

								<select multiple="multiple" data-placeholder="<?php _e( 'Select terms', 'woocommerce' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo $i; ?>][]">
									<?php
									$all_terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
									if ( $all_terms ) {
										foreach ( $all_terms as $term ) {
											echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( has_term( absint( $term->term_id ), $taxonomy, $thepostid ), true, false ) . '>' . $term->name . '</option>';
										}
									}
									?>
								</select>
								<button class="button plus select_all_attributes"><?php _e( 'Select all', 'woocommerce' ); ?></button>
								<button class="button minus select_no_attributes"><?php _e( 'Select none', 'woocommerce' ); ?></button>
								<button class="button fr plus add_new_attribute"><?php _e( 'Add new', 'woocommerce' ); ?></button>

							<?php elseif ( 'text' == $attribute_taxonomy->attribute_type ) : ?>

								<input type="text" name="attribute_values[<?php echo $i; ?>]" value="<?php

									// Text attributes should list terms pipe separated
									echo esc_attr( implode( ' ' . WC_DELIMITER . ' ', wp_get_post_terms( $thepostid, $taxonomy, array( 'fields' => 'names' ) ) ) );

								?>" placeholder="<?php echo esc_attr( sprintf( __( '"%s" separate terms', 'woocommerce' ), WC_DELIMITER ) ); ?>" />

							<?php endif; ?>

							<?php do_action( 'woocommerce_product_option_terms', $attribute_taxonomy, $i ); ?>

						<?php else : ?>

							<textarea name="attribute_values[<?php echo $i; ?>]" cols="5" rows="5" placeholder="<?php echo esc_attr( sprintf( __( 'Enter some text, or some attributes by "%s" separating values.', 'woocommerce' ), WC_DELIMITER ) ); ?>"><?php echo esc_textarea( $attribute['value'] ); ?></textarea>

						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td>
						<label><input type="checkbox" class="checkbox" <?php checked( $attribute['is_visible'], 1 ); ?> name="attribute_visibility[<?php echo $i; ?>]" value="1" /> <?php _e( 'Visible on the product page', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<div class="enable_variation show_if_variable">
							<label><input type="checkbox" class="checkbox" <?php checked( $attribute['is_variation'], 1 ); ?> name="attribute_variation[<?php echo $i; ?>]" value="1" /> <?php _e( 'Used for variations', 'woocommerce' ); ?></label>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>