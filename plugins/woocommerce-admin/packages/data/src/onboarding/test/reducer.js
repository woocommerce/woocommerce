/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	profileItems: {},
	errors: {},
	requesting: {},
};

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_PROFILE_ITEMS', () => {
		const state = reducer(
			{
				profileItems: { previousItem: 'value' },
			},
			{
				type: TYPES.SET_PROFILE_ITEMS,
				profileItems: { propertyName: 'value' },
			}
		);

		expect( state.profileItems ).toHaveProperty( 'previousItem' );
		expect( state.profileItems ).toHaveProperty( 'propertyName' );
		expect( state.profileItems.propertyName ).toBe( 'value' );
	} );

	it( 'should handle SET_PROFILE_ITEMS with replace', () => {
		const state = reducer(
			{
				profileItems: { previousItem: 'value' },
			},
			{
				type: TYPES.SET_PROFILE_ITEMS,
				profileItems: { propertyName: 'value' },
				replace: true,
			}
		);

		expect( state.profileItems ).not.toHaveProperty( 'previousItem' );
		expect( state.profileItems ).toHaveProperty( 'propertyName' );
		expect( state.profileItems.propertyName ).toBe( 'value' );
	} );

	it( 'should handle SET_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'getProfileItems',
			error: { code: 'error' },
		} );

		/* eslint-disable dot-notation */
		expect( state.errors[ 'getProfileItems' ].code ).toBe( 'error' );
		/* eslint-enable dot-notation */
	} );

	it( 'should handle SET_IS_REQUESTING', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_IS_REQUESTING,
			selector: 'updateProfileItems',
			isRequesting: true,
		} );

		/* eslint-disable dot-notation */
		expect( state.requesting[ 'updateProfileItems' ] ).toBeTruthy();
		/* eslint-enable dot-notation */
	} );
} );
