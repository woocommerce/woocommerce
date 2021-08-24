const { customerBilling, customerShipping } = require('./shared');

/**
 * A basic order.
 *
 * For more details on the order properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#order-properties
 */

const productLineItems = {
	name: '',
	product_id: '',
	variation_id: 0,
	quantity: 0,
	tax_class: '',
	subtotal: '',
	total: '',
}

const shippingLines = {
	method_title: '',
	method_id: '',
	total: '',
}

const feeLines = {
	name: '',
	tax_class: '',
	tax_status: '',
	total: '',
}

const couponLines = {
	code: ''
}

const order = {
	payment_method: '',
	payment_method_title: '',
	status: 'pending',
	set_paid: false,
	currency: 'USD',
	customer_note: '',
	customer_id: 0,
	billing: customerBilling,
	shipping: customerShipping,
	line_items: [
		productLineItems
	],
	shipping_lines: [
		shippingLines
	],
	fee_lines: [
		feeLines
	],
	coupon_lines: [
		couponLines
	]
}

module.exports = {
	order,
	productLineItems,
	shippingLines,
	feeLines,
	couponLines,
}
