/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

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
	productPage: '/product/v-neck-t-shirt/',
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

test.describe( `${ blockData.name }`, () => {
	test.beforeEach( async ( { requestUtils, admin, editorUtils, editor } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editor.openDocumentSettingsSidebar();
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await requestUtils.deleteAllTemplates( 'wp_template_part' );
	} );

	test( 'Renders Product Gallery Large Image block on the editor and frontend side', async ( {
		page,
		editorUtils,
		frontendUtils,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		const block = await editorUtils.getBlockByName( blockData.name );

		await expect( block ).toBeVisible();

		await editorUtils.saveTemplate();

		await page.goto( blockData.productPage, {
			waitUntil: 'commit',
		} );

		const blockFrontend = await frontendUtils.getBlockByName(
			blockData.name
		);

		await expect( blockFrontend ).toBeVisible();
	} );

	test.describe( 'Zoom while hovering setting', () => {
		test( 'should be enabled by default', async ( { pageObject } ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			const zoomWhileHoveringSetting =
				await pageObject.getZoomWhileHoveringSetting();

			await expect( zoomWhileHoveringSetting ).toBeChecked();
		} );
		test( 'should work on frontend when is enabled', async ( {
			pageObject,
			editorUtils,
			frontendUtils,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await pageObject.toggleZoomWhileHoveringSetting( true );
			await editorUtils.saveTemplate();

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const blockFrontend = await frontendUtils.getBlockByName(
				blockData.name
			);

			// img[style] is the selector because the style attribute is Interactivity API.
			const imgElement = blockFrontend.locator( 'img[style]' );
			const style = await imgElement.evaluate( ( el ) => el.style );

			await expect( style.transform ).toBe( 'scale(1)' );

			await imgElement.hover();

			const styleOnHover = await imgElement.evaluate(
				( el ) => el.style
			);

			await expect( styleOnHover.transform ).toBe( 'scale(1.3)' );
		} );

		test( 'should not work on frontend when is disabled', async ( {
			pageObject,
			editorUtils,
			frontendUtils,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await pageObject.toggleZoomWhileHoveringSetting( false );
			const buttonElement =
				await pageObject.getZoomWhileHoveringSetting();

			await expect( buttonElement ).not.toBeChecked();

			await editorUtils.saveTemplate();

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const blockFrontend = await frontendUtils.getBlockByName(
				blockData.name
			);

			// img[style] is the selector because the style attribute is added by Interactivity API. In this case, the style attribute should not be added.
			const imgElement = blockFrontend.locator( 'img[style]' );
			await expect( imgElement ).toBeHidden();
			await expect( blockFrontend.locator( 'img' ) ).toBeVisible();
		} );
	} );
} );
