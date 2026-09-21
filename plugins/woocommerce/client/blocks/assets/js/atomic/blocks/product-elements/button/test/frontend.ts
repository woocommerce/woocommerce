type Context = {
	addToCartText: string;
	groupedProductIds?: number[];
	displayViewCart: boolean;
	quantityToAdd: number;
	tempQuantity: number;
	animationStatus: string;
	hasPressedButton: boolean;
	inTheCartText: string;
};

type CartLine = { key?: string; quantity: number };

type ProductScope = {
	productId: number;
	variation: Array< { attribute: string; value: string } >;
	product: Record< string, unknown > | null;
	cartItem: CartLine | null;
};

type RegisteredStore = {
	state: Record< string, unknown >;
	actions: Record< string, ( ...args: never[] ) => unknown >;
};

const mockAddCartItem = jest.fn();
const mockRefreshCart = jest.fn();
const mockFindProductScope = jest.fn();

let mockContext: Context;
let mockScopeContext: { scopeName?: string };
let mockRegisteredStore: RegisteredStore | null;
let mockWooScopes: Record<
	string,
	{ draftCartItem?: Record< string, unknown > }
>;
let mockProductScope: ProductScope;
let mockAddToCartWithOptionsState: { isFormValid?: boolean };

const mockWooState = {
	get productScope() {
		return mockProductScope;
	},
	get productScopes() {
		return mockWooScopes;
	},
	findProductScope: ( ref: unknown ) => mockFindProductScope( ref ),
};

const mockStore = jest.fn( ( namespace: string, definition?: unknown ) => {
	if ( namespace === 'woocommerce' ) {
		return {
			state: mockWooState,
			actions: {
				addCartItem: mockAddCartItem,
				refreshCart: mockRefreshCart,
			},
		};
	}

	if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
		return { state: mockAddToCartWithOptionsState };
	}

	if ( namespace === 'woocommerce/product-button' ) {
		mockRegisteredStore = definition as RegisteredStore;
		return mockRegisteredStore;
	}

	return {};
} );

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( ( namespace?: string ) => {
			if ( namespace === 'woocommerce' ) {
				return mockScopeContext;
			}
			return mockContext;
		} ),
		useLayoutEffect: ( callback: () => void ) => callback(),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );

const getRegisteredStore = (): RegisteredStore => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Product Button store was not registered.' );
	}
	return mockRegisteredStore;
};

const runGenerator = async ( iterator: Generator ) => {
	let result = iterator.next();
	while ( ! result.done ) {
		await result.value;
		result = iterator.next();
	}
};

