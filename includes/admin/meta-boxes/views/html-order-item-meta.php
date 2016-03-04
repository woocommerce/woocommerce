<div class="view">
	<?php
		global $wpdb;

		if ( $metadata = $item->get_meta_data() ) {
			echo '<table cellspacing="0" class="display_meta">';
			foreach ( $metadata as $meta_id => $meta ) {

				// Skip hidden core fields
				if ( in_array( $meta->key, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'total_tax',
					'method_id',
					'cost'
				) ) ) ) {
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $meta->value ) ) {
					continue;
				}

				// Get attribute data
				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta->key ) ) ) {
					$term               = get_term_by( 'slug', $meta->value, wc_sanitize_taxonomy_name( $meta->key ) );
					$meta->key   = wc_attribute_label( wc_sanitize_taxonomy_name( $meta->key ) );
					$meta->value = isset( $term->name ) ? $term->name : $meta->value;
				} else {
					$meta->key   = apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $meta->key, $_product ), $meta->key );
				}

				echo '<tr><th>' . wp_kses_post( rawurldecode( $meta->key ) ) . ':</th><td>' . wp_kses_post( wpautop( make_clickable( rawurldecode( $meta->value ) ) ) ) . '</td></tr>';
			}
			echo '</table>';
		}
	?>
</div>
<div class="edit" style="display: none;">
	<table class="meta" cellspacing="0">
		<tbody class="meta_items">
		<?php
			if ( $metadata = $item->get_meta_data() ) {
				foreach ( $metadata as $meta_id => $meta ) {

					// Skip hidden core fields
					if ( in_array( $meta->key, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
						'_qty',
						'_tax_class',
						'_product_id',
						'_variation_id',
						'_line_subtotal',
						'_line_subtotal_tax',
						'_line_total',
						'_line_tax',
						'total_tax',
						'method_id',
						'cost'
					) ) ) ) {
						continue;
					}

					// Skip serialised meta
					if ( is_serialized( $meta->value ) ) {
						continue;
					}

					$meta->key   = rawurldecode( $meta->key );
					$meta->value = esc_textarea( rawurldecode( $meta->value ) ); // using a <textarea />

					echo '<tr data-meta_id="' . esc_attr( $meta_id ) . '">
						<td>
							<input type="text" name="key[' . esc_attr( $item->get_id() ) . '][' . esc_attr( $meta_id ) . ']" value="' . esc_attr( $meta->key ) . '" />
							<textarea name="value[' . esc_attr( $item->get_id() ) . '][' . esc_attr( $meta_id ) . ']">' . $meta->value . '</textarea>
						</td>
						<td width="1%"><button class="remove_order_item_meta button">&times;</button></td>
					</tr>';
				}
			}
		?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="4"><button class="add_order_item_meta button"><?php _e( 'Add&nbsp;meta', 'woocommerce' ); ?></button></td>
			</tr>
		</tfoot>
	</table>
</div>
