/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';
import {
	dinero,
	multiply,
	transformScale,
	toSnapshot,
	halfUp,
} from 'dinero.js';
import { USD } from 'dinero.js/currencies'; // USD is used as a placeholder currency for arithmetic; actual formatting is handled elsewhere.

/**
 * Calculate how much a cart line saves: its regular price for the whole quantity minus what the line costs.
 *
 * Working from the line, rather than multiplying a rounded per-unit saving, keeps the saving consistent with the line
 * total (e.g. 2 × £2.10 at 5% off saves £0.21, not 2 × £0.10).
 *
 * Whether the item is discounted is decided by the unit prices shown, each in the minor unit of its prices (which can
 * have more decimals than the totals, e.g. £0.207), so a crossed-out price always comes with a saving. The saving is in
 * the totals' minor unit.
 *
 * @param item            Cart item.
 * @param item.prices     Item prices, including raw prices.
 * @param item.totals     Item line totals.
 * @param item.quantity   Item quantity.
 * @param targetPrecision The minor unit of the line totals.
 * @param includeTax      Whether the line is shown including tax.
 * @return Line saving in the totals' minor unit, or 0 if the item isn't discounted.
 */
export function calculateLineSaleAmount(
	item: {
		prices: CartItem[ 'prices' ];
		totals: Pick<
			CartItem[ 'totals' ],
			'line_subtotal' | 'line_subtotal_tax'
		>;
		quantity: number;
	},
	targetPrecision: number,
	includeTax: boolean
): number {
	if (
		parseInt( item.prices.regular_price, 10 ) <=
		parseInt( item.prices.price, 10 )
	) {
		return 0;
	}

	const { raw_prices: rawPrices } = item.prices;
	// Dinero only multiplies by whole numbers, so pass a fractional quantity (allowed by some extensions) as a scaled amount.
	const quantityScale = ( String( item.quantity ).split( '.' )[ 1 ] || '' )
		.length;
	const regularLine = toSnapshot(
		transformScale(
			multiply(
				dinero( {
					amount: parseInt( rawPrices.regular_price, 10 ),
					currency: USD,
					scale:
						typeof rawPrices.precision === 'string'
							? parseInt( rawPrices.precision, 10 )
							: rawPrices.precision,
				} ),
				{
					amount: Math.round( item.quantity * 10 ** quantityScale ),
					scale: quantityScale,
				}
			),
			targetPrecision,
			halfUp
		)
	).amount;
	const line =
		parseInt( item.totals.line_subtotal, 10 ) +
		( includeTax ? parseInt( item.totals.line_subtotal_tax, 10 ) : 0 );

	return Math.max( 0, regularLine - line );
}
