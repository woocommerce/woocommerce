/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = { isUpdating: false, requestingErrors: {} };

describe( 'options reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle RECEIVE_OPTIONS', () => {
		const state = reducer( defaultState, {
			type: TYPES.RECEIVE_OPTIONS,
			options: { test_option: 'abc' },
		} );

		/* eslint-disable dot-notation */
		expect( state.requestingErrors[ 'test_option' ] ).toBeUndefined();
		expect( state[ 'test_option' ] ).toBe( 'abc' );
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_REQUESTING_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_REQUESTING_ERROR,
			error: 'My bad',
			name: 'test_option',
		} );

		/* eslint-disable dot-notation */
		expect( state.requestingErrors[ 'test_option' ] ).toBe( 'My bad' );
		expect( state[ 'test_option' ] ).toBeUndefined();
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_UPDATING_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_UPDATING_ERROR,
			error: 'My bad',
		} );

		expect( state.updatingError ).toBe( 'My bad' );
		expect( state.isUpdating ).toBe( false );
	} );

	it( 'should handle SET_IS_UPDATING', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_IS_UPDATING,
			isUpdating: true,
		} );

		expect( state.isUpdating ).toBe( true );
	} );
} );
