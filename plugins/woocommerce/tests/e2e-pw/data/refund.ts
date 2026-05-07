/**
 * A basic refund.
 *
 * For more details on the order refund properties, see:
 *
 * https://developer.woocommerce.com/docs/apis/rest-api/v3/order-refunds/#order-refund-properties
 *
 */
export const refund = {
	api_refund: false,
	amount: '1.00',
	reason: 'Late delivery refund.',
	line_items: [],
};
