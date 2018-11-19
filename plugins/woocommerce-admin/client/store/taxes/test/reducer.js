/**
 * @format
 */

/**
 * External dependencies
 */
import deepFreeze from 'deep-freeze';

/**
 * Internal dependencies
 */
import { ERROR } from 'store/constants';
import taxesReducer from '../reducer';
import { getJsonString } from 'store/utils';

describe( 'taxesReducer()', () => {
	it( 'returns an empty data object by default', () => {
		const state = taxesReducer( undefined, {} );
		expect( state ).toEqual( {} );
	} );

	it( 'returns with received taxes data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			orderby: 'orders_count',
		};
		const taxes = [ { tax_rate_id: 1214 }, { tax_rate_id: 1215 }, { tax_rate_id: 1216 } ];

		const state = taxesReducer( originalState, {
			type: 'SET_TAXES',
			query,
			taxes,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( taxes );
	} );

	it( 'tracks multiple queries in taxes data', () => {
		const otherQuery = {
			orderby: 'tax_rate_id',
		};
		const otherQueryKey = getJsonString( otherQuery );
		const otherTaxes = [ { tax_rate_id: 1 }, { tax_rate_id: 2 }, { tax_rate_id: 3 } ];
		const otherQueryState = {
			[ otherQueryKey ]: otherTaxes,
		};
		const originalState = deepFreeze( otherQueryState );
		const query = {
			orderby: 'orders_count',
		};
		const taxes = [ { tax_rate_id: 1214 }, { tax_rate_id: 1215 }, { tax_rate_id: 1216 } ];

		const state = taxesReducer( originalState, {
			type: 'SET_TAXES',
			query,
			taxes,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( taxes );
		expect( state[ otherQueryKey ] ).toEqual( otherTaxes );
	} );

	it( 'returns with received error data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			orderby: 'orders_count',
		};

		const state = taxesReducer( originalState, {
			type: 'SET_TAXES_ERROR',
			query,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( ERROR );
	} );
} );
