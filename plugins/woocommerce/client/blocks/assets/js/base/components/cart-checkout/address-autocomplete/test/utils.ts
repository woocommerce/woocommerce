/**
 * Internal dependencies
 */
import { getAutocompleteCountry } from '../utils';

jest.mock( '@woocommerce/block-settings', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/block-settings' ),
	// Billing allows several countries; shipping is locked to one. Together
	// these cover every branch of getAutocompleteCountry().
	ALLOWED_COUNTRIES: {
		US: 'United States',
		CA: 'Canada',
		GB: 'United Kingdom',
	},
	SHIPPING_COUNTRIES: {
		US: 'United States',
	},
} ) );

describe( 'getAutocompleteCountry', () => {
	it( 'returns the country as-is when it is allowed for the address type', () => {
		expect( getAutocompleteCountry( 'CA', 'billing' ) ).toBe( 'CA' );
		expect( getAutocompleteCountry( 'US', 'shipping' ) ).toBe( 'US' );
	} );

	it( 'falls back to the single allowed country when the current one is not allowed', () => {
		expect( getAutocompleteCountry( 'DE', 'shipping' ) ).toBe( 'US' );
		expect( getAutocompleteCountry( '', 'shipping' ) ).toBe( 'US' );
	} );

	it( 'returns an empty string when multiple countries are allowed and none match', () => {
		expect( getAutocompleteCountry( 'DE', 'billing' ) ).toBe( '' );
		expect( getAutocompleteCountry( '', 'billing' ) ).toBe( '' );
	} );
} );
