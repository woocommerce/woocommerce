/**
 * External dependencies
 */
import type { TemplateVariationAttribute } from '@woocommerce/stores/woocommerce';

/**
 * Internal dependencies
 */
import type { VariableProductAddToCartWithOptionsStore } from '../frontend';

type RegisteredStore = VariableProductAddToCartWithOptionsStore;

type VariationContext = {
	name: string;
	selectedValue: string | null;
	variationAttributeOptions: Array< {
		id: string;
		label: string;
		value: string;
	} >;
	autoselect: boolean;
	disabledAttributesAction?: 'disable' | 'hide';
	outOfStockMessage: string;
};

const mockClearErrors = jest.fn();
const mockAddError = jest.fn();
const mockSetQuantity = jest.fn();
const mockGetConfig = jest.fn();
const mockGetElement = jest.fn();

let mockContext: VariationContext;
let mockRegisteredStore: RegisteredStore | null;
let mockAddToCartStore: RegisteredStore;
let mockVariation: TemplateVariationAttribute[];
let mockBaseProduct: Record< string, unknown > | null;
let mockProductVariation: Record< string, unknown > | null;

const mockWooState = {
	get productScope() {
		return {
			get variation(): TemplateVariationAttribute[] {
				return mockVariation;
			},
			set variation( value: TemplateVariationAttribute[] ) {
				mockVariation = value;
			},
			get baseProduct() {
				return mockBaseProduct;
			},
			get productVariation() {
				return mockProductVariation;
			},
		};
	},
};

const mockStore = jest.fn( ( namespace, definition ) => {
	if ( namespace === 'woocommerce' ) {
		return { state: mockWooState };
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
		getContext: jest.fn( () => mockContext ),
		getConfig: mockGetConfig,
		getElement: mockGetElement,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );

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
			variationAttributeOptions: [],
			autoselect: false,
			disabledAttributesAction: 'disable',
			outOfStockMessage: '',
		};
		mockVariation = [];
		mockBaseProduct = null;
		mockProductVariation = null;
		mockRegisteredStore = null;
		mockAddToCartStore = {
			state: { effectiveQuantity: 0 } as RegisteredStore[ 'state' ],
			actions: {
				clearErrors: mockClearErrors,
				addError: mockAddError,
				setQuantity: mockSetQuantity,
			} as unknown as RegisteredStore[ 'actions' ],
			callbacks: {} as RegisteredStore[ 'callbacks' ],
		};
		mockGetConfig.mockReturnValue( {
			errorMessages: {
				variableProductMissingAttributes: 'Choose options.',
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
			mockBaseProduct = {
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
				variationAttributeOptions: [
					{ id: 'size-l', label: 'L', value: 'l' },
					{ id: 'size-xl', label: 'XL', value: 'xl' },
				],
			};
			mockVariation = [
				{ attribute: 'attribute_pa_color', value: 'blue' },
			];

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
		mockBaseProduct = {
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

		expect( mockVariation ).toEqual( [
			{ attribute: 'attribute_pa_numeric-size', value: '42' },
		] );
	} );

	it( 'autoselects only unique valid options outside the changed attribute', () => {
		mockBaseProduct = {
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
		mockVariation = [ { attribute: 'Color', value: 'blue' } ];

		getRegisteredStore().actions.autoselectAttributes( {
			excludedAttributes: [ 'attribute_pa_color' ],
		} );

		expect( mockVariation ).not.toContainEqual( {
			attribute: 'Material',
			value: 'cotton',
		} );
		expect( mockVariation ).toEqual( [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Type', value: 't-shirt' },
			{ attribute: 'Size', value: 'xl' },
		] );
	} );

	it( 'does not rewrite a changed custom attribute slug when its Store API label is excluded', () => {
		mockBaseProduct = {
			id: 100,
			type: 'variable',
			variations: [
				variation( 101, [ { name: 'Color', value: 'blue' } ] ),
			],
		};
		mockContext = {
			...mockContext,
			autoselect: true,
		};
		mockVariation = [ { attribute: 'attribute_pa_color', value: 'blue' } ];

		getRegisteredStore().actions.autoselectAttributes( {
			excludedAttributes: [ 'attribute_pa_color' ],
		} );

		expect( mockVariation ).toEqual( [
			{ attribute: 'attribute_pa_color', value: 'blue' },
		] );
	} );

	it( 'accepts any-value variations when another selected attribute matches', () => {
		mockBaseProduct = {
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
			variationAttributeOptions: [
				{ id: 'color-blue', label: 'Blue', value: 'blue' },
			],
		};
		mockVariation = [ { attribute: 'Size', value: 'large' } ];

		expect( getRegisteredStore().state.selectableItems[ 0 ].disabled ).toBe(
			false
		);
	} );

	it( 'preserves the in-place order when replacing an already-selected attribute', () => {
		mockBaseProduct = {
			id: 100,
			type: 'variable',
			variations: [
				variation( 101, [
					{ name: 'Color', value: 'blue' },
					{ name: 'Size', value: 'l' },
				] ),
				variation( 102, [
					{ name: 'Color', value: 'blue' },
					{ name: 'Size', value: 'xl' },
				] ),
			],
		};
		mockVariation = [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Size', value: 'l' },
		];

		getRegisteredStore().actions.setAttribute( 'Size', 'xl' );

		expect( mockVariation ).toEqual( [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Size', value: 'xl' },
		] );
	} );

	it( 'reports missing or unavailable variation matches', () => {
		mockBaseProduct = {
			id: 100,
			variations: [ variation( 101, [] ) ],
		};
		mockProductVariation = {
			id: 101,
			is_in_stock: false,
		};
		mockContext.outOfStockMessage =
			'You cannot add "Sample product" to the cart because the product is out of stock.';

		getRegisteredStore().callbacks.validateVariation();

		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'variableProductOutOfStock',
			message:
				'You cannot add "Sample product" to the cart because the product is out of stock.',
			group: 'variable-product',
		} );

		mockAddError.mockClear();
		mockProductVariation = null;
		getRegisteredStore().callbacks.validateVariation();
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'variableProductMissingAttributes',
			message: 'Choose options.',
			group: 'variable-product',
		} );
	} );

	it( 'does not validate a product with no variations', () => {
		mockBaseProduct = { id: 100, variations: [] };

		getRegisteredStore().callbacks.validateVariation();

		expect( mockClearErrors ).toHaveBeenCalledWith( 'variable-product' );
		expect( mockAddError ).not.toHaveBeenCalled();
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
			mockProductVariation = {
				id: 101,
				add_to_cart: { minimum: 4, maximum: 8 },
			};
			mockAddToCartStore.state.effectiveQuantity = current;

			getRegisteredStore().callbacks.watchQuantityConstraints();

			expect( mockSetQuantity ).toHaveBeenCalledWith( expected );
		}
	);

	it( 'does nothing when the input is idle and already matches the effective quantity', () => {
		const input = document.createElement( 'input' );
		input.type = 'number';
		input.value = '4';
		mockGetElement.mockReturnValue( { ref: input } );
		mockProductVariation = {
			id: 101,
			add_to_cart: { minimum: 4, maximum: 8 },
		};
		mockAddToCartStore.state.effectiveQuantity = 4;

		getRegisteredStore().callbacks.watchQuantityConstraints();

		expect( mockSetQuantity ).not.toHaveBeenCalled();
	} );
} );
