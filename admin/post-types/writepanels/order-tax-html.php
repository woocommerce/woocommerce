<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="tax_row" data-order_item_id="<?php echo $item_id; ?>">
	<p class="first">
		<label><?php _e( 'Tax Label:', 'woocommerce' ) ?></label>
		<input type="text" name="order_taxes_label[<?php echo $item_id; ?>]" placeholder="<?php echo $woocommerce->countries->tax_or_vat(); ?>" value="<?php if ( isset( $item['name'] ) ) echo esc_attr( $item['name'] ); ?>" />
		<input type="hidden" name="order_taxes_id[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item_id ); ?>" />
	</p>
	<p class="last">
		<label><?php _e( 'Compound:', 'woocommerce' ) ?>
		<input type="checkbox" name="order_taxes_compound[<?php echo $item_id; ?>]" <?php if ( isset( $item['compound'] ) ) checked( $item['compound'], 1 ); ?> /></label>
	</p>
	<p class="first">
		<label><?php _e( 'Sales Tax:', 'woocommerce' ) ?></label>
		<input type="text" name="order_taxes_amount[<?php echo $item_id; ?>]" placeholder="0.00" value="<?php if ( isset( $item['tax_amount'] ) ) echo esc_attr( $item['tax_amount'] ); ?>" />
	</p>
	<p class="last">
		<label><?php _e( 'Shipping Tax:', 'woocommerce' ) ?></label>
		<input type="text" name="order_taxes_shipping_amount[<?php echo $item_id; ?>]" placeholder="0.00" value="<?php if ( isset( $item['shipping_tax_amount'] ) ) echo esc_attr( $item['shipping_tax_amount'] ); ?>" />
	</p>
	<a href="#" class="delete_tax_row">&times;</a>
	<div class="clear"></div>
</div>