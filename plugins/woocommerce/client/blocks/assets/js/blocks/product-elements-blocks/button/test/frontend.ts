/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/** ProductButton state used by the frontend store tests. */
type ProductButtonTestState = {
	/** Quantity of the current product in the cart. */
	quantity: number;
	/** Text displayed for the current ProductButton. */
	addToCartText: string;
};

/** Product values returned by the products store mock. */
const mockProductsState = {
	/** Product resolved for the current block context. */
	productInContext: null as Record< string, unknown > | null,
	/** Selected variation resolved for the current block context. */
	productVariationInContext: null as Record< string, unknown > | null,
};

/** Cart state and lookup method returned by the WooCommerce store mock. */
const mockWooState: {
	/** Cart contents returned by the WooCommerce store mock. */
	cart: { items?: unknown[] } | undefined;
	/** Find a cart item by the requested product ID. */
	findItemInCart: jest.Mock;
} = {
	cart: { items: [] },
	findItemInCart: jest.fn(),
};

/** Current context returned to ProductButton and Add to Cart + Options. */
let mockContext: Record< string, unknown >;

/** Function mocked for the delegated cart quantity calculation. */
const mockGetInCartQuantity = jest.fn();

/** State registered by the ProductButton frontend store. */
let mockProductButtonState: ProductButtonTestState | null;

/** Register the mocked WooCommerce stores and retain ProductButton state. */
const mockStore = jest.fn(
	( namespace: string, definition?: { state?: unknown } ) => {
		if ( namespace === 'woocommerce/products' ) {
			return { state: mockProductsState };
		}

		if ( namespace === 'woocommerce' ) {
			return { state: mockWooState };
		}

		if ( namespace === 'woocommerce/product-button' ) {
			mockProductButtonState =
				definition?.state as ProductButtonTestState;
			return { state: definition?.state };
		}

		return { state: {} };
	}
);

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( () => mockContext ),
		useLayoutEffect: jest.fn(),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );
jest.mock( '../utils', () => ( {
	getInCartQuantity: mockGetInCartQuantity,
} ) );

/** Get the state registered by the ProductButton frontend store. */
const getProductButtonState = (): ProductButtonTestState => {
	if ( ! mockProductButtonState ) {
		throw new Error( 'ProductButton store was not registered.' );
	}
	return mockProductButtonState;
};

describe( 'ProductButton frontend store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockProductsState.productInContext = { id: 20, type: 'variable' };
		mockProductsState.productVariationInContext = null;
		mockWooState.cart = { items: [ { id: 20, quantity: 3 } ] };
		mockContext = {
			selectedAttributes: [ { attribute: 'Color', value: 'Blue' } ],
			animationStatus: 'IDLE',
			tempQuantity: 0,
			addToCartText: 'Add to cart',
			groupedProductIds: [ 31 ],
			hasPressedButton: true,
			inTheCartText: 'In cart',
		};
		mockProductButtonState = null;
		mockGetInCartQuantity.mockReturnValue( 3 );

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'counts a main product without passing direct-variation form selections', () => {
		const cartItems = [ { id: 20, quantity: 3 } ];
		mockWooState.cart = { items: cartItems };

		expect( getProductButtonState().quantity ).toBe( 3 );
		expect( mockGetInCartQuantity ).toHaveBeenCalledWith( cartItems, {
			id: 20,
		} );
		expect( mockWooState.findItemInCart ).not.toHaveBeenCalled();
	} );

	it( 'counts the selected variation using its shopper selections', () => {
		const selectedAttributes: SelectedAttributes[] = [
			{ attribute: 'Color', value: 'Blue' },
		];
		const variation = { id: 21, type: 'variation' };
		mockProductsState.productInContext = variation;
		mockProductsState.productVariationInContext = variation;
		mockContext.selectedAttributes = selectedAttributes;

		expect( getProductButtonState().quantity ).toBe( 3 );
		expect( mockGetInCartQuantity ).toHaveBeenCalledWith(
			mockWooState.cart?.items,
			{ id: 21, selectedAttributes }
		);
	} );

	it( 'uses an empty selection for a selected variation without form attributes', () => {
		const variation = { id: 21, type: 'variation' };
		mockProductsState.productInContext = variation;
		mockProductsState.productVariationInContext = variation;
		delete mockContext.selectedAttributes;

		expect( getProductButtonState().quantity ).toBe( 3 );

		expect( mockGetInCartQuantity ).toHaveBeenCalledWith(
			mockWooState.cart?.items,
			{ id: 21, selectedAttributes: [] }
		);
	} );

	it( 'returns zero when there is no product in context', () => {
		mockProductsState.productInContext = null;

		expect( getProductButtonState().quantity ).toBe( 0 );
		expect( mockGetInCartQuantity ).not.toHaveBeenCalled();
	} );

	it.each( [ undefined, {} ] )(
		'uses an empty cart when the cart is %p',
		( cart ) => {
			mockWooState.cart = cart;
			mockGetInCartQuantity.mockReturnValue( 0 );

			expect( getProductButtonState().quantity ).toBe( 0 );
			expect( mockGetInCartQuantity ).toHaveBeenCalledWith( [], {
				id: 20,
			} );
		}
	);

	it( 'keeps grouped-product cart lookups in the add-to-cart text getter', () => {
		mockProductsState.productInContext = { id: 30, type: 'grouped' };
		mockWooState.findItemInCart.mockReturnValue( { quantity: 2 } );

		expect( getProductButtonState().addToCartText ).toBe( 'In cart' );
		expect( mockWooState.findItemInCart ).toHaveBeenCalledWith( {
			id: 31,
		} );
		expect( mockGetInCartQuantity ).not.toHaveBeenCalled();
	} );
} );
