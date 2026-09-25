/**
 * External dependencies
 */
import { test as base, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

import { ProductGalleryPage } from '../../product-gallery.page';
const blockData = {
	name: 'woocommerce/product-gallery-large-image',
	selectors: {
		frontend: {},
		editor: {},
	},
	slug: 'single-product',
	productPage: '/product/hoodie/',
};

const test = base.extend< { pageObject: ProductGalleryPage } >( {
	pageObject: async ( { page, editor, frontendUtils }, use ) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
			frontendUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.openDocumentSettingsSidebar();
	} );

	test( 'Renders Product Gallery Viewer block on the editor and frontend side', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		const viewerBlock = await pageObject.getViewerBlock( {
			page: 'editor',
		} );

		await expect( viewerBlock ).toBeVisible();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );

		const viewerBlockFrontend = await pageObject.getViewerBlock( {
			page: 'frontend',
		} );

		await expect( viewerBlockFrontend ).toBeVisible();
	} );

	test.describe( 'Zoom while hovering setting', () => {
		test( 'should be enabled by default', async ( { pageObject } ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			const zoomWhileHoveringSetting =
				pageObject.getZoomWhileHoveringSetting();

			await expect( zoomWhileHoveringSetting ).toBeChecked();
		} );

		test( 'should work on frontend when is enabled', async ( {
			pageObject,
			editor,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await pageObject.toggleZoomWhileHoveringSetting( true );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );

			const selectedImage = viewerBlock.locator( 'img' ).first();

			await test.step( 'for selected image', async () => {
				// img[style] is the selector because the style attribute is Interactivity API.

				const style = await selectedImage.evaluate(
					( el ) => el.style
				);

				expect( style.transform ).toBe( '' );

				await selectedImage.hover();

				const styleOnHover = await selectedImage.evaluate(
					( el ) => el.style
				);

				expect( styleOnHover.transform ).toBe( 'scale(1.3)' );
			} );

			await test.step( 'styles are not applied to other images', async () => {
				// img[style] is the selector because the style attribute is Interactivity API.
				const hiddenImage = viewerBlock.locator( 'img' ).nth( 1 );
				const style = await hiddenImage.evaluate( ( el ) => el.style );

				expect( style.transform ).toBe( '' );

				await selectedImage.hover();

				const styleOnHover = await hiddenImage.evaluate(
					( el ) => el.style
				);

				expect( styleOnHover.transform ).toBe( '' );
			} );
		} );

		test( 'should not work on frontend when is disabled', async ( {
			pageObject,
			editor,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await pageObject.toggleZoomWhileHoveringSetting( false );
			const buttonElement = pageObject.getZoomWhileHoveringSetting();

			await expect( buttonElement ).not.toBeChecked();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );

			const imgElement = viewerBlock.locator( 'img' ).first();
			const style = await imgElement.evaluate( ( el ) => el.style );

			expect( style.transform ).toBe( '' );

			await imgElement.hover();

			const styleOnHover = await imgElement.evaluate(
				( el ) => el.style
			);

			expect( styleOnHover.transform ).toBe( '' );
		} );
	} );

	test( 'Variable product gallery: featured → variation → cleared', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );
		await pageObject.addClassicAddToCartFormBlock();

		const viewerBlock = await pageObject.getViewerBlock( {
			page: 'editor',
		} );
		await expect( viewerBlock ).toBeVisible();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );

		// Initial state: only the parent's featured image is visible.
		const featuredImageId = await pageObject.getViewerImageId();
		expect( featuredImageId ).not.toBeNull();

		const cartForm = await pageObject.getClassicAddToCartFormBlock( {
			page: 'frontend',
		} );
		const colorSelect = cartForm.getByLabel( 'Color' );
		const logoSelect = cartForm.getByLabel( 'Logo' );

		// Variation selected: visible image switches to the variation's image.
		await colorSelect.selectOption( 'Green' );
		await logoSelect.selectOption( 'No' );

		await expect( async () => {
			const variationImageId = await pageObject.getViewerImageId();
			expect( variationImageId ).not.toEqual( featuredImageId );
		} ).toPass( { timeout: 5_000 } );

		// Clear: gallery returns to the parent's featured image.
		await colorSelect.selectOption( '' );

		await expect( async () => {
			const afterClearImageId = await pageObject.getViewerImageId();
			expect( afterClearImageId ).toEqual( featuredImageId );
		} ).toPass( { timeout: 5_000 } );
	} );

	test( 'Variable product gallery: classic Add to Cart Form preserves the parent gallery between variations', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );
		await pageObject.addClassicAddToCartFormBlock();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );
		const featuredImageId = await pageObject.getViewerImageId();
		expect( featuredImageId ).not.toBeNull();
		const parentImageIds = await pageObject.getVisibleViewerImageIds();
		const parentGalleryImageIds = parentImageIds.slice( 1 );
		expect( parentGalleryImageIds.length ).toBeGreaterThan( 0 );

		const addToCartForm = await pageObject.getClassicAddToCartFormBlock( {
			page: 'frontend',
		} );
		await expect(
			addToCartForm.locator( 'form.variations_form' )
		).toBeVisible();

		const colorSelect = addToCartForm.getByLabel( 'Color' );
		const logoSelect = addToCartForm.getByLabel( 'Logo' );

		await colorSelect.selectOption( 'Blue' );
		await logoSelect.selectOption( 'Yes' );

		await expect( async () => {
			const variationImageId = await pageObject.getViewerImageId();
			expect( variationImageId ).not.toEqual( featuredImageId );
		} ).toPass( { timeout: 5_000 } );

		const firstVariationImageId = await pageObject.getViewerImageId();
		const firstVariationImageIds = Array.from(
			new Set( [ firstVariationImageId, ...parentGalleryImageIds ] )
		);
		// Product Gallery blocks update reactively and may not be ready
		// instantly hence expect().toPass with custom timeout since it's 0 by default.
		await expect( async () => {
			const variationImageIds =
				await pageObject.getVisibleViewerImageIds();
			const thumbnailImageIds =
				await pageObject.getVisibleThumbnailImageIds();
			const activeThumbnailImageId =
				await pageObject.getActiveThumbnailImageId();

			expect( variationImageIds ).toEqual( firstVariationImageIds );
			expect( thumbnailImageIds ).toEqual( firstVariationImageIds );
			expect( activeThumbnailImageId ).toEqual( firstVariationImageId );
		} ).toPass( { timeout: 5_000 } );

		await logoSelect.selectOption( 'No' );

		// Product Gallery blocks update reactively and may not be ready
		// instantly hence expect().toPass with custom timeout since it's 0 by default.
		await expect( async () => {
			const nextVariationImageId = await pageObject.getViewerImageId();
			expect( nextVariationImageId ).not.toEqual( firstVariationImageId );
		} ).toPass( { timeout: 5_000 } );

		const nextVariationImageId = await pageObject.getViewerImageId();
		const nextVariationImageIds = Array.from(
			new Set( [ nextVariationImageId, ...parentGalleryImageIds ] )
		);
		// Product Gallery blocks update reactively and may not be ready
		// instantly hence expect().toPass with custom timeout since it's 0 by default.
		await expect( async () => {
			const variationImageIds =
				await pageObject.getVisibleViewerImageIds();
			const thumbnailImageIds =
				await pageObject.getVisibleThumbnailImageIds();
			const activeThumbnailImageId =
				await pageObject.getActiveThumbnailImageId();

			expect( variationImageIds ).toEqual( nextVariationImageIds );
			expect( thumbnailImageIds ).toEqual( nextVariationImageIds );
			expect( activeThumbnailImageId ).toEqual( nextVariationImageId );
		} ).toPass( { timeout: 5_000 } );
	} );

	test.describe( 'Frontend navigation', () => {
		test.use( {
			hasTouch: true,
			contextOptions: { reducedMotion: 'no-preference' },
		} );

		test.beforeEach( async ( { pageObject, editor, page } ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( '/product/beanie/' );

			await page.setViewportSize( {
				height: 667,
				width: 390, // iPhone 12 Pro
			} );
		} );

		test( 'updates thumbnail selection during a native swipe', async ( {
			pageObject,
			page,
		} ) => {
			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );
			const viewerImage = viewerBlock.locator( 'img' ).first();

			const initialImageId = await pageObject.getViewerImageId();

			await viewerImage.scrollIntoViewIfNeeded();
			const box = ( await viewerImage.boundingBox() )!;
			expect( box ).not.toBeNull();

			// DOM-dispatched touch events cannot trigger the browser's native scrolling.
			const session = await page.context().newCDPSession( page );
			const startX = box.x + box.width * 0.8;
			const y = box.y + box.height / 2;
			await session.send( 'Input.dispatchTouchEvent', {
				type: 'touchStart',
				touchPoints: [ { x: startX, y } ],
			} );
			for ( let step = 1; step <= 8; step++ ) {
				await session.send( 'Input.dispatchTouchEvent', {
					type: 'touchMove',
					touchPoints: [
						{ x: startX - box.width * 0.075 * step, y },
					],
				} );
			}

			const scroller = viewerBlock.locator(
				'.wc-block-product-gallery-large-image__container'
			);
			await expect
				.poll( () =>
					scroller.evaluate( ( element ) => element.scrollLeft )
				)
				.toBeGreaterThan( 0 );
			const imageIds = await pageObject.getVisibleViewerImageIds();
			await expect
				.poll( () => pageObject.getActiveThumbnailImageId() )
				.toEqual( imageIds[ 1 ] );
			await session.send( 'Input.dispatchTouchEvent', {
				type: 'touchEnd',
				touchPoints: [],
			} );
			await session.detach();

			// Verify dialog is not opened
			const dialog = page.locator( '.wc-block-product-gallery-dialog' );
			await expect( dialog ).toBeHidden();

			await expect( async () => {
				// Verify the next image is shown
				const nextImageId = await pageObject.getViewerImageId();

				expect( nextImageId ).not.toEqual( initialImageId );
				expect( await pageObject.getActiveThumbnailImageId() ).toEqual(
					nextImageId
				);
			} ).toPass( { timeout: 5_000 } );

			await expect
				.poll( () =>
					scroller.evaluate( ( element ) =>
						Math.abs( element.scrollLeft - element.clientWidth )
					)
				)
				.toBeLessThan( 1 );
		} );

		test( 'a second thumbnail click overrides unfinished navigation', async ( {
			pageObject,
			page,
		} ) => {
			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );
			const scroller = viewerBlock.locator(
				'.wc-block-product-gallery-large-image__container'
			);
			const imageIds = await pageObject.getVisibleViewerImageIds();
			const initialImageId = imageIds[ 0 ];
			const thumbnails = page.locator(
				'.wc-block-product-gallery-thumbnails__thumbnail'
			);
			await thumbnails
				.locator( `[data-image-id="${ imageIds.at( -1 ) }"]` )
				.click();
			await thumbnails
				.locator( `[data-image-id="${ initialImageId }"]` )
				.click();
			await expect( async () => {
				expect( await pageObject.getViewerImageId() ).toEqual(
					initialImageId
				);
				expect( await pageObject.getActiveThumbnailImageId() ).toEqual(
					initialImageId
				);
				expect(
					await scroller.evaluate( ( element ) =>
						Math.abs( element.scrollLeft )
					)
				).toBeLessThanOrEqual( 1 );
			} ).toPass( { timeout: 5_000 } );
		} );

		test( 'interruption refreshes selection without another visibility threshold crossing', async ( {
			pageObject,
			page,
		} ) => {
			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );
			const scroller = viewerBlock.locator(
				'.wc-block-product-gallery-large-image__container'
			);
			const imageIds = await pageObject.getVisibleViewerImageIds();
			const initialImageId = imageIds[ 0 ];
			const thumbnails = page.locator(
				'.wc-block-product-gallery-thumbnails__thumbnail'
			);
			await thumbnails
				.locator( `[data-image-id="${ imageIds.at( -1 ) }"]` )
				.click();
			await scroller.evaluate( async ( element ) => {
				element.scrollTo( { left: 0, behavior: 'instant' } );
				await new Promise( ( resolve ) =>
					requestAnimationFrame( () =>
						requestAnimationFrame( resolve )
					)
				);
			} );
			await expect
				.poll( () => pageObject.getActiveThumbnailImageId() )
				.toEqual( imageIds.at( -1 ) );
			await scroller.dispatchEvent( 'pointerdown' );
			await expect
				.poll( () => pageObject.getActiveThumbnailImageId() )
				.toEqual( initialImageId );
		} );
	} );
} );
