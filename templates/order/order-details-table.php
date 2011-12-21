<?php
/**
 * Order Details
 */
 
global $woocommerce, $order_id;

$order = &new woocommerce_order( $order_id );
?>
<h2><?php _e('Order Details', 'woothemes'); ?></h2>
<table class="shop_table">
	<thead>
		<tr>
			<th><?php _e('Product', 'woothemes'); ?></th>
			<th><?php _e('Qty', 'woothemes'); ?></th>
			<th><?php _e('Totals', 'woothemes'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th scope="row" colspan="2"><?php _e('Cart Subtotal:', 'woothemes'); ?></th>
			<td><?php echo $order->get_subtotal_to_display(); ?></td>
		</tr>
		<?php if ($order->get_cart_discount() > 0) : ?><tr>
			<th scope="row" colspan="2"><?php _e('Cart Discount:', 'woothemes'); ?></th>
			<td><?php echo woocommerce_price($order->get_cart_discount()); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_shipping() > 0) : ?><tr>
			<th scope="row" colspan="2"><?php _e('Shipping:', 'woothemes'); ?></th>
			<td><?php echo $order->get_shipping_to_display(); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_total_tax() > 0) : ?><tr>
			<th scope="row" colspan="2"><?php echo $woocommerce->countries->tax_or_vat(); ?></th>
			<td><?php echo woocommerce_price($order->get_total_tax()); ?></td>
		</tr><?php endif; ?>
		<?php if ($order->get_order_discount() > 0) : ?><tr>
			<th scope="row" colspan="2"><?php _e('Order Discount:', 'woothemes'); ?></th>
			<td><?php echo woocommerce_price($order->get_order_discount()); ?></td>
		</tr><?php endif; ?>
		<tr>
			<th scope="row" colspan="2"><?php _e('Order Total:', 'woothemes'); ?></th>
			<td><?php echo woocommerce_price($order->get_order_total()); ?></td>
		</tr>
		<?php if ($order->customer_note) : ?>
		<tr>
			<td><?php _e('Note:', 'woothemes'); ?></td>
			<td colspan="2"><?php echo wpautop(wptexturize($order->customer_note)); ?></td>
		</tr>
		<?php endif; ?>
	</tfoot>
	<tbody>
		<?php
		if (sizeof($order->items)>0) :

			foreach($order->items as $item) :

				if (isset($item['variation_id']) && $item['variation_id'] > 0) :
					$_product = &new woocommerce_product_variation( $item['variation_id'] );
				else :
					$_product = &new woocommerce_product( $item['id'] );
				endif;

				echo '
					<tr>
						<td class="product-name">'.$item['name'];

				$item_meta = &new order_item_meta( $item['item_meta'] );
				$item_meta->display();
				
				if ($_product->exists && $_product->is_downloadable() && $order->status=='completed') :
					
					echo '<br/><small><a href="' . $order->get_downloadable_file_url( $item['id'], $item['variation_id'] ) . '">' . __('Download file &rarr;', 'woothemes') . '</a></small>';
		
				endif;	

				echo '	</td>
						<td>'.$item['qty'].'</td>
						<td>';
				
				if ($order->display_cart_ex_tax || !$order->prices_include_tax) :	
					if ($order->prices_include_tax) $ex_tax_label = 1; else $ex_tax_label = 0;
					echo woocommerce_price( $order->get_row_cost( $item, false ), array('ex_tax_label' => $ex_tax_label ));
				else :
					echo woocommerce_price( $order->get_row_cost( $item, true ) );
				endif;

				echo '</td>
					</tr>';
			endforeach;
		endif;
		?>
	</tbody>
</table>

<header>
	<h2><?php _e('Customer details', 'woothemes'); ?></h2>
</header>
<dl>
<?php
	if ($order->billing_email) echo '<dt>'.__('Email:', 'woothemes').'</dt><dd>'.$order->billing_email.'</dd>';
	if ($order->billing_phone) echo '<dt>'.__('Telephone:', 'woothemes').'</dt><dd>'.$order->billing_phone.'</dd>';
?>
</dl>

<div class="col2-set addresses">

	<div class="col-1">

		<header class="title">
			<h3><?php _e('Billing Address', 'woothemes'); ?></h3>
		</header>
		<address><p>
			<?php
				if (!$order->formatted_billing_address) _e('N/A', 'woothemes'); else echo $order->formatted_billing_address;
			?>
		</p></address>

	</div><!-- /.col-1 -->
	
	<div class="col-2">

		<header class="title">
			<h3><?php _e('Shipping Address', 'woothemes'); ?></h3>
		</header>
		<address><p>
			<?php
				if (!$order->formatted_shipping_address) _e('N/A', 'woothemes'); else echo $order->formatted_shipping_address;
			?>
		</p></address>

	</div><!-- /.col-2 -->

</div><!-- /.col2-set -->

<div class="clear"></div>