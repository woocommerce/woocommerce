/*
 * @format
 */

// @TODO: Create these tests when /reports/variations becomes available

// /**
//  * External dependencies
//  */
// import apiFetch from '@wordpress/api-fetch';
// import { dispatch } from '@wordpress/data';
//
// /**
//  * Internal dependencies
//  */
// import resolvers from '../resolvers';
//
// const { getVariations } = resolvers;

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		setVariations: jest.fn(),
	} ),
} ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getVariations', () => {
	// const variations = [
	// 	{
	// 		id: 3,
	// 		attributes: [],
	// 	},
	// 	{
	// 		id: 4,
	// 		attributes: [],
	// 	},
	// ];
	//
	// beforeAll( () => {
	// 	apiFetch.mockImplementation( options => {
	// 		if ( options.path === '/wc/v3/products/47/variations' ) {
	// 			return Promise.resolve( variations );
	// 		}
	// 	} );
	// } );

	it( 'returns requested variations', async () => {
		// // expect.assertions( 1 );
		// await getVariations( {}, { products: '47' } );
		// expect( dispatch().setVariations ).toHaveBeenCalledWith( variations, { products: '47' } );
	} );
} );
