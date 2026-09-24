/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useNoticeOverrides } from '../use-notice-overrides';
import { storeName as EMAIL_EDITOR_STORE_NAME } from '../../store/constants';

// Keep a reference to the plugin callback registered via `use()`.
let capturedPlugin: ( registry: {
	select: ( namespace: string ) => unknown;
} ) => { select: ( namespace: string ) => unknown };

jest.mock( '@wordpress/data', () => {
	const actual =
		jest.requireActual< typeof import('@wordpress/data') >(
			'@wordpress/data'
		);

	return Object.create( actual, {
		use: {
			value: jest.fn(
				(
					plugin: ( registry: {
						select: ( namespace: string ) => unknown;
					} ) => { select: ( namespace: string ) => unknown }
				) => {
					capturedPlugin = plugin;
				}
			),
		},
	} );
} );

jest.mock( '@wordpress/notices', () => ( {
	store: { name: 'core/notices' },
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
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

type Labels = Record< string, string > | undefined;

// `originalSelect` is called with either a store name string (from the
// plugin's own `select( namespace )`) or a store descriptor object (from
// the hook's own `originalSelect( coreStore )` / `originalSelect( storeName )`
// calls) — normalise both to a name for routing in the test doubles below.
const resolveStoreName = ( ns: string | { name: string } ): string =>
	typeof ns === 'object' ? ns.name : ns;

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

	/**
	 * Builds the `originalSelect` stub the plugin wraps, routing
	 * `core/notices`, `core`, and the email editor store to separate
	 * selector objects so the hook's cross-store label lookup can be
	 * exercised.
	 *
	 * @param notices  Notices `core/notices`' `getNotices` should return.
	 * @param labels   Labels `core`'s `getPostType` should return, keyed by
	 *                 post type. `undefined` means the post type isn't
	 *                 loaded yet.
	 * @param postType Post type the email editor store's
	 *                 `getEmailPostType` should return.
	 */
	function buildSelectOverride(
		notices: Notice[],
		labels?: Labels,
		postType = 'email'
	) {
		const originalGetNotices = jest.fn().mockReturnValue( notices );
		const noticesSelectors = { getNotices: originalGetNotices };

		const getPostType = jest
			.fn()
			.mockReturnValue( labels === undefined ? undefined : { labels } );
		const coreSelectors = { getPostType };

		const getEmailPostType = jest.fn().mockReturnValue( postType );
		const emailEditorSelectors = { getEmailPostType };

		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === 'core' ) {
					return coreSelectors;
				}
				if ( name === EMAIL_EDITOR_STORE_NAME ) {
					return emailEditorSelectors;
				}
				return undefined;
			} );

		renderHook( () => useNoticeOverrides() );

		const pluginResult = capturedPlugin( { select: originalSelect } );
		return {
			pluginResult,
			originalSelect,
			originalGetNotices,
			getPostType,
			getEmailPostType,
		};
	}

	it( 'getNotices returns the same array reference when notices and labels are unchanged', () => {
		const notices = [ makeNotice() ];
		const { pluginResult } = buildSelectOverride( notices, {
			item_updated: 'Post updated.',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};

		const firstResult = selectors.getNotices();
		const secondResult = selectors.getNotices();

		expect( firstResult ).toBe( secondResult );
	} );

	it( 'getNotices returns a new array reference when notices input changes', () => {
		const originalGetNotices = jest.fn();
		const noticesSelectors = { getNotices: originalGetNotices };
		const coreSelectors = {
			getPostType: jest.fn().mockReturnValue( {
				labels: { item_updated: 'Post updated.' },
			} ),
		};
		const emailEditorSelectors = {
			getEmailPostType: jest.fn().mockReturnValue( 'email' ),
		};
		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === 'core' ) {
					return coreSelectors;
				}
				if ( name === EMAIL_EDITOR_STORE_NAME ) {
					return emailEditorSelectors;
				}
				return undefined;
			} );

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

	it( 'getNotices returns a new array reference when the labels reference changes', () => {
		// Uses an overridable notice id: the postType/labels lookup (finding 3)
		// is skipped entirely for non-overridable notices, which would make
		// this label change invisible to the memoized selector.
		const notices = [ makeNotice( { id: 'editor-save' } ) ];
		const originalGetNotices = jest.fn().mockReturnValue( notices );
		const noticesSelectors = { getNotices: originalGetNotices };
		const getPostType = jest
			.fn()
			.mockReturnValue( { labels: { item_updated: 'Post updated.' } } );
		const coreSelectors = { getPostType };
		const emailEditorSelectors = {
			getEmailPostType: jest.fn().mockReturnValue( 'email' ),
		};
		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === 'core' ) {
					return coreSelectors;
				}
				if ( name === EMAIL_EDITOR_STORE_NAME ) {
					return emailEditorSelectors;
				}
				return undefined;
			} );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );
		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};

		const firstResult = selectors.getNotices();

		getPostType.mockReturnValue( {
			labels: { item_updated: 'Příspěvek byl aktualizován.' },
		} );
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

	it( 'transforms an editor-save notice whose content matches the localized item_updated label', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Příspěvek byl aktualizován.',
			actions: [ { label: 'Zobrazit e-mail', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ], {
			item_updated: 'Příspěvek byl aktualizován.',
			view_item: 'Zobrazit e-mail',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Email saved.' );
		expect( result[ 0 ].spokenMessage ).toBe( 'Email saved.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'transforms an editor-save notice matching a custom integration item_updated label', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Email updated.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ], {
			item_updated: 'Email updated.',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Email saved.' );
	} );

	it.each( [ [ 'item_published', 'Příspěvek byl publikován.' ] ] )(
		'transforms an editor-save notice matching the localized %s label',
		( labelKey, localizedText ) => {
			const originalNotice = makeNotice( {
				id: 'editor-save',
				content: localizedText,
				actions: [ { label: 'View', url: '#' } ],
			} );
			const { pluginResult } = buildSelectOverride( [ originalNotice ], {
				[ labelKey ]: localizedText,
			} );

			const selectors = pluginResult.select( 'core/notices' ) as {
				getNotices: () => Notice[];
			};
			const result = selectors.getNotices();

			expect( result[ 0 ].content ).toBe( 'Email saved.' );
			expect( result[ 0 ].actions ).toEqual( [] );
		}
	);

	it( 'leaves an editor-save notice unchanged when labels are not loaded yet, but still removes the action', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post updated.',
			actions: [ { label: 'View Email', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride(
			[ originalNotice ],
			undefined
		);

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Post updated.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'leaves an editor-save notice with content matching no label unchanged', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Updating failed.',
			actions: [],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ], {
			item_updated: 'Post updated.',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Updating failed.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it.each( [
		[ 'item_published_privately', 'Příspěvek byl publikován soukromě.' ],
		[ 'item_scheduled', 'Příspěvek byl naplánován.' ],
	] )(
		'leaves an editor-save notice matching the localized %s label unchanged, since the editor has no UI to reach that status',
		( labelKey, localizedText ) => {
			const originalNotice = makeNotice( {
				id: 'editor-save',
				content: localizedText,
				actions: [ { label: 'View', url: '#' } ],
			} );
			const { pluginResult } = buildSelectOverride( [ originalNotice ], {
				[ labelKey ]: localizedText,
			} );

			const selectors = pluginResult.select( 'core/notices' ) as {
				getNotices: () => Notice[];
			};
			const result = selectors.getNotices();

			expect( result[ 0 ].content ).toBe( localizedText );
			expect( result[ 0 ].actions ).toEqual( [] );
		}
	);

	it( 'leaves an editor-save notice with "Draft saved." content unchanged but removes the action', () => {
		// A saved draft is not used for sending; rewriting the notice to
		// "Email saved." would suggest the opposite. The view-post action is
		// still dropped, since it points at a permalink that is not the email.
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Draft saved.',
			actions: [ { label: 'View Preview', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ], {
			item_updated: 'Post updated.',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Draft saved.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'transforms site-editor-save-success notice and removes actions regardless of labels', () => {
		const originalNotice = makeNotice( {
			id: 'site-editor-save-success',
			content: 'Site updated.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride(
			[ originalNotice ],
			undefined
		);

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

	it( 'does not rewrite content that merely contains the label as a substring', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post updated. Extra',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ], {
			item_updated: 'Post updated.',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Post updated. Extra' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'skips the postType/labels lookup when the email post type is not set yet, but still removes the action', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post updated.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const originalGetNotices = jest
			.fn()
			.mockReturnValue( [ originalNotice ] );
		const noticesSelectors = { getNotices: originalGetNotices };
		const getPostType = jest
			.fn()
			.mockReturnValue( { labels: { item_updated: 'Post updated.' } } );
		const coreSelectors = { getPostType };
		const getEmailPostType = jest.fn().mockReturnValue( undefined );
		const emailEditorSelectors = { getEmailPostType };

		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === 'core' ) {
					return coreSelectors;
				}
				if ( name === EMAIL_EDITOR_STORE_NAME ) {
					return emailEditorSelectors;
				}
				return undefined;
			} );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );
		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Post updated.' );
		expect( result[ 0 ].actions ).toEqual( [] );
		expect( getPostType ).not.toHaveBeenCalled();
	} );

	it( 'leaves notices unchanged without throwing when the email editor store is not registered', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post updated.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const originalGetNotices = jest
			.fn()
			.mockReturnValue( [ originalNotice ] );
		const noticesSelectors = { getNotices: originalGetNotices };
		const coreSelectors = {
			getPostType: jest.fn().mockReturnValue( {
				labels: { item_updated: 'Post updated.' },
			} ),
		};

		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === 'core' ) {
					return coreSelectors;
				}
				// Email editor store not registered.
				return undefined;
			} );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );
		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};

		expect( () => selectors.getNotices() ).not.toThrow();
		const result = selectors.getNotices();
		expect( result[ 0 ].content ).toBe( 'Post updated.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'leaves notices unchanged without throwing when the core store selectors are unavailable', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post updated.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const originalGetNotices = jest
			.fn()
			.mockReturnValue( [ originalNotice ] );
		const noticesSelectors = { getNotices: originalGetNotices };
		const emailEditorSelectors = {
			getEmailPostType: jest.fn().mockReturnValue( 'email' ),
		};

		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === EMAIL_EDITOR_STORE_NAME ) {
					return emailEditorSelectors;
				}
				// Core store not resolvable in this registry.
				return undefined;
			} );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );
		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};

		expect( () => selectors.getNotices() ).not.toThrow();
		const result = selectors.getNotices();
		expect( result[ 0 ].content ).toBe( 'Post updated.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'skips the postType/labels lookup entirely when no notice is overridable', () => {
		const originalNotice = makeNotice( {
			id: 'unrelated-notice',
			content: 'Something else',
		} );
		const originalGetNotices = jest
			.fn()
			.mockReturnValue( [ originalNotice ] );
		const noticesSelectors = { getNotices: originalGetNotices };
		const getEmailPostType = jest.fn().mockReturnValue( 'email' );
		const emailEditorSelectors = { getEmailPostType };
		const getPostType = jest
			.fn()
			.mockReturnValue( { labels: { item_updated: 'Post updated.' } } );
		const coreSelectors = { getPostType };

		const originalSelect = jest
			.fn()
			.mockImplementation( ( ns: string | { name: string } ) => {
				const name = resolveStoreName( ns );
				if ( name === 'core/notices' ) {
					return noticesSelectors;
				}
				if ( name === 'core' ) {
					return coreSelectors;
				}
				if ( name === EMAIL_EDITOR_STORE_NAME ) {
					return emailEditorSelectors;
				}
				return undefined;
			} );

		renderHook( () => useNoticeOverrides() );
		const pluginResult = capturedPlugin( { select: originalSelect } );
		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ] ).toBe( originalNotice );
		expect( getEmailPostType ).not.toHaveBeenCalled();
		expect( getPostType ).not.toHaveBeenCalled();
	} );

	it( 'leaves a notice whose id collides with an Object.prototype member completely untouched', () => {
		const originalNotice = makeNotice( {
			id: 'constructor',
			content: 'Something else',
			spokenMessage: 'Something else',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride( [ originalNotice ], {
			item_updated: 'Post updated.',
		} );

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Something else' );
		expect( result[ 0 ].spokenMessage ).toBe( 'Something else' );
		expect( result[ 0 ].actions ).toEqual( originalNotice.actions );
	} );

	it.each( [ 'wp_template', 'wp_template_part' ] )(
		'transforms an editor-save notice matching item_updated for the %s post type to "Email design updated."',
		( postType ) => {
			const originalNotice = makeNotice( {
				id: 'editor-save',
				content: 'Template updated.',
				actions: [ { label: 'View', url: '#' } ],
			} );
			const { pluginResult } = buildSelectOverride(
				[ originalNotice ],
				{ item_updated: 'Template updated.' },
				postType
			);

			const selectors = pluginResult.select( 'core/notices' ) as {
				getNotices: () => Notice[];
			};
			const result = selectors.getNotices();

			expect( result[ 0 ].content ).toBe( 'Email design updated.' );
			expect( result[ 0 ].spokenMessage ).toBe( 'Email design updated.' );
			expect( result[ 0 ].actions ).toEqual( [] );
		}
	);

	it( 'transforms an editor-save notice matching item_published for the wp_template post type to "Email design updated."', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Post published.',
			actions: [ { label: 'View', url: '#' } ],
		} );
		const { pluginResult } = buildSelectOverride(
			[ originalNotice ],
			{ item_published: 'Post published.' },
			'wp_template'
		);

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Email design updated.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );

	it( 'leaves an editor-save notice with content matching no label unchanged for a template post type', () => {
		const originalNotice = makeNotice( {
			id: 'editor-save',
			content: 'Updating failed.',
			actions: [],
		} );
		const { pluginResult } = buildSelectOverride(
			[ originalNotice ],
			{ item_updated: 'Template updated.' },
			'wp_template'
		);

		const selectors = pluginResult.select( 'core/notices' ) as {
			getNotices: () => Notice[];
		};
		const result = selectors.getNotices();

		expect( result[ 0 ].content ).toBe( 'Updating failed.' );
		expect( result[ 0 ].actions ).toEqual( [] );
	} );
} );
