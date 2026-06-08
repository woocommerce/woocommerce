/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	ProductAddToCartContext,
	ProductAddToCartStore,
} from '../product-add-to-cart';

type MockStore = {
	state: ProductAddToCartStore[ 'state' ];
	actions: ProductAddToCartStore[ 'actions' ];
};

let mockRegisteredStore: MockStore | null = null;
let mockContext: ProductAddToCartContext | undefined;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace !== 'woocommerce/product-add-to-cart' ) {
				return {};
			}

			const stateBase = {};
			Object.defineProperties(
				stateBase,
				Object.getOwnPropertyDescriptors( definition.state )
			);

			mockRegisteredStore = {
				state: stateBase as ProductAddToCartStore[ 'state' ],
				actions: definition.actions,
			};

			return mockRegisteredStore;
		} ),
		getContext: jest.fn( ( namespace ) => {
			if ( namespace === 'woocommerce/product-add-to-cart' ) {
				return mockContext;
			}
			return undefined;
		} ),
	} ),
	{ virtual: true }
);

const loadStore = () => {
	let storeModule: typeof import( '../product-add-to-cart' );
	jest.isolateModules( () => {
		storeModule = require( '../product-add-to-cart' );
	} );

	return storeModule!;
};

describe( 'woocommerce/product-add-to-cart store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockRegisteredStore = null;
		mockContext = undefined;
	} );

	it( 'sets quantity in the product-scoped context when present', () => {
		mockContext = {
			quantity: { 42: 3 },
			selectedAttributes: [],
			validationErrors: [],
		};

		loadStore();

		expect( mockRegisteredStore!.state.quantityInContext[ 42 ] ).toBe( 3 );

		mockRegisteredStore!.actions.setQuantity( 42, 5 );

		expect( mockContext.quantity![ 42 ] ).toBe( 5 );
		expect( mockRegisteredStore!.state.quantity[ 42 ] ).toBeUndefined();
	} );

	it( 'falls back to store state when there is no product-scoped context', () => {
		const { getProductAddToCartPayload } = loadStore();
		const product = { id: 42, type: 'simple' } as ProductResponseItem;

		mockRegisteredStore!.actions.setQuantity( 42, 2 );

		expect( mockRegisteredStore!.state.quantity[ 42 ] ).toBe( 2 );
		expect( getProductAddToCartPayload( product, 1 ) ).toEqual( {
			id: 42,
			quantityToAdd: 2,
			type: 'simple',
		} );
	} );

	it( 'uses the fallback quantity when no scoped quantity exists', () => {
		mockContext = {
			quantity: {},
			selectedAttributes: [],
			validationErrors: [],
		};
		const { getProductAddToCartPayload } = loadStore();
		const product = { id: 42, type: 'simple' } as ProductResponseItem;

		expect( getProductAddToCartPayload( product, 7 ) ).toEqual( {
			id: 42,
			quantityToAdd: 7,
			type: 'simple',
		} );
	} );

	it( 'initializes quantity without overwriting an existing shopper selection', () => {
		mockContext = {
			quantity: { 42: 4 },
			selectedAttributes: [],
			validationErrors: [],
		};

		loadStore();

		mockRegisteredStore!.actions.initializeQuantity( 42, 1 );
		mockRegisteredStore!.actions.initializeQuantity( 43, 2 );

		expect( mockContext.quantity![ 42 ] ).toBe( 4 );
		expect( mockContext.quantity![ 43 ] ).toBe( 2 );
	} );

	it( 'includes selected attributes only when present', () => {
		mockContext = {
			quantity: { 99: 3 },
			selectedAttributes: [
				{ attribute: 'Color', value: 'Blue' },
			],
			validationErrors: [],
		};
		const { getProductAddToCartPayload } = loadStore();
		const product = { id: 99, type: 'variation' } as ProductResponseItem;

		expect( getProductAddToCartPayload( product, 1 ) ).toEqual( {
			id: 99,
			quantityToAdd: 3,
			type: 'variation',
			variation: [ { attribute: 'Color', value: 'Blue' } ],
		} );
	} );
} );
