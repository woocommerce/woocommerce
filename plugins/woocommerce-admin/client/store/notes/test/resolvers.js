/*
* @format
*/

/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import resolvers from '../resolvers';

const { getNotes } = resolvers;

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'getNotes', () => {
	const NOTES_1 = [ { id: 1214 }, { id: 1215 }, { id: 1216 } ];

	const NOTES_2 = [ { id: 1 }, { id: 2 }, { id: 3 } ];

	beforeAll( () => {
		apiFetch.mockImplementation( options => {
			if ( options.path === '/wc/v3/admin/notes' ) {
				return Promise.resolve( NOTES_1 );
			}
			if ( options.path === '/wc/v3/admin/notes/&page=2' ) {
				return Promise.resolve( NOTES_2 );
			}
		} );
	} );

	it( 'returns requested data', async () => {
		getNotes().then( data => expect( data ).toEqual( NOTES_1 ) );
	} );

	it( 'returns requested data for a specific query', async () => {
		getNotes( { page: 2 } ).then( data => expect( data ).toEqual( NOTES_2 ) );
	} );
} );
