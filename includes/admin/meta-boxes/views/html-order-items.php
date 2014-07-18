<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="woocommerce_order_items_wrapper wc-order-items-editable">
	<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
		<thead>
			<tr>
				<th><input type="checkbox" class="check-column" /></th>
				<th class="item" colspan="2"><?php _e( 'Item', 'woocommerce' ); ?></th>

				<?php do_action( 'woocommerce_admin_order_item_headers' ); ?>

				<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
					<th class="tax_class"><?php _e( 'Tax&nbsp;Class', 'woocommerce' ); ?></th>
				<?php endif; ?>

				<th class="quantity"><?php _e( 'Qty', 'woocommerce' ); ?></th>

				<th class="line_cost"><?php _e( 'Total', 'woocommerce' ); ?></th>

				<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
					<th class="line_tax"><?php _e( 'Tax', 'woocommerce' ); ?></th>
				<?php endif; ?>

				<th class="wc-order-item-refund-quantity" style="display:none"><?php _e( 'Refund', 'woocommerce' ); ?></th>

				<th class="wc-order-edit-line-item" width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody id="order_items_list">

			<?php
				// List order items
				$order_items      = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee', 'shipping', 'coupon' ) ) );
				$shipping_methods = WC()->shipping() ? WC()->shipping->load_shipping_methods() : array();

				foreach ( $order_items as $item_id => $item ) {

					switch ( $item['type'] ) {
						case 'line_item' :
							$_product  = $order->get_product_from_item( $item );
							$item_meta = $order->get_item_meta( $item_id );

							include( 'html-order-item.php' );
							break;
						case 'fee' :
							include( 'html-order-fee.php' );
							break;
						case 'shipping' :
							include( 'html-order-shipping.php' );
							break;
						case 'coupon' :
							include( 'html-order-coupon.php' );
							break;
					}

					do_action( 'woocommerce_order_item_' . $item['type'] . '_html', $item_id, $item );
				}

				if ( $refunds = $order->get_refunds() ) {
					foreach ( $refunds as $refund ) {
						include( 'html-order-refund.php' );
					}
				}
			?>
		</tbody>
	</table>
</div>
<div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
	<table class="wc-order-totals">
		<tr>
			<td class="label"><?php _e( 'Shipping', 'woocommerce' ); ?>:</td>
			<td class="total"><?php echo wc_price( $order->get_total_shipping() ); ?></td>
			<td width="1%"></td>
		</tr>
		<?php if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) : ?>
			<tr>
				<td class="label"><?php _e( 'Taxes', 'woocommerce' ); ?>:</td>
				<td class="total"><?php echo wc_price( $order->get_total_tax() ); ?></td>
				<td width="1%"></td>
			</tr>
		<?php endif; ?>
		<tr>
			<td class="label"><?php _e( 'Order Discount', 'woocommerce' ); ?>:</td>
			<td class="total">
				<div class="view"><?php echo wc_price( $order->get_total_discount() ); ?></div>
				<div class="edit" style="display: none;">
					<input type="text" class="wc_input_price" id="_order_discount" name="_order_discount" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $data['_order_discount'][0] ) ) ? esc_attr( wc_format_localized_price( $data['_order_discount'][0] ) ) : ''; ?>" />
					<div class="clear"></div>
				</div>
			</td>
			<td><div class="wc-order-edit-line-item-actions"><a class="edit-order-item" href="#"></a></div></td>
		</tr>
		<tr>
			<td class="label refunded-total"><?php _e( 'Refunded', 'woocommerce' ); ?>:</td>
			<td class="total refunded-total">-<?php echo wc_price( $order->get_total_refunded() ); ?></td>
			<td width="1%"></td>
		</tr>
		<tr>
			<td class="label"><?php _e( 'Order Total', 'woocommerce' ); ?>:</td>
			<td class="total">
				<div class="view"><?php echo wc_price( $order->get_total() ); ?></div>
				<div class="edit" style="display: none;">
					<input type="text" class="wc_input_price" id="_order_total" name="_order_total" placeholder="<?php echo wc_format_localized_price( 0 ); ?>" value="<?php echo ( isset( $data['_order_total'][0] ) ) ? esc_attr( wc_format_localized_price( $data['_order_total'][0] ) ) : ''; ?>" />
					<div class="clear"></div>
				</div>
			</td>
			<td><div class="wc-order-edit-line-item-actions"><a class="edit-order-item" href="#"></a></div></td>
		</tr>
	</table>
	<div class="clear"></div>
