/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { Locator } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from './product-gallery.page';

const blockData = {
	name: 'woocommerce/product-gallery',
	title: 'Product Gallery',
	selectors: {
		frontend: {},
		editor: {
			settings: {
				cropImagesOption:
					'.wc-block-product-gallery__crop-images .components-form-toggle__input',
			},
		},
	},
	slug: 'single-product',
	productPage: '/product/hoodie/',
};

const test = base.extend< { pageObject: ProductGalleryPage } >( {
	pageObject: async ( { page, editor, frontendUtils, editorUtils }, use ) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
			frontendUtils,
			editorUtils,
		} );
		await use( pageObject );
	},
} );

export const getVisibleLargeImageId = async (
	mainImageBlockLocator: Locator
) => {
	const mainImage = mainImageBlockLocator.locator(
		'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
	);

	const mainImageContext = ( await mainImage.getAttribute(
		'data-wc-context'
	) ) as string;

	const mainImageParsedContext = JSON.parse( mainImageContext );

	return mainImageParsedContext.imageId;
};

export const getIsDialogOpen = async (
	productGalleryBlock: Locator
): Promise< boolean > => {
	const productGalleryBlockContext = ( await productGalleryBlock.getAttribute(
		'data-wc-context'
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

	const imageContext = ( await image.getAttribute(
		'data-wc-context'
	) ) as string;

	const imageId = JSON.parse( imageContext ).imageId;

	return imageId;
};

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { requestUtils, admin, editorUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test.describe( 'with thumbnails', () => {
		test( 'should have as first thumbnail, the same image that it is visible in the Large Image block', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const visibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			const firstImageThumbnailId = await getThumbnailImageIdByNth(
				0,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( visibleLargeImageId ).toBe( firstImageThumbnailId );
		} );

		test( 'should change the image when the user click on a thumbnail image', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const visibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( visibleLargeImageId ).not.toBe( secondImageThumbnailId );

			await (
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			 )
				.locator( 'img' )
				.nth( 1 )
				.click();

			const newVisibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			expect( newVisibleLargeImageId ).toBe( secondImageThumbnailId );
		} );
	} );

	test.describe( 'with previous and next buttons', () => {
		test( 'should change the image when the user click on the previous or next button', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const initialVisibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( initialVisibleLargeImageId ).not.toBe(
				secondImageThumbnailId
			);

			const nextButton = page
				.locator(
					'.wc-block-product-gallery-large-image-next-previous--button'
				)
				.nth( 1 );
			await nextButton.click();

			const nextImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			expect( nextImageId ).toBe( secondImageThumbnailId );

			const previousButton = page
				.locator(
					'.wc-block-product-gallery-large-image-next-previous--button'
				)
				.first();
			await previousButton.click();

			const previousImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			expect( previousImageId ).toBe( initialVisibleLargeImageId );
		} );
	} );

	test.describe( 'with pager', () => {
		test( 'should change the image when the user click on a pager item', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const initialVisibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			const thirdImageThumbnailId = await getThumbnailImageIdByNth(
				2,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( initialVisibleLargeImageId ).not.toBe(
				secondImageThumbnailId
			);
			expect( initialVisibleLargeImageId ).not.toBe(
				thirdImageThumbnailId
			);

			const pagerBlock = pageObject.getPagerBlock( { page: 'frontend' } );
			const thirdPagerItem = ( await pagerBlock )
				.locator( '.wc-block-product-gallery-pager__pager-item' )
				.nth( 2 );
			await thirdPagerItem.click();

			let currentVisibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			expect( currentVisibleLargeImageId ).toBe( thirdImageThumbnailId );

			const firstPagerItem = ( await pagerBlock )
				.locator( '.wc-block-product-gallery-pager__pager-item' )
				.first();
			await firstPagerItem.click();

			currentVisibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			expect( currentVisibleLargeImageId ).toBe(
				initialVisibleLargeImageId
			);
		} );
	} );

	test.describe( 'within pop-up', () => {
		test( 'should display the same selected image when the pop-up is opened', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: false } );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const initialVisibleLargeImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( initialVisibleLargeImageId ).not.toBe(
				secondImageThumbnailId
			);

			const nextButton = page
				.locator(
					'.wc-block-product-gallery-large-image-next-previous--button'
				)
				.nth( 1 );
			await nextButton.click();

			const nextImageId = await getVisibleLargeImageId(
				await pageObject.getMainImageBlock( {
					page: 'frontend',
				} )
			);

			expect( nextImageId ).toBe( secondImageThumbnailId );

			const largeImageBlock = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );
			largeImageBlock.click();

			const productGalleryPopUpContent = page.locator(
				'.wc-block-product-gallery-dialog__body'
			);

			const popUpSelectedImageId = await getVisibleLargeImageId(
				productGalleryPopUpContent.locator(
					`[data-block-name="woocommerce/product-gallery-large-image"]`
				)
			);

			expect( popUpSelectedImageId ).toBe( nextImageId );
		} );

		test( 'should reset to the first thumbnail when the pop-up is closed', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const largeImageBlock = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );
			const initialVisibleLargeImageId = await getVisibleLargeImageId(
				largeImageBlock
			);

			const secondImageThumbnailId = await getThumbnailImageIdByNth(
				1,
				await pageObject.getThumbnailsBlock( {
					page: 'frontend',
				} )
			);

			expect( initialVisibleLargeImageId ).not.toBe(
				secondImageThumbnailId
			);

			const nextButton = page
				.locator(
					'.wc-block-product-gallery-large-image-next-previous--button'
				)
				.nth( 1 );
			await nextButton.click();

			const imageAfterClickingNextButton = await getVisibleLargeImageId(
				largeImageBlock
			);

			expect( imageAfterClickingNextButton ).toBe(
				secondImageThumbnailId
			);

			largeImageBlock.click();

			const productGalleryPopUpContent = page.locator(
				'.wc-block-product-gallery-dialog__body'
			);

			const popUpInitialSelectedImageId = await getVisibleLargeImageId(
				productGalleryPopUpContent.locator(
					`[data-block-name="woocommerce/product-gallery-large-image"]`
				)
			);

			const popUpNextButton = productGalleryPopUpContent
				.locator(
					'.wc-block-product-gallery-large-image-next-previous--button'
				)
				.nth( 1 );
			await popUpNextButton.click();

			const popUpNextImageId = await getVisibleLargeImageId(
				productGalleryPopUpContent.locator(
					`[data-block-name="woocommerce/product-gallery-large-image"]`
				)
			);

			expect( popUpInitialSelectedImageId ).not.toBe( popUpNextImageId );

			const productGalleryPopUpHeader = page.locator(
				'.wc-block-product-gallery-dialog__header'
			);
			const closePopUpButton = productGalleryPopUpHeader.locator(
				'.wc-block-product-gallery-dialog__close'
			);
			closePopUpButton.click();

			await page.waitForFunction( () => {
				const isPopUpOpen = document
					.querySelector( '[aria-label="Product gallery"]' )
					?.checkVisibility();

				return isPopUpOpen === false;
			} );

			const singleProductImageId = await getVisibleLargeImageId(
				largeImageBlock
			);

			expect( singleProductImageId ).toBe( initialVisibleLargeImageId );
		} );
	} );

	test.describe( 'open pop-up when clicked option', () => {
		test( 'should be enabled by default', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.openDocumentSettingsSidebar();
			const fullScreenOption =
				await pageObject.getFullScreenOnClickSetting();

			await expect( fullScreenOption ).toBeChecked();
		} );

		test( 'should open dialog on the frontend', async ( {
			pageObject,
			page,
			editor,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			const mainImageBlock = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			await expect( page.locator( 'dialog' ) ).toBeHidden();

			await mainImageBlock.click();

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
			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage );

			await expect( page.locator( 'dialog' ) ).toBeHidden();

			const mainImageBlock = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			await mainImageBlock.click();

			await expect( page.locator( 'dialog' ) ).toBeHidden();
		} );
	} );

	test.describe( 'block availability', () => {
		test( 'should be available on the Single Product Template', async ( {
			page,
			editorUtils,
		} ) => {
			await editorUtils.openGlobalBlockInserter();
			await page.getByRole( 'tab', { name: 'Blocks' } ).click();
			const productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeVisible();
		} );

		test( 'should be available on the Product Gallery template part', async ( {
			admin,
			editorUtils,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//product-gallery`,
				postType: 'wp_template_part',
			} );
			await editorUtils.enterEditMode();
			await editorUtils.openGlobalBlockInserter();
			await page.getByRole( 'tab', { name: 'Blocks' } ).click();
			const productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeVisible();
		} );

		test( 'should be hidden on the post editor', async ( {
			admin,
			page,
			editorUtils,
		} ) => {
			await admin.createNewPost();
			await editorUtils.openGlobalBlockInserter();
			const productGalleryBlockOption = page
				.getByRole( 'listbox', { name: 'WooCommerce' } )
				.getByRole( 'option', { name: blockData.title } );

			await expect( productGalleryBlockOption ).toBeHidden();
		} );
	} );

	test( 'should show (square) cropped main product images when crop option is enabled', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		await page
			.locator( blockData.selectors.editor.settings.cropImagesOption )
			.click();

		await editor.saveSiteEditorEntities();

		await expect(
			page.locator( blockData.selectors.editor.settings.cropImagesOption )
		).toBeChecked();

		await page.goto( blockData.productPage );

		const image = await page
			.locator(
				'img.wc-block-woocommerce-product-gallery-large-image__image'
			)
			.first()
			.boundingBox();

		const height = image?.height as number;
		const width = image?.width as number;

		// Allow 1 pixel of difference.
		expect(
			width === height + 1 || width === height - 1 || width === height
		).toBeTruthy();
	} );
} );
