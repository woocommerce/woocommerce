/**
 * Internal dependencies
 */
import type { AddToCartWithOptionsStore, Context } from '../frontend';

type RegisteredStore = {
	state: AddToCartWithOptionsStore[ 'state' ];
	actions: AddToCartWithOptionsStore[ 'actions' ] & {
		batchAddToCart?: () => void;
		validateGroupedProductQuantity?: () => void;
	};
};

type ScopeRecord = { draftCartItem?: Record< string, unknown > };
type ProductScope = {
	productId: number;
	variation: Array< { attribute: string; value: string } >;
	baseProduct: Record< string, unknown > | null;
	product: Record< string, unknown > | null;
	draftCartItem: Record< string, unknown >;
};

const mockAddCartItem = jest.fn();
const mockAddNotice = jest.fn();
const mockRemoveNotice = jest.fn();
const mockGetConfig = jest.fn();

let mockContext: Context;
let mockScopeContext: { scopeName?: string };
let mockQuantitySelectorContext: { inputElement?: HTMLInputElement };
let mockRegisteredStore: RegisteredStore | null;
let mockAddToCartStore: RegisteredStore;
let mockWooScopes: Record< string, ScopeRecord >;
let mockProductScope: ProductScope;

const mockWooState = {
	get productScopes() {
		return mockWooScopes;
	},
	get productScope() {
		return mockProductScope;
	},
};

