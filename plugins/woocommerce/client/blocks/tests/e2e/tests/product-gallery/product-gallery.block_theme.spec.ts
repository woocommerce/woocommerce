/**
 * External dependencies
 */
import { Locator } from '@playwright/test';
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from './product-gallery.page';

const blockData = {
	name: 'woocommerce/product-gallery',
	title: 'Product Gallery',
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

/**
 * Get the image id from the image element.
 *
 * @param imgElement - The image element.
 * @return The image id.
 */
export const getImageId = async ( imgElement: Locator ) => {
	const dataImageId = await imgElement.getAttribute( 'data-image-id' );

	if ( dataImageId ) {
		return dataImageId;
	}

	return null;
};

export const getIsDialogOpen = async (
	productGalleryBlock: Locator
): Promise< boolean > => {
	const productGalleryBlockContext = ( await productGalleryBlock.getAttribute(
		'data-wp-context'
	) ) as string;

	const productGalleryBlockParsedContext = JSON.parse(
		productGalleryBlockContext
	);

	return productGalleryBlockParsedContext.isDialogOpen;
};

const getThumbnailImageIdByNth = async (
	nth: number,
	thumbnailsLocator: Locator
) => {
	const image = thumbnailsLocator.locator( 'img' ).nth( nth );

	return getImageId( image );
};

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { admin, editor, requestUtils } ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: blockData.slug,
			title: 'Custom Single Product',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();
	} );

	test.describe( 'with thumbnails', () => {
		test( 'should have as first thumbnail, the same image that it is visible in the product block', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const viewerImageId = await pageObject.getViewerImageId();

			const firstImageThumbnailId = await getThumbnailImageIdByNth(
				0,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( viewerImageId ).toBe( firstImageThumbnailId );
		} );

		test( 'should change the image when the user click on a thumbnail image', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const viewerImageId = await pageObject.getViewerImageId();

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( viewerImageId ).not.toBe( secondImageThumbnailId );

			await (
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			 )
				.locator( 'img' )
				.nth( 1 )
				.click();

			await expect( async () => {
				const newViewerImageId = await pageObject.getViewerImageId();

				expect( newViewerImageId ).toBe( secondImageThumbnailId );
			} ).toPass( { timeout: 1_000 } );
		} );
	} );

	test.describe( 'with previous and next buttons', () => {
		test( 'should change the image when the user click on the previous or next button', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const initialViewerImageId = await pageObject.getViewerImageId();

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( initialViewerImageId ).not.toBe( secondImageThumbnailId );

			await pageObject.clickNextButton();

			const nextImageId = await pageObject.getViewerImageId();

			expect( nextImageId ).toBe( secondImageThumbnailId );

			await pageObject.clickPreviousButton();

			const previousImageId = await pageObject.getViewerImageId();

			expect( previousImageId ).toBe( initialViewerImageId );
		} );
	} );

	test.describe( 'within pop-up', () => {
		test( 'should display the same selected image when the pop-up is opened', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await page.setViewportSize( { width: 800, height: 800 } );

			await pageObject.addProductGalleryBlock( { cleanContent: false } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const initialViewerImageId = await pageObject.getViewerImageId();

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( initialViewerImageId ).not.toBe( secondImageThumbnailId );

			await pageObject.clickNextButton();

			const nextImageId = await pageObject.getViewerImageId();

			expect( nextImageId ).toBe( secondImageThumbnailId );

			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );
			await viewerBlock.click();

			const dialogImage = page
				.getByRole( 'dialog' )
				.locator( `img[data-image-id='${ nextImageId }']` );

			// The image should be in the viewport but it simply doesn't fit fully.
			await expect( dialogImage ).toBeInViewport( { ratio: 0.7 } );

			const closePopUpButton = page.locator(
				'.wc-block-product-gallery-dialog__close-button'
			);
			await closePopUpButton.click();

			const singleProductImageId = await pageObject.getViewerImageId();

			expect( singleProductImageId ).toBe( nextImageId );
		} );
	} );

	test.describe( 'open pop-up when clicked option', () => {
		test( 'should be enabled by default', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.openDocumentSettingsSidebar();
			const fullScreenOption = pageObject.getFullScreenOnClickSetting();

			await expect( fullScreenOption ).toBeChecked();
		} );

		test( 'should open dialog on the frontend', async ( {
			pageObject,
			page,
			editor,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );

			await expect( page.locator( 'dialog' ) ).toBeHidden();

			await viewerBlock.click();

			await expect( page.locator( 'dialog' ) ).toBeVisible();
		} );

		test( 'should not open dialog when the setting is disable on the frontend', async ( {
			pageObject,
			page,
			editor,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.openDocumentSettingsSidebar();
			await pageObject.toggleFullScreenOnClickSetting( false );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			await expect( page.locator( 'dialog' ) ).toBeHidden();

			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );

			await viewerBlock.click();

			await expect( page.locator( 'dialog' ) ).toBeHidden();
		} );
	} );

	test.describe( 'block availability', () => {
		test( 'should be available on the Single Product Template', async ( {
			page,
			editor,
		} ) => {
			await editor.openGlobalBlockInserter();
			await page.getByRole( 'tab', { name: 'Blocks' } ).click();
			const productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeVisible();
		} );

		test( 'should be hidden on the post editor globally', async ( {
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.openGlobalBlockInserter();
			const productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeHidden();
		} );

		test( 'should be visible on the post editor in Single Product block', async ( {
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlockUsingGlobalInserter( 'Product' );
			await editor.canvas.getByText( 'Album' ).click();
			await editor.canvas.getByText( 'Done' ).click();
			const singleProductBlock = await editor.getBlockByName(
				'woocommerce/single-product'
			);
			const singleProductClientId =
				( await singleProductBlock.getAttribute( 'data-block' ) ) ?? '';
			await editor.insertBlock(
				{ name: blockData.name },
				{ clientId: singleProductClientId }
			);

			await expect(
				await editor.getBlockByName( blockData.name )
			).toHaveCount( 1 );
		} );
	} );

	test( 'should persistently display the block when navigating back to the template without a page reload', async ( {
		editor,
		pageObject,
		page,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		// Switch to the Index template.
		await page.getByLabel( 'Open Navigation' ).click();
		await page.getByRole( 'button', { name: 'Index' } ).first().click();

		// Go back to the Custom Single Product template.
		await page.getByLabel( 'Open Navigation' ).click();

		await page
			.getByRole( 'button', { name: 'Custom Single Product' } )
			.first()
			.click();

		const productGalleryBlock = editor.canvas.getByLabel(
			'Block: Product Gallery'
		);

		await expect( productGalleryBlock ).toBeVisible();
	} );

	test( 'block has opinionated layout on mobile', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( blockData.productPage );

		await page.setViewportSize( {
			height: 667,
			width: 390, // iPhone 12 Pro
		} );

		const galleryBlock = page.locator( '.wc-block-product-gallery' );
		const thumbnailsBlock = await pageObject.getThumbnailsBlock( {
			page: 'frontend',
		} );
		const navigationArrowsBlock =
			await pageObject.getNextPreviousButtonsBlock( {
				page: 'frontend',
			} );

		// Verifying mobile layout
		// - Navigation arrows are hidden
		await expect( navigationArrowsBlock ).toBeHidden();

		// - Thumbnails are below large image
		const galleryDirection = await galleryBlock.evaluate( ( el ) =>
			window.getComputedStyle( el ).getPropertyValue( 'flex-direction' )
		);
		expect( galleryDirection ).toBe( 'column' );
		const thumbnailsOrder = await thumbnailsBlock.evaluate( ( el ) =>
			window.getComputedStyle( el ).getPropertyValue( 'order' )
		);
		expect( thumbnailsOrder ).toBe( '1' );

		// - Thumbnails container is horizontal
		const thumbnailsDirection = await thumbnailsBlock
			.locator( '.wc-block-product-gallery-thumbnails__scrollable' )
			.evaluate( ( el ) =>
				window
					.getComputedStyle( el )
					.getPropertyValue( 'flex-direction' )
			);
		expect( thumbnailsDirection ).toBe( 'row' );
	} );
} );
