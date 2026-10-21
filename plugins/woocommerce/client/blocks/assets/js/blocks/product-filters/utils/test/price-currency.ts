/**
 * Internal dependencies
 */
import { formatPrice, getCurrency, parsePriceInput } from '../price-currency';

jest.mock( '@wordpress/interactivity', () => ( {
	getConfig: () => ( {
		currency: {
			code: 'USD',
			symbol: '$',
			symbolPosition: 'left',
			decimalSeparator: '.',
			thousandSeparator: ',',
			minorUnit: 2,
		},
	} ),
} ) );

describe( 'price text input', () => {
	it.each( [
		[ '1.5', 1.5, null ],
		[ '1,5', null, 1.5 ],
		[ '1,234.50', 1234.5, null ],
		[ '1.234,50', null, 1234.5 ],
		[ '1,234', 1234, 1.234 ],
		[ '1.234', 1.234, 1234 ],
		[ '0', 0, 0 ],
		[ ' 2 ', 2, 2 ],
		[ '', null, null ],
		[ '1.', null, null ],
		[ '1,', null, null ],
		[ '1..5', null, null ],
		[ '1,,5', null, null ],
		[ '1.5.9', null, null ],
		[ '1,5,9', null, null ],
		[ '1.5abc', null, null ],
		[ 'abc1.5', null, null ],
		[ '1e2', null, null ],
		[ 'Infinity', null, null ],
		[ 'NaN', null, null ],
		[ '-1', null, null ],
		[ '12,34.50', null, null ],
		[ '9'.repeat( 400 ), null, null ],
	] )(
		'validates the whole value %j in both locales',
		( input, dot, comma ) => {
			expect( parsePriceInput( String( input ) ) ).toBe( dot );
			expect(
				parsePriceInput(
					String( input ),
					getCurrency( {
						decimalSeparator: ',',
						thousandSeparator: '.',
					} )
				)
			).toBe( comma );
		}
	);

	it.each( [
		{
			prefix: '$',
			suffix: '',
			decimalSeparator: '.',
			thousandSeparator: ',',
		},
		{
			prefix: '',
			suffix: ' €',
			decimalSeparator: ',',
			thousandSeparator: '.',
		},
	] )(
		'accepts the displayed value in $decimalSeparator format',
		( format ) => {
			const currency = getCurrency( { ...format, minorUnit: 0 } );
			expect(
				parsePriceInput( formatPrice( 1234.5, currency ), currency )
			).toBe( 1234.5 );
		}
	);
} );
