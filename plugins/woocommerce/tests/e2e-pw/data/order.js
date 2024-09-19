const { customerBilling, customerShipping } = require( './shared' );
const {
	customerBillingSearchTest,
	customerShippingSearchTest,
} = require( './shared/customer' );

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
 * @return {Object} Sample Order payload.
 */
const getOrderExample = () => {
	const orderExample = {
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
};

const getOrderExampleSearchTest = () => {
	const orderExampleSearchTest = {
		id: 0,
		payment_method: 'cod',
		payment_method_title: 'Cash on Delivery',
		status: 'processing',
		set_paid: false,
		currency: 'USD',
		customer_note: 'A customer provided note.',
		customer_id: 0,
		billing: customerBillingSearchTest,
		shipping: customerShippingSearchTest,
		line_items: [ productLineItems ],
		shipping_lines: [ shippingLines ],
		fee_lines: [ feeLines ],
		coupon_lines: [ couponLines ],
	};
	return orderExampleSearchTest;
};

module.exports = {
	order,
	productLineItems,
	shippingLines,
	feeLines,
	couponLines,
	getOrderExample,
	getOrderExampleSearchTest,
};
