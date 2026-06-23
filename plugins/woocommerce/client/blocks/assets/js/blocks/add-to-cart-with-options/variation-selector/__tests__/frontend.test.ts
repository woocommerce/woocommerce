/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

type MockAddToCartWithOptionsStore = {
	state: {
		selectableItems: Array< {
			value: string;
			title?: string;
			disabled: boolean;
			hidden: boolean;
			isUnavailable?: boolean;
		} >;
	};
	actions: {
		toggle: ( item?: {
			value: string;
			hidden?: boolean;
			disabled?: boolean;
		} ) => void;
	};
};

let mockAddToCartWithOptionsStore: MockAddToCartWithOptionsStore | null = null;

type MockContext = {
	name: string;
	selectedValue?: string | null;
	selectedAttributes: Array< { attribute: string; value: string } >;
	variationAttributeOptions: Array< {
		id: string;
		label: string;
		value: string;
		ariaLabel?: string;
		title?: string;
	} >;
	disabledAttributesAction?: 'disable' | 'hide';
	autoselect?: boolean;
};

let mockContext: MockContext = {
	name: '',
	selectedAttributes: [],
	variationAttributeOptions: [],
};

const mockProductsState: {
	mainProductInContext: ProductResponseItem | null;
	productVariations: Record< number, ProductResponseItem >;
} = {
	mainProductInContext: null,
	productVariations: {},
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition = {} ) => {
			if ( namespace === 'woocommerce/products' ) {
				return {
					state: mockProductsState,
				};
			}

			if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
				const state = {};
				Object.defineProperties(
					state,
					Object.getOwnPropertyDescriptors( definition.state )
				);
				mockAddToCartWithOptionsStore = {
					state: state as MockAddToCartWithOptionsStore[ 'state' ],
					actions: definition.actions,
				};
				return mockAddToCartWithOptionsStore;
			}

			return {};
		} ),
		getContext: jest.fn( () => mockContext ),
		getConfig: jest.fn( () => ( {
			errorMessages: {},
			variationOptionTooltips: {
				outOfStock: 'Out of stock',
			},
		} ) ),
		getElement: jest.fn( () => ( { ref: null } ) ),
	} ),
	{ virtual: true }
);

const createProduct = () =>
	( {
		id: 1,
		type: 'variable',
		variations: [
			{
				id: 10,
				attributes: [ { name: 'Size', value: 'small' } ],
			},
			{
				id: 11,
				attributes: [ { name: 'Size', value: 'large' } ],
			},
		],
	} as ProductResponseItem );

const createVariation = (
	id: number,
	isInStock: boolean,
	isPurchasable = true
) =>
	( {
		id,
		type: 'variation',
		is_in_stock: isInStock,
		is_purchasable: isPurchasable,
	} as ProductResponseItem );

describe( 'Add to Cart + Options variation selector frontend', () => {
	beforeEach( () => {
		// mockProductsState and mockContext are populated after requiring the
		// module because selectableItems is a lazy getter evaluated by each test.
		jest.isolateModules( () => {
			require( '../frontend' );
		} );

		mockProductsState.mainProductInContext = createProduct();
		mockProductsState.productVariations = {
			10: createVariation( 10, true ),
			11: createVariation( 11, false ),
		};
		mockContext = {
			name: 'Size',
			selectedAttributes: [],
			variationAttributeOptions: [
				{ id: 'size-small', label: 'Small', value: 'small' },
				{ id: 'size-large', label: 'Large', value: 'large' },
			],
			disabledAttributesAction: 'disable',
		};
	} );

	it( 'keeps out-of-stock variation options selectable', () => {
		expect(
			mockAddToCartWithOptionsStore?.state.selectableItems
		).toMatchObject( [
			{ value: 'small', disabled: false, hidden: false },
			{
				value: 'large',
				title: 'Out of stock',
				disabled: false,
				hidden: false,
				isUnavailable: true,
			},
		] );

		mockAddToCartWithOptionsStore?.actions.toggle(
			mockAddToCartWithOptionsStore.state.selectableItems[ 1 ]
		);

		expect( mockContext.selectedValue ).toBe( 'large' );
		expect( mockContext.selectedAttributes ).toEqual( [
			{ attribute: 'Size', value: 'large' },
		] );
	} );

	it( 'hides out-of-stock variation options when configured to hide invalid options', () => {
		mockContext.disabledAttributesAction = 'hide';

		expect(
			mockAddToCartWithOptionsStore?.state.selectableItems
		).toMatchObject( [
			{ value: 'small', disabled: false, hidden: false },
			{
				value: 'large',
				title: 'Out of stock',
				disabled: false,
				hidden: false,
				isUnavailable: true,
			},
		] );
	} );

	it( 'disables non-purchasable variation options', () => {
		mockProductsState.productVariations[ 11 ] = createVariation(
			11,
			true,
			false
		);

		expect(
			mockAddToCartWithOptionsStore?.state.selectableItems
		).toMatchObject( [
			{ value: 'small', disabled: false, hidden: false },
			{
				value: 'large',
				title: undefined,
				disabled: true,
				hidden: false,
			},
		] );
	} );

	it( 'disables non-purchasable variation options when only purchasable status is available', () => {
		mockProductsState.productVariations[ 11 ] = {
			id: 11,
			type: 'variation',
			is_purchasable: false,
		} as ProductResponseItem;

		expect(
			mockAddToCartWithOptionsStore?.state.selectableItems
		).toMatchObject( [
			{ value: 'small', disabled: false, hidden: false },
			{
				value: 'large',
				title: undefined,
				disabled: true,
				hidden: false,
			},
		] );
	} );

	it( 'does not show the out of stock title for invalid variation options', () => {
		mockProductsState.mainProductInContext = {
			...createProduct(),
			variations: [
				{
					id: 10,
					attributes: [
						{ name: 'Color', value: 'red' },
						{ name: 'Size', value: 'small' },
					],
				},
				{
					id: 11,
					attributes: [
						{ name: 'Color', value: 'blue' },
						{ name: 'Size', value: 'large' },
					],
				},
			],
		} as ProductResponseItem;
		mockProductsState.productVariations = {
			10: createVariation( 10, true ),
			11: createVariation( 11, true ),
		};
		mockContext = {
			...mockContext,
			name: 'Size',
			selectedAttributes: [ { attribute: 'Color', value: 'red' } ],
		};

		expect(
			mockAddToCartWithOptionsStore?.state.selectableItems
		).toMatchObject( [
			{ value: 'small', disabled: false, hidden: false },
			{
				value: 'large',
				title: undefined,
				disabled: true,
				hidden: false,
			},
		] );
	} );
} );
