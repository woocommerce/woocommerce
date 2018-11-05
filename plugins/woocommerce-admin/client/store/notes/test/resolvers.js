/*
* @format
*/

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getNotes } = resolvers;

jest.mock( '@wordpress/data', () => ( {
	dispatch: jest.fn().mockReturnValue( {
		setNotes: jest.fn(),
	} ),
} ) );
jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getNotes', () => {
	const NOTES_1 = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

	const NOTES_2 = [ { id: 1 }, { id: 2 }, { id: 3 } ];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/admin/notes' ) {
				return Promise.resolve( NOTES_1 );
			}
			if ( options.path === '/wc/v3/admin/notes?page=2' ) {
				return Promise.resolve( NOTES_2 );
			}
		} );
	} );

	it( 'returns requested data', async () => {
		expect.assertions( 1 );
		await getNotes();
		expect( dispatch().setNotes ).toHaveBeenCalledWith( NOTES_1, undefined );
	} );

	it( 'returns requested data for a specific query', async () => {
		expect.assertions( 1 );
		await getNotes( { page: 2 } );
		expect( dispatch().setNotes ).toHaveBeenCalledWith( NOTES_2, { page: 2 } );
	} );
} );
