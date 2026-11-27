/**
 * External dependencies
 */
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import { calculateLineSaleAmount } from '../calculate-sale-amount';

// The prices shown are the raw prices (6 decimals) rounded to the prices' minor unit, as the Store API returns them.
const shown = ( raw: string, minorUnit: number ) =>
	String( Math.round( parseInt( raw, 10 ) / 10 ** ( 6 - minorUnit ) ) );

const item = (
	regular: string,
	price: string,
	quantity: number,
	lineSubtotal: string,
	lineSubtotalTax = '0',
	pricesMinorUnit = 2
) => ( {
	prices: {
		...previewCart.items[ 0 ].prices,
		currency_minor_unit: pricesMinorUnit,
		price: shown( price, pricesMinorUnit ),
		regular_price: shown( regular, pricesMinorUnit ),
		sale_price: shown( price, pricesMinorUnit ),
		raw_prices: {
			precision: 6,
			regular_price: regular,
			price,
			sale_price: price,
		},
	},
	totals: {
		line_subtotal: lineSubtotal,
		line_subtotal_tax: lineSubtotalTax,
	},
	quantity,
} );

describe( 'calculateLineSaleAmount', () => {
	it( 'works out the saving from the line, not a rounded per-unit saving', () => {
		// 2 × £2.10 at 5% off: the line is £3.99, so the saving is £0.21 (not 2 × £0.10).
		expect(
			calculateLineSaleAmount(
				item( '2100000', '1995000', 2, '399' ),
				2,
				false
			)
		).toBe( 21 );
	} );

	it( 'includes line tax when prices are shown with tax', () => {
		// Regular £1.20 with tax, line of 3 charged £3.40 + £0.20 tax.
		expect(
			calculateLineSaleAmount(
				item( '1200000', '1150000', 3, '340', '20' ),
				2,
				true
			)
		).toBe( 0 );
		expect(
			calculateLineSaleAmount(
				item( '1200000', '1150000', 3, '300', '20' ),
				2,
				true
			)
		).toBe( 40 );
	} );

	it( 'is zero when the item is not discounted', () => {
		expect(
			calculateLineSaleAmount(
				item( '2000000', '2000000', 2, '300' ),
				2,
				false
			)
		).toBe( 0 );
	} );

	it( 'compares unit prices in their own minor unit and returns the saving in the totals’ minor unit', () => {
		// 200 × £0.207, down from £0.21: the prices have 3 decimals, the line £41.40 has 2, so the saving is £0.60.
		expect(
			calculateLineSaleAmount(
				item( '210000', '207000', 200, '4140', '0', 3 ),
				2,
				false
			)
		).toBe( 60 );
	} );

	it( 'is zero when the unit prices only differ beyond their minor unit', () => {
		expect(
			calculateLineSaleAmount(
				item( '207400', '207000', 200, '4140', '0', 3 ),
				2,
				false
			)
		).toBe( 0 );
	} );

	it( 'counts a discount whenever the prices shown differ, however small the exact difference', () => {
		// £1.236 and £1.234 show as £1.24 and £1.23: 200 cost £246.80 against £247.20 regular, a saving of £0.40.
		expect(
			calculateLineSaleAmount(
				item( '1236000', '1234000', 200, '24680' ),
				2,
				false
			)
		).toBe( 40 );
	} );

	it( 'handles a fractional quantity, which some extensions allow', () => {
		// £1.510001 × 0.5 is £0.7550005, so £0.76 against a £0.50 line.
		expect(
			calculateLineSaleAmount(
				item( '1510001', '1000000', 0.5, '50' ),
				2,
				false
			)
		).toBe( 26 );
	} );
} );
