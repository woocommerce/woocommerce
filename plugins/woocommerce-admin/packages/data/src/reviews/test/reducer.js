/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	reviews: {},
	errors: {},
	data: {},
};

describe( 'reviews reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle UPDATE_REVIEWS', () => {
		const reviews = [
			{ id: 1, review: 'Yum!' },
			{ id: 2, review: 'Dynamite!' },
		];
		const totalCount = 45;
		const query = { status: 'flavortown' };
		const state = reducer( defaultState, {
			type: TYPES.UPDATE_REVIEWS,
			reviews,
			query,
			totalCount,
		} );

		const stringifiedQuery = JSON.stringify( query );

		expect( state.reviews[ stringifiedQuery ].data ).toHaveLength( 2 );
		expect(
			state.reviews[ stringifiedQuery ].data.includes( 1 )
		).toBeTruthy();
		expect(
			state.reviews[ stringifiedQuery ].data.includes( 2 )
		).toBeTruthy();

		expect( state.reviews[ stringifiedQuery ].totalCount ).toBe( 45 );
		expect( state.data[ '1' ] ).toBe( reviews[ 0 ] );
		expect( state.data[ '2' ] ).toBe( reviews[ 1 ] );
	} );

	it( 'should handle SET_ERROR', () => {
		const query = { status: 'flavortown' };
		const error = 'Baaam!';
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			query,
			error,
		} );

		const stringifiedQuery = JSON.stringify( query );
		expect( state.errors[ stringifiedQuery ] ).toBe( error );
	} );
} );
