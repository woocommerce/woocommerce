import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
const { mockGetContext, mockGetElement } = vi.hoisted( () => {
	const mockGetContext = vi.fn();
	const mockGetElement = vi.fn();
	return {
		mockGetContext,
		mockGetElement,
	};
} );

/**
 * External dependencies
 */
import type { ProductsStore } from '@woocommerce/stores/woocommerce/products';

/**
 * Internal dependencies
 */
import { CoreCollectionNames } from '../types';
type ProductCollectionStoreDescriptor = {
	actions: {
		viewProduct: () => Generator;
	};
	callbacks: {
		onRender: () => Generator;
	};
};
let mockContext: {
	collection: CoreCollectionNames;
} | null = null;
let mockProductsState: ProductsStore[ 'state' ];
let mockProductCollectionDescriptor: ProductCollectionStoreDescriptor | null =
	null;
vi.mock(
	'@wordpress/interactivity',
	() => {
		const mock = {
			getContext: mockGetContext,
			getElement: mockGetElement,
			store: vi.fn( ( namespace, descriptor ) => {
				if ( namespace === 'woocommerce/products' ) {
					return {
						state: mockProductsState,
					};
				}
				if ( namespace === 'woocommerce/product-collection' ) {
					mockProductCollectionDescriptor = descriptor;
					return descriptor;
				}
				return {};
			} ),
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
const getProductCollectionStore = (): ProductCollectionStoreDescriptor => {
	if ( ! mockProductCollectionDescriptor ) {
		throw new Error( 'Product collection store was not registered.' );
	}
	return mockProductCollectionDescriptor;
};
const runGenerator = ( callback: () => Generator ) => {
	const generator = callback();
	let result = generator.next();
	while ( ! result.done ) {
		result = generator.next();
	}
};
describe( 'product collection frontend events', () => {
	beforeEach( async () => {
		mockContext = null;
		mockProductsState = {
			productInContext: null,
		} as ProductsStore[ 'state' ];
		mockProductCollectionDescriptor = null;
		mockGetContext.mockImplementation( () => mockContext );
		mockGetElement.mockReset();
		vi.resetModules();
		vi.resetModules(),
			await ( async () => {
				await vi.importActual( '../frontend' );
			} )();
	} );
	afterEach( () => {
		document.body.replaceChildren();
		mockContext = null;
		mockProductsState = {} as ProductsStore[ 'state' ];
		mockProductCollectionDescriptor = null;
		mockGetContext.mockReset();
		mockGetElement.mockReset();
		vi.clearAllMocks();
		vi.resetModules();
	} );
	it( 'dispatches one product-list-rendered event for each render callback', () => {
		const collection = CoreCollectionNames.RELATED;
		const events: CustomEvent[] = [];
		const listener = ( event: Event ) =>
			events.push( event as CustomEvent );
		mockContext = {
			collection,
		};
		document.addEventListener(
			'wc-blocks_product_list_rendered',
			listener
		);
		try {
			const { callbacks } = getProductCollectionStore();
			runGenerator( callbacks.onRender );
			runGenerator( callbacks.onRender );
			runGenerator( callbacks.onRender );
			expect( events ).toHaveLength( 3 );
			expect(
				events.map( ( { detail, bubbles, cancelable } ) => ( {
					detail,
					bubbles,
					cancelable,
				} ) )
			).toEqual( [
				{
					detail: {
						collection,
					},
					bubbles: true,
					cancelable: true,
				},
				{
					detail: {
						collection,
					},
					bubbles: true,
					cancelable: true,
				},
				{
					detail: {
						collection,
					},
					bubbles: true,
					cancelable: true,
				},
			] );
		} finally {
			document.removeEventListener(
				'wc-blocks_product_list_rendered',
				listener
			);
		}
	} );
	it( 'dispatches a viewed-product event only when the context product has an ID', () => {
		const collection = CoreCollectionNames.RELATED;
		const events: CustomEvent[] = [];
		const listener = ( event: Event ) =>
			events.push( event as CustomEvent );
		mockContext = {
			collection,
		};
		mockProductsState.productInContext = {
			id: 42,
		} as ProductsStore[ 'state' ][ 'productInContext' ];
		document.addEventListener( 'wc-blocks_viewed_product', listener );
		try {
			const { actions } = getProductCollectionStore();
			runGenerator( actions.viewProduct );
			expect( events ).toHaveLength( 1 );
			expect( {
				detail: events[ 0 ].detail,
				bubbles: events[ 0 ].bubbles,
				cancelable: events[ 0 ].cancelable,
			} ).toEqual( {
				detail: {
					collection,
					productId: 42,
				},
				bubbles: true,
				cancelable: true,
			} );
			mockProductsState.productInContext = null;
			runGenerator( actions.viewProduct );
			expect( events ).toHaveLength( 1 );
		} finally {
			document.removeEventListener(
				'wc-blocks_viewed_product',
				listener
			);
		}
	} );
} );
