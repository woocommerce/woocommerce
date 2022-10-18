/**
 * A basic refund.
 *
 * For more details on the order refund properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#order-refund-properties
 *
 */
const refund = {
	api_refund: false,
	amount: '1.00',
	reason: 'Late delivery refund.',
	line_items: [],
};

module.exports = {
	refund,
};
