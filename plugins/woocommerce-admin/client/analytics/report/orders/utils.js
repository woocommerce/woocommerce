/** @format */
/**
 * External dependencies
 */

/**
 * WooCommerce dependencies
 */
import { getCurrencyFormatDecimal } from 'lib/currency-format';
import { getOrderRefundTotal } from 'lib/order-values';

export function formatTableOrders( orders ) {
	return orders.map( order => {
		const {
			date_created,
			id,
			status,
			customer_id,
			line_items,
			coupon_lines,
			currency,
			total,
			total_tax,
			shipping_total,
			discount_total,
		} = order;

		return {
			date: date_created,
			id,
			status,
			customer_id,
			line_items,
			items_sold: line_items.reduce( ( acc, item ) => item.quantity + acc, 0 ),
			coupon_lines,
			currency,
			net_revenue: getCurrencyFormatDecimal(
				total - total_tax - shipping_total - discount_total + getOrderRefundTotal( order )
			),
		};
	} );
}
