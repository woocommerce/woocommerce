/**
 * External dependencies
 */
import type { CartResponseTotals, CurrencyInfo } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	getLineRoundingNote,
	getSubtotalRoundingNote,
	type RoundedTotalLineItem,
} from '../rounded-total';

const currency = (
	code: string,
	symbol: string,
	minorUnit: number,
	{ prefix = symbol, suffix = '', decimal = '.', thousand = ',' } = {}
) =>
	( {
		currency_code: code,
		currency_symbol: symbol,
		currency_minor_unit: minorUnit,
		currency_prefix: prefix,
		currency_suffix: suffix,
		currency_decimal_separator: decimal,
		currency_thousand_separator: thousand,
	} ) as CurrencyInfo;

const GBP = currency( 'GBP', '£', 2 );
const JPY = currency( 'JPY', '¥', 0 );
const EUR = currency( 'EUR', '€', 2, {
	prefix: '',
	suffix: ' €',
	decimal: ',',
	thousand: '.',
} );

/**
 * A Store API cart item. `price` is the unit price shown, in minor units of `priceMinorUnit` (the Store API raises it
 * for prices shown with the decimals they were entered with). `raw` is the exact unit price at 6 decimals.
 */
const lineItem = ( {
	money = GBP,
	price,
	priceMinorUnit = money.currency_minor_unit,
	raw,
	quantity,
	net,
	tax = 0,
}: {
	money?: CurrencyInfo;
	price: number;
	priceMinorUnit?: number;
	raw: number;
	quantity: number;
	net: number;
	tax?: number;
} ) =>
	( {
		prices: {
			...money,
			currency_minor_unit: priceMinorUnit,
			price: String( price ),
			raw_prices: {
				precision: 6,
				price: String( raw ),
				regular_price: String( raw ),
				sale_price: String( raw ),
			},
		},
		totals: {
			...money,
			line_subtotal: String( net ),
			line_subtotal_tax: String( tax ),
		},
		quantity,
	} ) as RoundedTotalLineItem;

const VAT = { tax: 'VAT', included: '(incl. VAT)', excluded: '(excl. VAT)' };

