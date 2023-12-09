/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */

import { ProductGalleryPage } from '../../product-gallery.page';

const blockData = {
	name: 'woocommerce/product-gallery-pager',
	selectors: {
		frontend: {
			pagerBlock:
				'div[data-block-name="woocommerce/product-gallery-pager"]',
			pagerListContainer: 'ul.wc-block-product-gallery-pager__pager',
			pagerListItem: '.wc-block-product-gallery-pager__pager-item',
		},
		editor: {
			settings: {
				pagerSettingsContainer: 'div[aria-label="Pager"]',
				displayModeOffOption: 'button[data-value=off]',
				displayModeDotsOption: 'button[data-value=dots]',
				displayModeDigitsOption: 'button[data-value=digits]',
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

	test( 'Renders Product Gallery Pager block on the editor and frontend side', async ( {
		page,
		editorUtils,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		const block = await pageObject.getMainImageBlock( {
			page: 'editor',
		} );

		await expect( block ).toBeVisible();

		await editorUtils.saveTemplate();

		await page.goto( blockData.productPage, {
			waitUntil: 'commit',
		} );

		const blockFrontend = await pageObject.getMainImageBlock( {
			page: 'frontend',
		} );

		await expect( blockFrontend ).toBeVisible();
	} );

	test.describe( `Block Settings`, () => {
		test( 'correctly hides the block when display mode is set to "Off"', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await (
				await pageObject.getPagerBlock( {
					page: 'editor',
				} )
			 ).click();
			await editor.openDocumentSettingsSidebar();

			await page
				.locator(
					blockData.selectors.editor.settings.pagerSettingsContainer
				)
				.locator(
					blockData.selectors.editor.settings.displayModeOffOption
				)
				.click();

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const pagerBlock = await page
				.locator( blockData.selectors.frontend.pagerBlock )
				.isVisible();

			expect( pagerBlock ).toBe( false );
		} );

		test( 'display pages as dot icons when display mode is set to "Dots"', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await (
				await pageObject.getPagerBlock( {
					page: 'editor',
				} )
			 ).click();
			await editor.openDocumentSettingsSidebar();

			await page
				.locator(
					blockData.selectors.editor.settings.pagerSettingsContainer
				)
				.locator(
					blockData.selectors.editor.settings.displayModeDotsOption
				)
				.click();

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage, {
				waitUntil: 'domcontentloaded',
			} );

			const pagerBlock = await page
				.locator( blockData.selectors.frontend.pagerBlock )
				.nth( 0 )
				.isVisible();

			expect( pagerBlock ).toBe( true );

			const dotIconsAmount = await page
				.locator( blockData.selectors.frontend.pagerListContainer )
				.nth( 0 )
				.locator( 'svg' )
				.count();

			expect( dotIconsAmount ).toEqual( 3 );
		} );

		test( 'display pages as numbers when display mode is set to "Digits"', async ( {
			page,
			editor,
			pageObject,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await (
				await pageObject.getPagerBlock( {
					page: 'editor',
				} )
			 ).click();
			await editor.openDocumentSettingsSidebar();

			await page
				.locator(
					blockData.selectors.editor.settings.pagerSettingsContainer
				)
				.locator(
					blockData.selectors.editor.settings.displayModeDigitsOption
				)
				.click();

			await editor.saveSiteEditorEntities();

			await page.goto( blockData.productPage, {
				waitUntil: 'domcontentloaded',
			} );

			const pagerBlock = await page
				.locator( blockData.selectors.frontend.pagerBlock )
				.nth( 0 )
				.isVisible();

			expect( pagerBlock ).toBe( true );

			const pages = await page
				.locator( blockData.selectors.frontend.pagerListContainer )
				.nth( 0 )
				.locator( blockData.selectors.frontend.pagerListItem )
				.all();

			expect( pages.length ).toEqual( 3 );
			await expect( pages[ 0 ] ).toHaveText( '1' );
			await expect( pages[ 1 ] ).toHaveText( '2' );
			await expect( pages[ 2 ] ).toHaveText( '3' );
		} );
	} );
} );
