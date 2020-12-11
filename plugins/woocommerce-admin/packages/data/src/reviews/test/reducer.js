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
		expect( state.data[ '1' ] ).toEqual( reviews[ 0 ] );
		expect( state.data[ '2' ] ).toEqual( reviews[ 1 ] );
	} );

	it( 'should handle UPDATE_REVIEWS with _fields, only update updated fields', () => {
		const reviews = [ { id: 1 }, { id: 2 } ];
		const totalCount = 45;
		const query = { status: 'flavortown', _fields: [ 'id' ] };
		const state = reducer(
			{
				...defaultState,
				data: {
					1: { id: 1, review: 'Yum!' },
					2: { id: 2, review: 'Dynamite!' },
				},
			},
			{
				type: TYPES.UPDATE_REVIEWS,
				reviews,
				query,
				totalCount,
			}
		);

		const stringifiedQuery = JSON.stringify( query );

		expect( state.reviews[ stringifiedQuery ].data ).toHaveLength( 2 );
		expect(
			state.reviews[ stringifiedQuery ].data.includes( 1 )
		).toBeTruthy();
		expect(
			state.reviews[ stringifiedQuery ].data.includes( 2 )
		).toBeTruthy();

		expect( state.reviews[ stringifiedQuery ].totalCount ).toBe( 45 );
		expect( state.data[ '1' ].review ).toEqual( 'Yum!' );
		expect( state.data[ '2' ].review ).toEqual( 'Dynamite!' );
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

	it( 'should handle SET_REVIEW', () => {
		const state = reducer(
			{
				...defaultState,
				data: {
					4: { title: 'test' },
				},
			},
			{
				type: TYPES.SET_REVIEW,
				reviewId: 4,
				reviewData: {
					title: 'test updated',
				},
			}
		);

		expect( state.data[ 4 ].title ).toEqual( 'test updated' );
	} );

	it( 'should handle SET_REVIEW_IS_UPDATING', () => {
		const state = reducer(
			{
				...defaultState,
				data: {
					4: { title: 'test' },
				},
			},
			{
				type: TYPES.SET_REVIEW_IS_UPDATING,
				reviewId: 4,
				isUpdating: true,
			}
		);

		expect( state.data[ 4 ].isUpdating ).toEqual( true );

		const newstate = reducer( state, {
			type: TYPES.SET_REVIEW_IS_UPDATING,
			reviewId: 4,
			isUpdating: false,
		} );

		expect( newstate.data[ 4 ].isUpdating ).toEqual( false );
	} );
} );
