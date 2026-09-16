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

type Layout = 'column' | 'row' | 'row-rtl';

/**
 * Gallery fixture whose rects follow the wrappers' current `hidden` and
 * `order` state, one slot pitch apart; hidden wrappers report an empty rect.
 */
const createGallery = (
	imageIds: number[],
	{ layout = 'column' }: { layout?: Layout } = {}
): Fixture => {
	document.body.innerHTML = '';

	const gallery = document.createElement( 'div' );
	gallery.className = 'wp-block-woocommerce-product-gallery';

	const scroller = document.createElement( 'div' );
	scroller.className = 'wc-block-product-gallery-thumbnails__scrollable';
	scroller.style.flexDirection = layout === 'column' ? 'column' : 'row';
	scroller.style.direction = layout === 'row-rtl' ? 'rtl' : 'ltr';
	gallery.appendChild( scroller );
	document.body.appendChild( gallery );

	const isVertical = layout === 'column';
	const rect = ( start: number, size: number ): DOMRect => {
		const top = isVertical ? start : 0;
		const left = isVertical ? 0 : start;
		const height = isVertical ? size : SLOT_SIZE;
		const width = isVertical ? SLOT_SIZE : size;
		return {
			top,
			left,
			height,
			width,
			bottom: top + height,
			right: left + width,
		} as DOMRect;
	};

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
			if ( layout === 'row-rtl' ) {
				return rect(
					STRIP_SIZE - SLOT_SIZE - slot * SLOT_PITCH,
					SLOT_SIZE
				);
			}
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

/** Apply hidden/order the way the thumbnail watches would. */
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

	it( 'scrolls to the slot the selected thumbnail will occupy, not the slot it is leaving', () => {
		// 39 sits at slot 7 in the parent set.
		const parentIds = [
			38, 4, 35, 36, 37, 42, 43, 39, 44, 45, 40, 46, 47, 41, 48,
		];
		const fixture = createGallery( parentIds );
		applyLayout( fixture );

		// The set puts 39 first; the DOM still shows the parent layout.
		const blueIds = [
			39, 4, 35, 36, 37, 42, 43, 44, 45, 40, 46, 47, 41, 48,
		];
		getActions().setImageData( blueIds, 39 );

		expect( fixture.context.imageData ).toEqual( blueIds );
		expect( fixture.context.selectedImageId ).toBe( 39 );
		expect( fixture.wrappers.get( 39 )?.style.order ).toBe( '0' );
		expect( fixture.scrollTo ).toHaveBeenCalledTimes( 1 );
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 0 * SLOT_PITCH,
			left: 0,
			behavior: 'smooth',
		} );
	} );

	it( 'shows a thumbnail that joins the set before scrolling to it', () => {
		const parentIds = [ 38, 4, 35, 36, 37, 42, 43 ];
		const fixture = createGallery( [ ...parentIds, 39 ] );
		fixture.context.imageData = [ ...parentIds ];
		applyLayout( fixture );
		const joiningWrapper = fixture.wrappers.get( 39 ) as HTMLElement;
		expect( joiningWrapper.hidden ).toBe( true );

		getActions().setImageData( [ ...parentIds, 39 ], 39 );

		expect( joiningWrapper.hidden ).toBe( false );
		expect( joiningWrapper.style.order ).toBe( '7' );
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 7 * SLOT_PITCH,
			left: 0,
			behavior: 'smooth',
		} );
	} );

	it( 'aligns the selected thumbnail to the start when the set keeps its order', () => {
		// 46 is in the parent gallery, so only the selection moves.
		const parentIds = [
			38, 4, 35, 36, 37, 42, 43, 39, 44, 45, 40, 46, 47, 41, 48,
		];
		const fixture = createGallery( parentIds );
		applyLayout( fixture );

		getActions().setImageData( parentIds, 46 );

		expect( fixture.context.imageData ).toEqual( parentIds );
		expect( fixture.context.selectedImageId ).toBe( 46 );
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 11 * SLOT_PITCH,
			left: 0,
			behavior: 'smooth',
		} );
	} );

	it( 'scrolls to a thumbnail that was hidden before the image set changed', () => {
		// 39 is rendered but hidden, so its own rect is empty.
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 39 ] );
		fixture.context.imageData = [ 38, 4, 35, 36, 37, 42 ];
		applyLayout( fixture );

		getActions().setImageData( [ 38, 4, 35, 36, 37, 42, 39 ], 39 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 6 * SLOT_PITCH,
			left: 0,
			behavior: 'smooth',
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
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 6 * SLOT_PITCH - CENTERING_OFFSET,
			left: 0,
			behavior: 'smooth',
		} );
	} );

	it( 'accounts for the current scroll offset of the strip', () => {
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43, 44 ] );
		applyLayout( fixture );
		fixture.scroller.scrollTop = 300;

		getActions().selectImage( 2 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 2 * SLOT_PITCH - CENTERING_OFFSET,
			left: 0,
			behavior: 'smooth',
		} );
	} );

	it( 'scrolls horizontally when the strip is laid out as a row', () => {
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43, 39 ], {
			layout: 'row',
		} );
		applyLayout( fixture );

		getActions().setImageData( [ 39, 4, 35, 36, 37, 42, 43 ], 39 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 0,
			left: 0 * SLOT_PITCH,
			behavior: 'smooth',
		} );
	} );

	it( 'aligns to the right edge of a right-to-left strip', () => {
		const parentIds = [ 38, 4, 35, 36, 37, 42, 43, 39 ];
		const fixture = createGallery( parentIds, { layout: 'row-rtl' } );
		applyLayout( fixture );

		getActions().setImageData( parentIds, 42 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 0,
			left: -5 * SLOT_PITCH,
			behavior: 'smooth',
		} );
	} );

	it( 'centers clicked thumbnails in a right-to-left strip', () => {
		const fixture = createGallery( [ 38, 4, 35, 36, 37, 42, 43, 39 ], {
			layout: 'row-rtl',
		} );
		applyLayout( fixture );

		getActions().selectImage( 5 );

		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 0,
			left: -5 * SLOT_PITCH + CENTERING_OFFSET,
			behavior: 'smooth',
		} );
	} );

	it( 'keeps the lineup and selects the requested image when a variation has no image set', () => {
		const lineup = [ 38, 4, 35, 36, 37, 42, 43 ];
		mockGetConfig.mockReturnValue( {
			products: { 11: { image_id: 38, image_ids: lineup } },
		} );
		const fixture = createGallery( lineup );
		applyLayout( fixture );

		getActions().setImageData( [], 42 );

		expect( fixture.context.imageData ).toEqual( lineup );
		expect( fixture.context.selectedImageId ).toBe( 42 );
		expect( fixture.scrollTo ).toHaveBeenCalledWith( {
			top: 5 * SLOT_PITCH,
			left: 0,
			behavior: 'smooth',
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
			top: 0 * SLOT_PITCH,
			left: 0,
			behavior: 'smooth',
		} );
	} );
} );
