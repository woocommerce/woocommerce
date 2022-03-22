/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = { errors: {}, notices: {} };

describe( 'notes reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should set notices on GET_NOTICES_SUCCESS', () => {
		const state = reducer( defaultState, {
			type: TYPES.GET_NOTICES_SUCCESS,
			notices: { testNotice: 'test' },
		} );

		expect( state.notices ).toStrictEqual( { testNotice: 'test' } );
	} );

	it( 'should set an error on GET_NOTICES_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.GET_NOTICES_ERROR,
			error: 'test-error',
		} );

		expect( state.errors.getNotices ).toStrictEqual( 'test-error' );
	} );

	it( 'should remove the notice on DISMISS_NOTICE_SUCCESS', () => {
		const state = reducer(
			{
				...defaultState,
				notices: {
					testNotice: 'test',
				},
			},
			{
				type: TYPES.DISMISS_NOTICE_SUCCESS,
				id: 'testNotice',
			}
		);

		expect( state.notices ).toStrictEqual( {} );
	} );

	it( 'should set an error on DISMISS_NOTICE_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.DISMISS_NOTICE_ERROR,
			error: 'test-error',
			id: 'testNotice',
		} );

		expect( state.errors.testNotice ).toBe( 'test-error' );
	} );
} );
