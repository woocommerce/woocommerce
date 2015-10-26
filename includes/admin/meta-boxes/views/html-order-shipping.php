<?php
/**
 * Shows a shipping line
 *
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<tr class="shipping <?php echo ( ! empty( $class ) ) ? $class : ''; ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="check-column"><input type="checkbox" /></td>

	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php echo ! empty( $item['name'] ) ? wc_clean( $item['name'] ) : __( 'Shipping', 'woocommerce' ); ?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" placeholder="<?php esc_attr_e( 'Shipping Name', 'woocommerce' ); ?>" name="shipping_method_title[<?php echo $item_id; ?>]" value="<?php echo ( isset( $item['name'] ) ) ? wc_clean( $item['name'] ) : ''; ?>" />
			<select name="shipping_method[<?php echo $item_id; ?>]">
				<optgroup label="<?php esc_attr_e( 'Shipping Method', 'woocommerce' ); ?>">
					<option value=""><?php _e( 'N/A', 'woocommerce' ); ?></option>
					<?php
						$found_method = false;

						foreach ( $shipping_methods as $method ) {
							$method_id = isset( $item['method_id'] ) ? $item['method_id'] : '';
							$current_method = ( 0 === strpos( $method_id, $method->id ) ) ? $method_id : $method->id;

							echo '<option value="' . esc_attr( $current_method ) . '" ' . selected( $method_id == $current_method, true, false ) . '>' . esc_html( $method->get_title() ) . '</option>';

							if ( $method_id == $current_method ) {
								$found_method = true;
							}
						}

						if ( ! $found_method && ! empty( $method_id ) ) {
							echo '<option value="' . esc_attr( $method_id ) . '" selected="selected">' . __( 'Other', 'woocommerce' ) . '</option>';
						} else {
							echo '<option value="other">' . __( 'Other', 'woocommerce' ) . '</option>';
						}
					?>
				</optgroup>
			</select>
			<input type="hidden" name="shipping_method_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		</div>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $item, absint( $item_id ) ); ?>

	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php
				echo ( isset( $item['cost'] ) ) ? wc_price( wc_round_tax_total( $item['cost'] ), array( 'currency' => $order->get_order_currency() ) ) : '';

				if ( $refunded = $order->get_total_refunded_for_item( $item_id, 'shipping' ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" name="shipping_cost[<?php echo $item_id; ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $item['cost'] ) ) ? esc_attr( wc_format_localized_price( $item['cost'] ) ) : ''; ?>" class="line_total wc_input_price" />
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php
		if ( empty( $legacy_order ) && wc_tax_enabled() ) :
			$shipping_taxes = isset( $item['taxes'] ) ? $item['taxes'] : '';
			$tax_data       = maybe_unserialize( $shipping_taxes );

			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $tax_data[ $tax_item_id ] ) ? $tax_data[ $tax_item_id ] : '';
				?>
					<td class="line_tax" width="1%">
						<div class="view">
							<?php
								echo ( '' != $tax_item_total ) ? wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_order_currency() ) ) : '&ndash;';

								if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' ) ) {
									echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
								}
							?>
						</div>
						<div class="edit" style="display: none;">
							<input type="text" name="shipping_taxes[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>" class="line_tax wc_input_price" />
						</div>
						<div class="refund" style="display: none;">
							<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
						</div>
					</td>

				<?php
			endforeach;
		endif;
	?>

	<td class="wc-order-edit-line-item">
		<?php if ( $order->is_editable() ) : ?>
			<div class="wc-order-edit-line-item-actions">
				<a class="edit-order-item" href="#"></a><a class="delete-order-item" href="#"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
