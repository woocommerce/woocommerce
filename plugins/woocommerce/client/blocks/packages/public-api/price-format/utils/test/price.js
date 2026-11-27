/**
 * Internal dependencies
 */
import { formatPrice, getCurrency } from '../price';
import { formatPriceWithCurrency } from '../currency';

/** @type {import('@woocommerce/types').Currency} */
const GBP = {
	code: 'GBP',
	symbol: '£',
	thousandSeparator: ',',
	decimalSeparator: '.',
	minorUnit: 2,
	prefix: '£',
	suffix: '',
};

/** @type {import('@woocommerce/types').Currency} */
const JPY = { ...GBP, code: 'JPY', symbol: '¥', minorUnit: 0, prefix: '¥' };

describe( 'The function formatPriceWithCurrency()', () => {
	test.each`
		value     | storeCurrency | minorUnit | expected
		${ 2100 } | ${ GBP }      | ${ 3 }    | ${ '£2.10' }
		${ 1995 } | ${ GBP }      | ${ 3 }    | ${ '£1.995' }
		${ 207 }  | ${ GBP }      | ${ 3 }    | ${ '£0.207' }
		${ 230 }  | ${ GBP }      | ${ 3 }    | ${ '£0.23' }
		${ 1995 } | ${ JPY }      | ${ 1 }    | ${ '¥199.5' }
		${ 2000 } | ${ JPY }      | ${ 1 }    | ${ '¥200' }
		${ 210 }  | ${ GBP }      | ${ 2 }    | ${ '£2.10' }
	`(
		'drops zeros beyond the store’s decimals: $value at minor unit $minorUnit is "$expected"',
		( { value, storeCurrency, minorUnit, expected } ) => {
			expect(
				formatPriceWithCurrency( value, storeCurrency, { minorUnit } )
			).toBe( expected );
		}
	);

	it( 'keeps every decimal of a currency other than the store’s', () => {
		expect(
			formatPriceWithCurrency( 2100, GBP, {
				code: 'KWD',
				minorUnit: 3,
				prefix: 'KD ',
			} )
		).toBe( 'KD 2.100' );
	} );
} );

describe( 'The function formatPrice()', () => {
	test.each`
		value               | prefix         | suffix   | thousandSeparator | decimalSeparator | minorUnit | expected
		${ 1020 }           | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€10.20' }
		${ 1000 }           | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€10.00' }
		${ 1000 }           | ${ '' }        | ${ '€' } | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '10.00€' }
		${ 1000 }           | ${ '' }        | ${ '$' } | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '10.00$' }
		${ '1000' }         | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€10.00' }
		${ 0 }              | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€0.00' }
		${ '' }             | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '' }
		${ null }           | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '' }
		${ undefined }      | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '' }
		${ 100000 }         | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€1,000.00' }
		${ 1000000 }        | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€10,000.00' }
		${ 1000000000 }     | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '€10,000,000.00' }
		${ 10000000000 }    | ${ '€' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 3 }    | ${ '€10,000,000.000' }
		${ 10000000000000 } | ${ '€ ' }      | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 6 }    | ${ '€ 10,000,000.000000' }
		${ 10000000 }       | ${ '€ ' }      | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 0 }    | ${ '€ 10,000,000' }
		${ 1000000099 }     | ${ '$' }       | ${ '' }  | ${ ',' }          | ${ '.' }         | ${ 2 }    | ${ '$10,000,000.99' }
		${ 1000000099 }     | ${ '$' }       | ${ '' }  | ${ '.' }          | ${ ',' }         | ${ 2 }    | ${ '$10.000.000,99' }
		${ 1000 }           | ${ '&cent;' }  | ${ '' }  | ${ '.' }          | ${ ',' }         | ${ 2 }    | ${ '¢10,00' }
		${ 1000 }           | ${ '&#x62f;' } | ${ '' }  | ${ '.' }          | ${ ',' }         | ${ 2 }    | ${ 'د10,00' }
	`(
		'correctly formats price given "$value", "$prefix" prefix, "$suffix" suffix, "$thousandSeparator" thousandSeparator, "$decimalSeparator" decimalSeparator, and "$minorUnit" minorUnit as "$expected"',
		( {
			value,
			prefix,
			suffix,
			expected,
			thousandSeparator,
			decimalSeparator,
			minorUnit,
		} ) => {
			const formattedPrice = formatPrice(
				value,
				getCurrency( {
					prefix,
					suffix,
					thousandSeparator,
					decimalSeparator,
					minorUnit,
				} )
			);

			expect( formattedPrice ).toEqual( expected );
		}
	);

	test.each`
		value          | expected
		${ 1000 }      | ${ '$10.00' }
		${ 0 }         | ${ '$0.00' }
		${ '' }        | ${ '' }
		${ null }      | ${ '' }
		${ undefined } | ${ '' }
	`(
		'correctly formats price given "$value" only as "$expected"',
		( { value, expected } ) => {
			const formattedPrice = formatPrice( value );

			expect( formattedPrice ).toEqual( expected );
		}
	);
} );
