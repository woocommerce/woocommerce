/*
 * @jest-environment-options {"url": "https://example.com/shop/"}
 */

/**
 * Internal dependencies
 */
import type { ProductFiltersStore } from '../frontend';

const mockGetContext = jest.fn();
const mockGetServerContext = jest.fn();
const mockGetConfig = jest.fn();
const mockGetElement = jest.fn();
const mockReload = jest.fn();

jest.mock( '../../../utils/navigation', () => ( {
	reload: mockReload,
} ) );

let mockRegisteredStore: {
	state: ProductFiltersStore[ 'state' ];
	actions: ProductFiltersStore[ 'actions' ];
	callbacks: ProductFiltersStore[ 'callbacks' ];
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
		getServerContext: mockGetServerContext,
		getConfig: mockGetConfig,
		getElement: mockGetElement,
		withSyncEvent: ( callback: unknown ) => callback,
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: definition.state,
				actions: definition.actions,
				callbacks: definition.callbacks,
			};
			return mockRegisteredStore;
		} ),
	} ),
	{ virtual: true }
);

jest.mock(
	'@wordpress/interactivity-router',
	() => ( {
		actions: {
			navigate: jest.fn(),
		},
	} ),
	{ virtual: true }
);

// Captured before any test navigates, so each test starts from the env URL.
const initialUrl = window.location.href;

