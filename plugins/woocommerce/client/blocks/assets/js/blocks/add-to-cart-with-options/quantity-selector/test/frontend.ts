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
let mockProduct: Record< string, unknown > | null;

const mockAddToCartStore = {
	state: {
		effectiveQuantity: 0,
	},
	actions: {
		setQuantity: mockSetQuantity,
	},
};

const mockWooState = {
	get productScope() {
		return { product: mockProduct };
	},
};

const mockStore = jest.fn( ( namespace, definition ) => {
	if ( namespace === 'woocommerce' ) {
		return { state: mockWooState };
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

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );

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
		mockProduct = {
			id: 42,
			is_in_stock: true,
			sold_individually: false,
			add_to_cart: {
				minimum: 4,
				maximum: 8,
				multiple_of: 2,
			},
		};
		mockAddToCartStore.state.effectiveQuantity = 4;

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'derives quantity controls from stock, sold-individually, and bounds', () => {
		const state = getRegisteredStore().state;

		expect( state.allowsQuantityChange ).toBe( true );
		expect( state.allowsDecrease ).toBe( false );
		expect( state.allowsIncrease ).toBe( true );

		mockProduct = {
			...mockProduct,
			is_in_stock: false,
		};
		expect( state.allowsQuantityChange ).toBe( false );

		mockProduct = {
			...mockProduct,
			is_in_stock: true,
		};

		mockAddToCartStore.state.effectiveQuantity = 8;
		expect( state.allowsIncrease ).toBe( false );

		mockAddToCartStore.state.effectiveQuantity = 4;
		mockContext.allowZero = true;
		expect( state.allowsDecrease ).toBe( true );

		mockProduct = {
			...mockProduct,
			sold_individually: true,
		};
		expect( state.allowsQuantityChange ).toBe( false );
	} );

	it( 'reads inputQuantity from the effective quantity of the scope', () => {
		expect( getRegisteredStore().state.inputQuantity ).toBe( 4 );

		mockAddToCartStore.state.effectiveQuantity = 7;
		expect( getRegisteredStore().state.inputQuantity ).toBe( 7 );

		mockProduct = null;
		expect( getRegisteredStore().state.inputQuantity ).toBe( 0 );
	} );

	it( 'clamps increase and decrease button actions to product bounds', () => {
		mockContext.inputElement = createInput( '7' );

		getRegisteredStore().actions.increaseQuantity();

		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 8 );

		mockSetQuantity.mockClear();
		mockContext.inputElement.value = '5';
		getRegisteredStore().actions.decreaseQuantity();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 4 );

		mockSetQuantity.mockClear();
		mockContext.allowZero = true;
		mockContext.inputElement.value = '4';
		getRegisteredStore().actions.decreaseQuantity();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 0 );
	} );

	it.each( [
		{ label: 'zero', value: '0' },
		{ label: 'empty', value: '' },
	] )(
		'resets $label simple-product input to the minimum on blur',
		( { value } ) => {
			mockContext.inputElement = createInput( value );

			getRegisteredStore().actions.handleQuantityBlur();

			expect( mockSetQuantity ).toHaveBeenCalledWith( 4 );
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

			expect( mockSetQuantity ).toHaveBeenCalledWith( 0 );
		}
	);

	it( 'preserves a positive manual value for validation by the parent store', () => {
		mockContext.inputElement = createInput( '3' );

		getRegisteredStore().actions.handleQuantityBlur();

		expect( mockSetQuantity ).toHaveBeenCalledWith( 3 );
	} );

	it( 'maps a sold-individually checkbox to zero or one', () => {
		const checkbox = document.createElement( 'input' );
		checkbox.type = 'checkbox';
		mockGetElement.mockReturnValue( { ref: checkbox } );

		checkbox.checked = true;
		getRegisteredStore().actions.handleQuantityCheckboxChange();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 1 );

		checkbox.checked = false;
		getRegisteredStore().actions.handleQuantityCheckboxChange();
		expect( mockSetQuantity ).toHaveBeenLastCalledWith( 0 );
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
