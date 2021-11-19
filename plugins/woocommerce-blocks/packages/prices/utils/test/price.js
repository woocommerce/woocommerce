/**
 * Internal dependencies
 */
import { formatPrice, getCurrency } from '../price';

describe( 'formatPrice', () => {
	test.each`
		value           | prefix   | suffix   | expected
		${ 1000 }       | ${ '€' } | ${ '' }  | ${ '€10' }
		${ 1000 }       | ${ '' }  | ${ '€' } | ${ '10€' }
		${ 1000 }       | ${ '' }  | ${ '$' } | ${ '10$' }
		${ '1000' }     | ${ '€' } | ${ '' }  | ${ '€10' }
		${ 0 }          | ${ '€' } | ${ '' }  | ${ '€0' }
		${ '' }         | ${ '€' } | ${ '' }  | ${ '' }
		${ null }       | ${ '€' } | ${ '' }  | ${ '' }
		${ undefined }  | ${ '€' } | ${ '' }  | ${ '' }
		${ 100000 }     | ${ '€' } | ${ '' }  | ${ '€1,000' }
		${ 1000000 }    | ${ '€' } | ${ '' }  | ${ '€10,000' }
		${ 1000000000 } | ${ '€' } | ${ '' }  | ${ '€10,000,000' }
	`(
		'correctly formats price given "$value", "$prefix" prefix, and "$suffix" suffix as "$expected"',
		( { value, prefix, suffix, expected } ) => {
			const formattedPrice = formatPrice(
				value,
				getCurrency( { prefix, suffix } )
			);

			expect( formattedPrice ).toEqual( expected );
		}
	);

	test.each`
		value           | prefix   | decimalSeparator | thousandSeparator | expected
		${ 1000000099 } | ${ '$' } | ${ '.' }         | ${ ',' }          | ${ '$10,000,000.99' }
		${ 1000000099 } | ${ '$' } | ${ ',' }         | ${ '.' }          | ${ '$10.000.000,99' }
	`(
		'correctly formats price given "$value", "$prefix" prefix, "$decimalSeparator" decimal separator, "$thousandSeparator" thousand separator as "$expected"',
		( {
			value,
			prefix,
			decimalSeparator,
			thousandSeparator,
			expected,
		} ) => {
			const formattedPrice = formatPrice(
				value,
				getCurrency( { prefix, decimalSeparator, thousandSeparator } )
			);

			expect( formattedPrice ).toEqual( expected );
		}
	);

	test.each`
		value          | expected
		${ 1000 }      | ${ '$10' }
		${ 0 }         | ${ '$0' }
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
