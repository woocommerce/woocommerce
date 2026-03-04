/**
 * External dependencies
 */
import { dinero, subtract, transformScale, toSnapshot } from 'dinero.js';
import { USD } from 'dinero.js/currencies';
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

	const regular = dinero( {
		amount: parseInt( prices.raw_prices.regular_price, 10 ),
		currency: USD,
		scale: rawPrecision,
	} );

	const purchase = dinero( {
		amount: parseInt( prices.raw_prices.price, 10 ),
		currency: USD,
		scale: rawPrecision,
	} );

	return toSnapshot(
		transformScale( subtract( regular, purchase ), targetPrecision )
	).amount;
}
