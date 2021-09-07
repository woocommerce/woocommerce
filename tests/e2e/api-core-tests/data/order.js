const { customerBilling, customerShipping } = require('./shared');

/**
 * A basic order.
 *
 * For more details on the order properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#order-properties
 */
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
	line_items: [],
	shipping_lines: [],
	fee_lines: [],
	coupon_lines: [],
};

const productLineItems = {
	name: '',
	product_id: '',
	variation_id: 0,
	quantity: 0,
	tax_class: '',
	subtotal: '',
	total: '',
};

const shippingLines = {
	method_title: '',
	method_id: 'flat_rate',
	total: '',
};

const feeLines = {
	name: 'Fee',
	tax_class: '',
	tax_status: 'none',
	total: '',
};

const couponLines = {
	code: '10off',
};

module.exports = {
	order,
	productLineItems,
	shippingLines,
	feeLines,
	couponLines,
};
