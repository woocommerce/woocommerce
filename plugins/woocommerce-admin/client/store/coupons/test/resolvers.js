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

const { getCoupons } = resolvers;

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		setCoupons: jest.fn(),
	} ),
} ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

// @TODO reactivate tests when we use the correct API routes instead of swaggerhub
xdescribe( 'getCoupons', () => {
	const COUPONS_1 = [ { coupon_id: 1214 }, { coupon_id: 1215 }, { coupon_id: 1216 } ];

	const COUPONS_2 = [ { coupon_id: 1 }, { coupon_id: 2 }, { coupon_id: 3 } ];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/reports/coupons' ) {
				return Promise.resolve( COUPONS_1 );
			}
			if ( options.path === '/wc/v3/reports/coupons?orderby=coupon_id' ) {
				return Promise.resolve( COUPONS_2 );
			}
		} );
	} );

	it( 'returns requested report data', async () => {
		expect.assertions( 1 );
		await getCoupons();
		expect( dispatch().setCoupons ).toHaveBeenCalledWith( COUPONS_1, undefined );
	} );

	it( 'returns requested report data for a specific query', async () => {
		expect.assertions( 1 );
		await getCoupons( { orderby: 'coupon_id' } );
		expect( dispatch().setCoupons ).toHaveBeenCalledWith( COUPONS_2, { orderby: 'coupon_id' } );
	} );
} );
