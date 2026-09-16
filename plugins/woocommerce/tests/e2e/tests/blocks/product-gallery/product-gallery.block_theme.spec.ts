/**
 * External dependencies
 */
import { Locator } from '@playwright/test';
import { test as base, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from './product-gallery.page';

const blockData = {
	name: 'woocommerce/product-gallery',
	title: 'Product Gallery',
	slug: 'single-product',
	productPage: '/product/beanie/',
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

		await expect(
			editor.getCustomHtmlBlockContentLocator( 'placeholder' )
		).toBeVisible();
	} );

	test.describe( 'with thumbnails', () => {
		test( 'selects thumbnails and keeps the active viewer in sync', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( blockData.productPage );

			const thumbnailsBlock = await pageObject.getThumbnailsBlock( {
				page: 'frontend',
			} );
			const initialViewerImageId = await pageObject.getViewerImageId();
			const firstImageThumbnailId = await getThumbnailImageIdByNth(
				0,
				thumbnailsBlock
			);
			const initialActiveThumbnailId =
				await pageObject.getActiveThumbnailImageId();

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				thumbnailsBlock
			);

			expect( initialViewerImageId ).not.toBeNull();
			expect( firstImageThumbnailId ).not.toBeNull();
			expect( initialActiveThumbnailId ).not.toBeNull();
			expect( initialViewerImageId ).toBe( firstImageThumbnailId );
			expect( initialActiveThumbnailId ).toBe( firstImageThumbnailId );
			expect( secondImageThumbnailId ).not.toBeNull();
			expect( initialViewerImageId ).not.toBe( secondImageThumbnailId );

			await thumbnailsBlock.locator( 'img' ).nth( 1 ).click();

			await expect( async () => {
				const newViewerImageId = await pageObject.getViewerImageId();
				const newActiveThumbnailId =
					await pageObject.getActiveThumbnailImageId();

				expect( newViewerImageId ).toBe( secondImageThumbnailId );
				expect( newActiveThumbnailId ).toBe( secondImageThumbnailId );
			} ).toPass( { timeout: 1_000 } );
		} );

		test( 'keeps the active thumbnail in view after switching variations', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			// Give the Hoodie enough gallery images for the thumbnails strip to
			// overflow, with the Green variation's image at the very end so a
			// variation switch moves it from the last slot to the first.
			// `wpCLI` output starts with the `npm run` banner; the command's
			// own output is the last line.
			const lastLine = ( output: { stdout: string } ) =>
				output.stdout.trim().split( '\n' ).pop()?.trim() ?? '';
			const hoodieProductId = lastLine(
				await wpCLI(
					'post list --post_type=product --field=ID --name="Hoodie" --format=ids'
				)
			);
			const greenImageId = lastLine(
				await wpCLI(
					'post list --post_type=attachment --field=ID --name="hoodie-green-1.jpg" --format=ids'
				)
			);
			const attachmentIds = lastLine(
				await wpCLI(
					'post list --post_type=attachment --post_mime_type=image --field=ID --format=ids'
				)
			)
				.split( /\s+/ )
				.filter( ( id ) => /^\d+$/.test( id ) && id !== greenImageId );

			expect( hoodieProductId ).toMatch( /^\d+$/ );
			expect( greenImageId ).toMatch( /^\d+$/ );

			expect( attachmentIds.length ).toBeGreaterThan( 10 );

			await wpCLI(
				`post meta update ${ hoodieProductId } _product_image_gallery "${ [
					...attachmentIds,
					greenImageId,
				].join( ',' ) }"`
			);

			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.insertBlock( {
				name: 'woocommerce/add-to-cart-with-options',
			} );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await page.goto( '/product/hoodie/' );

			const thumbnailsBlock = await pageObject.getThumbnailsBlock( {
				page: 'frontend',
			} );
			const scrollableContainer = thumbnailsBlock.locator(
				'.wc-block-product-gallery-thumbnails__scrollable'
			);
			const activeThumbnail = thumbnailsBlock.locator(
				'.wc-block-product-gallery-thumbnails__thumbnail:not([hidden])',
				{
					has: page.locator(
						'.wc-block-product-gallery-thumbnails__thumbnail__image--is-active'
					),
				}
			);

			await expect( thumbnailsBlock ).toHaveClass(
				/wc-block-product-gallery-thumbnails--overflow-bottom/
			);

			const addToCartBlock = page.locator(
				'.wp-block-add-to-cart-with-options'
			);
			await addToCartBlock
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } )
				.click();
			await addToCartBlock
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Green', exact: true } )
				.click();

			await expect( async () => {
				expect( await pageObject.getActiveThumbnailImageId() ).toBe(
					greenImageId
				);
				expect( await pageObject.getViewerImageId() ).toBe(
					greenImageId
				);
			} ).toPass( { timeout: 3_000 } );

			// The selected thumbnail now sits in the first slot; the strip must
			// be scrolled so that slot is visible, not to where the thumbnail
			// was before the gallery re-ordered it.
			await expect( async () => {
				const containerBox = await scrollableContainer.boundingBox();
				const thumbnailBox = await activeThumbnail.boundingBox();

				expect( containerBox ).not.toBeNull();
				expect( thumbnailBox ).not.toBeNull();
				expect( thumbnailBox!.y ).toBeGreaterThanOrEqual(
					containerBox!.y - 1
				);
				expect(
					thumbnailBox!.y + thumbnailBox!.height
				).toBeLessThanOrEqual(
					containerBox!.y + containerBox!.height + 1
				);
			} ).toPass( { timeout: 3_000 } );
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

			expect( initialViewerImageId ).not.toBeNull();
			expect( secondImageThumbnailId ).not.toBeNull();
			expect( initialViewerImageId ).not.toBe( secondImageThumbnailId );
			expect( await pageObject.getActiveThumbnailImageId() ).toBe(
				initialViewerImageId
			);

			await pageObject.clickNextButton();

			await expect( async () => {
				expect( await pageObject.getViewerImageId() ).toBe(
					secondImageThumbnailId
				);
				expect( await pageObject.getActiveThumbnailImageId() ).toBe(
					secondImageThumbnailId
				);
			} ).toPass( { timeout: 1_000 } );

			await pageObject.clickPreviousButton();

			await expect( async () => {
				expect( await pageObject.getViewerImageId() ).toBe(
					initialViewerImageId
				);
				expect( await pageObject.getActiveThumbnailImageId() ).toBe(
					initialViewerImageId
				);
			} ).toPass( { timeout: 1_000 } );
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

			expect( initialViewerImageId ).not.toBeNull();
			expect( secondImageThumbnailId ).not.toBeNull();
			expect( initialViewerImageId ).not.toBe( secondImageThumbnailId );

			await pageObject.clickNextButton();

			await expect( async () => {
				expect( await pageObject.getViewerImageId() ).toBe(
					secondImageThumbnailId
				);
				expect( await pageObject.getActiveThumbnailImageId() ).toBe(
					secondImageThumbnailId
				);
			} ).toPass( { timeout: 1_000 } );

			const viewerBlock = await pageObject.getViewerBlock( {
				page: 'frontend',
			} );
			await viewerBlock.click();

			const dialogImage = page
				.getByRole( 'dialog' )
				.locator( `img[data-image-id='${ secondImageThumbnailId }']` );

			// The image should be in the viewport but it simply doesn't fit fully.
			await expect( dialogImage ).toBeInViewport( { ratio: 0.7 } );

			await page.getByRole( 'button', { name: 'Close dialog' } ).click();

			await expect( async () => {
				expect( await pageObject.getViewerImageId() ).toBe(
					secondImageThumbnailId
				);
				expect( await pageObject.getActiveThumbnailImageId() ).toBe(
					secondImageThumbnailId
				);
			} ).toPass( { timeout: 1_000 } );
		} );
	} );

	test.describe( 'open pop-up when clicked option', () => {
		test( 'enables pop-up by default and opens it on the frontend', async ( {
			pageObject,
			page,
			editor,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.openDocumentSettingsSidebar();
			await expect(
				pageObject.getFullScreenOnClickSetting()
			).toBeChecked();
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
		test( 'is available only in supported Single Product contexts', async ( {
			admin,
			page,
			editor,
		} ) => {
			await editor.openGlobalBlockInserter();
			await page.getByRole( 'tab', { name: 'Blocks' } ).click();
			let productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeVisible();

			await admin.createNewPost();
			await editor.openGlobalBlockInserter();
			productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeHidden();

			await editor.insertBlockUsingGlobalInserter( 'Product' );
			await editor.canvas.getByText( 'Album' ).click();
			await editor.canvas.getByText( 'Done' ).click();
			// Block should be in Single Product by default.
			await expect(
				await editor.getBlockByName( blockData.name )
			).toHaveCount( 1 );
			const singleProductBlock = await editor.getBlockByName(
				'woocommerce/single-product'
			);
			const singleProductClientId =
				( await singleProductBlock.getAttribute( 'data-block' ) ) ?? '';
			await editor.insertBlock(
				{ name: blockData.name },
				{ clientId: singleProductClientId }
			);

			// Block should be visible in inserter and hence can be inserted in Single Product block.
			await expect(
				await editor.getBlockByName( blockData.name )
			).toHaveCount( 2 );
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
