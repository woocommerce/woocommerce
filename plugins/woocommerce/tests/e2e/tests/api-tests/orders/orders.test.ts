/**
 * External dependencies
 */
import type { APIRequestContext } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test, expect } from '../../../fixtures/api-tests-fixtures';

const verifyPaginationContract = async ( request: APIRequestContext ) => {
	const orderIds: number[] = [];
	let primaryError: unknown;

	try {
		for ( let index = 0; index < 5; index++ ) {
			const response = await request.post( './wp-json/wc/v3/orders', {
				data: {
					customer_note: `pagination fixture ${ index }`,
				},
			} );
			const body = await response.json();

			if ( Number.isSafeInteger( body.id ) && body.id > 0 ) {
				orderIds.push( body.id );
			}

			expect( response.status() ).toBe( 201 );
			expect( body.id ).toBeGreaterThan( 0 );
			expect( Number.isSafeInteger( body.id ) ).toBe( true );
		}

		expect( orderIds ).toHaveLength( 5 );
		expect( new Set( orderIds ).size ).toBe( orderIds.length );

		const getPage = async ( page: number, offset?: number ) => {
			const response = await request.get( './wp-json/wc/v3/orders', {
				params: {
					include: orderIds.join( ',' ),
					orderby: 'include',
					per_page: 2,
					page,
					...( offset === undefined ? {} : { offset } ),
				},
			} );
			const body = await response.json();

			expect( response.status() ).toBe( 200 );
			expect( Array.isArray( body ) ).toBe( true );

			return { response, body };
		};

		const page1 = await getPage( 1 );
		const page2 = await getPage( 2 );
		const page3 = await getPage( 3 );
		const page4 = await getPage( 4 );
		const offsetPage = await getPage( 2, 3 );

		expect( page1.response.headers()[ 'x-wp-total' ] ).toBe( '5' );
		expect( page1.response.headers()[ 'x-wp-totalpages' ] ).toBe( '3' );
		expect( page1.body.map( ( order ) => order.id ) ).toEqual(
			orderIds.slice( 0, 2 )
		);
		expect( page2.body.map( ( order ) => order.id ) ).toEqual(
			orderIds.slice( 2, 4 )
		);
		expect( page3.body.map( ( order ) => order.id ) ).toEqual(
			orderIds.slice( 4 )
		);
		expect( page4.body ).toEqual( [] );
		expect( offsetPage.body.map( ( order ) => order.id ) ).toEqual(
			orderIds.slice( 3, 5 )
		);
		expect(
			new Set( [
				...page1.body.map( ( order ) => order.id ),
				...page2.body.map( ( order ) => order.id ),
			] ).size
		).toBe( 4 );
	} catch ( error ) {
		primaryError = error;
	}

	const cleanupErrors: unknown[] = [];
	for ( const orderId of orderIds.toReversed() ) {
		try {
			const response = await request.delete(
				`./wp-json/wc/v3/orders/${ orderId }`,
				{
					data: { force: true },
				}
			);
			expect( [ 200, 404 ] ).toContain( response.status() );
		} catch ( error ) {
			cleanupErrors.push( error );
		}
	}

	if ( primaryError && cleanupErrors.length ) {
		throw new AggregateError(
			[ primaryError, ...cleanupErrors ],
			'Pagination assertions and order cleanup both failed.'
		);
	}
	if ( cleanupErrors.length ) {
		throw new AggregateError( cleanupErrors, 'Order cleanup failed.' );
	}
	if ( primaryError ) {
		throw primaryError;
	}
};

test.describe( 'Orders API tests', () => {
	test.describe( 'List all orders', () => {
		test( 'pagination', async ( { request } ) => {
			await expect(
				verifyPaginationContract( request )
			).resolves.toBeUndefined();
		} );
	} );
} );
