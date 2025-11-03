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
			label: 'Café',
			value: 'caf%C3%A9',
			// The canonical result keeps the single encoding for the original unicode value.
			// Without the explicit decode step the percent signs would be encoded again,
			// producing `caf%25C3%25A9` instead of the intended `caf%C3%A9`.
			expectedUrl: 'https://example.com/shop/?color=caf%C3%A9',
		},
		{
			description: 'latin value',
			label: 'Blue',
			value: 'blue',
			expectedUrl: 'https://example.com/shop/?color=blue',
		},
	].forEach( ( { description, label, value, expectedUrl } ) => {
		it( `Test URL encoding before navigation: ${ description }`, () => {
			if ( ! mockRegisteredStore ) {
				throw new Error( 'Product filters store was not registered.' );
			}

			const originalLocation = window.location;

			const locationMock = {
				href: 'https://example.com/shop/?existing=1',
			};

			delete ( window as unknown as Record< string, unknown > ).location;
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
			} finally {
				Object.defineProperty( window, 'location', {
					value: originalLocation,
					writable: true,
					configurable: true,
				} );
			}
		} );
	} );
} );
