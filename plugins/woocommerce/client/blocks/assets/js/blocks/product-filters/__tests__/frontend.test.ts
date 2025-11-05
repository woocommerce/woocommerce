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
							isProductArchive: true,
						};
					}
					if ( key === 'woocommerce' ) {
						return {
							isBlockTheme: true,
							needsRefreshForInteractivityAPI: false,
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
} );
