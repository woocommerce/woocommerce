/**
 * External dependencies
 */
import { isPostcode } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { formatPostcode } from '../format-postcode';
import formatPostcodeFixtures from './format-postcode-fixtures.json';

describe( 'formatPostcode', () => {
	// The same fixtures are checked against wc_format_postcode() in PHP.
	it.each( formatPostcodeFixtures )(
		'formats $postcode for $country like wc_format_postcode()',
		( { postcode, country, expected } ) => {
			expect( formatPostcode( postcode, country ) ).toBe( expected );
		}
	);

	it.each( [
		[ '2630166', 'PT' ],
		[ ' 2630-166', 'PT' ],
		[ '2630-166 ', 'PT' ],
		[ '12345', 'PL' ],
		[ ' K1A0B1 ', 'CA' ],
		[ '1234ab ', 'NL' ],
	] )(
		'lets %s for %s pass validation once formatted, as the Store API does',
		( postcode, country ) => {
			expect( isPostcode( { postcode, country } ) ).toBe( false );
			expect(
				isPostcode( {
					postcode: formatPostcode( postcode, country ),
					country,
				} )
			).toBe( true );
		}
	);

	it( 'still rejects a postcode containing a no-break space, like the server', () => {
		expect(
			isPostcode( {
				postcode: formatPostcode( '2630 166', 'PT' ),
				country: 'PT',
			} )
		).toBe( false );
	} );
} );
