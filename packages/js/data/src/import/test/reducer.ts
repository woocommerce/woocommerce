/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = {
	activeImport: false,
	importStatus: {},
	importTotals: {},
	errors: {},
	lastImportStartTimestamp: 0,
	period: {
		date: moment().format( 'MM/DD/YYYY' ),
		label: 'all',
	},
	skipPrevious: true,
};

describe( 'import reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error reducer action should not be empty but it is
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_IMPORT_STATUS', () => {
		const query = Date.now();
		const state = reducer( defaultState, {
			type: TYPES.SET_IMPORT_STATUS,
			query,
			importStatus: { is_importing: false },
		} );
		const stringifiedQuery = JSON.stringify( query );
		expect( state.importStatus ).toHaveProperty( stringifiedQuery );
		expect( state.importStatus[ stringifiedQuery ].is_importing ).toEqual(
			false
		);
	} );

	it( 'should handle SET_IMPORT_TOTALS', () => {
		const query = { days: 90, skip_existing: true };
		const state = reducer( defaultState, {
			type: TYPES.SET_IMPORT_TOTALS,
			query,
			importTotals: {
				customers: 1,
				orders: 6,
			},
		} );
		const stringifiedQuery = JSON.stringify( query );

		expect( state.importTotals ).toHaveProperty( stringifiedQuery );
		expect( state.importTotals[ stringifiedQuery ].customers ).toEqual( 1 );
		expect( state.importTotals[ stringifiedQuery ].orders ).toEqual( 6 );
	} );

	it( 'should handle SET_IMPORT_STARTED', () => {
		const activeImport = true;
		const state = reducer( defaultState, {
			type: TYPES.SET_IMPORT_STARTED,
			activeImport,
		} );

		expect( state.activeImport ).toBeTruthy();
		expect( state.lastImportStartTimestamp > 0 ).toBeTruthy();
	} );

	it( 'should handle SET_IMPORT_DATE', () => {
		const date = '08/04/2020';
		const state = reducer( defaultState, {
			type: TYPES.SET_IMPORT_DATE,
			date,
		} );

		expect( state.period.date ).toEqual( date );
		expect( state.period.label ).toEqual( 'custom' );
		expect( state.activeImport ).toEqual( false );
	} );

	it( 'should handle SET_IMPORT_PERIOD', () => {
		defaultState.activeImport = true;
		const date = '08/04/2020';
		const state = reducer( defaultState, {
			type: TYPES.SET_IMPORT_PERIOD,
			date,
		} );
		expect( state.period.label ).toEqual( date );
		expect( state.activeImport ).toEqual( false );
	} );

	it( 'should handle SET_SKIP_IMPORTED', () => {
		const skipPrevious = false;
		defaultState.activeImport = true;
		const state = reducer( defaultState, {
			type: TYPES.SET_SKIP_IMPORTED,
			skipPrevious,
		} );
		expect( state.skipPrevious ).toEqual( false );
		expect( state.activeImport ).toEqual( false );
	} );

	it( 'should handle SET_IMPORT_ERROR', () => {
		const query = 'test-import-error';
		const state = reducer( defaultState, {
			type: TYPES.SET_IMPORT_ERROR,
			query,
			error: { code: 'error' },
		} );
		const stringifiedQuery = JSON.stringify( query );
		expect(
			(
				state.errors[ stringifiedQuery ] as {
					code: string;
				}
			 ).code
		).toBe( 'error' );
	} );
} );
