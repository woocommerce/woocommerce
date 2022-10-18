/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';
import { hashExportArgs } from '../utils';

const defaultState = {
	errors: {},
	requesting: {},
	exportMeta: {},
	exportIds: {},
};

describe( 'export reducer', () => {
	it( 'should return a default state', () => {
		// @ts-expect-error reducer action should not be empty but it is
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_IS_REQUESTING', () => {
		const selectorArgs = {
			type: 'orders',
			args: {
				after: '2020-01-01T00:00:00',
				before: '2019-12-31T23:59:59',
				status_is: 'pending',
			},
		};
		const state = reducer( defaultState, {
			type: TYPES.SET_IS_REQUESTING,
			selector: 'startExport',
			selectorArgs,
			isRequesting: true,
		} );

		expect(
			state.requesting.startExport[ hashExportArgs( selectorArgs ) ]
		).toBe( true );
	} );

	it( 'should handle SET_EXPORT_ID', () => {
		const exportType = 'orders';
		const exportArgs = {
			after: '2020-01-01T00:00:00',
			before: '2019-12-31T23:59:59',
			status_is: 'pending',
		};
		const hashArgs = {
			type: exportType,
			args: exportArgs,
		};
		const exportId = '15967352870671';
		const state = reducer( defaultState, {
			type: TYPES.SET_EXPORT_ID,
			exportType,
			exportArgs,
			exportId,
		} );

		expect(
			state.exportIds[ exportType ][ hashExportArgs( hashArgs ) ]
		).toBe( exportId );
		expect( state.exportMeta[ exportId ] ).toEqual( {
			exportType,
			exportArgs,
		} );
	} );

	it( 'should handle SET_ERROR', () => {
		const selectorArgs = {
			type: 'orders',
			args: {
				after: '2020-01-01T00:00:00',
				before: '2019-12-31T23:59:59',
				status_is: 'pending',
			},
		};
		const error = 'There is no data to export for the given request.';
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'startExport',
			selectorArgs,
			error,
		} );

		expect(
			state.errors.startExport[ hashExportArgs( selectorArgs ) ]
		).toBe( error );
	} );
} );