const mockStore = jest.fn( ( namespace, definition ) => {
	if ( namespace === 'woocommerce' ) {
		return {
			state: mockWooState,
			actions: { addCartItem: mockAddCartItem },
		};
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
			if (
				namespace ===
				'woocommerce/add-to-cart-with-options-quantity-selector'
			) {
				return mockQuantitySelectorContext;
			}
			return mockContext;
		} ),
		getConfig: mockGetConfig,
		withSyncEvent: ( action: unknown ) => action,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );
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
			initialQuantity: { 42: 2 },
			validationErrors: [],
			noticeIds: [],
			groupedProductIds: [],
			groupedScopeNames: [],
			outOfStockMessage: '',
		};
		mockScopeContext = {};
		mockQuantitySelectorContext = {};
		mockRegisteredStore = null;
		mockAddToCartStore = {
			state: {} as AddToCartWithOptionsStore[ 'state' ],
			actions: {} as RegisteredStore[ 'actions' ],
		};
		mockWooScopes = {};
		mockProductScope = {
			productId: 42,
			variation: [],
			baseProduct: {
				id: 42,
				type: 'simple',
			},
			product: {
				id: 42,
				type: 'simple',
				is_purchasable: true,
				is_in_stock: true,
				add_to_cart: {
					minimum: 1,
					maximum: 5,
				},
			},
			draftCartItem: {},
		};
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
		mockProductScope.product = {
			...mockProductScope.product,
			add_to_cart: {
				minimum: 0,
				maximum: 5,
			},
		};

		registeredStore.actions.validateQuantity( 0 );

		expect( registeredStore.state.validationErrors ).toEqual( [
			{
				code: 'invalidQuantities',
				message: 'Choose a valid quantity.',
				group: 'invalid-quantities',
			},
		] );
		expect( registeredStore.state.isFormValid ).toBe( false );

		registeredStore.actions.validateQuantity( 6 );
		expect( registeredStore.state.validationErrors ).toHaveLength( 1 );

		registeredStore.actions.validateQuantity( 3 );
		expect( registeredStore.state.validationErrors ).toEqual( [] );
		expect( registeredStore.state.isFormValid ).toBe( true );
	} );

	it( 'rejects a positive quantity below the product minimum', () => {
		mockProductScope.product = {
			...mockProductScope.product,
			add_to_cart: {
				minimum: 4,
				maximum: 5,
			},
		};

		getRegisteredStore().actions.validateQuantity( 2 );

		expect( getRegisteredStore().state.validationErrors ).toEqual( [
			{
				code: 'invalidQuantities',
				message: 'Choose a valid quantity.',
				group: 'invalid-quantities',
			},
		] );
	} );

	it( 'falls back to the initial quantity when nothing has been typed', () => {
		mockContext.initialQuantity = { 42: 3 };

		expect( getRegisteredStore().state.effectiveQuantity ).toBe( 3 );
	} );

	it( 'reads the typed quantity from the scope record once one exists, never the fallback', () => {
		mockContext.initialQuantity = { 42: 3 };
		mockWooScopes._default = { draftCartItem: { quantity: 1 } };

		expect( getRegisteredStore().state.effectiveQuantity ).toBe( 1 );
	} );

	it( 'writes a typed quantity to the scope record, never to initialQuantity', () => {
		getRegisteredStore().actions.setQuantity( 5 );

		expect( mockProductScope.draftCartItem.quantity ).toBe( 5 );
		expect( mockContext.initialQuantity ).toEqual( { 42: 2 } );
	} );

	it( 'validates a plain quantity outside a grouped form', () => {
		mockContext.groupedProductIds = [];

		getRegisteredStore().actions.setQuantity( 0 );

		expect( getRegisteredStore().state.validationErrors ).toHaveLength( 1 );
	} );

	it( 'delegates to grouped validation from inside a grouped form', () => {
		const mockValidateGroupedProductQuantity = jest.fn();
		mockAddToCartStore.actions.validateGroupedProductQuantity =
			mockValidateGroupedProductQuantity;
		mockContext.groupedProductIds = [ 10, 11 ];

		getRegisteredStore().actions.setQuantity( 1 );

		expect( mockValidateGroupedProductQuantity ).toHaveBeenCalledTimes( 1 );
	} );

	it.each( [
		{
			title: 'simple product',
			type: 'simple',
			variation: [],
			resolvedId: 42,
		},
		{
			title: 'selected variation, posting the variation’s own id, not the parent’s',
			type: 'variation',
			variation: [
				{ attribute: 'attribute_pa_color', value: 'blue' },
				{ attribute: 'Logo', value: 'No' },
			],
			resolvedId: 55,
		},
	] )(
		'forwards the exact $title cart payload, with quantityToAdd/type dropped',
		async ( { type, variation, resolvedId } ) => {
			mockProductScope.variation = variation;
			mockProductScope.product = {
				...mockProductScope.product,
				id: resolvedId,
				type,
			};
			mockWooScopes._default = {
				draftCartItem: { id: 42, variation, quantity: 2 },
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
					id: resolvedId,
					variation,
					quantity: 2,
				},
				{ showCartUpdatesNotices: false }
			);
		}
	);

	it( 'forwards extension props written on the scope record', async () => {
		mockWooScopes._default = {
			draftCartItem: {
				id: 42,
				variation: [],
				quantity: 2,
				giftWrap: true,
			},
		};

		await runGenerator(
			getRegisteredStore().actions.addToCart( {
				preventDefault: jest.fn(),
			} as unknown as SubmitEvent )
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{
				id: 42,
				variation: [],
				quantity: 2,
				giftWrap: true,
			},
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'posts the initial quantity, not 1, when nothing was typed', async () => {
		mockContext.initialQuantity = { 42: 4 };

		await runGenerator(
			getRegisteredStore().actions.addToCart( {
				preventDefault: jest.fn(),
			} as unknown as SubmitEvent )
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			expect.objectContaining( { quantity: 4 } ),
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'delegates to batchAddToCart for a grouped product without posting directly', async () => {
		const mockBatchAddToCart = jest.fn();
		mockAddToCartStore.actions.batchAddToCart = mockBatchAddToCart;
		mockProductScope.product = {
			...mockProductScope.product,
			type: 'grouped',
		};

		await runGenerator(
			getRegisteredStore().actions.addToCart( {
				preventDefault: jest.fn(),
			} as unknown as SubmitEvent )
		);

		expect( mockBatchAddToCart ).toHaveBeenCalledTimes( 1 );
		expect( mockAddCartItem ).not.toHaveBeenCalled();
	} );

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
