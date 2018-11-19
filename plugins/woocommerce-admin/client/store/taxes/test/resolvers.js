/*
* @format
*/

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getTaxes } = resolvers;

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		setTaxes: jest.fn(),
	} ),
} ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// @TODO reactivate tests when we use the correct API routes instead of swaggerhub
xdescribe( 'getTaxes', () => {
	const TAXES_1 = [ { tax_rate_id: 1214 }, { tax_rate_id: 1215 }, { tax_rate_id: 1216 } ];

	const TAXES_2 = [ { tax_rate_id: 1 }, { tax_rate_id: 2 }, { tax_rate_id: 3 } ];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/reports/taxes' ) {
				return Promise.resolve( TAXES_1 );
			}
			if ( options.path === '/wc/v3/reports/taxes?orderby=tax_rate_id' ) {
				return Promise.resolve( TAXES_2 );
			}
		} );
	} );

	it( 'returns requested report data', async () => {
		expect.assertions( 1 );
		await getTaxes();
		expect( dispatch().setTaxes ).toHaveBeenCalledWith( TAXES_1, undefined );
	} );

	it( 'returns requested report data for a specific query', async () => {
		expect.assertions( 1 );
		await getTaxes( { orderby: 'tax_rate_id' } );
		expect( dispatch().setTaxes ).toHaveBeenCalledWith( TAXES_2, { orderby: 'tax_rate_id' } );
	} );
} );
