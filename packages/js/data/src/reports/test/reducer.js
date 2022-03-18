/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	itemErrors: {},
	items: {},
	statErrors: {},
	stats: {},
};

describe( 'reports reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_REPORT_ITEMS', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_REPORT_ITEMS,
			resourceName: 'test-resource-items',
			items: [ 1, 2 ],
		} );

		expect( state.items ).toHaveProperty( 'test-resource-items' );
		expect( state.items[ 'test-resource-items' ] ).toContain( 1 );
		expect( state.items[ 'test-resource-items' ] ).toContain( 2 );
	} );

	it( 'should handle SET_REPORT_STATS', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_REPORT_STATS,
			resourceName: 'test-resource-stats',
			stats: [ 3, 4 ],
		} );

		expect( state.stats ).toHaveProperty( 'test-resource-stats' );
		expect( state.stats[ 'test-resource-stats' ] ).toContain( 3 );
		expect( state.stats[ 'test-resource-stats' ] ).toContain( 4 );
	} );

	it( 'should handle SET_ITEM_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ITEM_ERROR,
			resourceName: 'test-resource-items',
			error: { code: 'error' },
		} );

		expect( state.itemErrors[ 'test-resource-items' ].code ).toBe(
			'error'
		);
	} );

	it( 'should handle SET_STAT_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_STAT_ERROR,
			resourceName: 'test-resource-stats',
			error: { code: 'error' },
		} );

		expect( state.statErrors[ 'test-resource-stats' ].code ).toBe(
			'error'
		);
	} );
} );