describe( 'product filters interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetContext.mockReset();
		mockGetServerContext.mockReset();
		mockGetConfig.mockReset();
		mockGetElement.mockReset();
		mockGetElement.mockReturnValue( { ref: null } );
		mockReload.mockReset();
		mockRegisteredStore = null;

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	afterEach( () => {
		window.history.replaceState( {}, '', initialUrl );
	} );

	it( 'ignores invalid selectable item payloads', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		const context = {
			isOverlayOpened: false,
			params: {},
			activeFilters: [],
			item: {
				label: 'Blue',
				value: 'blue',
				selected: false,
				count: 1,
			},
			activeLabelTemplate: '{{label}}',
			filterType: 'attribute/color',
		};

		mockGetContext.mockReturnValue( context );

		mockRegisteredStore.actions.toggle();

		expect( context.activeFilters ).toEqual( [] );
	} );

	it( 'uses value as active filter label fallback', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		const context = {
			isOverlayOpened: false,
			params: {},
			activeFilters: [],
			item: {
				type: 'attribute/color',
				value: 'blue',
				selected: false,
				count: 1,
			},
			activeLabelTemplate: 'Color: {{label}}',
			filterType: 'attribute/color',
		};

		mockGetContext.mockReturnValue( context );

		mockRegisteredStore.actions.toggle();

		expect( context.activeFilters ).toEqual( [
			{
				value: 'blue',
				type: 'attribute/color',
				activeLabel: 'Color: blue',
			},
		] );
	} );

	it( 'toggles an attribute and removes its query parameters when deselected', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		const context = {
			isOverlayOpened: false,
			params: {},
			activeFilters: [],
			item: {
				type: 'attribute/color',
				label: 'Gray',
				value: 'gray',
				selected: false,
				count: 2,
				attributeQueryType: 'or' as const,
			},
			activeLabelTemplate: 'Color: {{label}}',
			filterType: 'attribute/color',
		};

		mockGetContext.mockReturnValue( context );

		mockRegisteredStore.actions.toggle();

		expect( context.activeFilters ).toEqual( [
			{
				value: 'gray',
				type: 'attribute/color',
				activeLabel: 'Color: Gray',
				attributeQueryType: 'or',
			},
		] );
		expect( mockRegisteredStore.state.params ).toEqual( {
			filter_color: 'gray',
			query_type_color: 'or',
		} );

		mockRegisteredStore.actions.toggle();

		expect( context.activeFilters ).toEqual( [] );
		expect( mockRegisteredStore.state.params ).toEqual( {} );
	} );

	it( 'returns no selectable items when server context items are not an array', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		mockGetServerContext.mockReturnValue( {
			items: 'invalid',
			activeFilters: [],
		} );

		expect( mockRegisteredStore.state.selectableItems ).toEqual( [] );
	} );

	it( 'closes from the backdrop but not the dialog', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}
		const context = { isOverlayOpened: true };
		const wrapper = document.createElement( 'div' );
		const dialog = document.createElement( 'div' );
		mockGetContext.mockReturnValue( context );

		mockRegisteredStore.actions.closeOverlayOnBackdrop( {
			target: dialog,
			currentTarget: wrapper,
		} as unknown as MouseEvent );
		expect( context.isOverlayOpened ).toBe( true );

		mockRegisteredStore.actions.closeOverlayOnBackdrop( {
			target: wrapper,
			currentTarget: wrapper,
		} as unknown as MouseEvent );
		expect( context.isOverlayOpened ).toBe( false );
	} );

	it( 'focuses the dialog after the overlay is shown', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		const root = document.createElement( 'div' );
		const openButton = document.createElement( 'button' );
		const dialog = document.createElement( 'div' );
		const closeButton = document.createElement( 'button' );
		Object.defineProperty( closeButton, 'offsetParent', {
			get: () => document.body,
		} );
		dialog.append( closeButton );
		root.append( openButton, dialog );
		document.body.append( root );

		const context = { isOverlayOpened: false };
		mockGetContext.mockReturnValue( context );
		mockGetElement.mockReturnValue( { ref: openButton } );

		let frame: ( ( time: number ) => void ) | undefined;
		const requestAnimationFrameSpy = jest
			.spyOn( window, 'requestAnimationFrame' )
			.mockImplementation( ( callback ) => {
				frame = callback;
				return 1;
			} );

		try {
			mockRegisteredStore.actions.openOverlay();
			mockGetElement.mockReturnValue( { ref: dialog } );
			mockRegisteredStore.callbacks.focusOverlay();

			expect( context.isOverlayOpened ).toBe( true );
			expect( root.ownerDocument.activeElement ).not.toBe( closeButton );

			frame?.( 0 );

			expect( root.ownerDocument.activeElement ).toBe( closeButton );

			mockRegisteredStore.actions.closeOverlay();
			expect( root.ownerDocument.activeElement ).toBe( openButton );
		} finally {
			root.remove();
			requestAnimationFrameSpy.mockRestore();
		}
	} );

	it( 'does not add child-owned index metadata to selectable items', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		mockGetServerContext.mockReturnValue( {
			items: [
				{
					id: 'attribute-blue',
					label: 'Blue',
					value: 'blue',
					type: 'attribute/color',
				},
			],
			activeFilters: [],
		} );
		mockGetContext.mockReturnValue( {
			activeFilters: [],
		} );

		expect( mockRegisteredStore.state.selectableItems ).toEqual( [
			{
				id: 'attribute-blue',
				label: 'Blue',
				value: 'blue',
				type: 'attribute/color',
				selected: false,
			},
		] );
	} );

	[
		{
			description: 'unicode value',
			label: 'Աուդիոգիրք',
			value: '%D4%B1%D5%B8%D6%82%D5%A4%D5%AB%D5%B8%D5%A3%D5%AB%D6%80%D6%84',
			// The canonical result keeps the single encoding for the original unicode value.
			// Without the explicit decode step the percent signs would be encoded again,
			// producing `%25D4%25B1%25D5%25B8%25D6%2582%25D5%25A4%25D5%25AB%25D5%25B8%25D5%25A3%25D5%25AB%25D6%2580%25D6%2584`
			// instead of the intended `%D4%B1%D5%B8%D6%82%D5%A4%D5%AB%D5%B8%D5%A3%D5%AB%D6%80%D6%84`.
			expectedUrl:
				'https://example.com/shop/?existing=1&color=%D4%B1%D5%B8%D6%82%D5%A4%D5%AB%D5%B8%D5%A3%D5%AB%D6%80%D6%84',
		},
		{
			description: 'latin value',
			label: 'Blue',
			value: 'blue',
			expectedUrl: 'https://example.com/shop/?existing=1&color=blue',
		},
		{
			description: 'malformed encoded value',
			label: 'Invalid',
			value: '%E0%A4%A',
			expectedUrl:
				'https://example.com/shop/?existing=1&color=%25E0%25A4%25A',
			expectConsoleWarn: true,
		},
	].forEach(
		( {
			description,
			label,
			value,
			expectedUrl,
			expectConsoleWarn = false,
		} ) => {
			it( `Test URL encoding before navigation: ${ description }`, () => {
				if ( ! mockRegisteredStore ) {
					throw new Error(
						'Product filters store was not registered.'
					);
				}

				window.history.replaceState(
					{},
					'',
					'https://example.com/shop/?existing=1'
				);

				const canonicalUrl = 'https://example.com/shop/';

				const context = {
					isOverlayOpened: false,
					params: {
						color: value,
					},
					activeFilters: [],
					item: {
						type: 'attribute/color',
						label,
						value,
						selected: true,
						count: 1,
						attributeQueryType: 'or' as const,
					},
					activeLabelTemplate: '{{label}}',
					filterType: 'attribute/color',
				};

				mockGetContext.mockReturnValue( context );
				mockGetServerContext.mockReturnValue( context );

				mockGetConfig.mockImplementation( ( key: string ) => {
					if ( key === 'woocommerce/product-filters' ) {
						return {
							canonicalUrl,
						};
					}
					return {};
				} );

				Object.defineProperty( mockRegisteredStore.state, 'params', {
					get: () => ( {
						color: value,
					} ),
				} );

				const routerNavigate = jest.fn();
				const consoleWarnSpy = jest
					.spyOn( console, 'warn' )
					.mockImplementation( () => {} );

				try {
					const iterator = mockRegisteredStore.actions.navigate();

					const firstYield = iterator.next();
					expect( firstYield.done ).toBe( false );

					iterator.next( {
						actions: {
							navigate: routerNavigate,
						},
					} as unknown as typeof import( '@wordpress/interactivity-router' ) );

					expect( routerNavigate ).toHaveBeenCalledTimes( 1 );
					const [ navigatedUrl ] = routerNavigate.mock.calls[ 0 ];
					const result = new URL( navigatedUrl );

					expect( result.toString() ).toBe( expectedUrl );

					expect( consoleWarnSpy ).toHaveBeenCalledTimes(
						expectConsoleWarn ? 1 : 0
					);
				} finally {
					consoleWarnSpy.mockRestore();
				}
			} );
		}
	);

	it.each( [ 0, 17 ] )(
		'scopes navigation and clearing to collection %i',
		( queryId ) => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Product filters store was not registered.' );
			}
			window.history.replaceState(
				{},
				'',
				`https://example.com/shop/?min_price=5&wcp-${ queryId }-min_price=10&wcp-${ queryId }-maker=old&wcp-28-min_price=60&query-${ queryId }-page=3&query-28-page=2&campaign=spring`
			);
			const context = {
				queryId,
				params: { min_price: '10', maker: 'old' },
				activeFilters: [
					{
						type: 'taxonomy/product_brand',
						value: 'acme',
						activeLabel: 'Acme',
					},
				],
				forcePageReload: true,
			};
			mockGetContext.mockReturnValue( context );
			mockGetServerContext.mockReturnValue( context );
			mockGetConfig.mockReturnValue( {
				canonicalUrl: 'https://example.com/shop/',
				filterParamKeys: [ 'min_price', 'maker' ],
				taxonomyParamsMap: { product_brand: 'maker' },
			} );

			expect( mockRegisteredStore.state.params ).toEqual( {
				maker: 'acme',
			} );
			mockRegisteredStore.actions.navigate().next();
			expect( mockReload ).toHaveBeenLastCalledWith(
				`https://example.com/shop/?min_price=5&wcp-28-min_price=60&query-28-page=2&campaign=spring&wcp-${ queryId }-maker=acme`
			);

			context.activeFilters = [];
			mockRegisteredStore.actions.navigate().next();
			expect( mockReload ).toHaveBeenLastCalledWith(
				'https://example.com/shop/?min_price=5&wcp-28-min_price=60&query-28-page=2&campaign=spring'
			);
		}
	);

	it.each( [ 'completion', 'failure', 'history', 'other navigation' ] )(
		'preserves overlapping changes and resets pending state on %s',
		( finish ) => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Product filters store was not registered.' );
			}
			const { actions } = mockRegisteredStore;
			window.history.replaceState(
				{},
				'',
				'https://example.com/shop/?query-0-page=2&query-28-page=3'
			);
			mockGetConfig.mockReturnValue( {
				filterParamKeys: [ 'categories' ],
				taxonomyParamsMap: { product_cat: 'categories' },
			} );
			const routerNavigate = jest.fn();
			const router = {
				actions: { navigate: routerNavigate },
			} as unknown as typeof import( '@wordpress/interactivity-router' );
			const navigate = ( queryId: number, value: string ) => {
				const context = {
					queryId,
					params: {},
					activeFilters: value
						? [ { type: 'taxonomy/product_cat', value } ]
						: [],
				};
				mockGetContext.mockReturnValue( context );
				mockGetServerContext.mockReturnValue( context );
				const iterator = actions.navigate();
				iterator.next();
				iterator.next( router );
				return iterator;
			};

			const first = navigate( 0, 'shirts' );
			const second = navigate( 28, 'hats' );
			expect( routerNavigate ).toHaveBeenLastCalledWith(
				'https://example.com/shop/?wcp-0-categories=shirts&wcp-28-categories=hats'
			);
			first.next();
			const clear = navigate( 0, '' );
			expect( routerNavigate ).toHaveBeenLastCalledWith(
				'https://example.com/shop/?wcp-28-categories=hats'
			);
			second.next();
			let error;
			try {
				if ( finish === 'failure' ) {
					clear.throw( new Error( 'Navigation failed' ) );
				}
			} catch ( caught ) {
				error = caught;
			}
			expect( error ).toEqual(
				finish === 'failure'
					? new Error( 'Navigation failed' )
					: undefined
			);
			if ( finish === 'history' ) {
				window.dispatchEvent( new PopStateEvent( 'popstate' ) );
			} else if ( finish === 'other navigation' ) {
				window.history.replaceState( {}, '', '/shop/?campaign=summer' );
			} else {
				clear.next();
			}
			const next = navigate( 0, 'boots' );
			expect( routerNavigate ).toHaveBeenLastCalledWith(
				finish === 'other navigation'
					? 'https://example.com/shop/?campaign=summer&wcp-0-categories=boots'
					: 'https://example.com/shop/?query-28-page=3&wcp-0-categories=boots'
			);
			next.next();
		}
	);

	it( 'does not restore cleared local parameters from the global canonical URL', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}
		window.history.replaceState( {}, '', '/shop/?wcp-0-categories=shirts' );
		mockGetConfig.mockReturnValue( {
			canonicalUrl: 'https://example.com/shop/?wcp-0-categories=shirts',
			filterParamKeys: [ 'categories' ],
			taxonomyParamsMap: { product_cat: 'categories' },
		} );
		mockGetContext.mockReturnValue( { activeFilters: [] } );
		mockGetServerContext.mockReturnValue( { queryId: 0, params: {} } );
		const local = mockRegisteredStore.actions.navigate();
		local.next();
		mockGetServerContext.mockReturnValue( { params: {} } );
		mockGetContext.mockReturnValue( {
			activeFilters: [ { type: 'taxonomy/product_cat', value: 'hats' } ],
		} );
		const global = mockRegisteredStore.actions.navigate();
		global.next();
		const navigate = jest.fn();
		const router = {
			actions: { navigate },
		} as unknown as typeof import( '@wordpress/interactivity-router' );
		global.next( router );
		local.next( router );
		expect( navigate.mock.calls ).toEqual( [
			[ 'https://example.com/shop/?categories=hats' ],
		] );
		window.history.replaceState( {}, '', '/shop/?categories=hats' );
		global.next();
		mockGetContext.mockReturnValue( { activeFilters: [] } );
		const clear = mockRegisteredStore.actions.navigate();
		clear.next();
		clear.next( router );
		expect( navigate ).toHaveBeenLastCalledWith(
			'https://example.com/shop/'
		);
		clear.next();
	} );

	it( 'triggers a full-page reload instead of router when forcePageReload is true', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		window.history.replaceState( {}, '', 'https://example.com/shop/' );

		const canonicalUrl = 'https://example.com/shop/';

		const context = {
			isOverlayOpened: false,
			params: { color: 'blue' },
			activeFilters: [],
			item: {
				type: 'attribute/color',
				label: 'Blue',
				value: 'blue',
				selected: true,
				count: 1,
				attributeQueryType: 'or' as const,
			},
			activeLabelTemplate: '{{label}}',
			filterType: 'attribute/color',
		};

		mockGetContext.mockReturnValue( context );
		mockGetServerContext.mockReturnValue( context );

		mockGetConfig.mockImplementation( ( key: string ) => {
			if ( key === 'woocommerce/product-filters' ) {
				return { canonicalUrl, forcePageReload: true };
			}
			return {};
		} );

		Object.defineProperty( mockRegisteredStore.state, 'params', {
			get: () => ( { color: 'blue' } ),
		} );

		const iterator = mockRegisteredStore.actions.navigate();

		// forcePageReload exits early before yielding the router import
		const result = iterator.next();
		expect( result.done ).toBe( true );

		expect( mockReload ).toHaveBeenCalledTimes( 1 );
		expect( mockReload ).toHaveBeenCalledWith(
			'https://example.com/shop/?color=blue'
		);
	} );

	describe( 'forcePageReload context resolution', () => {
		const setupNavigate = ( {
			contextForcePageReload,
			configForcePageReload,
		}: {
			contextForcePageReload: boolean | null | undefined;
			configForcePageReload: boolean | undefined;
		} ) => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Product filters store was not registered.' );
			}

			window.history.replaceState( {}, '', 'https://example.com/shop/' );

			const context = {
				isOverlayOpened: false,
				params: { color: 'blue' },
				activeFilters: [],
				item: {
					type: 'attribute/color',
					label: 'Blue',
					value: 'blue',
					selected: true,
					count: 1,
					attributeQueryType: 'or' as const,
				},
				activeLabelTemplate: '{{label}}',
				filterType: 'attribute/color',
				forcePageReload: contextForcePageReload,
			};

			mockGetContext.mockReturnValue( context );
			mockGetServerContext.mockReturnValue( context );

			mockGetConfig.mockImplementation( ( key: string ) => {
				if ( key === 'woocommerce/product-filters' ) {
					return {
						canonicalUrl: 'https://example.com/shop/',
						forcePageReload: configForcePageReload,
					};
				}
				return {};
			} );

			Object.defineProperty( mockRegisteredStore.state, 'params', {
				get: () => ( { color: 'blue' } ),
			} );

			return {
				store: mockRegisteredStore,
			};
		};

		it( 'reloads when context.forcePageReload is true (descendant case, no config)', () => {
			const { store: registeredStore } = setupNavigate( {
				contextForcePageReload: true,
				configForcePageReload: undefined,
			} );

			const iterator = registeredStore.actions.navigate();
			const result = iterator.next();

			expect( result.done ).toBe( true );
			expect( mockReload ).toHaveBeenCalledWith(
				'https://example.com/shop/?color=blue'
			);
		} );

		it( 'context.forcePageReload=true overrides config.forcePageReload=false', () => {
			const { store: registeredStore } = setupNavigate( {
				contextForcePageReload: true,
				configForcePageReload: false,
			} );

			const iterator = registeredStore.actions.navigate();
			const result = iterator.next();

			expect( result.done ).toBe( true );
			expect( mockReload ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'context.forcePageReload=false overrides config.forcePageReload=true (uses router)', () => {
			const { store: registeredStore } = setupNavigate( {
				contextForcePageReload: false,
				configForcePageReload: true,
			} );

			const routerNavigate = jest.fn();

			const iterator = registeredStore.actions.navigate();
			const firstYield = iterator.next();

			expect( firstYield.done ).toBe( false );
			iterator.next( {
				actions: { navigate: routerNavigate },
			} as unknown as typeof import( '@wordpress/interactivity-router' ) );

			expect( mockReload ).not.toHaveBeenCalled();
			expect( routerNavigate ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
