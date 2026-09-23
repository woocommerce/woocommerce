/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { VariableProductAddToCartWithOptionsStore } from '../frontend';

type RegisteredStore = VariableProductAddToCartWithOptionsStore;

type VariationContext = {
	name: string;
	selectedValue: string | null;
	selectedAttributes: SelectedAttributes[];
	variationAttributeOptions: Array< {
		id: string;
		label: string;
		value: string;
	} >;
	autoselect: boolean;
	disabledAttributesAction?: 'disable' | 'hide';
	quantity: Record< number, number >;
};

const mockClearErrors = jest.fn();
const mockAddError = jest.fn();
const mockSetQuantity = jest.fn();
const mockGetConfig = jest.fn();
const mockGetElement = jest.fn();

let mockContext: VariationContext;
let mockProductContext: { variationId?: number | null } | null;
let mockRegisteredStore: RegisteredStore | null;
let mockAddToCartStore: RegisteredStore;

const mockProductsState = {
	productId: 100,
	variationId: null as number | null,
	mainProductInContext: null as Record< string, unknown > | null,
	productVariationInContext: null as Record< string, unknown > | null,
	productVariations: {} as Record< number, Record< string, unknown > >,
	findProduct: jest.fn(),
};

const mockStore = jest.fn( ( namespace, definition ) => {
	if ( namespace === 'woocommerce/products' ) {
		return { state: mockProductsState };
	}

	if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
		if ( definition?.state ) {
			Object.defineProperties(
				mockAddToCartStore.state,
				Object.getOwnPropertyDescriptors( definition.state )
			);
		}
		if ( definition?.actions ) {
			Object.assign( mockAddToCartStore.actions, definition.actions );
		}
		if ( definition?.callbacks ) {
			Object.assign( mockAddToCartStore.callbacks, definition.callbacks );
		}
		mockRegisteredStore = mockAddToCartStore;
		return mockAddToCartStore;
	}

	return {};
} );

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( ( namespace?: string ) =>
			namespace === 'woocommerce/products'
				? mockProductContext
				: mockContext
		),
		getConfig: mockGetConfig,
		getElement: mockGetElement,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );

const getRegisteredStore = (): RegisteredStore => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Variation selector store was not registered.' );
	}
	return mockRegisteredStore;
};

const variation = (
	id: number,
	attributes: Array< { name: string; value: string | null } >
) => ( { id, attributes } );

