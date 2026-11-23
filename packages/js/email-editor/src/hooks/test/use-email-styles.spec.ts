/**
 * Internal dependencies
 */
import {
	cleanupUserStyles,
	setImmutably,
	shortenWpPresetVariables,
} from '../use-email-styles';

jest.mock( '../../store', () => ( { storeName: 'email-editor/editor' } ) );
jest.mock( '../use-user-theme', () => ( { useUserTheme: jest.fn() } ) );

describe( 'email style helpers', () => {
	describe( 'setImmutably', () => {
		it( 'sets a nested value without changing the original object', () => {
			const original = { color: { text: '#000' }, spacing: {} };

			const next = setImmutably(
				original,
				[ 'color', 'background' ],
				'#fff'
			);

			expect( next ).toEqual( {
				color: { text: '#000', background: '#fff' },
				spacing: {},
			} );
			expect( original ).toEqual( {
				color: { text: '#000' },
				spacing: {},
			} );
			expect( next.spacing ).toBe( original.spacing );
		} );

		it( 'creates missing levels and accepts a single key or an array', () => {
			expect( setImmutably( undefined, [ 'a', 'b' ], 1 ) ).toEqual( {
				a: { b: 1 },
			} );
			expect( setImmutably( { a: 1 }, 'b', 2 ) ).toEqual( {
				a: 1,
				b: 2,
			} );
			expect( setImmutably( [ 1, 2 ], 0, 9 ) ).toEqual( [ 9, 2 ] );
		} );
	} );

	describe( 'shortenWpPresetVariables', () => {
		it( 'shortens preset variables at any depth and leaves other values alone', () => {
			expect(
				shortenWpPresetVariables( {
					color: { text: 'var(--wp--preset--color--accent-1)' },
					spacing: {
						padding: { top: 'var(--wp--preset--spacing--10)' },
						margin: 'calc(10px + 2px)',
					},
					size: 12,
				} )
			).toEqual( {
				color: { text: 'var:preset|color|accent-1' },
				spacing: {
					padding: { top: 'var:preset|spacing|10' },
					margin: 'calc(10px + 2px)',
				},
				size: 12,
			} );
		} );
	} );

	describe( 'cleanupUserStyles', () => {
		it( 'removes empty arrays, undefined values and empty nested objects', () => {
			expect(
				cleanupUserStyles( {
					color: {
						text: '#000',
						gradient: undefined,
						background: '',
					},
					elements: { link: { color: {} } },
					blocks: [],
				} )
			).toEqual( { color: { text: '#000' } } );
		} );

		it( 'keeps zero, false and other numbers set by the user', () => {
			expect(
				cleanupUserStyles( {
					typography: { lineHeight: 1.5, letterSpacing: 0 },
					border: { radius: 0 },
					custom: { enabled: false },
				} )
			).toEqual( {
				typography: { lineHeight: 1.5, letterSpacing: 0 },
				border: { radius: 0 },
				custom: { enabled: false },
			} );
		} );
	} );
} );
