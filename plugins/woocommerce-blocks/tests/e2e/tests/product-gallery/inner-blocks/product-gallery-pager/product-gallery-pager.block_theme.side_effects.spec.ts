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
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.visitSiteEditor( {
			postId: `woocommerce/woocommerce//${ blockData.slug }`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.openDocumentSettingsSidebar();
	} );

	test( 'Renders Product Gallery Pager block on the editor and frontend side', async ( {
		page,
		editor,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		const block = await pageObject.getMainImageBlock( {
			page: 'editor',
		} );

		await expect( block ).toBeVisible();

		await editor.saveSiteEditorEntities();

		await page.goto( blockData.productPage );

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

			await page.goto( blockData.productPage );

			const pagerBlock = page.locator(
				blockData.selectors.frontend.pagerBlock
			);

			await expect( pagerBlock ).toBeHidden();
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

			await page.goto( blockData.productPage );

			const pagerBlock = page
				.locator( blockData.selectors.frontend.pagerBlock )
				.first();

			await expect( pagerBlock ).toBeVisible();

			const dotIconsAmount = page
				.locator( blockData.selectors.frontend.pagerListContainer )
				.first()
				.locator( 'svg' );

			await expect( dotIconsAmount ).toHaveCount( 3 );
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

			await page.goto( blockData.productPage );

			const pagerBlock = page
				.locator( blockData.selectors.frontend.pagerBlock )
				.first();

			await expect( pagerBlock ).toBeVisible();

			const pages = page
				.locator( blockData.selectors.frontend.pagerListContainer )
				.first()
				.locator( blockData.selectors.frontend.pagerListItem );

			await expect( pages ).toHaveCount( 3 );
			await expect( pages ).toHaveText( [ '1', '2', '3' ] );
		} );
	} );
} );
