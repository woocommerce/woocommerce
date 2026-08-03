/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useUserTheme } from '../use-user-theme';
import { storeName } from '../../store/constants';

jest.mock( '@wordpress/data', () => {
	const actual =
		jest.requireActual< typeof import('@wordpress/data') >(
			'@wordpress/data'
		);

	return Object.create( actual, {
		useSelect: { value: jest.fn() },
		dispatch: {
			value: jest.fn( () => ( { editEntityRecord: jest.fn() } ) ),
		},
	} );
} );

jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );

// The store barrel pulls in `@wordpress/components` through the events module,
// which Jest cannot transform. Re-export the real constant so the store name
// stays linked to `src/store/constants.ts`.
jest.mock( '../../store', () => ( {
	storeName: jest.requireActual< typeof import('../../store/constants') >(
		'../../store/constants'
	).storeName,
} ) );

const mockedUseSelect = useSelect as unknown as jest.Mock;

/**
 * Runs the hook's own `mapSelect` against a stub store, so the store it selects
 * and its `|| null` normalisation are exercised rather than stubbed over.
 *
 * @param post Global styles post the stubbed selector should return.
 */
function mockGlobalStylePost( post: unknown ) {
	mockedUseSelect.mockImplementation(
		( mapSelect: ( s: unknown ) => unknown ) =>
			mapSelect( ( store: unknown ) => {
				if ( store !== storeName ) {
					throw new Error( `Unexpected store: ${ String( store ) }` );
				}
				return { getGlobalEmailStylesPost: () => post };
			} )
	);
}

describe( 'useUserTheme', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	// Consumers use `userTheme` as a memo dependency for regenerating the global
	// stylesheet, so a fresh object per render makes those memos always miss.
	it( 'keeps the same userTheme across re-renders when nothing changed', () => {
		mockGlobalStylePost( {
			id: 1,
			styles: { color: { background: '#fff' } },
			settings: { color: { palette: [] } },
		} );

		const { result, rerender } = renderHook( () => useUserTheme() );
		const first = result.current.userTheme;

		rerender();

		expect( result.current.userTheme ).toBe( first );
	} );

	// The counterpart: an edit must still produce a new object, or the canvas
	// would never restyle. Both values are checked because
	// `useGlobalStylesOutputWithConfig` bails out to an empty stylesheet unless
	// it has styles *and* settings, so a stale `settings` drops the generated
	// CSS entirely rather than just leaving it out of date.
	it( 'returns a new userTheme when the styles or the settings change', () => {
		const settings = { color: { palette: [] } };
		mockGlobalStylePost( {
			id: 1,
			styles: { color: { background: '#fff' } },
			settings,
		} );

		const { result, rerender } = renderHook( () => useUserTheme() );
		const first = result.current.userTheme;

		const updatedStyles = { color: { background: '#000' } };
		mockGlobalStylePost( { id: 1, styles: updatedStyles, settings } );
		rerender();

		expect( result.current.userTheme ).not.toBe( first );
		expect( result.current.userTheme.styles ).toBe( updatedStyles );

		const afterStyles = result.current.userTheme;
		const updatedSettings = { color: { palette: [ { slug: 'accent' } ] } };
		mockGlobalStylePost( {
			id: 1,
			styles: updatedStyles,
			settings: updatedSettings,
		} );
		rerender();

		expect( result.current.userTheme ).not.toBe( afterStyles );
		expect( result.current.userTheme.settings ).toBe( updatedSettings );
	} );

	// The post is absent until it resolves, which is when the editor first
	// mounts and the stylesheet is generated.
	it( 'keeps the same userTheme when there is no global styles post', () => {
		mockGlobalStylePost( null );

		const { result, rerender } = renderHook( () => useUserTheme() );
		const first = result.current.userTheme;

		rerender();

		expect( result.current.userTheme ).toBe( first );
	} );
} );
