/* eslint-disable @woocommerce/dependency-group -- mocks must be imported first */
/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useNoticeOverrides } from '../use-notice-overrides';

// Keep a reference to the plugin callback registered via `use()`.
let capturedPlugin: ( registry: {
	select: ( namespace: string ) => unknown;
} ) => { select: ( namespace: string ) => unknown };

jest.mock( '@wordpress/data', () => {
	const actual =
		jest.requireActual< typeof import('@wordpress/data') >(
			'@wordpress/data'
		);
	return {
		...actual,
		use: jest.fn(
			(
				plugin: ( registry: {
					select: ( namespace: string ) => unknown;
				} ) => { select: ( namespace: string ) => unknown }
			) => {
				capturedPlugin = plugin;
			}
		),
	};
} );

jest.mock( '@wordpress/notices', () => ( {
	store: { name: 'core/notices' },
} ) );

jest.mock( '@wordpress/i18n', () => ( {
	__: ( text: string ) => text,
} ) );

interface Notice {
	id: string;
	content: string;
	spokenMessage: string;
	actions: unknown[];
}

const makeNotice = ( partial: Partial< Notice > = {} ): Notice => ( {
	id: 'test-notice',
	content: 'Test notice',
	spokenMessage: 'Test notice',
	actions: [],
	...partial,
} );

describe( 'useNoticeOverrides — memoized selector stability', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	function buildSelectOverride( notices: Notice[] ) {
		const originalGetNotices = jest.fn().mockReturnValue( notices );
		const originalSelectors = { getNotices: originalGetNotices };

		const originalSelect = jest.fn().mockReturnValue( originalSelectors );

		renderHook( () => useNoticeOverrides() );

		const pluginResult = capturedPlugin( { select: originalSelect } );
		return { pluginResult, originalSelect, originalGetNotices };
	}

	it( 'getNotices returns the same array reference when notices input is unchanged', () => {
		const notices = [ makeNotice() ];
		const { pluginResult } = buildSelectOverride( notices );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};

		const firstResult = selectors.getNotices();
		const secondResult = selectors.getNotices();

		expect( firstResult ).toBe( secondResult );
	} );

	it( 'getNotices returns a new array reference when notices input changes', () => {
		const originalGetNotices = jest.fn();
		const originalSelectors = { getNotices: originalGetNotices };
		const originalSelect = jest.fn().mockReturnValue( originalSelectors );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );

		const firstNotices = [ makeNotice( { id: 'a' } ) ];
		originalGetNotices.mockReturnValue( firstNotices );
		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const firstResult = selectors.getNotices();

		const secondNotices = [ makeNotice( { id: 'b' } ) ];
		originalGetNotices.mockReturnValue( secondNotices );
		const secondResult = selectors.getNotices();

		expect( firstResult ).not.toBe( secondResult );
	} );

	it( 'passes through select for non-notices stores unchanged', () => {
		const notices = [ makeNotice() ];
		const otherSelectors = { getSomething: jest.fn() };
		const originalGetNotices = jest.fn().mockReturnValue( notices );
		const originalSelectors = { getNotices: originalGetNotices };

		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string ) =>
				ns === 'core/notices' ? originalSelectors : otherSelectors
			);

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );

		const result = pluginResult.select( 'some/other-store' );
		expect( result ).toBe( otherSelectors );
	} );

	it( 'transforms known notice content via getNotices', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post updated.',
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ] );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Email saved.' );
	} );

	it( 'transforms site-editor-save-success notice and removes actions', () => {
		const originalNotice = makeNotice( {
			id: 'site-editor-save-success',
			content: 'Site updated.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ] );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Email design updated.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'returns selectors unchanged when getNotices is not present', () => {
		const originalSelectors = { someOtherSelector: jest.fn() };
		const originalSelect = jest.fn().mockReturnValue( originalSelectors );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );

		const result = pluginResult.select( 'core/notices' );
		expect( result ).toBe( originalSelectors );
	} );
} );
