/**
 * Internal dependencies
 */
import type { Store } from '../frontend';
import type { ProductGalleryContext } from '../types';

type Actions = Store[ 'actions' ];

const mockGetContext = jest.fn();
const mockGetElement = jest.fn();
const mockGetConfig = jest.fn( () => ( {} ) );

let mockRegisteredStore: { actions: Actions } | null = null;

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: mockGetContext,
		getElement: mockGetElement,
		getConfig: mockGetConfig,
		withScope: ( fn: unknown ) => fn,
		withSyncEvent: ( fn: unknown ) => fn,
		store: jest.fn( ( namespace: string, definition?: unknown ) => {
			if ( namespace === 'woocommerce/product-gallery' && definition ) {
				mockRegisteredStore = definition as { actions: Actions };
				return mockRegisteredStore;
			}
			return { state: {} };
		} ),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ), {
	virtual: true,
} );

jest.mock( '../legacy-jquery-form', () => ( {
	subscribeLegacyJQueryFormVariations: () => null,
} ) );

const SLOT_SIZE = 80;
const SLOT_GAP = 10;
const SLOT_PITCH = SLOT_SIZE + SLOT_GAP;
const STRIP_SIZE = 400;
const CENTERING_OFFSET = ( STRIP_SIZE - SLOT_SIZE ) / 2;

type Fixture = {
	gallery: HTMLElement;
	scroller: HTMLElement;
	scrollTo: jest.Mock;
	context: ProductGalleryContext;
	wrappers: Map< number, HTMLElement >;
};

/**
 * Build a gallery with a thumbnail strip whose layout mirrors what the
 * browser would paint for the wrappers' *current* `hidden` / `order`
 * state: visible wrappers are laid out one slot pitch apart in `order`
 * (falling back to DOM order), hidden ones report an empty rect.
 */
