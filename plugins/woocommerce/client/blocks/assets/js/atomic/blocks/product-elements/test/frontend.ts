/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';
import type { WooCommerceStore } from '@woocommerce/stores/woocommerce';

type Context = { productElementKey: keyof ProductResponseItem };
type ProductElementsStoreDescriptor = {
	callbacks: {
		updateValue: () => void;
	};
};

const mockGetContext = jest.fn();
const mockGetElement = jest.fn();

let mockContext: Context | null = null;
let mockProduct: ProductResponseItem | null = null;
let mockWooState: WooCommerceStore[ 'state' ];
let mockProductElementsDescriptor: ProductElementsStoreDescriptor | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
		getElement: mockGetElement,
		store: jest.fn( ( namespace, descriptor ) => {
			if ( namespace === 'woocommerce' ) {
				return { state: mockWooState };
			}

			if ( namespace === 'woocommerce/product-elements' ) {
				mockProductElementsDescriptor = descriptor;
				return descriptor;
			}

			return {};
		} ),
	} ),
	{ virtual: true }
);

// `frontend.ts` imports `@woocommerce/stores/woocommerce` for its build-time
// script module dependency declaration (so WordPress loads the unified
// store alongside this one); replacing it here keeps the suite from
// evaluating the real module's cart bootstrapping as a side effect of
// import — this suite only exercises `updateValue` against the `store` mock
// above.
jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );

const getProductElementsStore = (): ProductElementsStoreDescriptor => {
	if ( ! mockProductElementsDescriptor ) {
		throw new Error( 'Product elements store was not registered.' );
	}

	return mockProductElementsDescriptor;
};

describe( 'product elements frontend', () => {
	beforeEach( () => {
		mockContext = null;
		mockProduct = null;
		mockWooState = {
			get productScope() {
				return { product: mockProduct };
			},
		} as WooCommerceStore[ 'state' ];
		mockProductElementsDescriptor = null;
		mockGetContext.mockImplementation( () => mockContext );
		mockGetElement.mockReset();

		jest.resetModules();
		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	afterEach( () => {
		mockContext = null;
		mockProduct = null;
		mockProductElementsDescriptor = null;
		mockGetContext.mockReset();
		mockGetElement.mockReset();
		jest.clearAllMocks();
		jest.resetModules();
	} );

	it( 'renders the value of the product resolved by its scope', () => {
		const ref = document.createElement( 'span' );
		mockGetElement.mockReturnValue( { ref } );
		mockContext = { productElementKey: 'sku' };
		mockProduct = { id: 1, sku: 'ABC123' } as ProductResponseItem;

		getProductElementsStore().callbacks.updateValue();

		expect( ref.textContent ).toBe( 'ABC123' );
	} );

	it( 'follows a variation selected in the scope', () => {
		const ref = document.createElement( 'span' );
		mockGetElement.mockReturnValue( { ref } );
		mockContext = { productElementKey: 'sku' };
		mockProduct = { id: 1, sku: 'BASE-SKU' } as ProductResponseItem;

		getProductElementsStore().callbacks.updateValue();
		expect( ref.textContent ).toBe( 'BASE-SKU' );

		// Selecting a variation resolves the scope's `product` to the
		// matching variation instead of the base product.
		mockProduct = { id: 2, sku: 'VARIATION-SKU' } as ProductResponseItem;

		getProductElementsStore().callbacks.updateValue();
		expect( ref.textContent ).toBe( 'VARIATION-SKU' );
	} );

	it( 'does nothing when the scope resolves no product', () => {
		const ref = document.createElement( 'span' );
		ref.textContent = 'previous';
		mockGetElement.mockReturnValue( { ref } );
		mockContext = { productElementKey: 'sku' };
		mockProduct = null;

		getProductElementsStore().callbacks.updateValue();

		expect( ref.textContent ).toBe( 'previous' );
	} );
} );
