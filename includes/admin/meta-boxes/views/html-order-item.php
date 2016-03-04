<?php
/**
 * Shows an order item
 *
 * @var object $item The item being displayed
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$_product = $item->get_product();
?>
<tr class="item <?php echo apply_filters( 'woocommerce_admin_html_order_item_class', ( ! empty( $class ) ? $class : '' ), $item ); ?>" data-order_item_id="<?php echo esc_attr( $item->get_id() ); ?>">
	<td class="check-column"><input type="checkbox" /></td>
	<td class="thumb">
		<?php if ( $_product ) : ?>
			<a href="<?php echo esc_url( admin_url( 'post.php?post=' . esc_attr( $_product->get_id() ) . '&action=edit' ) ); ?>" class="tips" data-tip="<?php

				echo '<strong>' . __( 'Product ID:', 'woocommerce' ) . '</strong> ' . esc_html( $item->get_product_id() );

				if ( $item->get_variation_id() && 'product_variation' === get_post_type( $item->get_variation_id() ) ) {
					echo '<br/><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong> ' . esc_html( $item->get_variation_id() );
				} elseif ( $item->get_variation_id() ) {
					echo '<br/><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong> ' . esc_html( $item->get_variation_id() ) . ' (' . __( 'No longer exists', 'woocommerce' ) . ')';
				}

				if ( $_product->get_sku() ) {
					echo '<br/><strong>' . __( 'Product SKU:', 'woocommerce' ).'</strong> ' . esc_html( $_product->get_sku() );
				}

				if ( isset( $_product->variation_data ) ) {
					echo '<br/>' . wc_get_formatted_variation( $_product->variation_data, true );
				}
			?>"><?php echo apply_filters( 'woocommerce_admin_order_item_thumbnail', $_product->get_image( 'shop_thumbnail', array( 'title' => '' ) ), $item->get_id(), $item ); ?></a>
		<?php else : ?>
			<?php echo wc_placeholder_img( 'shop_thumbnail' ); ?>
		<?php endif; ?>
	</td>
	<td class="name" data-sort-value="<?php echo esc_attr( $item->get_name() ); ?>">

		<?php if ( $_product ) : ?>
			<?php echo $_product->get_sku() ? esc_html( $_product->get_sku() ) . ' &ndash; ' : ''; ?>

			<a target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . esc_attr( $_product->get_id() ) . '&action=edit' ) ); ?>">
				<?php echo esc_html( $item->get_name() ); ?>
			</a>
		<?php else : ?>
			<?php echo esc_html( $item->get_name() ); ?>
		<?php endif; ?>

		<input type="hidden" class="order_item_id" name="order_item_id[]" value="<?php echo esc_attr( $item->get_id() ); ?>" />
		<input type="hidden" name="order_item_tax_class[<?php echo esc_attr( $item->get_id() ); ?>]" value="<?php echo esc_attr( $item->get_tax_class() ); ?>" />

		<?php do_action( 'woocommerce_before_order_itemmeta', $item->get_id(), $item, $_product ) ?>
		<?php include( 'html-order-item-meta.php' ); ?>
		<?php do_action( 'woocommerce_after_order_itemmeta', $item->get_id(), $item, $_product ) ?>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', $_product, $item, $item->get_id() ); ?>

	<td class="item_cost" width="1%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) ); ?>">
		<div class="view">
			<?php
				if ( $item->get_subtotal() && $item->get_subtotal() !== $item->get_total() ) {
					echo '<del>' . wc_price( $order->get_item_subtotal( $item, false, true ), array( 'currency' => $order->get_currency() ) ) . '</del> ';
				}
				echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_currency() ) );
			?>
		</div>
	</td>

	<td class="quantity" width="1%">
		<div class="view">
			<?php
				echo esc_html( $item->get_qty() );

				if ( $refunded_qty = $order->get_qty_refunded_for_item( $item->get_id() ) ) {
					echo '<small class="refunded">' . $refunded_qty . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'woocommerce_quantity_input_step', '1', $_product ); ?>" min="0" autocomplete="off" name="order_item_qty[<?php echo esc_attr( $item->get_id() ); ?>]" placeholder="0" value="<?php echo esc_attr( $item->get_qty() ); ?>" data-qty="<?php echo esc_attr( $item->get_qty() ); ?>" size="4" class="quantity" />
		</div>
		<div class="refund" style="display: none;">
			<input type="number" step="<?php echo apply_filters( 'woocommerce_quantity_input_step', '1', $_product ); ?>" min="0" max="<?php echo esc_attr( $item->get_qty() ); ?>" autocomplete="off" name="refund_order_item_qty[<?php echo esc_attr( $item->get_id() ); ?>]" placeholder="0" size="4" class="refund_order_item_qty" />
		</div>
	</td>

	<td class="line_cost" width="1%" data-sort-value="<?php echo esc_attr( $item->get_total() ); ?>">
		<div class="view">
			<?php
				if ( $item->get_subtotal() && $item->get_subtotal() !== $item->get_total() ) {
					echo '<del>' . wc_price( $item->get_subtotal(), array( 'currency' => $order->get_currency() ) ) . '</del> ';
				}
				echo wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) );

				if ( $refunded = $order->get_total_refunded_for_item( $item->get_id() ) ) {
					echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
				}
			?>
		</div>
		<div class="edit" style="display: none;">
			<div class="split-input">
				<?php $item_total = esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>
				<input type="text" name="line_total[<?php echo esc_attr( $item->get_id() ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total; ?>" class="line_total wc_input_price tips" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total="<?php echo $item_total; ?>" />

				<?php $item_subtotal = esc_attr( wc_format_localized_price( $item->get_subtotal() ) ); ?>
				<input type="text" name="line_subtotal[<?php echo esc_attr( $item->get_id() ); ?>]" value="<?php echo $item_subtotal; ?>" class="line_subtotal wc_input_price tips" data-tip="<?php esc_attr_e( 'Before pre-tax discounts.', 'woocommerce' ); ?>" data-subtotal="<?php echo $item_subtotal; ?>" />
			</div>
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo esc_attr( $item->get_id() ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php
		if ( empty( $legacy_order ) && wc_tax_enabled() ) :
			$tax_data = $item->get_taxes();

			foreach ( $order_taxes as $tax_item ) :
				$tax_item_id       = $tax_item->get_rate_id();
				$tax_item_total    = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
				$tax_item_subtotal = isset( $tax_data['subtotal'][ $tax_item_id ] ) ? $tax_data['subtotal'][ $tax_item_id ] : '';
				?>
					<td class="line_tax" width="1%">
						<div class="view">
							<?php
								if ( '' != $tax_item_total ) {
									if ( isset( $tax_item_subtotal ) && $tax_item_subtotal != $tax_item_total ) {
										echo '<del>' . wc_price( wc_round_tax_total( $tax_item_subtotal ), array( 'currency' => $order->get_currency() ) ) . '</del> ';
									}

									echo wc_price( wc_round_tax_total( $tax_item_total ), array( 'currency' => $order->get_currency() ) );
								} else {
									echo '&ndash;';
								}

								if ( $refunded = $order->get_tax_refunded_for_item( $item->get_id(), $tax_item_id ) ) {
									echo '<small class="refunded">-' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>';
								}
							?>
						</div>
						<div class="edit" style="display: none;">
							<div class="split-input">
								<?php $item_total_tax = ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>
								<input type="text" name="line_tax[<?php echo esc_attr( $item->get_id() ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo $item_total_tax; ?>" class="line_tax wc_input_price tips" data-tip="<?php esc_attr_e( 'After pre-tax discounts.', 'woocommerce' ); ?>" data-total_tax="<?php echo $item_total_tax; ?>" />

								<?php $item_subtotal_tax = ( isset( $tax_item_subtotal ) ) ? esc_attr( wc_format_localized_price( $tax_item_subtotal ) ) : ''; ?>
								<input type="text" name="line_subtotal_tax[<?php echo esc_attr( $item->get_id() ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" value="<?php echo $item_subtotal_tax; ?>" class="line_subtotal_tax wc_input_price tips" data-tip="<?php esc_attr_e( 'Before pre-tax discounts.', 'woocommerce' ); ?>" data-subtotal_tax="<?php echo $item_subtotal_tax; ?>" />
							</div>
						</div>
						<div class="refund" style="display: none;">
							<input type="text" name="refund_line_tax[<?php echo esc_attr( $item->get_id() ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
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