const createGallery = (
	imageIds: number[],
	{ direction = 'column' }: { direction?: 'column' | 'row' } = {}
): Fixture => {
	document.body.innerHTML = '';

	const gallery = document.createElement( 'div' );
	gallery.className = 'wp-block-woocommerce-product-gallery';

	const scroller = document.createElement( 'div' );
	scroller.className = 'wc-block-product-gallery-thumbnails__scrollable';
	scroller.style.flexDirection = direction;
	gallery.appendChild( scroller );
	document.body.appendChild( gallery );

	const isVertical = direction === 'column';
	const rect = ( start: number, size: number ): DOMRect =>
		( {
			top: isVertical ? start : 0,
			left: isVertical ? 0 : start,
			height: isVertical ? size : SLOT_SIZE,
			width: isVertical ? SLOT_SIZE : size,
		} ) as DOMRect;

	scroller.getBoundingClientRect = () => rect( 0, STRIP_SIZE );

	const wrappers = new Map< number, HTMLElement >();
	imageIds.forEach( ( imageId, domIndex ) => {
		const wrapper = document.createElement( 'div' );
		wrapper.className = 'wc-block-product-gallery-thumbnails__thumbnail';
		const image = document.createElement( 'img' );
		image.setAttribute( 'data-image-id', String( imageId ) );
		wrapper.appendChild( image );
		scroller.appendChild( wrapper );
		wrappers.set( imageId, wrapper );

		wrapper.getBoundingClientRect = () => {
			if ( wrapper.hidden ) {
				return rect( 0, 0 );
			}
			const slot =
				wrapper.style.order === ''
					? domIndex
					: Number( wrapper.style.order );
			return rect( slot * SLOT_PITCH - scroller.scrollTop, SLOT_SIZE );
		};
	} );

	const scrollTo = jest.fn();
	scroller.scrollTo = scrollTo;

	const context: ProductGalleryContext = {
		selectedImageId: imageIds[ 0 ],
		isDialogOpen: false,
		productId: '11',
		touchStartX: 0,
		touchCurrentX: 0,
		isDragging: false,
		imageData: [ ...imageIds ],
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

	mockGetContext.mockReturnValue( context );
	mockGetElement.mockReturnValue( { ref: gallery } );

	return { gallery, scroller, scrollTo, context, wrappers };
};

/** Paint the strip the way the `syncThumbnailState` watches would. */
const applyLayout = ( { context, wrappers }: Fixture ) => {
	wrappers.forEach( ( wrapper, imageId ) => {
		const index = context.imageData.indexOf( imageId );
		wrapper.hidden = index < 0;
		wrapper.style.order = index < 0 ? '' : String( index );
	} );
};

const getActions = (): Actions => {
	if ( ! mockRegisteredStore ) {
		throw new Error( 'Product Gallery store was not registered.' );
	}
	return mockRegisteredStore.actions;
};

describe( 'Product Gallery thumbnails scroll position', () => {
	beforeEach( () => {
		jest.resetModules();
		mockGetContext.mockReset();
		mockGetElement.mockReset();
		mockRegisteredStore = null;

		jest.isolateModules( () => {
			require( '../frontend' );
		} );
	} );

	it( 'centers the slot the selected thumbnail will occupy, not the slot it is leaving', () => {
		// Parent gallery: 15 images, the strip fits ~4 slots. The Blue
		// variation's image (39) sits at slot 7 in the parent set.
		const parentIds = [
			38, 4, 35, 36, 37, 42, 43, 39, 44, 45, 40, 46, 47, 41, 48,
		];
		const fixture = createGallery( parentIds );
		applyLayout( fixture );

		// Selecting the Blue variation moves 39 to slot 0; the watches that
		// re-slot the DOM haven't run yet, so the wrappers still describe
		// the parent layout above.
		const blueIds = [
			39, 4, 35, 36, 37, 42, 43, 44, 45, 40, 46, 47, 41, 48,
		];
		getActions().setImageData( blueIds, 39 );

		expect( fixture.context.imageData ).toEqual( blueIds );
		expect( fixture.context.selectedImageId ).toBe( 39 );
		expect( fixture.scrollTo ).toHaveBeenCalledTimes( 1 );
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 0 * SLOT_PITCH - CENTERING_OFFSET,
			behavior: 'instant',
		} );
		// The stale rect would have centered slot 7 instead.
		expect( fixture.scrollTo.mock.calls[ 0 ][ 0 ].top ).not.toBe(
			7 * SLOT_PITCH - CENTERING_OFFSET
		);
	} );

	it( 'centers a thumbnail that was hidden before the image set changed', () => {
		// Variation-only image 39 is rendered but hidden in the parent set,
		// so its own rect is empty.
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 39 ] );
		fixture.context.imageData = [ 38, 4, 35, 36, 37, 42 ];
		applyLayout( fixture );

		getActions().setImageData( [ 38, 4, 35, 36, 37, 42, 39 ], 39 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 6 * SLOT_PITCH - CENTERING_OFFSET,
			behavior: 'instant',
		} );
	} );

	it( 'keeps centering clicked thumbnails on their current slot', () => {
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43, 44 ] );
		applyLayout( fixture );

		mockGetElement.mockReturnValue( {
			ref: fixture.wrappers.get( 43 )?.firstElementChild,
		} );
		getActions().selectCurrentImage();

		expect( fixture.context.selectedImageId ).toBe( 43 );
		expect( fixture.scrollTo ).toHaveBeenCalledWith(
			expect.objectContaining( {
				top: 6 * SLOT_PITCH - CENTERING_OFFSET,
				behavior: 'smooth',
			} )
		);
	} );

	it( 'accounts for the current scroll offset of the strip', () => {
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43, 44 ] );
		applyLayout( fixture );
		fixture.scroller.scrollTop = 300;

		getActions().selectImage( 2 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith(
			expect.objectContaining( {
				top: 2 * SLOT_PITCH - CENTERING_OFFSET,
				behavior: 'smooth',
			} )
		);
	} );

	it( 'scrolls horizontally when the strip is laid out as a row', () => {
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43, 39 ], {
			direction: 'row',
		} );
		applyLayout( fixture );

		getActions().setImageData( [ 39, 4, 35, 36, 37, 42, 43 ], 39 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			left: 0 * SLOT_PITCH - CENTERING_OFFSET,
			behavior: 'instant',
		} );
	} );

	it( 'restores the parent image set and scrolls back to its first slot', () => {
		mockGetConfig.mockReturnValue( {
			products: {
				11: { image_id: 38, image_ids: [ 38, 4, 35, 36, 37, 42, 43 ] },
			},
		} );
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43 ] );
		fixture.context.imageData = [ 43, 4, 35, 36, 37, 42 ];
		fixture.context.selectedImageId = 43;
		applyLayout( fixture );

		getActions().resetImageData();

		expect( fixture.context.imageData ).toEqual( [
			38, 4, 35, 36, 37, 42, 43,
		] );
		expect( fixture.context.selectedImageId ).toBe( 38 );
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 0 * SLOT_PITCH - CENTERING_OFFSET,
			behavior: 'instant',
		} );
	} );
} );
