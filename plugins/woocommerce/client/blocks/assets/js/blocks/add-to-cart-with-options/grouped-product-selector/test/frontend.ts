import { beforeEach, describe, expect, it, vi } from 'vitest';
const { mockStore, mockGetConfig, mockProductsState, mockBatchAddCartItems } =
	vi.hoisted( () => {
		const mockBatchAddCartItems = vi.fn();
		const mockProductsState = {
			findProduct: vi.fn(),
		};
		const mockStore = vi.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce/products' ) {
				return {
					state: mockProductsState,
				};
			}
			if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
				if ( definition?.actions ) {
					Object.assign(
						mockAddToCartStore.actions,
						definition.actions
					);
				}
				if ( definition?.callbacks ) {
					Object.assign(
						mockAddToCartStore.callbacks,
						definition.callbacks
					);
				}
				mockRegisteredStore = mockAddToCartStore;
				return mockAddToCartStore;
			}
			if ( namespace === 'woocommerce' ) {
				return {
					actions: {
						batchAddCartItems: mockBatchAddCartItems,
					},
				};
			}
			return {};
		} );
		const mockGetConfig = vi.fn();
		return {
			mockStore,
			mockGetConfig,
			mockProductsState,
			mockBatchAddCartItems,
		};
	} );

/**
 * Internal dependencies
 */
import type { Context } from '../../frontend';
import type { GroupedProductAddToCartWithOptionsStore } from '../frontend';
const mockClearErrors = vi.fn();
const mockAddError = vi.fn();
let mockContext: Context;
let mockRegisteredStore: GroupedProductAddToCartWithOptionsStore | null;
let mockAddToCartStore: GroupedProductAddToCartWithOptionsStore;
vi.mock(
	'@wordpress/interactivity',
	() => {
		const mock = {
			store: mockStore,
			getContext: vi.fn( () => mockContext ),
			getConfig: mockGetConfig,
		};
		return Object.defineProperties(
			{
				default: mock,
			},
			Object.getOwnPropertyDescriptors( mock )
		);
	},
	{
		virtual: true,
	}
);
vi.mock( '@woocommerce/stores/woocommerce/cart', () => {
	const mock = {};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/stores/woocommerce/products', () => {
	const mock = {};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const getRegisteredStore = (): GroupedProductAddToCartWithOptionsStore => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Grouped product selector store was not registered.' );
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
describe( 'Add to Cart + Options grouped product selector store', () => {
	beforeEach( async () => {
		vi.resetModules();
		vi.clearAllMocks();
		mockContext = {
			selectedAttributes: [],
			quantity: {},
			validationErrors: [],
			tempQuantity: 0,
			groupedProductIds: [],
		};
		mockRegisteredStore = null;
		mockAddToCartStore = {
			state: {} as GroupedProductAddToCartWithOptionsStore[ 'state' ],
			actions: {
				clearErrors: mockClearErrors,
				addError: mockAddError,
			} as unknown as GroupedProductAddToCartWithOptionsStore[ 'actions' ],
			callbacks:
				{} as GroupedProductAddToCartWithOptionsStore[ 'callbacks' ],
		};
		mockGetConfig.mockReturnValue( {
			errorMessages: {
				groupedProductAddToCartMissingItems: 'Choose products.',
				invalidQuantities: 'Choose valid quantities.',
			},
		} );
		mockProductsState.findProduct.mockReturnValue( null );
		vi.resetModules(),
			await ( async () => {
				await vi.importActual( '../frontend' );
			} )();
	} );
	it( 'reports an empty grouped selection', () => {
		mockContext.quantity = {
			11: 0,
			12: 0,
		};
		getRegisteredStore().callbacks.validateQuantities();
		expect( mockClearErrors ).toHaveBeenCalledWith( 'invalid-quantities' );
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'groupedProductAddToCartMissingItems',
			message: 'Choose products.',
			group: 'invalid-quantities',
		} );
	} );
	it( 'reports nonzero child quantities outside the product bounds', () => {
		mockContext.quantity = {
			11: 4,
			12: 1,
		};
		mockProductsState.findProduct.mockImplementation( ( { id } ) => ( {
			id,
			add_to_cart: {
				minimum: 1,
				maximum: id === 11 ? 3 : 1,
			},
		} ) );
		getRegisteredStore().actions.validateGroupedProductQuantity();
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'invalidQuantities',
			message: 'Choose valid quantities.',
			group: 'invalid-quantities',
		} );
	} );
	it( 'reports a positive child quantity below the product minimum', () => {
		mockContext.quantity = {
			11: 2,
			12: 0,
		};
		mockProductsState.findProduct.mockImplementation( ( { id } ) => ( {
			id,
			add_to_cart: {
				minimum: id === 11 ? 3 : 1,
				maximum: 5,
			},
		} ) );
		getRegisteredStore().actions.validateGroupedProductQuantity();
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'invalidQuantities',
			message: 'Choose valid quantities.',
			group: 'invalid-quantities',
		} );
	} );
	it( 'accepts zero optional children and valid selected quantities', () => {
		mockContext.quantity = {
			11: 0,
			12: 1,
		};
		mockProductsState.findProduct.mockImplementation( ( { id } ) => ( {
			id,
			add_to_cart: {
				minimum: 1,
				maximum: 2,
			},
		} ) );
		getRegisteredStore().actions.validateGroupedProductQuantity();
		expect( mockAddError ).not.toHaveBeenCalled();
	} );
	it( 'sends exact nonzero child vectors including sold-individually choices', async () => {
		mockContext.groupedProductIds = [ 11, 12, 13, 14 ];
		mockContext.quantity = {
			11: 2,
			12: 0,
			13: 1,
			14: 3,
		};
		mockContext.selectedAttributes = [
			{
				attribute: 'attribute_pa_color',
				value: 'blue',
			},
		];
		mockProductsState.findProduct.mockImplementation( ( { id } ) => {
			if ( id === 14 ) {
				return null;
			}
			return {
				id,
				type: 'simple',
				sold_individually: id === 13,
			};
		} );
		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as Generator
		);
		expect( mockBatchAddCartItems ).toHaveBeenCalledWith(
			[
				{
					id: 11,
					quantityToAdd: 2,
					variation: mockContext.selectedAttributes,
					type: 'simple',
				},
				{
					id: 13,
					quantityToAdd: 1,
					variation: mockContext.selectedAttributes,
					type: 'simple',
				},
			],
			{
				showCartUpdatesNotices: false,
			}
		);
	} );
} );
