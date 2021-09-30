const { customerBilling, customerShipping } = require('./shared');
const { USER_KEY, USER_ID } = process.env;
const util = require('util');
const exec = util.promisify(require('child_process').exec);

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
	product_id: 93,
	variation_id: 0,
	quantity: 2,
	tax_class: '',
	subtotal: '',
	total: '',
};

const shippingLines = {
	method_title: 'Flat rate',
	method_id: 'flat_rate',
	total: '10.00',
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

/**
 * Builds an example order request.
 *
 * @returns {Object} Sample Order payload.
 */
const getOrderExample = () => {
	let orderExample = {
		id: 0,
		payment_method: 'cod',
		payment_method_title: 'Cash on Delivery',
		status: 'processing',
		set_paid: false,
		currency: 'USD',
		customer_note: 'A customer provided note.',
		customer_id: 0,
		billing: customerBilling,
		shipping: customerShipping,
		line_items: [ productLineItems ],
		shipping_lines: [ shippingLines ],
		fee_lines: [ feeLines ],
		coupon_lines: [ couponLines ],
	};
	return orderExample;
}

const createOrderInDb = async( order ) => {
	const create_order_command =
		'wp wc shop_order create ' +
		`--status=${order.status} ` +
		`--currency=${order.currency} ` +
		`--customer_id=${order.customer_id} ` +
		`--customer_note=${JSON.stringify(order.customer_note)} ` +
		`--billing='${JSON.stringify(order.billing)}' ` +
		`--shipping='${JSON.stringify(order.shipping)}' ` +
		`--payment_method=${JSON.stringify(order.payment_method)} `+
		`--payment_method_title=${JSON.stringify(order.payment_method_title)} `+
		`--line_items='${JSON.stringify(order.line_items)}' `+
		`--shipping_lines='${JSON.stringify(order.shipping_lines)}' `+
		`--fee_lines='${JSON.stringify(order.fee_lines)}' `+
		`--coupon_lines='${JSON.stringify(order.coupon_lines)}' `+
		`--set_paid=${order.set_paid} ` +
		`--user=${JSON.stringify(USER_ID ? USER_ID : USER_KEY)} ` +
		`--porcelain`;

	const { stdout } = await exec( create_order_command );
	return parseInt( stdout.replace( "\n", "" ) );
}

const deleteOrderFromDb = async( orderId ) => {
	const delete_order_command =
		`wp wc shop_order delete ${orderId} `+
		`--user=${JSON.stringify(USER_ID ? USER_ID : USER_KEY)} ` +
		'--force=true --porcelain';

	const { stdout } = await exec( delete_order_command );
	return parseInt( stdout.replace( "\n", "" ) );
}

module.exports = {
	order,
	productLineItems,
	shippingLines,
	feeLines,
	couponLines,
	getOrderExample,
	createOrderInDb,
	deleteOrderFromDb
};
