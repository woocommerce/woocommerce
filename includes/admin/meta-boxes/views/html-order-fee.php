<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="fee <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="check-column"><input type="checkbox" /></td>

	<td class="thumb"></td>

	<td class="name">
		<div class="view">
			<?php echo ! empty( $item['name'] ) ? esc_html( $item['name'] ) : __( 'Fee', 'woocommerce' ); ?>
		</div>
		<div class="edit" style="display:none">
			<input type="text" placeholder="<?php _e( 'Fee Name', 'woocommerce' ); ?>" name="order_item_name[<?php echo absint( $item_id ); ?>]" value="<?php echo ( isset( $item['name'] ) ) ? esc_attr( $item['name'] ) : ''; ?>" />
			<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		</div>
	</td>

	<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) :
		$tax_classes         = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
		$classes_options     = array();
		$classes_options[''] = __( 'Standard', 'woocommerce' );
		$tax_class           = isset( $item['tax_class'] ) ? sanitize_title( $item['tax_class'] ) : '';

		if ( $tax_classes ) {
			foreach ( $tax_classes as $class ) {
				$classes_options[ sanitize_title( $class ) ] = $class;
			}
		}
	?>
	<td class="tax_class" width="1%">
		<div class="view">
			<?php
				$item_value = isset( $classes_options[ $tax_class ] ) ? esc_attr( $classes_options[ $tax_class ] ) : '';
				echo $item_value ? $item_value : __( 'Standard', 'woocommerce' );
			?>
		</div>
		<div class="edit" style="display:none">
			<select class="tax_class" name="order_item_tax_class[<?php echo absint( $item_id ); ?>]" title="<?php _e( 'Tax class', 'woocommerce' ); ?>">
				<option value="0" <?php selected( 0, $tax_class ) ?>><?php _e( 'N/A', 'woocommerce' ); ?></option>
				<optgroup label="<?php _e( 'Taxable', 'woocommerce' ); ?>">
					<?php
						foreach ( $classes_options as $value => $name ) {
							echo '<option value="' . esc_attr( $value ) . '" ' . selected( $value, $tax_class, false ) . '>'. esc_html( $name ) . '</option>';
						}
					?>
				</optgroup>
			</select>
		</div>
	</td>

	<?php endif; ?>

	<td class="quantity" width="1%">1</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php echo ( isset( $item['line_total'] ) ) ? wc_price( wc_round_tax_total( $item['line_total'] ) ) : ''; ?>
		</div>
		<div class="edit" style="display:none">
			<label><?php _e( 'Total', 'woocommerce' ); ?>: <input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $item['line_total'] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) ) : ''; ?>" class="line_total wc_input_price" /></label>
		</div>
	</td>

	<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>

	<td class="line_tax" width="1%">
		<div class="view">
			<?php echo ( isset( $item['line_tax'] ) ) ? wc_price( wc_round_tax_total( $item['line_tax'] ) ) : ''; ?>
		</div>
		<div class="edit" style="display:none">
			<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $item['line_tax'] ) ) ? esc_attr( wc_format_localized_price( $item['line_tax'] ) ) : ''; ?>" class="line_tax wc_input_price" />
		</div>
	</td>

	<?php endif; ?>

	<td>
		<a class="edit_order_item" href="#"><img src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/edit.png" alt="<?php _e( 'Edit', 'woocommerce' ) ?>" width="14" /></a>
	</td>

</tr>
