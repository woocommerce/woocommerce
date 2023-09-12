/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { Locator, Page } from '@playwright/test';
import { FrontendUtils } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from './product-gallery.page';

const blockData = {
	name: 'woocommerce/product-gallery',
	selectors: {
		frontend: {},
		editor: {},
	},
	slug: 'single-product',
	productPage: '/product/logo-collection/',
};

const test = base.extend< { pageObject: ProductGalleryPage } >( {
	pageObject: async ( { page, editor }, use ) => {
		const pageObject = new ProductGalleryPage( {
			page,
			editor,
		} );
		await use( pageObject );
	},
} );

export const getVisibleLargeImageId = async (
	frontendUtils: FrontendUtils
) => {
	const mainImageBlock = await frontendUtils.getBlockByName(
		'woocommerce/product-gallery-large-image'
	);

	const mainImage = mainImageBlock.locator( 'img:not([hidden])' ) as Locator;

	const mainImageContext = ( await mainImage.getAttribute(
		'data-wc-context'
	) ) as string;

	const mainImageParsedContext = JSON.parse( mainImageContext );

	return mainImageParsedContext.woocommerce.imageId;
};

export const waitForJavascriptFrontendFileIsLoaded = async ( page: Page ) => {
	await page.waitForResponse(
		( response ) =>
			response.url().includes( 'product-gallery-frontend' ) &&
			response.status() === 200
	);
};

export const getThumbnailImageIdByNth = async (
	nth: number,
	frontendUtils: FrontendUtils
) => {
	const thumbnailsBlock = await frontendUtils.getBlockByName(
		'woocommerce/product-gallery-thumbnails'
	);

	const image = thumbnailsBlock.locator( 'img' ).nth( nth );

	const imageContext = ( await image.getAttribute(
		'data-wc-context'
	) ) as string;

	const imageId = JSON.parse( imageContext ).woocommerce.imageId;

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

	test( 'should have as first thumbnail, the same image that it is visible in the Large Image block', async ( {
		page,
		editorUtils,
		frontendUtils,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		await editorUtils.saveTemplate();

		await page.goto( blockData.productPage, {
			waitUntil: 'commit',
		} );

		const visibleLargeImageId = await getVisibleLargeImageId(
			frontendUtils
		);

		const firstImageThumbnailId = await getThumbnailImageIdByNth(
			0,
			frontendUtils
		);

		expect( visibleLargeImageId ).toBe( firstImageThumbnailId );
	} );

	// @todo: Fix this test. It's failing because the thumbnail images aren't generated correctly when the products are imported via .xml: https://github.com/woocommerce/woocommerce/issues/31646
	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'should change the image when the user click on a thumbnail image', async ( {
		page,
		editorUtils,
		frontendUtils,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		await editorUtils.saveTemplate();

		await Promise.all( [
			page.goto( blockData.productPage, {
				waitUntil: 'load',
			} ),
			waitForJavascriptFrontendFileIsLoaded( page ),
		] );

		const visibleLargeImageId = await getVisibleLargeImageId(
			frontendUtils
		);

		const secondImageThumbnailId = await getThumbnailImageIdByNth(
			1,
			frontendUtils
		);

		expect( visibleLargeImageId ).not.toBe( secondImageThumbnailId );

		await (
			await frontendUtils.getBlockByName(
				'woocommerce/product-gallery-thumbnails'
			)
		 )
			.locator( 'img' )
			.nth( 1 )
			.click();

		const newVisibleLargeImageId = await getVisibleLargeImageId(
			frontendUtils
		);

		expect( newVisibleLargeImageId ).toBe( secondImageThumbnailId );
	} );
} );
