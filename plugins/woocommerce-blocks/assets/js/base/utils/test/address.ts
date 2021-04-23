/**
 * External dependencies
 */
import { emptyHiddenAddressFields } from '@woocommerce/base-utils';

describe( 'emptyHiddenAddressFields', () => {
	it( "Removes state from an address where the country doesn't use states", () => {
		const address = {
			first_name: 'Jonny',
			last_name: 'Awesome',
			company: 'WordPress',
			address_1: '123 Address Street',
			address_2: 'Address 2',
			city: 'Vienna',
			postcode: '1120',
			country: 'AT',
			state: 'CA', // This should be removed.
			email: 'jonny.awesome@email.com',
			phone: '',
		};
		const filteredAddress = emptyHiddenAddressFields( address );
		expect( filteredAddress ).toHaveProperty( 'state', '' );
	} );
} );
