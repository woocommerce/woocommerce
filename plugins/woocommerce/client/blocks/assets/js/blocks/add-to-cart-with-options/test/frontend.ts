/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore, Context } from '../frontend';

type RegisteredStore = {
	state: AddToCartWithOptionsStore[ 'state' ];
	actions: AddToCartWithOptionsStore[ 'actions' ];
};

const mockAddCartItem = jest.fn();
const mockBatchAddCartItems = jest.fn();
const mockAddNotice = jest.fn();
const mockRemoveNotice = jest.fn();
const mockGetConfig = jest.fn();

let mockContext: Context;
let mockQuantitySelectorContext: { inputElement?: HTMLInputElement };
let mockRegisteredStore: RegisteredStore | null;
let mockAddToCartStore: RegisteredStore;

const mockProductsState = {
	productId: 0,
	productInContext: null as Record< string, unknown > | null,
	mainProductInContext: null as Record< string, unknown > | null,
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
		mockRegisteredStore = mockAddToCartStore;
		return mockAddToCartStore;
	}

	if ( namespace === 'woocommerce/store-notices' ) {
		return {
			actions: {
				addNotice: mockAddNotice,
				removeNotice: mockRemoveNotice,
			},
		};
	}

	if ( namespace === 'woocommerce' ) {
		return {
			actions: {
				addCartItem: mockAddCartItem,
				batchAddCartItems: mockBatchAddCartItems,
			},
		};
	}

	return {};
} );

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( ( namespace?: string ) =>
			namespace ===
			'woocommerce/add-to-cart-with-options-quantity-selector'
				? mockQuantitySelectorContext
				: mockContext
		),
		getConfig: mockGetConfig,
		withSyncEvent: ( action: unknown ) => action,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/cart', () => ( {} ) );
jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );
jest.mock( '@woocommerce/stores/store-notices', () => ( {} ) );

const getRegisteredStore = (): RegisteredStore => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Add to Cart + Options store was not registered.' );
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

describe( 'Add to Cart + Options interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockContext = {
			selectedAttributes: [],
			quantity: { 42: 2 },
			validationErrors: [],
			tempQuantity: 0,
			groupedProductIds: [],
		};
		mockQuantitySelectorContext = {};
		mockRegisteredStore = null;
		mockAddToCartStore = {
			state: {} as AddToCartWithOptionsStore[ 'state' ],
			actions: {} as AddToCartWithOptionsStore[ 'actions' ],
		};
		mockProductsState.productId = 42;
		mockProductsState.productInContext = {
			id: 42,
			type: 'simple',
			is_purchasable: true,
			is_in_stock: true,
			add_to_cart: {
				minimum: 1,
				maximum: 5,
			},
		};
		mockProductsState.mainProductInContext =
			mockProductsState.productInContext;
		mockGetConfig.mockReturnValue( {
			errorMessages: {
				invalidQuantities: 'Choose a valid quantity.',
			},
		} );

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'validates zero and out-of-range quantities with the configured message', () => {
		const registeredStore = getRegisteredStore();
		mockProductsState.productInContext = {
			...mockProductsState.productInContext,
			add_to_cart: {
				minimum: 0,
				maximum: 5,
			},
		};

		registeredStore.actions.validateQuantity( 42, 0 );

		expect( registeredStore.state.validationErrors ).toEqual( [
			{
				code: 'invalidQuantities',
				message: 'Choose a valid quantity.',
				group: 'invalid-quantities',
			},
		] );
		expect( registeredStore.state.isFormValid ).toBe( false );

		registeredStore.actions.validateQuantity( 42, 6 );
		expect( registeredStore.state.validationErrors ).toHaveLength( 1 );

		registeredStore.actions.validateQuantity( 42, 3 );
		expect( registeredStore.state.validationErrors ).toEqual( [] );
		expect( registeredStore.state.isFormValid ).toBe( true );
	} );

	it( 'rejects a positive quantity below the product minimum', () => {
		mockProductsState.productInContext = {
			...mockProductsState.productInContext,
			add_to_cart: {
				minimum: 4,
				maximum: 5,
			},
		};

		getRegisteredStore().actions.validateQuantity( 42, 2 );

		expect( getRegisteredStore().state.validationErrors ).toEqual( [
			{
				code: 'invalidQuantities',
				message: 'Choose a valid quantity.',
				group: 'invalid-quantities',
			},
		] );
	} );

	it.each( [
		{
			title: 'simple product',
			type: 'simple',
			selectedAttributes: [],
		},
		{
			title: 'selected variation',
			type: 'variation',
			selectedAttributes: [
				{ attribute: 'attribute_pa_color', value: 'blue' },
				{ attribute: 'Logo', value: 'No' },
			],
		},
	] )(
		'forwards the exact $title cart payload',
		async ( { type, selectedAttributes } ) => {
			mockContext.selectedAttributes = selectedAttributes;
			mockProductsState.productInContext = {
				...mockProductsState.productInContext,
				id: 42,
				type,
			};
			const preventDefault = jest.fn();

			await runGenerator(
				getRegisteredStore().actions.addToCart( {
					preventDefault,
				} as unknown as SubmitEvent )
			);

			expect( preventDefault ).toHaveBeenCalledTimes( 1 );
			expect( mockAddCartItem ).toHaveBeenCalledWith(
				{
					id: 42,
					quantityToAdd: 2,
					variation: selectedAttributes,
					type,
				},
				{ showCartUpdatesNotices: false }
			);
		}
	);

	it( 'surfaces validation errors without sending a cart request', async () => {
		const registeredStore = getRegisteredStore();
		registeredStore.actions.addError( {
			code: 'missingVariation',
			group: 'variable-product',
			message: 'Choose product options.',
		} );
		mockAddNotice.mockReturnValue( 'notice-1' );

		await runGenerator(
			registeredStore.actions.addToCart( {
				preventDefault: jest.fn(),
			} as unknown as SubmitEvent )
		);

		expect( mockAddNotice ).toHaveBeenCalledWith( {
			notice: 'Choose product options.',
			type: 'error',
			dismissible: true,
		} );
		expect( registeredStore.state.noticeIds ).toEqual( [ 'notice-1' ] );
		expect( mockAddCartItem ).not.toHaveBeenCalled();
	} );
} );
