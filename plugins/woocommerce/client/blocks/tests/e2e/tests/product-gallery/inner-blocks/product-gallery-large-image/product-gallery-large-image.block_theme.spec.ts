/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

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
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.openDocumentSettingsSidebar();
	} );

	test( 'Renders Product Gallery Large Image block on the editor and frontend side', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		const block = await pageObject.getMainImageBlock( {
			page: 'editor',
		} );

		await expect( block ).toBeVisible();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );

		const blockFrontend = await pageObject.getMainImageBlock( {
			page: 'frontend',
		} );

		await expect( blockFrontend ).toBeVisible();
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

			const blockFrontend = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			const selectedImage = blockFrontend.locator( 'img' ).first();

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
				const hiddenImage = blockFrontend.locator( 'img' ).nth( 1 );
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

			const blockFrontend = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			const imgElement = blockFrontend.locator( 'img' ).first();
			const style = await imgElement.evaluate( ( el ) => el.style );

			expect( style.transform ).toBe( '' );

			await imgElement.hover();

			const styleOnHover = await imgElement.evaluate(
				( el ) => el.style
			);

			expect( styleOnHover.transform ).toBe( '' );
		} );
	} );

	// TODO: This test is flaky, we will fix it in https://github.com/woocommerce/woocommerce/pull/55246
	test.skip( 'Renders correct image when selecting a product variation in the Add to Cart with Options block', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: false } );
		await pageObject.addAddToCartWithOptionsBlock();

		const block = await pageObject.getMainImageBlock( {
			page: 'editor',
		} );

		await expect( block ).toBeVisible();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );

		const largeImageBlockOnFrontend = await pageObject.getMainImageBlock( {
			page: 'frontend',
		} );

		const largeImageElement = largeImageBlockOnFrontend.locator(
			'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
		);

		const imageSourceForLargeImageElement =
			await largeImageElement.getAttribute( 'src' );

		const addToCartWithOptionsBlock =
			await pageObject.getAddToCartWithOptionsBlock( {
				page: 'frontend',
			} );
		const addToCartWithOptionsColorSelector =
			addToCartWithOptionsBlock.getByLabel( 'Color' );
		const addToCartWithOptionsSizeSelector =
			addToCartWithOptionsBlock.getByLabel( 'Logo' );

		await addToCartWithOptionsColorSelector.selectOption( 'Green' );
		await addToCartWithOptionsSizeSelector.selectOption( 'No' );

		// We trigger img srcs to be set after the mutation observer is triggered.
		// eslint-disable-next-line playwright/no-wait-for-timeout, no-restricted-syntax
		await page.waitForTimeout( 500 );

		const largeImageElementAfterSelectingVariation =
			largeImageBlockOnFrontend.locator(
				'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
			);

		const imageSourceForLargeImageElementAfterSelectingVariation =
			await largeImageElementAfterSelectingVariation.getAttribute(
				'src'
			);

		expect( imageSourceForLargeImageElement ).not.toEqual(
			imageSourceForLargeImageElementAfterSelectingVariation
		);
		expect(
			imageSourceForLargeImageElementAfterSelectingVariation
		).toContain( 'hoodie-green-1' );
	} );

	test.describe( 'Swipe to navigate', () => {
		test.use( { hasTouch: true } ); // Enable touch support

		test( 'should work on frontend when is enabled', async ( {
			pageObject,
			editor,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const blockFrontend = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			await expect( blockFrontend ).toBeVisible();

			const largeImageElement = blockFrontend.locator(
				'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
			);

			// Get the element's bounding box
			const box = await largeImageElement.boundingBox();
			if ( ! box ) {
				return;
			}

			// Calculate start and end points for the swipe
			const swipeStartX = box.x + box.width / 2; // middle of element
			const swipeStartY = box.y + box.height / 2;
			const swipeEndX = swipeStartX - 200; // swipe left by 200px
			const swipeEndY = swipeStartY;

			const initialImageSrc = await largeImageElement.getAttribute(
				'src'
			);

			// Dispatch touch events to simulate swipe
			await largeImageElement.evaluate(
				( element, { startX, startY, endX, endY } ) => {
					const touchStart = new TouchEvent( 'touchstart', {
						bubbles: true,
						cancelable: true,
						touches: [
							new Touch( {
								identifier: 0,
								target: element,
								clientX: startX,
								clientY: startY,
							} ),
						],
					} );

					const touchMove = new TouchEvent( 'touchmove', {
						bubbles: true,
						cancelable: true,
						touches: [
							new Touch( {
								identifier: 0,
								target: element,
								clientX: endX,
								clientY: endY,
							} ),
						],
					} );

					const touchEnd = new TouchEvent( 'touchend', {
						bubbles: true,
						cancelable: true,
						touches: [],
					} );

					element.dispatchEvent( touchStart );
					element.dispatchEvent( touchMove );
					element.dispatchEvent( touchEnd );
				},
				{
					startX: swipeStartX,
					startY: swipeStartY,
					endX: swipeEndX,
					endY: swipeEndY,
				}
			);

			// Verify dialog is not opened
			const dialog = page.locator( '.wc-block-product-gallery-dialog' );
			await expect( dialog ).toBeHidden();

			// Timeout is needed to allow the image animation to finish.
			// eslint-disable-next-line playwright/no-wait-for-timeout, no-restricted-syntax
			await page.waitForTimeout( 1000 );

			// Verify the next image is shown
			const nextImage = blockFrontend.locator(
				'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
			);
			const nextImageSrc = await nextImage.getAttribute( 'src' );

			// The next image should be visible and have a different src
			await expect( nextImage ).toBeVisible();
			expect( nextImageSrc ).not.toEqual( initialImageSrc );
		} );
	} );
} );
