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
import notesReducer from '../reducer';
import { getJsonString } from 'store/utils';

describe( 'notesReducer()', () => {
	it( 'returns an empty data object by default', () => {
		const state = notesReducer( undefined, {} );
		expect( state ).toEqual( {} );
	} );

	it( 'returns with received notes data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			page: 2,
		};
		const notes = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

		const state = notesReducer( originalState, {
			type: 'SET_NOTES',
			query,
			notes,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( notes );
	} );

	it( 'tracks multiple queries in notes data', () => {
		const otherQuery = {
			page: 3,
		};
		const otherQueryKey = getJsonString( otherQuery );
		const otherNotes = [ { id: 1 }, { id: 2 }, { id: 3 } ];
		const otherQueryState = {
			[ otherQueryKey ]: otherNotes,
		};
		const originalState = deepFreeze( otherQueryState );
		const query = {
			page: 2,
		};
		const notes = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

		const state = notesReducer( originalState, {
			type: 'SET_NOTES',
			query,
			notes,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( notes );
		expect( state[ otherQueryKey ] ).toEqual( otherNotes );
	} );

	it( 'returns with received error data', () => {
		const originalState = deepFreeze( {} );
		const query = {
			page: 2,
		};

		const state = notesReducer( originalState, {
			type: 'SET_NOTES_ERROR',
			query,
		} );

		const queryKey = getJsonString( query );
		expect( state[ queryKey ] ).toEqual( ERROR );
	} );
} );