describe( 'getLineRoundingNote', () => {
	it.each`
		description                                        | item                                                                         | includeTax
		${ 'the unit price × quantity is the line total' } | ${ { price: 199, raw: 1990000, quantity: 3, net: 597 } }                     | ${ false }
		${ 'a unit price with more decimals adds up' }     | ${ { price: 1995, priceMinorUnit: 3, raw: 1995000, quantity: 2, net: 399 } } | ${ false }
		${ 'nothing explains the line and it has no tax' } | ${ { price: 200, raw: 2000000, quantity: 2, net: 399 } }                     | ${ false }
		${ 'a fractional quantity adds up' }               | ${ { price: 150, raw: 1500000, quantity: 0.28, net: 42 } }                   | ${ false }
	`( 'returns null when $description', ( { item, includeTax } ) => {
		expect(
			getLineRoundingNote( lineItem( item ), includeTax, VAT )
		).toBeNull();
	} );

	it.each`
		description                                          | item                                                                                       | includeTax | note
		${ '1.66 entered without VAT, shown with it' }       | ${ { price: 199, raw: 1992000, quantity: 3, net: 498, tax: 100 } }                         | ${ true }  | ${ 'Rounded to £5.98 from 3 units priced £1.992 (incl. VAT), shown as £1.99 in your cart.' }
		${ '9.99 entered with VAT, shown without it' }       | ${ { price: 833, raw: 8325000, quantity: 3, net: 2498, tax: 499 } }                        | ${ false } | ${ 'Rounded to £24.98 from 3 units priced £8.325 (excl. VAT), shown as £8.33 in your cart.' }
		${ 'one unit shown as entered' }                     | ${ { price: 1995, priceMinorUnit: 3, raw: 1995000, quantity: 1, net: 200 } }               | ${ false } | ${ 'Rounded to £2.00 from 1 unit priced £1.995.' }
		${ 'a sub-penny price shown as entered' }            | ${ { price: 207, priceMinorUnit: 3, raw: 207000, quantity: 2, net: 41 } }                  | ${ false } | ${ 'Rounded to £0.41 from 2 units priced £0.207.' }
		${ '1.41 adjusted to a 21% VAT rate' }               | ${ { price: 142, raw: 1421750, quantity: 7, net: 822, tax: 173 } }                         | ${ true }  | ${ 'Rounded to £9.95 from 7 units priced £1.42175 (incl. VAT), shown as £1.42 in your cart.' }
		${ '1.99 adjusted to a 21% VAT rate' }               | ${ { price: 201, raw: 2006583, quantity: 3, net: 498, tax: 104 } }                         | ${ true }  | ${ 'Rounded to £6.02 from 3 units priced £2.006583 (incl. VAT), shown as £2.01 in your cart.' }
		${ '1.66 with 7% off from a discount extension' }    | ${ { price: 154, raw: 1543800, quantity: 3, net: 386, tax: 77 } }                          | ${ true }  | ${ 'Rounded to £4.63 from 3 units priced £1.5438 (incl. VAT), shown as £1.54 in your cart.' }
		${ '9.99 for a customer who pays no tax' }           | ${ { price: 833, raw: 8325000, quantity: 3, net: 2498 } }                                  | ${ true }  | ${ 'Rounded to £24.98 from 3 units priced £8.325, shown as £8.33 in your cart.' }
		${ 'tax rounded per line, shown with tax' }          | ${ { price: 25, raw: 248400, quantity: 3, net: 62, tax: 12 } }                             | ${ true }  | ${ 'Rounded to £0.74 after adding VAT to the line.' }
		${ 'tax rounded per line, shown without tax' }       | ${ { price: 21, raw: 210000, quantity: 3, net: 62, tax: 13 } }                             | ${ false } | ${ 'Rounded to £0.62 after taking VAT off the line.' }
		${ 'a currency without decimals' }                   | ${ { money: JPY, price: 1995, priceMinorUnit: 1, raw: 199500000, quantity: 1, net: 200 } } | ${ false } | ${ 'Rounded to ¥200 from 1 unit priced ¥199.5.' }
		${ 'a currency with its own separators and suffix' } | ${ { money: EUR, price: 199, raw: 1992000, quantity: 3, net: 498, tax: 100 } }             | ${ true }  | ${ 'Rounded to 5,98 € from 3 units priced 1,992 € (incl. VAT), shown as 1,99 € in your cart.' }
		${ 'a fractional quantity rounded half up' }         | ${ { price: 150, raw: 1500000, quantity: 0.29, net: 44 } }                                 | ${ false } | ${ 'Rounded to £0.44 from 0.29 units priced £1.50.' }
	`( 'explains $description', ( { item, includeTax, note } ) => {
		expect( getLineRoundingNote( lineItem( item ), includeTax, VAT ) ).toBe(
			note
		);
	} );

	it( 'returns null without exact prices', () => {
		const item = lineItem( {
			price: 199,
			raw: 1992000,
			quantity: 3,
			net: 498,
			tax: 100,
		} );

		expect(
			getLineRoundingNote(
				{
					...item,
					prices: {
						...item.prices,
						raw_prices: undefined,
					} as unknown as RoundedTotalLineItem[ 'prices' ],
				},
				true,
				VAT
			)
		).toBeNull();
	} );
} );

describe( 'getSubtotalRoundingNote', () => {
	const line = ( net: number, tax: number ) => ( {
		totals: {
			line_subtotal: String( net ),
			line_subtotal_tax: String( tax ),
		},
	} );
	const cartTotals = ( items: number, itemsTax: number ) =>
		( {
			...GBP,
			total_items: String( items ),
			total_items_tax: String( itemsTax ),
		} ) as CartResponseTotals;
	// Three lines shown as £1.99 each (£5.97), but VAT on the whole subtotal makes £5.98.
	const lines = [ line( 166, 33 ), line( 166, 33 ), line( 166, 33 ) ];

	it( 'explains a subtotal with tax rounded at subtotal level', () => {
		expect(
			getSubtotalRoundingNote(
				lines,
				cartTotals( 498, 100 ),
				true,
				true,
				VAT
			)
		).toBe( 'Rounded to £5.98 after adding VAT to the subtotal.' );
	} );

	it( 'returns null when the line totals add up to the subtotal', () => {
		expect(
			getSubtotalRoundingNote(
				[ line( 166, 33 ), line( 166, 33 ) ],
				cartTotals( 332, 66 ),
				true,
				true,
				VAT
			)
		).toBeNull();
	} );

	it( 'returns null when tax is rounded per line, prices are shown without tax, or there is no tax', () => {
		expect(
			getSubtotalRoundingNote(
				lines,
				cartTotals( 498, 100 ),
				true,
				false,
				VAT
			)
		).toBeNull();
		expect(
			getSubtotalRoundingNote(
				lines,
				cartTotals( 499, 100 ),
				false,
				true,
				VAT
			)
		).toBeNull();
		expect(
			getSubtotalRoundingNote(
				[ line( 166, 0 ), line( 166, 0 ) ],
				cartTotals( 333, 0 ),
				true,
				true,
				VAT
			)
		).toBeNull();
	} );
} );
