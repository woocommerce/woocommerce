<?php if (!defined('ABSPATH')) exit; ?>

<?php global $order, $woocommerce; ?>

<?php do_action('woocommerce_email_header'); ?>

<?php if ($order->status=='pending') : ?>

	<p><?php echo sprintf( __( 'An order has been created for you on &ldquo;%s&rdquo;. To pay for this order please use the following link: <a href="%s">Pay</a>', 'woothemes' ), get_bloginfo( 'name' ), $order->get_checkout_payment_url() ); ?></p>
	
<?php endif; ?>

<?php do_action('woocommerce_email_before_order_table', $order, false); ?>

<h2><?php echo __('Order #', 'woothemes') . $order->id; ?></h2>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<thead>
		<tr>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e('Product', 'woothemes'); ?></th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e('Quantity', 'woothemes'); ?></th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e('Price', 'woothemes'); ?></th>
		</tr>
	</thead>
	<tfoot>
	<?php 
		$total_rows = array();
		
		$total_rows[ __('Cart Subtotal:', 'woothemes') ] = $order->get_subtotal_to_display();
		
		if ($order->get_cart_discount() > 0) 
			$total_rows[ __('Cart Discount:', 'woothemes') ] = woocommerce_price($order->get_cart_discount());
		
		if ($order->get_shipping() > 0)
			$total_rows[ __('Shipping:', 'woothemes') ] = $order->get_shipping_to_display();
		
		if ($order->get_total_tax() > 0) :
			
			if ( is_array($order->taxes) && sizeof($order->taxes) > 0 ) :
			
				$has_compound_tax = false;
				
				foreach ($order->taxes as $tax) : if ($tax['compound']) : $has_compound_tax = true; continue; endif;
	
					$total_rows[ $tax['label'] ] = woocommerce_price( $tax['total'] );
					
				endforeach;
				
				if ($has_compound_tax) :
			
				endif;
				
				foreach ($order->taxes as $tax) : if (!$tax['compound']) continue;
	
					$total_rows[ $tax['label'] ] = woocommerce_price( $tax['total'] );
					
				endforeach;
			
			else :
			
				$total_rows[ $woocommerce->countries->tax_or_vat() ] = woocommerce_price($order->get_total_tax());
			
			endif;
			
		endif;
		
		if ($order->get_order_discount() > 0)
			$total_rows[ __('Order Discount:', 'woothemes') ] = woocommerce_price($order->get_order_discount());
		
		$total_rows[ __('Order Total:', 'woothemes') ] = woocommerce_price($order->get_order_total());
		
		foreach ($total_rows as $label => $value) :
		?>
		<tr>
			<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; border-top-width: 4px;"><?php echo $label; ?></th>
			<td style="text-align:left; border: 1px solid #eee; border-top-width: 4px;"><?php echo $value; ?></td>
		</tr>
		<?php 
		endforeach; 
	?>
	</tfoot>
	<tbody>
		<?php echo $order->email_order_items_table(); ?>
	</tbody>
</table>

<?php do_action('woocommerce_email_after_order_table', $order, false); ?>

<?php do_action('woocommerce_email_footer'); ?>