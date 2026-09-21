/**
 * Internal dependencies
 */
import type { Context } from '../../frontend';
import type { GroupedProductAddToCartWithOptionsStore } from '../frontend';

const mockClearErrors = jest.fn();
const mockAddError = jest.fn();
const mockAddCartItem = jest.fn();
const mockGetConfig = jest.fn();

let mockContext: Context;
let mockRegisteredStore: GroupedProductAddToCartWithOptionsStore | null;
let mockAddToCartStore: GroupedProductAddToCartWithOptionsStore;
let mockProductScopes: Record<
	string,
	{ draftCartItem?: Record< string, unknown > }
>;
let mockScopeProducts: Record<
	string,
	{ id: number; add_to_cart: { minimum: number; maximum: number } } | null
>;

type MockScopeRef = { productId: number; scopeName: string };

const mockFindProductScope = jest.fn( ( ref: MockScopeRef ) => {
	const record = mockProductScopes[ ref.scopeName ]?.draftCartItem;
	return {
		productId: ( record?.id as number | undefined ) ?? ref.productId,
		variation:
			( record?.variation as
				| Array< { attribute: string; value: string } >
				| undefined ) ?? [],
		product: mockScopeProducts[ ref.scopeName ] ?? null,
	};
} );

const mockWooState = {
	get productScopes() {
		return mockProductScopes;
	},
	findProductScope: mockFindProductScope,
};

