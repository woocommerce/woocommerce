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

test.describe( blockData.name, () => {
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
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: true } );

		const block = await pageObject.getMainImageBlock( {
			page: 'editor',
		} );

		await expect( block ).toBeVisible();

		await editorUtils.saveTemplate();

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
			editorUtils,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await pageObject.toggleZoomWhileHoveringSetting( true );
			await editorUtils.saveTemplate();

			await page.goto( blockData.productPage );

			const blockFrontend = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			const imgElement = blockFrontend.locator( 'img' ).first();
			await expect( imgElement ).toHaveAttribute(
				'style',
				/transform: scale\(1\)/
			);

			await imgElement.hover();
			await expect( imgElement ).toHaveAttribute(
				'style',
				/transform: scale\(1\.3\)/
			);
		} );

		test( 'should not work on frontend when is disabled', async ( {
			pageObject,
			editorUtils,
			page,
		} ) => {
			await pageObject.addProductGalleryBlock( { cleanContent: true } );
			await pageObject.toggleZoomWhileHoveringSetting( false );
			const buttonElement = pageObject.getZoomWhileHoveringSetting();

			await expect( buttonElement ).not.toBeChecked();

			await editorUtils.saveTemplate();

			await page.goto( blockData.productPage );

			const blockFrontend = await pageObject.getMainImageBlock( {
				page: 'frontend',
			} );

			const imgElement = blockFrontend.locator( 'img' ).first();
			await expect( imgElement ).not.toHaveAttribute(
				'style',
				/transform: scale\(1\)/
			);

			await imgElement.hover();
			await expect( imgElement ).not.toHaveAttribute(
				'style',
				/transform: scale\(1\.3\)/
			);
		} );
	} );

	test( 'Renders correct image when selecting a product variation in the Add to Cart With Options block', async ( {
		page,
		editorUtils,
		pageObject,
	} ) => {
		await pageObject.addProductGalleryBlock( { cleanContent: false } );
		await pageObject.addAddToCartWithOptionsBlock();

		const mainImageBlock = await pageObject.getMainImageBlock( {
			page: 'editor',
		} );
		await expect( mainImageBlock ).toBeVisible();

		await editorUtils.saveTemplate();
		await page.goto( blockData.productPage );

		const largeImageBlockOnFrontend = await pageObject.getMainImageBlock( {
			page: 'frontend',
		} );
		const largeImageElement = largeImageBlockOnFrontend.locator(
			'.wc-block-woocommerce-product-gallery-large-image__image--active-image-slide'
		);

		await expect( largeImageElement ).toHaveAttribute(
			'src',
			/hoodie-2-2/
		);

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

		await expect( largeImageElement ).toHaveAttribute(
			'src',
			/hoodie-green-1-2/
		);
	} );
} );
