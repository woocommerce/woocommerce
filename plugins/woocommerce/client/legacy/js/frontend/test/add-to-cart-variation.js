/**
 * @jest-environment jest-fixed-jsdom
 */
const $ = require( 'jquery' );

const galleryHtml = ( secondImage = 'second.jpg' ) => `
	<div class="woocommerce-product-gallery images">
		<figure class="woocommerce-product-gallery__wrapper">
			<div class="woocommerce-product-gallery__image">
				<a href="first.jpg"><img class="wp-post-image" src="first.jpg"></a>
			</div>
			<div class="woocommerce-product-gallery__image"><img src="${ secondImage }"></div>
		</figure>
	</div>`;

describe( 'Classic variation gallery updates', () => {
	let $form;
	let beforeDestroy;
	let afterInit;

	beforeAll( () => {
		window.jQuery = $;
		window.wc_single_product_params = {};
		require( '../add-to-cart-variation' );
	} );

	beforeEach( () => {
		document.body.innerHTML = `
			<div class="product">
				${ galleryHtml() }
				<form data-product_id="1"></form>
			</div>`;
		$form = $( 'form' );
		window.wc_variation_gallery_defaults = { 1: galleryHtml() };
		$.fn.wc_product_gallery = jest.fn( function () {
			return this.data( 'testGalleryState', { slide: 0 } );
		} );
		beforeDestroy = jest.fn();
		afterInit = jest.fn();
		$( document ).on(
			'wc-product-gallery-before-destroy.gallery-test',
			beforeDestroy
		);
		$( document ).on(
			'wc-product-gallery-after-init.gallery-test',
			afterInit
		);
	} );

	afterEach( () => {
		$( document ).off( '.gallery-test' );
		jest.restoreAllMocks();
	} );

	afterAll( () => {
		delete window.wc_single_product_params;
		delete window.wc_variation_gallery_defaults;
		delete $.fn.wc_product_gallery;
	} );

	test( 'keeps the current gallery and slide when variations share its markup', () => {
		$form.wc_variations_image_update( {
			image_id: 1,
			gallery_images_html: galleryHtml(),
		} );
		const $gallery = $( '.woocommerce-product-gallery' );
		$gallery.data( 'testGalleryState' ).slide = 1;
		$gallery.append( '<span class="extension-content"></span>' );

		$form.wc_variations_image_update( {
			image_id: 1,
			gallery_images_html: galleryHtml(),
		} );

		expect( $( '.woocommerce-product-gallery' )[ 0 ] ).toBe( $gallery[ 0 ] );
		expect( $gallery.data( 'testGalleryState' ).slide ).toBe( 1 );
		expect( $gallery.find( '.extension-content' ) ).toHaveLength( 1 );
		expect( $.fn.wc_product_gallery ).toHaveBeenCalledTimes( 1 );
		expect( beforeDestroy ).toHaveBeenCalledTimes( 1 );
		expect( afterInit ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'replaces changed gallery markup even when the featured image is unchanged', () => {
		$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 1 } );
		const gallery = $( '.woocommerce-product-gallery' )[ 0 ];

		$form.wc_variations_gallery_replace( galleryHtml( 'different.jpg' ), {
			image_id: 1,
		} );

		expect( $( '.woocommerce-product-gallery' )[ 0 ] ).not.toBe( gallery );
		expect( $( '.woocommerce-product-gallery img' ).last().attr( 'src' ) ).toBe(
			'different.jpg'
		);
		expect( beforeDestroy ).toHaveBeenCalledTimes( 2 );
		expect( afterInit ).toHaveBeenCalledTimes( 2 );
	} );

	test( 'updates the current image when equivalent gallery markup is reused', () => {
		$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 1 } );
		const gallery = $( '.woocommerce-product-gallery' )[ 0 ];

		expect(
			$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 2 } )
		).toBe( true );

		expect( $( '.woocommerce-product-gallery' )[ 0 ] ).toBe( gallery );
		expect( $form.attr( 'current-image' ) ).toBe( '2' );
		expect( $form.wc_variations_gallery_is_active() ).toBe( true );
	} );

	test( 'clears the selection by restoring the default gallery even when its markup matches', () => {
		$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 1 } );
		const $gallery = $( '.woocommerce-product-gallery' );
		$gallery.data( 'testGalleryState' ).slide = 1;

		$form.wc_variations_image_update( false );

		expect( $( '.woocommerce-product-gallery' )[ 0 ] ).not.toBe( $gallery[ 0 ] );
		expect( $( '.woocommerce-product-gallery' ).data( 'testGalleryState' ).slide ).toBe( 0 );
		expect( $form.attr( 'current-image' ) ).toBe( '' );
		expect( $form.wc_variations_gallery_is_active() ).toBe( false );
	} );

	test( 'restores gallery images after a variation changes only the featured image', () => {
		$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 1 } );
		$form.wc_variations_image_update( {
			image_id: 3,
			image: { src: 'featured-only.jpg', full_src: 'featured-only.jpg' },
		} );
		expect( $( '.wp-post-image' ).attr( 'src' ) ).toBe( 'featured-only.jpg' );

		$form.wc_variations_image_update( {
			image_id: 1,
			gallery_images_html: galleryHtml(),
		} );

		expect( $( '.wp-post-image' ).attr( 'src' ) ).toBe( 'first.jpg' );
		expect( $form.wc_variations_gallery_is_active() ).toBe( true );
	} );

	test( 'initializes an externally replaced gallery instead of reusing stale markup', () => {
		$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 1 } );
		$( '.woocommerce-product-gallery' ).replaceWith( galleryHtml() );
		const externalGallery = $( '.woocommerce-product-gallery' )[ 0 ];

		$form.wc_variations_gallery_replace( galleryHtml(), { image_id: 1 } );

		expect( $( '.woocommerce-product-gallery' )[ 0 ] ).not.toBe( externalGallery );
		expect( $.fn.wc_product_gallery ).toHaveBeenCalledTimes( 2 );
	} );
} );