</div>
<div class="wc-order-data-row wc-order-bulk-actions">
	<p class="bulk-actions">
		<select>
			<option value=""><?php _e( 'Actions', 'woocommerce' ); ?></option>
			<optgroup label="<?php _e( 'Edit', 'woocommerce' ); ?>">
				<option value="delete"><?php _e( 'Delete line item(s)', 'woocommerce' ); ?></option>
			</optgroup>
			<optgroup label="<?php _e( 'Stock Actions', 'woocommerce' ); ?>">
				<option value="reduce_stock"><?php _e( 'Reduce line item stock', 'woocommerce' ); ?></option>
				<option value="increase_stock"><?php _e( 'Increase line item stock', 'woocommerce' ); ?></option>
			</optgroup>
		</select>

		<button type="button" class="button do_bulk_action wc-reload" title="<?php _e( 'Apply', 'woocommerce' ); ?>"><span><?php _e( 'Apply', 'woocommerce' ); ?></span></button>
	</p>
	<p class="add-items">
		<button type="button" class="button add-line-item"><?php _e( 'Add line item(s)', 'woocommerce' ); ?></button>
		<button type="button" class="button refund-items"><?php _e( 'Refund', 'woocommerce' ); ?></button>
		<button type="button" class="button button-primary calculate-action"><?php _e( 'Calculate Total', 'woocommerce' ); ?></button>
	</p>
</div>
<div class="wc-order-data-row wc-order-add-item" style="display:none;">
	<button type="button" class="button add-order-item"><?php _e( 'Add product(s)', 'woocommerce' ); ?></button>
	<button type="button" class="button add-order-fee"><?php _e( 'Add fee', 'woocommerce' ); ?></button>
	<button type="button" class="button add-order-shipping"><?php _e( 'Add shipping cost', 'woocommerce' ); ?></button>
	<button type="button" class="button cancel-action"><?php _e( 'Cancel', 'woocommerce' ); ?></button>
	<button type="button" class="button button-primary save-action"><?php _e( 'Save', 'woocommerce' ); ?></button>
	<button type="button" class="button button-primary calculate-action"><?php _e( 'Calculate Total and Save', 'woocommerce' ); ?></button>
</div>
<div class="wc-order-data-row wc-order-refund-items" style="display:none;">
	<table class="wc-order-totals">
		<tr>
			<td class="label"><label for="restock_refunded_items"><?php _e( 'Restock refunded items', 'woocommerce' ); ?>:</label></td>
			<td class="total"><input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" checked="checked" /></td>
		</tr>
		<tr>
			<td class="label"><?php _e( 'Amount already refunded', 'woocommerce' ); ?>:</td>
			<td class="total">-<?php echo wc_price( $order->get_total_refunded() ); ?></td>
		</tr>
		<tr>
			<td class="label"><?php _e( 'Total available to refund', 'woocommerce' ); ?>:</td>
			<td class="total"><?php echo wc_price( $order->get_total() - $order->get_total_refunded() ); ?></td>
		</tr>
		<tr>
			<td class="label"><label for="refund_amount"><?php _e( 'Refund amount', 'woocommerce' ); ?>:</label></td>
			<td class="total">
				<input type="text" class="text" id="refund_amount" name="refund_amount" class="wc_input_price" />
				<div class="clear"></div>
			</td>
		</tr>
		<tr>
			<td class="label"><label for="refund_reason"><?php _e( 'Reason for refund (optional)', 'woocommerce' ); ?>:</label></td>
			<td class="total">
				<input type="text" class="text" id="refund_reason" name="refund_reason" />
				<div class="clear"></div>
			</td>
		</tr>
	</table>
	<div class="clear"></div>
	<div class="refund-actions">
		<button type="button" class="button button-primary do-api-refund"><?php printf( _x( 'Refund %s via %s', 'Refund $amount', 'woocommerce' ), '<span class="wc-order-refund-amount">' . wc_price( 0 ) . '</span>', $order->payment_method_title ); ?></button>
		<button type="button" class="button button-primary do-manual-refund"><?php _e( 'Refund manually', 'woocommerce' ); ?></button>
		<button type="button" class="button cancel-action"><?php _e( 'Cancel', 'woocommerce' ); ?></button>
		<div class="clear"></div>
	</div>
</div>

<script type="text/template" id="wc-modal-add-products">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header>
					<h1><?php echo __( 'Add products', 'woocommerce' ); ?></h1>
				</header>
				<article>
					<form>
						<select id="add_item_id" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width: 96%"></select>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-cancel" class="button button-large"><?php echo __( 'Cancel' , 'woocommerce' ); ?></button>
						<button id="btn-ok" class="button button-primary button-large"><?php echo __( 'Add' , 'woocommerce' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop">&nbsp;</div>
</script>
