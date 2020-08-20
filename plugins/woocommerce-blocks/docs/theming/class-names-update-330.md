# Class names update in 2.8.0

WC Blocks 3.3.0, introduced express payment methods in the Cart block. In order to make it easy to write styles for express payment methods for the Cart and Checkout blocks separately, we updated several class names:

## Replaced classes

<table>
	<tr>
		<th>Removed</th>
		<th>New class</th>
	</tr>
	<tr>
		<td rowspan="3"><code>wc-block-components-express-checkout</td>
		<td><code>wc-block-components-express-payment</code> (generic)</td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-payment--checkout</code> (Checkout block)</td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-payment--cart</code> (Cart block)</td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-checkout__title-container</code></td>
		<td><code>wc-block-components-express-payment__title-container</code></td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-checkout__title</code></td>
		<td><code>wc-block-components-express-payment__title</code></td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-checkout__content</code></td>
		<td><code>wc-block-components-express-payment__content</code></td>
	</tr>
	<tr>
		<td rowspan="3"><code>wc-block-components-express-checkout-continue-rule</code></td>
		<td><code>wc-block-components-express-payment-continue-rule</code> (generic)</td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-payment-continue-rule--checkout</code> (Checkout block)</td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-payment-continue-rule--cart</code> (Cart block)</td>
	</tr>
	<tr>
		<td><code>wc-block-components-express-checkout-payment-event-buttons</code></td>
		<td><code>wc-block-components-express-payment__event-buttons</code></td>
	</tr>
</table>
