/**
 * Internal dependencies
 */
import { getNotes } from '../selectors';

describe( 'getNotes', () => {
	it( 'should return an empty array by default', () => {
		const state = {
			noteQueries: {},
		};

		expect( getNotes( state, { param: 'test' } ) ).toEqual( [] );
	} );

	it( 'should return results with records', () => {
		const query = { param: 'test' };
		const state = {
			noteQueries: {
				[ JSON.stringify( query ) ]: [ 1, 2 ],
			},
			notes: {
				1: { name: 'test' },
				2: { name: 'another' },
			},
		};
		expect( getNotes( state, query ) ).toEqual( [
			{ name: 'test' },
			{ name: 'another' },
		] );
	} );

	it( 'should return the same instance with the same arguments, and if original query not updated', () => {
		const query = { param: 'test' };
		let state = {
			noteQueries: {
				[ JSON.stringify( query ) ]: [ 1, 2 ],
			},
			notes: {
				1: { name: 'test' },
				2: { name: 'another' },
			},
		};

		const firstCall = getNotes( state, query );

		// Simulate update states
		state = {
			...state,
			noteQueries: {
				...state.noteQueries,
				randomQuery: [ 3, 4 ],
			},
		};

		const secondCall = getNotes( state, query );

		expect( firstCall ).toBe( secondCall );
	} );

	it( 'should return updated instance if a note is updated', () => {
		const query = { param: 'test' };
		let state = {
			noteQueries: {
				[ JSON.stringify( query ) ]: [ 1, 2 ],
			},
			notes: {
				1: { name: 'test' },
				2: { name: 'another' },
			},
		};

		const firstCall = getNotes( state, query );

		// Simulate update states
		state = {
			...state,
			notes: {
				...state.notes,
				1: {
					...state.notes[ 1 ],
					updated: true,
				},
			},
		};

		const secondCall = getNotes( state, query );

		expect( firstCall ).not.toBe( secondCall );
		expect( firstCall[ 0 ].updated ).not.toBeDefined();
		expect( secondCall[ 0 ].updated ).toBe( true );
	} );

	it( 'should return updated instance if query is updated', () => {
		const query = { param: 'test' };
		let state = {
			noteQueries: {
				[ JSON.stringify( query ) ]: [ 1, 2 ],
			},
			notes: {
				1: { name: 'test' },
				2: { name: 'another' },
			},
		};

		const firstCall = getNotes( state, query );

		// Simulate update states
		state = {
			...state,
			noteQueries: {
				...state.noteQueries,
				[ JSON.stringify( query ) ]: [ 1 ],
			},
		};

		const secondCall = getNotes( state, query );

		expect( firstCall ).not.toBe( secondCall );
		expect( firstCall.length ).toBe( 2 );
		expect( secondCall.length ).toBe( 1 );
	} );
} );
