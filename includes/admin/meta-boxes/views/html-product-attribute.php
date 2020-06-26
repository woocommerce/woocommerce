<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div data-taxonomy="<?php echo esc_attr( $attribute->get_taxonomy() ); ?>" class="woocommerce_attribute wc-metabox closed <?php echo esc_attr( implode( ' ', $metabox_class ) ); ?>" rel="<?php echo esc_attr( $attribute->get_position() ); ?>">
	<h3>
		<a href="#" class="remove_row delete"><?php esc_html_e( 'Remove', 'woocommerce' ); ?></a>
		<div class="handlediv" title="<?php esc_attr_e( 'Click to toggle', 'woocommerce' ); ?>"></div>
		<div class="tips sort" data-tip="<?php esc_attr_e( 'Drag and drop to set admin attribute order', 'woocommerce' ); ?>"></div>			
		<strong class="attribute_name"><?php echo wc_attribute_label( $attribute->get_name() ); ?></strong>
	</h3>
	<div class="woocommerce_attribute_data wc-metabox-content hidden">
		<table cellpadding="0" cellspacing="0">
			<tbody>
				<tr>
					<td class="attribute_name">
						<label><?php esc_html_e( 'Name', 'woocommerce' ); ?>:</label>

						<?php if ( $attribute->is_taxonomy() ) : ?>
							<strong><?php echo wc_attribute_label( $attribute->get_name() ); ?></strong>
							<input type="hidden" name="attribute_names[<?php echo esc_attr( $i ); ?>]" value="<?php echo esc_attr( $attribute->get_name() ); ?>" />
						<?php else : ?>
							<input type="text" class="attribute_name" name="attribute_names[<?php echo esc_attr( $i ); ?>]" value="<?php echo esc_attr( $attribute->get_name() ); ?>" />
						<?php endif; ?>

						<input type="hidden" name="attribute_position[<?php echo esc_attr( $i ); ?>]" class="attribute_position" value="<?php echo esc_attr( $attribute->get_position() ); ?>" />
					</td>
					<td rowspan="3">
						<label><?php esc_html_e( 'Value(s)', 'woocommerce' ); ?>:</label>
						<?php
						if ( $attribute->is_taxonomy() && $attribute_taxonomy = $attribute->get_taxonomy_object() ) {
							$attribute_types = wc_get_attribute_types();

							if ( ! array_key_exists( $attribute_taxonomy->attribute_type, $attribute_types ) ) {
								$attribute_taxonomy->attribute_type = 'select';
							}

							if ( 'select' === $attribute_taxonomy->attribute_type ) {
								?>
								<select multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select terms', 'woocommerce' ); ?>" class="multiselect attribute_values wc-enhanced-select" name="attribute_values[<?php echo esc_attr( $i ); ?>][]">
									<?php
									$args      = array(
										'orderby'    => ! empty( $attribute_taxonomy->attribute_orderby ) ? $attribute_taxonomy->attribute_orderby : 'name',
										'hide_empty' => 0,
									);
									$all_terms = get_terms( $attribute->get_taxonomy(), apply_filters( 'woocommerce_product_attribute_terms', $args ) );
									if ( $all_terms ) {
										foreach ( $all_terms as $term ) {
											$options = $attribute->get_options();
											$options = ! empty( $options ) ? $options : array();
											echo '<option value="' . esc_attr( $term->term_id ) . '"' . wc_selected( $term->term_id, $options ) . '>' . esc_html( apply_filters( 'woocommerce_product_attribute_term_name', $term->name, $term ) ) . '</option>';
										}
									}
									?>
								</select>
								<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></button>
								<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></button>
								<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Add new', 'woocommerce' ); ?></button>
								<?php
							}

							do_action( 'woocommerce_product_option_terms', $attribute_taxonomy, $i, $attribute );
						} else {
							/* translators: %s: WC_DELIMITER */
							?>
							<textarea name="attribute_values[<?php echo esc_attr( $i ); ?>]" cols="5" rows="5" placeholder="<?php printf( esc_attr__( 'Enter some text, or some attributes by "%s" separating values.', 'woocommerce' ), WC_DELIMITER ); ?>"><?php echo esc_textarea( wc_implode_text_attributes( $attribute->get_options() ) ); ?></textarea>
							<?php
						}
						?>
					</td>
				</tr>
				<tr>
					<td>
						<label><input type="checkbox" class="checkbox" <?php checked( $attribute->get_visible(), true ); ?> name="attribute_visibility[<?php echo esc_attr( $i ); ?>]" value="1" /> <?php esc_html_e( 'Visible on the product page', 'woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<div class="enable_variation show_if_variable">
							<label><input type="checkbox" class="checkbox" <?php checked( $attribute->get_variation(), true ); ?> name="attribute_variation[<?php echo esc_attr( $i ); ?>]" value="1" /> <?php esc_html_e( 'Used for variations', 'woocommerce' ); ?></label>
						</div>
					</td>
				</tr>
				<?php do_action( 'woocommerce_after_product_attribute_settings', $attribute, $i ); ?>
			</tbody>
		</table>
	</div>
</div>
