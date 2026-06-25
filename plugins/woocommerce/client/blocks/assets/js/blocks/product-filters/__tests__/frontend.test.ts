/**
 * Internal dependencies
 */
import type { ProductFiltersStore } from '../frontend';

const mockGetContext = jest.fn();
const mockGetServerContext = jest.fn();
const mockGetConfig = jest.fn();

let mockRegisteredStore: {
	state: ProductFiltersStore[ 'state' ];
	actions: ProductFiltersStore[ 'actions' ];
} | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
		getServerContext: mockGetServerContext,
		getConfig: mockGetConfig,
		store: jest.fn( ( _name, definition ) => {
			mockRegisteredStore = {
				state: definition.state,
				actions: definition.actions,
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

describe( 'product filters interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetContext.mockReset();
		mockGetServerContext.mockReset();
		mockGetConfig.mockReset();
		mockRegisteredStore = null;

		jest.isolateModules( () => {
			require( '../frontend' );
		} );
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
				'https://example.com/shop/?color=%D4%B1%D5%B8%D6%82%D5%A4%D5%AB%D5%B8%D5%A3%D5%AB%D6%80%D6%84',
		},
		{
			description: 'latin value',
			label: 'Blue',
			value: 'blue',
			expectedUrl: 'https://example.com/shop/?color=blue',
		},
		{
			description: 'malformed encoded value',
			label: 'Invalid',
			value: '%E0%A4%A',
			expectedUrl: 'https://example.com/shop/?color=%25E0%25A4%25A',
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

				const originalLocation = window.location;

				const locationMock = {
					href: 'https://example.com/shop/?existing=1',
				};

				delete ( window as unknown as Record< string, unknown > )
					.location;
				Object.defineProperty( window, 'location', {
					value: locationMock,
					writable: true,
					configurable: true,
				} );

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
					} );

					expect( routerNavigate ).toHaveBeenCalledTimes( 1 );
					const [ navigatedUrl ] = routerNavigate.mock.calls[ 0 ];
					const result = new URL( navigatedUrl );

					expect( result.toString() ).toBe( expectedUrl );

					expect( consoleWarnSpy ).toHaveBeenCalledTimes(
						expectConsoleWarn ? 1 : 0
					);
				} finally {
					consoleWarnSpy.mockRestore();

					Object.defineProperty( window, 'location', {
						value: originalLocation,
						writable: true,
						configurable: true,
					} );
				}
			} );
		}
	);

	it( 'calls window.location.assign instead of router when forcePageReload is true', () => {
		if ( ! mockRegisteredStore ) {
			throw new Error( 'Product filters store was not registered.' );
		}

		const originalLocation = window.location;
		const assignMock = jest.fn();

		delete ( window as unknown as Record< string, unknown > ).location;
		Object.defineProperty( window, 'location', {
			value: {
				href: 'https://example.com/shop/',
				assign: assignMock,
			},
			writable: true,
			configurable: true,
		} );

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

		try {
			const iterator = mockRegisteredStore.actions.navigate();

			// forcePageReload exits early before yielding the router import
			const result = iterator.next();
			expect( result.done ).toBe( true );

			expect( assignMock ).toHaveBeenCalledTimes( 1 );
			expect( assignMock ).toHaveBeenCalledWith(
				'https://example.com/shop/?color=blue'
			);
		} finally {
			Object.defineProperty( window, 'location', {
				value: originalLocation,
				writable: true,
				configurable: true,
			} );
		}
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

			const originalLocation = window.location;
			const assignMock = jest.fn();

			delete ( window as unknown as Record< string, unknown > ).location;
			Object.defineProperty( window, 'location', {
				value: {
					href: 'https://example.com/shop/',
					assign: assignMock,
				},
				writable: true,
				configurable: true,
			} );

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
				assignMock,
				cleanup: () => {
					Object.defineProperty( window, 'location', {
						value: originalLocation,
						writable: true,
						configurable: true,
					} );
				},
			};
		};

		it( 'reloads when context.forcePageReload is true (descendant case, no config)', () => {
			const {
				store: registeredStore,
				assignMock,
				cleanup,
			} = setupNavigate( {
				contextForcePageReload: true,
				configForcePageReload: undefined,
			} );

			try {
				const iterator = registeredStore.actions.navigate();
				const result = iterator.next();

				expect( result.done ).toBe( true );
				expect( assignMock ).toHaveBeenCalledWith(
					'https://example.com/shop/?color=blue'
				);
			} finally {
				cleanup();
			}
		} );

		it( 'context.forcePageReload=true overrides config.forcePageReload=false', () => {
			const {
				store: registeredStore,
				assignMock,
				cleanup,
			} = setupNavigate( {
				contextForcePageReload: true,
				configForcePageReload: false,
			} );

			try {
				const iterator = registeredStore.actions.navigate();
				const result = iterator.next();

				expect( result.done ).toBe( true );
				expect( assignMock ).toHaveBeenCalledTimes( 1 );
			} finally {
				cleanup();
			}
		} );

		it( 'context.forcePageReload=false overrides config.forcePageReload=true (uses router)', () => {
			const {
				store: registeredStore,
				assignMock,
				cleanup,
			} = setupNavigate( {
				contextForcePageReload: false,
				configForcePageReload: true,
			} );

			const routerNavigate = jest.fn();

			try {
				const iterator = registeredStore.actions.navigate();
				const firstYield = iterator.next();

				expect( firstYield.done ).toBe( false );
				iterator.next( {
					actions: { navigate: routerNavigate },
				} );

				expect( assignMock ).not.toHaveBeenCalled();
				expect( routerNavigate ).toHaveBeenCalledTimes( 1 );
			} finally {
				cleanup();
			}
		} );
	} );
} );
