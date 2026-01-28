/**
 * A basic refund.
 *
 * For more details on the order refund properties, see:
 *
 * https://woocommerce.github.io/woocommerce-rest-api-docs/#order-refund-properties
 *
 */

export interface RefundLineItem {
	id?: number;
	quantity?: number;
	refund_total?: string;
}

export interface Refund {
	api_refund: boolean;
	amount: string;
	reason: string;
	line_items: RefundLineItem[];
}

export const refund: Refund = {
	api_refund: false,
	amount: '1.00',
	reason: 'Late delivery refund.',
	line_items: [],
};
