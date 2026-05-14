<?php
/**
 * Shows a shipping line
 *
 * @package WooCommerce\Admin
 *
 * @var object $item The item being displayed
 * @var int $item_id The id of the item being displayed
 *
 * @package WooCommerce\Admin\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr class="shipping <?php echo ( ! empty( $class ) ) ? esc_attr( $class ) : ''; ?>" data-order_item_id="<?php echo esc_attr( $item_id ); ?>">
	<td class="thumb"><div></div></td>

	<td class="name">
		<div class="view">
			<?php echo esc_html( $item->get_name() ? $item->get_name() : __( 'Shipping', 'woocommerce' ) ); ?>
		</div>
		<div class="edit" style="display: none;">
			<input type="hidden" name="shipping_method_id[]" value="<?php echo esc_attr( $item_id ); ?>" />
			<input type="text" class="shipping_method_name" placeholder="<?php esc_attr_e( 'Shipping name', 'woocommerce' ); ?>" name="shipping_method_title[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $item->get_name() ); ?>" />
			<select class="shipping_method" name="shipping_method[<?php echo esc_attr( $item_id ); ?>]">
				<option value=""><?php esc_html_e( 'N/A', 'woocommerce' ); ?></option>
				<?php
				$shipping_item       = $item instanceof WC_Order_Item_Shipping ? $item : null;
				$found_method        = false;
				$current_method_id   = $shipping_item ? $shipping_item->get_method_id() : '';
				$current_instance_id = $shipping_item ? $shipping_item->get_instance_id() : 0;
				$current_value       = $current_instance_id ? $current_method_id . ':' . $current_instance_id : $current_method_id;
				$zones               = isset( $shipping_zones ) && is_array( $shipping_zones ) ? $shipping_zones : array();

				// List shipping methods grouped by zone so the saved value carries the instance id.
				// See https://github.com/woocommerce/woocommerce/issues/38481.
				foreach ( $zones as $zone ) {
					if ( empty( $zone['shipping_methods'] ) ) {
						continue;
					}

					$zone_label = ! empty( $zone['zone_name'] ) ? $zone['zone_name'] : __( 'Shipping zone', 'woocommerce' );

					echo '<optgroup label="' . esc_attr( $zone_label ) . '">';

					foreach ( $zone['shipping_methods'] as $zone_method ) {
						/**
						 * The zone method instance.
						 *
						 * @var WC_Shipping_Method $zone_method
						 */
						if ( empty( $zone_method->instance_id ) ) {
							continue;
						}

						$value     = $zone_method->id . ':' . $zone_method->instance_id;
						$is_active = $current_instance_id && (int) $current_instance_id === (int) $zone_method->instance_id;

						if ( $is_active ) {
							$found_method = true;
						}

						$method_label = $zone_method->get_title() ? $zone_method->get_title() : $zone_method->get_method_title();

						echo '<option value="' . esc_attr( $value ) . '" ' . selected( true, $is_active, false ) . '>' . esc_html( $method_label ) . '</option>';
					}

					echo '</optgroup>';
				}

				echo '<optgroup label="' . esc_attr__( 'Other shipping methods', 'woocommerce' ) . '">';

				foreach ( $shipping_methods as $method ) {
					$is_active = ! $current_instance_id && $current_method_id === $method->id;

					echo '<option value="' . esc_attr( $method->id ) . '" ' . selected( true, $is_active, false ) . '>' . esc_html( $method->get_method_title() ) . '</option>';

					if ( $is_active ) {
						$found_method = true;
					}
				}

				if ( ! $found_method && $current_method_id ) {
					echo '<option value="' . esc_attr( $current_value ) . '" selected="selected">' . esc_html__( 'Other', 'woocommerce' ) . '</option>';
				} else {
					echo '<option value="other">' . esc_html__( 'Other', 'woocommerce' ) . '</option>';
				}

				echo '</optgroup>';
				?>
			</select>
		</div>

		<?php do_action( 'woocommerce_before_order_itemmeta', $item_id, $item, null ); ?>
		<?php require __DIR__ . '/html-order-item-meta.php'; ?>
		<?php do_action( 'woocommerce_after_order_itemmeta', $item_id, $item, null ); ?>
	</td>

	<?php do_action( 'woocommerce_admin_order_item_values', null, $item, absint( $item_id ) ); ?>

	<?php if ( $cogs_is_enabled ) : ?>
	<td class="item_cost_of_goods"></td>
	<?php endif; ?>
	<td class="item_cost" width="1%">&nbsp;</td>
	<td class="quantity" width="1%">&nbsp;</td>

	<td class="line_cost" width="1%">
		<div class="view">
			<?php
			echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) );
			$refunded = -1 * $order->get_total_refunded_for_item( $item_id, 'shipping' );
			if ( $refunded ) {
				echo wp_kses_post( '<small class="refunded">' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>' );
			}
			?>
		</div>
		<div class="edit" style="display: none;">
			<input type="text" name="shipping_cost[<?php echo esc_attr( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $item->get_total() ) ); ?>" class="line_total wc_input_price" />
		</div>
		<div class="refund" style="display: none;">
			<input type="text" name="refund_line_total[<?php echo absint( $item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" class="refund_line_total wc_input_price" />
		</div>
	</td>

	<?php
	$tax_data = $item->get_taxes();
	if ( $tax_data && wc_tax_enabled() ) {
		foreach ( $order_taxes as $tax_item ) {
			$tax_item_id    = $tax_item->get_rate_id();
			$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
			?>
			<td class="line_tax" width="1%">
				<div class="view">
					<?php
					echo wp_kses_post( ( '' !== $tax_item_total ) ? wc_price( $tax_item_total, array( 'currency' => $order->get_currency() ) ) : '&ndash;' );
					$refunded = -1 * $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' );
					if ( $refunded ) {
						echo wp_kses_post( '<small class="refunded">' . wc_price( $refunded, array( 'currency' => $order->get_currency() ) ) . '</small>' );
					}
					?>
				</div>
				<div class="edit" style="display: none;">
					<input type="text" name="shipping_taxes[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" value="<?php echo ( isset( $tax_item_total ) ) ? esc_attr( wc_format_localized_price( $tax_item_total ) ) : ''; ?>" class="line_tax wc_input_price" />
				</div>
				<div class="refund" style="display: none;">
					<input type="text" name="refund_line_tax[<?php echo absint( $item_id ); ?>][<?php echo esc_attr( $tax_item_id ); ?>]" placeholder="<?php echo esc_attr( wc_format_localized_price( 0 ) ); ?>" class="refund_line_tax wc_input_price" data-tax_id="<?php echo esc_attr( $tax_item_id ); ?>" />
				</div>
			</td>
			<?php
		}
	}
	?>
	<td class="wc-order-edit-line-item">
		<?php if ( $order->is_editable() ) : ?>
			<div class="wc-order-edit-line-item-actions">
				<a class="edit-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Edit shipping', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Edit shipping', 'woocommerce' ); ?>"></a><a class="delete-order-item tips" href="#" data-tip="<?php esc_attr_e( 'Delete shipping', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Delete shipping', 'woocommerce' ); ?>"></a>
			</div>
		<?php endif; ?>
	</td>
</tr>
