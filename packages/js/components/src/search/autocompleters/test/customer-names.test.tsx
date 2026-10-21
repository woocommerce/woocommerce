/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import customerNames from '../customer-names';
import customers from '../customers';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const mockedApiFetch = apiFetch as unknown as jest.Mock;

describe( 'customer names autocompleter', () => {
	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( [] );
	} );

	it( 'searches the name field only', () => {
		customerNames.options( 'zoe' );
		const path = mockedApiFetch.mock.calls[ 0 ][ 0 ].path;

		expect( getQueryArg( path, 'searchby' ) ).toBe( 'name' );
		expect( getQueryArg( path, 'user_type' ) ).toBeUndefined();
	} );

	it( 'matches customers on their name only', () => {
		expect(
			customerNames.getOptionKeywords( {
				id: 1,
				name: 'Zoe Bloggs',
				username: 'bloggs',
				email: 'zoe@example.test',
			} )
		).toEqual( [ 'Zoe Bloggs' ] );
	} );
} );

describe( 'deprecated customers autocompleter', () => {
	beforeEach( () => {
		mockedApiFetch.mockReset();
		mockedApiFetch.mockResolvedValue( [] );
	} );

	it( 'behaves like the customer names completer it aliases', () => {
		const { name, className, ...behaviour } = customerNames;
		expect( customers ).toMatchObject( behaviour );
	} );

	it( 'keeps its own name and class so existing consumers are unaffected', () => {
		expect( customers.name ).toBe( 'customers' );
		expect( customers.className ).toBe(
			'woocommerce-search__customers-result'
		);
	} );
} );