describe( 'Product Button interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockContext = {
			addToCartText: 'Add to cart',
			groupedProductIds: [],
			displayViewCart: false,
			quantityToAdd: 1,
			tempQuantity: 0,
			animationStatus: 'IDLE',
			hasPressedButton: false,
			inTheCartText: '### in cart',
		};
		mockScopeContext = {};
		mockRegisteredStore = null;
		mockWooScopes = {};
		mockProductScope = {
			productId: 42,
			variation: [],
			product: { id: 42, type: 'simple' },
			cartItem: null,
		};
		mockAddToCartWithOptionsState = {};

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'acts on the product resolved by its scope, including a variation selected in an enclosing form', () => {
		mockProductScope.product = { id: 55, type: 'variation' };
		mockProductScope.variation = [
			{ attribute: 'attribute_pa_color', value: 'Blue' },
		];
		mockProductScope.cartItem = { key: 'abc', quantity: 3 };

		expect( getRegisteredStore().state.quantity ).toBe( 3 );
	} );

	it( 'reads no quantity when the scope resolves no product', () => {
		mockProductScope.product = null;
		mockProductScope.cartItem = { quantity: 7 };

		expect( getRegisteredStore().state.quantity ).toBe( 0 );
	} );

	it.each( [
		{ title: 'simple product', type: 'simple', cartQuantity: 4 },
		{ title: 'variable product', type: 'variation', cartQuantity: 2 },
	] )(
		'shows the cart line\'s own quantity as the "in cart" count for a $title',
		( { type, cartQuantity } ) => {
			mockProductScope.product = { id: 42, type };
			mockProductScope.cartItem = { quantity: cartQuantity };

			expect( getRegisteredStore().state.quantity ).toBe( cartQuantity );
		}
	);

	it( 'sums each grouped child\'s own cart line for the grouped "in cart" count', () => {
		mockProductScope.product = { id: 10, type: 'grouped' };
		mockContext.groupedProductIds = [ 20, 21 ];
		mockContext.animationStatus = 'IDLE';
		mockContext.hasPressedButton = true;
		mockFindProductScope.mockImplementation(
			( ref: { productId: number } ) => ( {
				cartItem:
					ref.productId === 20 ? { quantity: 2 } : { quantity: 0 },
			} )
		);

		expect( getRegisteredStore().state.addToCartText ).toBe(
			mockContext.inTheCartText
		);
		expect( mockFindProductScope ).toHaveBeenCalledWith( {
			productId: 20,
		} );
		expect( mockFindProductScope ).toHaveBeenCalledWith( {
			productId: 21,
		} );
	} );

	it( 'shows the plain add-to-cart text for a grouped product with nothing in the cart', () => {
		mockProductScope.product = { id: 10, type: 'grouped' };
		mockContext.groupedProductIds = [ 20, 21 ];
		mockFindProductScope.mockReturnValue( { cartItem: null } );

		expect( getRegisteredStore().state.addToCartText ).toBe(
			mockContext.addToCartText
		);
	} );

	it( "posts a payload whose quantity is the button's own quantityToAdd context value, not 1, when a filter seeds it", async () => {
		mockContext.quantityToAdd = 5;
		mockProductScope.product = { id: 42, type: 'simple' };

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			expect.objectContaining( { id: 42, quantity: 5 } ),
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'posts quantity 1 when no filter changed the context value', async () => {
		mockContext.quantityToAdd = 1;
		mockProductScope.product = { id: 42, type: 'simple' };

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			expect.objectContaining( { quantity: 1 } ),
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'shows no cart-update notice', async () => {
		mockProductScope.product = { id: 42, type: 'simple' };

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith( expect.anything(), {
			showCartUpdatesNotices: false,
		} );
	} );

	it( "posts the scope's resolved variation id, not the parent's, beside the selected variation", async () => {
		mockProductScope.productId = 42;
		mockProductScope.variation = [
			{ attribute: 'attribute_pa_color', value: 'Blue' },
		];
		mockProductScope.product = { id: 55, type: 'variation' };

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			expect.objectContaining( {
				id: 55,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Blue' },
				],
			} ),
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'ignores a quantity typed into a form sharing its scope, posting its own quantityToAdd instead', async () => {
		mockContext.quantityToAdd = 3;
		mockProductScope.product = { id: 42, type: 'simple' };
		mockWooScopes._default = {
			draftCartItem: { id: 42, variation: [], quantity: 99 },
		};

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			expect.objectContaining( { quantity: 3 } ),
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'forwards extension props written on the scope record', async () => {
		mockProductScope.product = { id: 42, type: 'simple' };
		mockWooScopes._default = {
			draftCartItem: { id: 42, variation: [], giftWrap: true },
		};

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			expect.objectContaining( { giftWrap: true } ),
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'shows "View cart" after a successful add', async () => {
		mockProductScope.product = { id: 42, type: 'simple' };
		mockProductScope.cartItem = { quantity: 1 };
		mockAddCartItem.mockResolvedValue( { success: true } );

		expect( getRegisteredStore().state.displayViewCart ).toBe( false );

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockContext.displayViewCart ).toBe( true );
		expect( getRegisteredStore().state.displayViewCart ).toBe( true );
	} );

	it( 'posts nothing when the scope resolves no product', async () => {
		mockProductScope.product = null;

		await runGenerator(
			getRegisteredStore().actions.addCartItem() as Generator
		);

		expect( mockAddCartItem ).not.toHaveBeenCalled();
	} );
} );
