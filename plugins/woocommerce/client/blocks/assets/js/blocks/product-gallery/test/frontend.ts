/**
 * Internal dependencies
 */
import type { Store as ProductGalleryStore } from '../frontend';
import type {
	ProductGalleryContext,
	ProductGalleryConfig,
	ProductImageSet,
} from '../types';

type ProductScope = {
	productId: number;
	baseProduct: { id: number } | null;
	productVariation: { id: number } | null;
};

const mockGetConfig = jest.fn();
let mockGalleryContext: ProductGalleryContext;
let mockProductScope: ProductScope;
let mockRegisteredStore: ProductGalleryStore | null;

const mockWooState = {
	get productScope() {
		return mockProductScope;
	},
};

const mockStore = jest.fn( ( namespace: string, definition?: unknown ) => {
	if ( namespace === 'woocommerce' ) {
		return { state: mockWooState };
	}

	if ( namespace === 'woocommerce/product-gallery' ) {
		mockRegisteredStore = definition as ProductGalleryStore;
		return mockRegisteredStore;
	}

	return {};
} );

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: mockStore,
		getContext: jest.fn( () => mockGalleryContext ),
		getElement: jest.fn( () => undefined ),
		withScope: ( fn: unknown ) => fn,
		withSyncEvent: ( fn: unknown ) => fn,
		getConfig: mockGetConfig,
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce', () => ( {} ) );

const getRegisteredStore = (): ProductGalleryStore => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Product Gallery store was not registered.' );
	}
	return mockRegisteredStore;
};

const buildConfig = (
	products: Record< string, ProductImageSet >
): ProductGalleryConfig => ( { products } );

describe( 'Product Gallery interactivity store', () => {
	beforeEach( () => {
		jest.resetModules();
		jest.clearAllMocks();

		mockGalleryContext = {
			selectedImageId: 1,
			isDialogOpen: false,
			productId: '42',
			touchStartX: 0,
			touchCurrentX: 0,
			isDragging: false,
			imageData: [ 1, 2, 3 ],
			thumbnailsOverflow: {
				top: false,
				bottom: false,
				left: false,
				right: false,
			},
			hideNextPreviousButtons: false,
			isDisabledPrevious: true,
			isDisabledNext: false,
			ariaLabelPrevious: 'Previous image',
			ariaLabelNext: 'Next image',
		};
		mockProductScope = {
			productId: 42,
			baseProduct: { id: 42 },
			productVariation: null,
		};
		mockRegisteredStore = null;
		mockGetConfig.mockReturnValue( {} );

		jest.isolateModules( () => {
			jest.requireActual( '../frontend' );
		} );
	} );

	it( 'never registers or references the woocommerce/products namespace', () => {
		getRegisteredStore();

		expect( mockStore ).not.toHaveBeenCalledWith(
			'woocommerce/products',
			expect.anything(),
			expect.anything()
		);
		expect( mockStore ).toHaveBeenCalledWith(
			'woocommerce',
			expect.anything(),
			expect.anything()
		);
	} );

	it( "switches to the variation resolved by the gallery's own scope", () => {
		mockGetConfig.mockReturnValue(
			buildConfig( {
				42: {
					image_ids: [ 1, 2, 3 ],
					variations: {
						99: { image_id: 5, image_ids: [ 4, 5 ] },
					},
				},
			} )
		);
		mockProductScope.productVariation = { id: 99 };

		getRegisteredStore().callbacks.listenToProductDataChanges();

		expect( mockGalleryContext.imageData ).toEqual( [ 4, 5 ] );
		expect( mockGalleryContext.selectedImageId ).toBe( 5 );
		expect( mockGetConfig ).toHaveBeenCalledWith( 'woocommerce' );
	} );

	it( "restores the base product's images once the scope's selection is cleared", () => {
		mockGetConfig.mockReturnValue(
			buildConfig( {
				42: {
					image_ids: [ 1, 2, 3 ],
					variations: {
						99: { image_id: 5, image_ids: [ 4, 5 ] },
					},
				},
			} )
		);
		mockProductScope.productVariation = { id: 99 };
		const store = getRegisteredStore();
		store.callbacks.listenToProductDataChanges();
		expect( mockGalleryContext.imageData ).toEqual( [ 4, 5 ] );

		mockProductScope.productVariation = null;
		store.callbacks.listenToProductDataChanges();

		expect( mockGalleryContext.imageData ).toEqual( [ 1, 2, 3 ] );
		expect( mockGalleryContext.selectedImageId ).toBe( 1 );
	} );

	it( "follows whatever variation the reading element's own scope resolves, regardless of which scope that is", () => {
		// A block-scoped selection (e.g. inside a Single Product block,
		// where the gallery shares its own form's scope) reaches the
		// gallery the exact same way a page-wide selection would: through
		// `state.productScope`, with no separate page-wide field involved.
		mockGetConfig.mockReturnValue(
			buildConfig( {
				7: {
					image_ids: [ 10, 11 ],
					variations: {
						21: { image_id: 30, image_ids: [ 30 ] },
					},
				},
			} )
		);
		mockGalleryContext.productId = '7';
		mockProductScope = {
			productId: 7,
			baseProduct: { id: 7 },
			productVariation: { id: 21 },
		};

		getRegisteredStore().callbacks.listenToProductDataChanges();

		expect( mockGalleryContext.imageData ).toEqual( [ 30 ] );
		expect( mockGalleryContext.selectedImageId ).toBe( 30 );
	} );

	it( 'does nothing when the resolved product carries no configured image set', () => {
		mockGetConfig.mockReturnValue( buildConfig( {} ) );
		mockProductScope.productVariation = { id: 99 };

		getRegisteredStore().callbacks.listenToProductDataChanges();

		expect( mockGalleryContext.imageData ).toEqual( [ 1, 2, 3 ] );
	} );

	it( 'resets to the base images when the resolved variation has no image set of its own', () => {
		mockGetConfig.mockReturnValue(
			buildConfig( {
				42: {
					image_ids: [ 1, 2, 3 ],
					variations: {},
				},
			} )
		);
		mockProductScope.productVariation = { id: 99 };

		getRegisteredStore().callbacks.listenToProductDataChanges();

		expect( mockGalleryContext.imageData ).toEqual( [ 1, 2, 3 ] );
		expect( mockGalleryContext.selectedImageId ).toBe( 1 );
	} );

	it( 'does not recompute when the resolved variation id is unchanged since the last check', () => {
		mockGetConfig.mockReturnValue(
			buildConfig( {
				42: {
					image_ids: [ 1, 2, 3 ],
					variations: {
						99: { image_id: 5, image_ids: [ 4, 5 ] },
					},
				},
			} )
		);
		mockProductScope.productVariation = { id: 99 };
		const store = getRegisteredStore();
		store.callbacks.listenToProductDataChanges();
		mockGalleryContext.imageData = [ 999 ];

		store.callbacks.listenToProductDataChanges();

		// A same-as-last-seen variation id makes no further change.
		expect( mockGalleryContext.imageData ).toEqual( [ 999 ] );
	} );
} );