const mockStore = jest.fn( ( namespace, definition ) => {
	if ( namespace === 'woocommerce/add-to-cart-with-options' ) {
		if ( definition?.actions ) {
			Object.assign( mockAddToCartStore.actions, definition.actions );
		}
		if ( definition?.callbacks ) {
			Object.assign( mockAddToCartStore.callbacks, definition.callbacks );
		}
		mockRegisteredStore = mockAddToCartStore;
		return mockAddToCartStore;
	}

	if ( namespace === 'woocommerce' ) {
		return {
			state: mockWooState,
			actions: {
				addCartItem: mockAddCartItem,
			},
		};
	}

	return {};
} );

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( () => mockContext ),
		getConfig: mockGetConfig,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );

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
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockContext = {
			initialQuantity: {},
			validationErrors: [],
			noticeIds: [],
			groupedProductIds: [],
			groupedScopeNames: [],
			outOfStockMessage: '',
		};
		mockProductScopes = {};
		mockScopeProducts = {};
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

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'reports an empty grouped selection', () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 0, 12: 0 };

		getRegisteredStore().callbacks.validateQuantities();

		expect( mockClearErrors ).toHaveBeenCalledWith( 'invalid-quantities' );
		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'groupedProductAddToCartMissingItems',
			message: 'Choose products.',
			group: 'invalid-quantities',
		} );
	} );

	it( 'reports nonzero child quantities outside the product bounds', () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 4, 12: 1 };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 3 } },
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 1 } },
		};

		getRegisteredStore().actions.validateGroupedProductQuantity();

		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'invalidQuantities',
			message: 'Choose valid quantities.',
			group: 'invalid-quantities',
		} );
	} );

	it( 'reports a positive child quantity below the product minimum', () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 2, 12: 0 };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 3, maximum: 5 } },
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		getRegisteredStore().actions.validateGroupedProductQuantity();

		expect( mockAddError ).toHaveBeenCalledWith( {
			code: 'invalidQuantities',
			message: 'Choose valid quantities.',
			group: 'invalid-quantities',
		} );
	} );

	it( 'accepts zero optional children and valid selected quantities', () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 0, 12: 1 };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 2 } },
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 2 } },
		};

		getRegisteredStore().actions.validateGroupedProductQuantity();

		expect( mockAddError ).not.toHaveBeenCalled();
	} );

	it( "does not report a child whose product the store's catalog does not hold", () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 9, 12: 1 };
		// Child 11's scope carries no catalog entry, so its bounds are
		// unknown; only child 12's is seeded.
		mockScopeProducts = {
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 2 } },
		};

		getRegisteredStore().actions.validateGroupedProductQuantity();

		expect( mockAddError ).not.toHaveBeenCalled();
	} );

	it( 'validates a child by its own typed quantity, not the initial one', () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 0, 12: 0 };
		mockProductScopes[ 'form:11' ] = { draftCartItem: { quantity: 2 } };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		getRegisteredStore().actions.validateGroupedProductQuantity();

		expect( mockAddError ).not.toHaveBeenCalled();
	} );

	it( 'calls addCartItem once per non-skipped child, all in one tick, with cart-update notices suppressed', async () => {
		mockContext.groupedProductIds = [ 11, 12, 13 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12', 'form:13' ];
		mockContext.initialQuantity = { 11: 2, 12: 0, 13: 1 };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
			'form:13': { id: 13, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		const generator =
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator;

		// The generator's first (and only) yield is `Promise.all(...)` — every
		// `addCartItem` call has already happened by then, synchronously, in
		// this same tick.
		generator.next();

		expect( mockAddCartItem ).toHaveBeenCalledTimes( 2 );
		expect( mockAddCartItem ).toHaveBeenNthCalledWith(
			1,
			{ id: 11, variation: [], quantity: 2 },
			{ showCartUpdatesNotices: false }
		);
		expect( mockAddCartItem ).toHaveBeenNthCalledWith(
			2,
			{ id: 13, variation: [], quantity: 1 },
			{ showCartUpdatesNotices: false }
		);

		await runGenerator( generator );
	} );

	it( 'posts a child at its own typed quantity, not the initial one', async () => {
		mockContext.groupedProductIds = [ 11 ];
		mockContext.groupedScopeNames = [ 'form:11' ];
		mockContext.initialQuantity = { 11: 1 };
		mockProductScopes[ 'form:11' ] = {
			draftCartItem: { id: 11, variation: [], quantity: 4 },
		};
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{ id: 11, variation: [], quantity: 4 },
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'posts a resubmitted child at the quantity resubmission seeded, with no client record', async () => {
		mockContext.groupedProductIds = [ 11 ];
		mockContext.groupedScopeNames = [ 'form:11' ];
		mockContext.initialQuantity = { 11: 3 };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{ id: 11, variation: [], quantity: 3 },
			{ showCartUpdatesNotices: false }
		);
	} );

	it( "posts a child at its envelope's resolved product id, not its raw scope id", async () => {
		mockContext.groupedProductIds = [ 11 ];
		mockContext.groupedScopeNames = [ 'form:11' ];
		mockContext.initialQuantity = { 11: 2 };
		mockScopeProducts = {
			'form:11': { id: 99, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{ id: 99, variation: [], quantity: 2 },
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'skips a child whose envelope resolves no product, but still posts the others', async () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 1, 12: 2 };
		mockScopeProducts = {
			'form:11': null,
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledTimes( 1 );
		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{ id: 12, variation: [], quantity: 2 },
			{ showCartUpdatesNotices: false }
		);
	} );

	it( 'posts nothing when every child is untouched, including a sold-individually one', async () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		// Child 12 is sold individually: its initial quantity is forced to 0.
		mockContext.initialQuantity = { 11: 0, 12: 0 };

		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).not.toHaveBeenCalled();
	} );

	it( "posts each of two forms sharing one child at that child's own quantity, never the other form's", async () => {
		mockContext.groupedProductIds = [ 11 ];
		mockContext.initialQuantity = { 11: 2 };
		mockProductScopes[ 'formA:11' ] = { draftCartItem: { quantity: 2 } };
		mockProductScopes[ 'formB:11' ] = { draftCartItem: { quantity: 5 } };
		mockScopeProducts = {
			'formA:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
			'formB:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
		};

		mockContext.groupedScopeNames = [ 'formA:11' ];
		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{ id: 11, variation: [], quantity: 2 },
			{ showCartUpdatesNotices: false }
		);

		mockAddCartItem.mockClear();

		mockContext.groupedScopeNames = [ 'formB:11' ];
		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledWith(
			{ id: 11, variation: [], quantity: 5 },
			{ showCartUpdatesNotices: false }
		);
	} );

	it( "still adds the other child when the server rejects one child's add", async () => {
		mockContext.groupedProductIds = [ 11, 12 ];
		mockContext.groupedScopeNames = [ 'form:11', 'form:12' ];
		mockContext.initialQuantity = { 11: 1, 12: 1 };
		mockScopeProducts = {
			'form:11': { id: 11, add_to_cart: { minimum: 1, maximum: 5 } },
			'form:12': { id: 12, add_to_cart: { minimum: 1, maximum: 5 } },
		};
		// `addCartItem` never rejects; a server rejection resolves with
		// `success: false` instead, which is what lets `Promise.all` here
		// wait for every child's own outcome without one failure aborting
		// the others.
		mockAddCartItem.mockImplementation( ( payload ) =>
			Promise.resolve(
				( payload as { id: number } ).id === 11
					? { success: false, error: { message: 'Out of stock' } }
					: { success: true }
			)
		);

		await runGenerator(
			getRegisteredStore().actions.batchAddToCart() as unknown as Generator
		);

		expect( mockAddCartItem ).toHaveBeenCalledTimes( 2 );
	} );
} );
