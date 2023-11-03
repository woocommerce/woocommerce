/**
 * @jest-environment node
 */

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import TYPES from '../action-types';

const defaultState = { errors: {}, noteQueries: {}, notes: {}, requesting: {} };

describe( 'notes reducer', () => {
	it( 'should return a default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( defaultState );
		expect( state ).not.toBe( defaultState );
	} );

	it( 'should handle SET_ERROR', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_ERROR,
			selector: 'getNotes',
			error: '404',
		} );

		expect( state.errors.updateNote ).toBeUndefined();
		expect( state.errors.getNotes ).toBe( '404' );
	} );

	it( 'should handle SET_NOTE', () => {
		const state = reducer( defaultState, {
			type: TYPES.SET_NOTE,
			noteId: 21,
			noteFields: {
				field1: 'value1',
			},
		} );

		expect( state.notes[ 21 ].field1 ).toBe( 'value1' );
	} );

	it( 'should handle SET_NOTE on an existing note', () => {
		const state = reducer(
			{
				...defaultState,
				notes: {
					10: {
						field1: 'old-value',
					},
				},
			},
			{
				type: TYPES.SET_NOTE,
				noteId: 10,
				noteFields: {
					field1: 'updated-value',
				},
			}
		);

		expect( state.notes[ 10 ].field1 ).toBe( 'updated-value' );
	} );

	it( 'should handle SET_NOTE_IS_UPDATING', () => {
		const state = reducer(
			{
				...defaultState,
				notes: {
					10: {},
				},
			},
			{
				type: TYPES.SET_NOTE_IS_UPDATING,
				noteId: 10,
				isUpdating: true,
			}
		);

		expect( state.notes[ 10 ].isUpdating ).toBe( true );
	} );

	it( 'should handle SET_NOTES', () => {
		const state = reducer(
			{
				...defaultState,
				notes: {
					10: {
						id: 10,
						title: 'Initial note',
					},
				},
			},
			{
				type: TYPES.SET_NOTES,
				notes: [
					{
						id: 5,
						title: 'Notes on notes on notes',
					},
					{
						id: 22,
						title: 'Update now!',
					},
				],
			}
		);

		expect( state.notes[ 10 ].title ).toBe( 'Initial note' );
		expect( state.notes[ 5 ].title ).toBe( 'Notes on notes on notes' );
		expect( state.notes[ 22 ].title ).toBe( 'Update now!' );
	} );

	it( 'should handle SET_NOTES_QUERY', () => {
		const query = {
			page: 1,
			status: 'unactioned',
		};

		const state = reducer( defaultState, {
			type: TYPES.SET_NOTES_QUERY,
			query,
			noteIds: [ 10, 22, 5 ],
		} );

		expect( state.noteQueries[ JSON.stringify( query ) ] ).toContain( 10 );
		expect( state.noteQueries[ JSON.stringify( query ) ] ).toContain( 22 );
		expect( state.noteQueries[ JSON.stringify( query ) ] ).toContain( 5 );
	} );
} );
