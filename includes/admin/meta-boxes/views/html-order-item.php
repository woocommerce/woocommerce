<?php
/**
 * Shows an order item
 *
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$product_link  = $_product ? admin_url( 'post.php?post=' . absint( $_product->id ) . '&action=edit' ) : '';
$thumbnail     = $_product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $_product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
$tax_data      = empty( $legacy_order ) && wc_tax_enabled() ? maybe_unserialize( isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '' ) : false;
$item_total    = ( isset( $item['line_total'] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) ) : '';
$item_subtotal = ( isset( $item['line_subtotal'] ) ) ? esc_attr( wc_format_localized_price( $item['line_subtotal'] ) ) : '';
?>
<tr class="item <?php echo apply_filters( 'woocommerce_admin_html_order_item_class', ( ! empty( $class ) ? $class : '' ), $item, $order ); ?>" data-order_item_id="<?php echo $item_id; ?>">
	<td class="thumb">
		<?php
			echo '<div class="wc-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>';
		?>
	</td>
	<td class="name" data-sort-value="<?php echo esc_attr( $item['name'] ); ?>">
		<?php
			echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-name">' .  esc_html( $item['name'] ) . '</a>' : '<div class="class="wc-order-item-name"">' . esc_html( $item['name'] ) . '</div>';

			if ( $_product && $_product->get_sku() ) {
				echo '<div class="wc-order-item-sku"><strong>' . __( 'SKU:', 'woocommerce' ) . '</strong> ' . esc_html( $_product->get_sku() ) . '</div>';
			}

			if ( ! empty( $item['variation_id'] ) ) {
				echo '<div class="wc-order-item-variation"><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong> ';
				if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
					echo esc_html( $item['variation_id'] );
				} elseif ( ! empty( $item['variation_id'] ) ) {
					echo esc_html( $item['variation_id'] ) . ' (' . __( 'No longer exists', 'woocommerce' ) . ')';
				}
				echo '</div>';
			}
		?>
		<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
		<input type="hidden" name="order_item_tax_class[<?php echo absint( $item_id ); ?>]" value="<?php echo isset( $item['tax_class'] ) ? esc_attr( $item['tax_class'] ) : ''; ?>" />

		<?php do_action( 'woocommerce_before_order_itemmeta', $item_id, $item, $_product ) ?>
		<?php include( 'html-order-item-meta.php' ); ?>
		<?php do_action( 'woocommerce_after_order_itemmeta', $item_id, $item, $_product ) ?>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', $_product, $item, absint( $item_id ) ); ?>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) ); ?>">
		<div class="view">
			<?php
				if ( isset( $item['line_total'] ) ) {
					echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_order_currency() ) );

					if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
						echo '<span class="wc-order-item-discount">-' . wc_price( wc_format_decimal( $order->get_item_subtotal( $item, false, false ) - $order->get_item_total( $item, false, false ), '' ), array( 'currency' => $order->get_order_currency() ) ) . '</span>';
					}
				}
			?>
		</div>
	</td>
	<td class="quantity" width="1%">
		<div class="view">
			<?php
				echo '<small class="times">&times;</small> ' . ( isset( $item['qty'] ) ? esc_html( $item['qty'] ) : '1' );

				if ( $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">' . ( $refunded_qty * -1 ) . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<?php $item_qty = esc_attr( $item['qty'] ); ?>
			<input type="number" step="<?php echo apply_filters( 'woocommerce_quantity_input_step', '1', $_product ); ?>" min="0" autocomplete="off" name="order_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" value="<?php echo $item_qty; ?>" data-qty="<?php echo $item_qty; ?>" size="4" class="quantity" />
		</div>
		<div class="refund" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'woocommerce_quantity_input_step', '1', $_product ); ?>" min="0" max="<?php echo $item['qty']; ?>" autocomplete="off" name="refund_order_item_qty[<?php echo absint( $item_id ); ?>]" placeholder="0" size="4" class="refund_order_item_qty" />
		</div>
	</td>
	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( isset( $item['line_total'] ) ? $item['line_total'] : '' ); ?>">
		<div class="view">
			<?php
				if ( isset( $item['line_total'] ) ) {
					echo wc_price( $item['line_total'], array( 'currency' => $order->get_order_currency() ) );
				}

				if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] !== $item['line_total'] ) {
					echo '<span class="wc-order-item-discount">-' . wc_price( wc_format_decimal( $item['line_subtotal'] - $item['line_total'], '' ), array( 'currency' => $order->get_order_currency() ) ) . '</span>';
				}

				if ( $refunded = $order->get_total_refunded_for_item( $item_id ) ) {
					echo '<small class="refunded">' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<div class="split-input">
				<div class="input">
					<label><?php esc_attr_e( 'Pre-discount:', 'woocommerce' ); ?></label>
					<input type="text" name="line_subtotal[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_subtotal; ?>" class="line_subtotal wc_input_price" data-subtotal="<?php echo $item_subtotal; ?>" />
				</div>
				<div class="input">
					<label><?php esc_attr_e( 'Total:', 'woocommerce' ); ?></label>
					<input type="text" name="line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total; ?>" class="line_total wc_input_price" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo $item_total; ?>" />
				</div>
			</div>
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php
		if ( ! empty( $tax_data ) ) {
			foreach ( $order_taxes as $tax_item ) {
				$tax_item_id       = $tax_item['rate_id'];
				$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
				$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
				?>
				<td class="line_tax" width="1%">
					<div class="view">
						<?php
							if ( '' != $tax_item_total ) {
								echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_order_currency() ) );
							} else {
								echo '&ndash;';
							}

							if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] !== $item['line_total'] ) {
								echo '<span class="wc-order-item-discount">-' . wc_price( wc_round_tax_total( $tax_item_subtotal - $tax_item_total ), array( 'currency' => $order->get_order_currency() ) ) . '</span>';
							}

							if ( $refunded = $order->get_tax_refunded_for_item( $item_id, $tax_item_id ) ) {
								echo '<small class="refunded">' . wc_price( $refunded, array( 'currency' => $order->get_order_currency() ) ) . '</small>';
							}
						?>
					</div>
					<div class="edit" style="display: none;">
						<div class="split-input">
							<div class="input">
								<label><?php esc_attr_e( 'Pre-discount:', 'woocommerce' ); ?></label>
								<input type="text" name="line_subtotal_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $tax_item_subtotal ) ); ?>" class="line_subtotal_tax wc_input_price" data-subtotal_tax="<?php echo esc_attr( wc_format_localized_price( $tax_item_subtotal ) ); ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
							</div>
							<div class="input">
								<label><?php esc_attr_e( 'Total:', 'woocommerce' ); ?></label>
								<input type="text" name="line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $tax_item_total ) ); ?>" class="line_tax wc_input_price" data-total_tax="<?php echo esc_attr( wc_format_localized_price( $tax_item_total ) ); ?>" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
							</div>
						</div>
					</div>
					<div class="refund" style="display: none;">
						<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
					</div>
				</td>
				<?php
			}
		}
	?>

	<td class="wc-order-edit-line-item" width="1%">
		<div class="wc-order-edit-line-item-actions">
			<?php if ( $order->is_editable() ) : ?>
				<a class="edit-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit item', 'woocommerce' ); ?>"></a><a class="delete-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Delete item', 'woocommerce' ); ?>"></a>
			<?php endif; ?>
		</div>
	</td>
</tr>
