/**
 * Internal dependencies
 */
import { formatPrice, getCurrency } from '../price';

describe( 'formatPrice', () => {
	test.each`
		value          | prefix   | suffix   | expected
		${ 1000 }      | ${ '€' } | ${ '' }  | ${ '€10' }
		${ 1000 }      | ${ '' }  | ${ '€' } | ${ '10€' }
		${ 1000 }      | ${ '' }  | ${ '$' } | ${ '10$' }
		${ '1000' }    | ${ '€' } | ${ '' }  | ${ '€10' }
		${ 0 }         | ${ '€' } | ${ '' }  | ${ '€0' }
		${ '' }        | ${ '€' } | ${ '' }  | ${ '' }
		${ null }      | ${ '€' } | ${ '' }  | ${ '' }
		${ undefined } | ${ '€' } | ${ '' }  | ${ '' }
	`(
		'correctly formats price given "$value", "$prefix" prefix, and "$suffix" suffix',
		( { value, prefix, suffix, expected } ) => {
			const formattedPrice = formatPrice(
				value,
				getCurrency( { prefix, suffix } )
			);

			expect( formattedPrice ).toEqual( expected );
		}
	);
} );
