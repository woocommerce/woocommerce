/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer, { defaultState } from '../reducer';
import TYPES from '../action-types';

describe( 'plugins reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
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

	it( 'should handle GET_PAYMENT_METHODS_SUCCESS', () => {
		const state = reducer(
			{
				paymentMethods: [ { previousItem: 'value' } ],
			},
			{
				type: TYPES.GET_PAYMENT_METHODS_SUCCESS,
				paymentMethods: [ { newItem: 'changed' } ],
			}
		);

		expect( state.paymentMethods[ 0 ] ).not.toHaveProperty(
			'previousItem'
		);
		expect( state.paymentMethods[ 0 ] ).toHaveProperty( 'newItem' );
		expect( state.paymentMethods[ 0 ].newItem ).toBe( 'changed' );
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

	it( 'should handle SET_TASKS_STATUS', () => {
		const state = reducer(
			{
				tasksStatus: { hasProducts: true },
			},
			{
				type: TYPES.SET_TASKS_STATUS,
				tasksStatus: { hasHomepage: false },
			}
		);

		expect( state.tasksStatus ).toHaveProperty( 'hasProducts' );
		expect( state.tasksStatus ).toHaveProperty( 'hasHomepage' );
		expect( state.tasksStatus.hasProducts ).toBe( true );
		expect( state.tasksStatus.hasHomepage ).toBe( false );
	} );
} );
