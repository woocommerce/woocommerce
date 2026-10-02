type StoreDefinition = {
	state?: Record< string, unknown >;
	actions?: Record< string, unknown >;
	callbacks?: Record< string, unknown >;
};

type RegisteredStore = {
	state: Record< string, unknown >;
	actions: Record< string, unknown >;
	callbacks: Record< string, unknown >;
};

const mockRegisteredStores: Record< string, RegisteredStore > = {};

const mockGetConfig = jest.fn();

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getConfig: ( namespace: string ) => mockGetConfig( namespace ),
		// Every call for a namespace returns the same object, and definitions
		// are merged into it with their property descriptors, so getters stay
		// lazy the way the Interactivity API keeps them.
		store: ( namespace: string, definition: StoreDefinition = {} ) => {
			if ( ! mockRegisteredStores[ namespace ] ) {
				mockRegisteredStores[ namespace ] = {
					state: {},
					actions: {},
					callbacks: {},
				};
			}
			const registered = mockRegisteredStores[ namespace ];
			if ( definition.state ) {
				Object.defineProperties(
					registered.state,
					Object.getOwnPropertyDescriptors( definition.state )
				);
			}
			Object.assign( registered.actions, definition.actions ?? {} );
			Object.assign( registered.callbacks, definition.callbacks ?? {} );
			return registered;
		},
		getContext: jest.fn( () => ( {} ) ),
		getElement: jest.fn( () => ( { ref: null, attributes: {} } ) ),
		useLayoutEffect: jest.fn(),
		useRef: jest.fn( () => ( { current: null } ) ),
		withSyncEvent: jest.fn( ( callback: unknown ) => callback ),
	} ),
	{ virtual: true }
);

// Imported for their side effects only; their own store registrations are not
// part of what this suite exercises.
jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ) );
jest.mock( '@woocommerce/stores/store-notices', () => ( {} ) );

const ITEMS_IN_CART_TEXT_TEMPLATE = '%d items in your cart';

const serverCart = () => ( {
	items: [ { quantity: 1 }, { quantity: 2 } ],
	// Deliberately different from the sum of the quantities above: this is what
	// a `woocommerce_cart_contents_count` filter on the server produces.
	items_count: 999,
	totals: {
		total_items: '1000',
		total_items_tax: '0',
	},
} );

const loadFrontend = ( nonOptimisticProperties: string[] ) => {
	// The `woocommerce` store is server-hydrated before the Mini Cart module
	// runs, so seed it first.
	mockRegisteredStores.woocommerce = {
		state: { cart: serverCart() },
		actions: {},
		callbacks: {},
	};

	mockGetConfig.mockImplementation( ( namespace: string ) => {
		switch ( namespace ) {
			case 'woocommerce':
				return { nonOptimisticProperties };
			case 'woocommerce/mini-cart-title-items-counter-block':
				return {
					itemsInCartTextTemplate: ITEMS_IN_CART_TEXT_TEMPLATE,
				};
			default:
				return {};
		}
	} );

	jest.isolateModules( () => {
		require( '../frontend' );
	} );

	return {
		miniCart: mockRegisteredStores[ 'woocommerce/mini-cart' ].state,
		titleCounter:
			mockRegisteredStores[
				'woocommerce/mini-cart-title-items-counter-block'
			].state,
	};
};

describe( 'Mini Cart title item count', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetConfig.mockReset();
		Object.keys( mockRegisteredStores ).forEach( ( namespace ) => {
			delete mockRegisteredStores[ namespace ];
		} );
	} );

	it( 'uses the server item count when cart.items_count is non-optimistic', () => {
		const { miniCart, titleCounter } = loadFrontend( [
			'cart.items_count',
		] );

		expect( miniCart.totalItemsInCart ).toBe( 999 );
		expect( titleCounter.itemsInCartText ).toBe( '999 items in your cart' );
	} );

	it( 'ignores cart.items_count and sums the cart item quantities when the property is optimistic', () => {
		const { miniCart, titleCounter } = loadFrontend( [] );

		expect( miniCart.totalItemsInCart ).toBe( 3 );
		expect( titleCounter.itemsInCartText ).toBe( '3 items in your cart' );
	} );

	it( 'sums the cart item quantities when only unrelated properties are non-optimistic', () => {
		const { miniCart, titleCounter } = loadFrontend( [
			'cart.totals.total_items',
		] );

		expect( miniCart.totalItemsInCart ).toBe( 3 );
		expect( titleCounter.itemsInCartText ).toBe( '3 items in your cart' );
	} );
} );
