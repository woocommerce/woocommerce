<?php if (get_option('woocommerce_ship_to_billing_address_only')=='no') : ?>

	<div style="float:left; width: 49%;">

<?php endif; ?>
	
		<h3><?php _e('Billing address', 'woocommerce'); ?></h3>
		
		<p><?php echo $order->get_formatted_billing_address(); ?></p>

<?php if (get_option('woocommerce_ship_to_billing_address_only')=='no') : ?>

	</div>
	
	<div style="float:right; width: 49%;">
	
		<h3><?php _e('Shipping address', 'woocommerce'); ?></h3>
		
		<p><?php echo $order->get_formatted_shipping_address(); ?></p>
	
	</div>
	
	<div style="clear:both;"></div>

<?php endif; ?>