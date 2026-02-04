/**
 * External dependencies
 */
import Dinero from 'dinero.js';
import type { CartItem } from '@woocommerce/types';

/**
 * Calculate the per-unit sale amount from raw prices.
 *
 * @param prices          Cart item prices containing raw_prices.
 * @param targetPrecision The target currency minor unit precision.
 * @return Per-unit sale amount as a number, or 0 if no discount.
 */
export function calculateSaleAmount(
	prices: CartItem[ 'prices' ],
	targetPrecision: number
): number {
	const rawPrecision =
		typeof prices.raw_prices.precision === 'string'
			? parseInt( prices.raw_prices.precision, 10 )
			: prices.raw_prices.precision;

	const regular = Dinero( {
		amount: parseInt( prices.raw_prices.regular_price, 10 ),
		precision: rawPrecision,
	} );

	const purchase = Dinero( {
		amount: parseInt( prices.raw_prices.price, 10 ),
		precision: rawPrecision,
	} );

	return regular
		.subtract( purchase )
		.convertPrecision( targetPrecision )
		.getAmount();
}
