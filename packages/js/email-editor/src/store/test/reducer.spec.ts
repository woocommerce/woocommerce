/**
 * Internal dependencies
 */
import { reducer } from '../reducer';
import { State } from '../types';

const state = {
	preview: { toEmail: 'old@example.com', isModalOpened: false },
	urls: { listings: '/listings', send: '/send' },
	styles: { globalStylesPostId: 5, other: 'kept' },
	theme: { styles: {} },
	editorSettings: { old: true },
	postId: 1,
	postType: 'woo_email',
} as unknown as State;

describe( 'email editor store reducer', () => {
	it( 'merges a preview change into the existing preview state', () => {
		const next = reducer( state, {
			type: 'CHANGE_PREVIEW_STATE',
			state: { isModalOpened: true },
		} );

		expect( next.preview ).toEqual( {
			toEmail: 'old@example.com',
			isModalOpened: true,
		} );
		expect( state.preview.isModalOpened ).toBe( false );
	} );

	it( 'merges new URLs into the existing ones', () => {
		const next = reducer( state, {
			type: 'SET_EDITOR_URLS',
			urls: { send: '/new-send' },
		} );

		expect( next.urls ).toEqual( {
			listings: '/listings',
			send: '/new-send',
		} );
	} );

	it( 'applies the editor config without dropping other preview and style values', () => {
		const next = reducer( state, {
			type: 'SET_EDITOR_CONFIG',
			config: {
				editorSettings: { fresh: true },
				theme: { styles: { color: {} } },
				urls: { listings: '/l' },
				userEmail: 'me@example.com',
			},
		} );

		expect( next.preview ).toEqual( {
			toEmail: 'me@example.com',
			isModalOpened: false,
		} );
		expect( next.styles ).toEqual( {
			globalStylesPostId: null,
			other: 'kept',
		} );
		expect( next.editorSettings ).toEqual( { fresh: true } );
	} );

	it( 'returns the same state for an unknown action', () => {
		expect( reducer( state, { type: 'UNKNOWN' } ) ).toBe( state );
	} );
} );