describe( 'Add to Cart + Options variation selector store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockContext = {
			name: 'Color',
			selectedValue: '',
			selectedAttributes: [],
			variationAttributeOptions: [],
			autoselect: false,
			disabledAttributesAction: 'disable',
			quantity: {},
		};
		mockProductContext = { variationId: null };
		mockRegisteredStore = null;
		mockAddToCartStore = {
			state: {} as RegisteredStore[ 'state' ],
			actions: {
				clearErrors: mockClearErrors,
				addError: mockAddError,
				setQuantity: mockSetQuantity,
			} as unknown as RegisteredStore[ 'actions' ],
			callbacks: {} as RegisteredStore[ 'callbacks' ],
		};
		mockProductsState.mainProductInContext = null;
		mockProductsState.productVariationInContext = null;
		mockProductsState.productVariations = {};
		mockProductsState.findProduct.mockReturnValue( null );
		mockGetConfig.mockReturnValue( {
			errorMessages: {
				variableProductMissingAttributes: 'Choose options.',
				variableProductOutOfStock: 'Variation unavailable.',
			},
		} );

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it.each( [
		{ action: 'disable', hidden: false },
		{ action: 'hide', hidden: true },
	] as const )(
		'marks invalid choices for the $action action',
		( { action, hidden } ) => {
			mockProductsState.mainProductInContext = {
				id: 100,
				type: 'variable',
				variations: [
					variation( 101, [
						{ name: 'Color', value: 'blue' },
						{ name: 'Size', value: 'xl' },
					] ),
					variation( 102, [
						{ name: 'Color', value: 'red' },
						{ name: 'Size', value: 'l' },
					] ),
				],
			};
			mockContext = {
				...mockContext,
				name: 'Size',
				disabledAttributesAction: action,
				selectedAttributes: [
					{ attribute: 'attribute_pa_color', value: 'blue' },
				],
				variationAttributeOptions: [
					{ id: 'size-l', label: 'L', value: 'l' },
					{ id: 'size-xl', label: 'XL', value: 'xl' },
				],
			};

			const selectableByValue = Object.fromEntries(
				getRegisteredStore().state.selectableItems.map( ( item ) => [
					item.value,
					item,
				] )
			);

			expect( selectableByValue.l ).toMatchObject( {
				id: 'size-l',
				selected: false,
				disabled: true,
				hidden,
			} );
			expect( selectableByValue.xl ).toMatchObject( {
				id: 'size-xl',
				selected: false,
				disabled: false,
				hidden: false,
			} );
		}
	);

	it( 'preserves a custom attribute slug while matching its Store API label', () => {
		mockProductsState.mainProductInContext = {
			id: 100,
			type: 'variable',
			variations: [
				variation( 101, [ { name: 'Numeric Size', value: '42' } ] ),
			],
		};
		mockContext = {
			...mockContext,
			name: 'attribute_pa_numeric-size',
			variationAttributeOptions: [
				{ id: 'numeric-42', label: '42', value: '42' },
			],
		};

		const item = getRegisteredStore().state.selectableItems[ 0 ];
		expect( item.disabled ).toBe( false );

		getRegisteredStore().actions.toggle( item );

		expect( mockContext.selectedAttributes ).toEqual( [
			{ attribute: 'attribute_pa_numeric-size', value: '42' },
		] );
	} );

	it( 'autoselects only unique valid options outside the changed attribute', () => {
		mockProductsState.mainProductInContext = {
			id: 100,
			type: 'variable',
			variations: [
				variation( 101, [
					{ name: 'Type', value: 't-shirt' },
					{ name: 'Color', value: 'blue' },
					{ name: 'Size', value: 'xl' },
					{ name: 'Material', value: 'cotton' },
				] ),
				variation( 102, [
					{ name: 'Type', value: 't-shirt' },
					{ name: 'Color', value: 'blue' },
					{ name: 'Size', value: 'xl' },
					{ name: 'Material', value: 'linen' },
				] ),
				variation( 103, [
					{ name: 'Type', value: 't-shirt' },
					{ name: 'Color', value: 'green' },
					{ name: 'Size', value: 's' },
				] ),
			],
		};
		mockContext.autoselect = true;
		mockContext.selectedAttributes = [
			{ attribute: 'Color', value: 'blue' },
		];

		getRegisteredStore().actions.autoselectAttributes( {
			excludedAttributes: [ 'attribute_pa_color' ],
		} );

		expect( mockContext.selectedAttributes ).not.toContainEqual( {
			attribute: 'Material',
			value: 'cotton',
		} );
		expect( mockContext.selectedAttributes ).toEqual( [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Type', value: 't-shirt' },
			{ attribute: 'Size', value: 'xl' },
		] );
	} );

	it( 'does not rewrite a changed custom attribute slug when its Store API label is excluded', () => {
		mockProductsState.mainProductInContext = {
			id: 100,
			type: 'variable',
			variations: [
				variation( 101, [ { name: 'Color', value: 'blue' } ] ),
			],
		};
		mockContext = {
			...mockContext,
			autoselect: true,
			selectedAttributes: [
				{ attribute: 'attribute_pa_color', value: 'blue' },
			],
		};

		getRegisteredStore().actions.autoselectAttributes( {
			excludedAttributes: [ 'attribute_pa_color' ],
		} );

		expect( mockContext.selectedAttributes ).toEqual( [
			{ attribute: 'attribute_pa_color', value: 'blue' },
		] );
	} );

	it( 'accepts any-value variations when another selected attribute matches', () => {
		mockProductsState.mainProductInContext = {
			id: 100,
			type: 'variable',
			variations: [
				variation( 101, [
					{ name: 'Color', value: null },
					{ name: 'Size', value: 'large' },
				] ),
			],
		};
		mockContext = {
			...mockContext,
			name: 'Color',
			selectedAttributes: [ { attribute: 'Size', value: 'large' } ],
			variationAttributeOptions: [
				{ id: 'color-blue', label: 'Blue', value: 'blue' },
			],
		};

		expect( getRegisteredStore().state.selectableItems[ 0 ].disabled ).toBe(
			false
		);
	} );

	it( 'updates the selected variation ID and reports missing or unavailable matches', () => {
		const selectedAttributes = [ { attribute: 'Color', value: 'blue' } ];
		mockProductsState.mainProductInContext = {
			id: 100,
			variations: [ variation( 101, [] ) ],
		};
		mockContext.selectedAttributes = selectedAttributes;
		mockProductsState.findProduct.mockImplementation(
			( { id, selectedAttributes: receivedAttributes } ) => {
				const selectedColor = receivedAttributes?.find(
					( attribute ) => attribute.attribute === 'Color'
				);

				return id === 100 &&
					receivedAttributes?.length === 1 &&
					selectedColor?.value === 'blue'
					? { id: 101 }
					: mockProductsState.mainProductInContext;
			}
		);
		mockProductsState.productVariations[ 101 ] = {
			id: 101,
			is_in_stock: false,
		};

		getRegisteredStore().callbacks.setSelectedVariationId();
		getRegisteredStore().callbacks.validateVariation();

		expect( mockProductContext?.variationId ).toBe( 101 );
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'variableProductOutOfStock',
			message: 'Variation unavailable.',
			group: 'variable-product',
		} );

		mockAddError.mockClear();
		mockProductsState.findProduct.mockReturnValue(
			mockProductsState.mainProductInContext
		);
		getRegisteredStore().callbacks.validateVariation();
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'variableProductMissingAttributes',
			message: 'Choose options.',
			group: 'variable-product',
		} );
	} );

	it.each( [
		{ current: 2, expected: 4 },
		{ current: 10, expected: 8 },
	] )(
		'clamps variation quantity $current to $expected when the input is idle',
		( { current, expected } ) => {
			const input = document.createElement( 'input' );
			input.type = 'number';
			input.value = String( current );
			mockGetElement.mockReturnValue( { ref: input } );
			mockProductsState.productVariationInContext = {
				id: 101,
				add_to_cart: { minimum: 4, maximum: 8 },
			};
			mockContext.quantity = { 101: current };

			getRegisteredStore().callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).toHaveBeenCalledWith( 101, expected );
		}
	);
} );
