/**
 * Internal dependencies
 */
import type { QuantitySelectorStore } from '../frontend';

const mockSetQuantity = jest.fn();
const mockGetElement = jest.fn();

let mockContext: {
	allowZero?: boolean;
	inputElement?: HTMLInputElement | null;
};
let mockRegisteredStore: QuantitySelectorStore | null;

const mockProductsState = {
	productInContext: null as Record< string, unknown > | null,
};

const mockAddToCartStore = {
	state: {
		quantity: {} as Record< number, number >,
	},
	actions: {
		setQuantity: mockSetQuantity,
	},
};

const mockStore = jest.fn( ( namespace, definition ) => {
	if ( namespace === 'woocommerce/products' ) {
		return { state: mockProductsState };
	}
	if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
		return mockAddToCartStore;
	}
	if (
		namespace === 'woocommerce/add-to-cart-with-options-quantity-selector'
	) {
		mockRegisteredStore = definition;
		return definition;
	}
	return {};
} );

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( () => mockContext ),
		getElement: mockGetElement,
	} ),
	{ virtual: true }
);

const getRegisteredStore = (): QuantitySelectorStore => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Quantity selector store was not registered.' );
	}
	return mockRegisteredStore;
};

const createInput = ( value: string ) => {
	const input = document.createElement( 'input' );
	input.type = 'number';
	input.value = value;
	return input;
};

describe( 'Add to Cart + Options quantity selector store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockContext = {};
		mockRegisteredStore = null;
		mockProductsState.productInContext = {
			id: 42,
			is_in_stock: true,
			sold_individually: false,
			add_to_cart: {
				minimum: 4,
				maximum: 8,
				multiple_of: 2,
			},
		};
		mockAddToCartStore.state.quantity = { 42: 4 };

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'derives quantity controls from stock, sold-individually, and bounds', () => {
		const state = getRegisteredStore().state;

		expect( state.allowsQuantityChange ).toBe( true );
		expect( state.allowsDecrease ).toBe( false );
		expect( state.allowsIncrease ).toBe( true );

		mockProductsState.productInContext = {
			...mockProductsState.productInContext,
			is_in_stock: false,
		};
		expect( state.allowsQuantityChange ).toBe( false );

		mockProductsState.productInContext = {
			...mockProductsState.productInContext,
			is_in_stock: true,
		};

		mockAddToCartStore.state.quantity[ 42 ] = 8;
		expect( state.allowsIncrease ).toBe( false );

		mockAddToCartStore.state.quantity[ 42 ] = 4;
		mockContext.allowZero = true;
		expect( state.allowsDecrease ).toBe( true );

		mockProductsState.productInContext = {
			...mockProductsState.productInContext,
			sold_individually: true,
		};
		expect( state.allowsQuantityChange ).toBe( false );
	} );

	it( 'clamps increase and decrease button actions to product bounds', () => {
		mockContext.inputElement = createInput( '7' );

		getRegisteredStore().actions.increaseQuantity();

		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 42, 8 );

		mockSetQuantity.mockClear();
		mockContext.inputElement.value = '5';
		getRegisteredStore().actions.decreaseQuantity();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 42, 4 );

		mockSetQuantity.mockClear();
		mockContext.allowZero = true;
		mockContext.inputElement.value = '4';
		getRegisteredStore().actions.decreaseQuantity();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 42, 0 );
	} );

	it.each( [
		{ label: 'zero', value: '0' },
		{ label: 'empty', value: '' },
	] )(
		'resets $label simple-product input to the minimum on blur',
		( { value } ) => {
			mockContext.inputElement = createInput( value );

			getRegisteredStore().actions.handleQuantityBlur();

			expect( mockSetQuantity ).toHaveBeenCalledWith( 42, 4 );
		}
	);

	it.each( [
		{ label: 'zero', value: '0' },
		{ label: 'empty', value: '' },
	] )(
		'keeps $label grouped-product input at zero when zero is allowed',
		( { value } ) => {
			mockContext.allowZero = true;
			mockContext.inputElement = createInput( value );

			getRegisteredStore().actions.handleQuantityBlur();

			expect( mockSetQuantity ).toHaveBeenCalledWith( 42, 0 );
		}
	);

	it( 'preserves a positive manual value for validation by the parent store', () => {
		mockContext.inputElement = createInput( '3' );

		getRegisteredStore().actions.handleQuantityBlur();

		expect( mockSetQuantity ).toHaveBeenCalledWith( 42, 3 );
	} );

	it( 'maps a sold-individually checkbox to zero or one', () => {
		const checkbox = document.createElement( 'input' );
		checkbox.type = 'checkbox';
		mockGetElement.mockReturnValue( { ref: checkbox } );

		checkbox.checked = true;
		getRegisteredStore().actions.handleQuantityCheckboxChange();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 42, 1 );

		checkbox.checked = false;
		getRegisteredStore().actions.handleQuantityCheckboxChange();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 42, 0 );
	} );

	it( 'stores the native quantity input from the rendered wrapper', () => {
		const wrapper = document.createElement( 'div' );
		const input = createInput( '4' );
		input.className = 'qty';
		wrapper.appendChild( input );
		mockGetElement.mockReturnValue( { ref: wrapper } );

		getRegisteredStore().callbacks.storeInputElementRef();

		expect( mockContext.inputElement ).toBe( input );
	} );
} );
