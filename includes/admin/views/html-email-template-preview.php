<p><?php _e( 'Thank you, we are now processing your order. Your order\'s details are below.', 'woocommerce' ); ?></p>

<h2><?php _e( 'Order:', 'woocommerce' ); ?> #1000</h2>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin: 0 0 20px" border="1" bordercolor="#eee">
	<thead>
		<tr>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Price', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>An awesome product</td>
			<td>1</td>
			<td>$9.99</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<th colspan="2"><?php _e( 'Order total:', 'woocommerce' ); ?></td>
			<td>$9.99</td>
		</tr>
	</tfoot>
</table>

<h2><?php _e( 'Customer details', 'woocommerce' ); ?></h2>

<table cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">
	<tr>
		<td valign="top" width="50%">
			<h3><?php _e( 'Billing address', 'woocommerce' ); ?></h3>
			<p>Some Guy
			1 infinite loop
			Cupertino
			CA 95014</p>
		</td>
		<td valign="top" width="50%">
			<h3><?php _e( 'Shipping address', 'woocommerce' ); ?></h3>
			<p>Some Guy
			1 infinite loop
			Cupertino
			CA 95014</p>
		</td>
	</tr>
</table>