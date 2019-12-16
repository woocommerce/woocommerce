/**
 * Internal dependencies
 */
import { formatPrice } from '../price';

describe( 'formatPrice', () => {
	test.each`
		value        | priceFormat   | currencySymbol | expected
		${10}        | ${'%1$s%2$s'} | ${'€'}         | ${'€10'}
		${10}        | ${'%2$s%1$s'} | ${'€'}         | ${'10€'}
		${10}        | ${'%2$s%1$s'} | ${'$'}         | ${'10$'}
		${'10'}      | ${'%1$s%2$s'} | ${'€'}         | ${'€10'}
		${0}         | ${'%1$s%2$s'} | ${'€'}         | ${'€0'}
		${''}        | ${'%1$s%2$s'} | ${'€'}         | ${''}
		${null}      | ${'%1$s%2$s'} | ${'€'}         | ${''}
		${undefined} | ${'%1$s%2$s'} | ${'€'}         | ${''}
	`(
		'correctly formats price given "$value", "$priceFormat", and "$currencySymbol"',
		( { value, priceFormat, currencySymbol, expected } ) => {
			const formattedPrice = formatPrice(
				value,
				priceFormat,
				currencySymbol
			);

			expect( formattedPrice ).toEqual( expected );
		}
	);
} );
