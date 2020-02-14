/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */
import { getCurrencyFormatDecimal } from 'lib/currency-format';
import { getOrderRefundTotal } from 'lib/order-values';

export function formatTableOrders( orders ) {
	return orders.map( ( order ) => {
		const {
			date_created: date,
			id,
			status,
			customer_id: customerId,
			line_items: lineItems,
			coupon_lines: couponLines,
			currency,
			total,
			total_tax: totalTax,
			shipping_total: shippingTotal,
			discount_total: discountTotal,
		} = order;

		return {
			date,
			id,
			status,
			customer_id: customerId,
			line_items: lineItems,
			items_sold: lineItems.reduce(
				( acc, item ) => item.quantity + acc,
				0
			),
			coupon_lines: couponLines,
			currency,
			net_revenue: getCurrencyFormatDecimal(
				total -
				totalTax -
				shippingTotal -
				discountTotal +
				getOrderRefundTotal( order )
			),
		};
	} );
}
