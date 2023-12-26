/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import { EditorUtils, FrontendUtils } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { ProductGalleryPage } from '../product-gallery/product-gallery.page';

const blockData = {
	name: 'woocommerce/product-sale-badge',
	mainClass: '.wp-block-woocommerce-product-sale-badge',
	selectors: {
		frontend: {
			productSaleBadge: '.wc-block-components-product-sale-badge',
			productSaleBadgeContainer:
				'.wp-block-woocommerce-product-sale-badge',
		},
		editor: {
			productSaleBadge: '.wc-block-components-product-sale-badge',
			productSaleBadgeContainer:
				'.wp-block-woocommerce-product-sale-badge',
		},
	},
	// This margin is applied via Block Styles to the product sale badge. It's necessary to take it into account when calculating the position of the badge. https://github.com/woocommerce/woocommerce-blocks/blob/445b9431ccba460f9badd41d52ed991958524e33/assets/js/blocks/product-gallery/edit.tsx/#L44-L53
	margin: 4,
	slug: 'single-product',
	productPage: '/product/hoodie/',
	productPageNotOnSale: '/product/album/',
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

const getBoundingClientRect = async ( {
	frontendUtils,
	editorUtils,
	isFrontend,
}: {
	frontendUtils: FrontendUtils;
	editorUtils: EditorUtils;
	isFrontend: boolean;
} ) => {
	const page = isFrontend ? frontendUtils.page : editorUtils.editor.canvas;
	return {
		productSaleBadge: await page
			.locator(
				blockData.selectors[ isFrontend ? 'frontend' : 'editor' ]
					.productSaleBadge
			)
			.first()
			.evaluate( ( el ) => el.getBoundingClientRect() ),
		productSaleBadgeContainer: await page
			.locator(
				blockData.selectors[ isFrontend ? 'frontend' : 'editor' ]
					.productSaleBadgeContainer
			)
			.first()
			.evaluate( ( el ) => el.getBoundingClientRect() ),
	};
};
test.describe( `${ blockData.name }`, () => {
	test.describe( `On the Single Product Template`, () => {
		test.beforeEach(
			async ( { requestUtils, admin, editorUtils, editor } ) => {
				await requestUtils.deleteAllTemplates( 'wp_template' );
				await requestUtils.deleteAllTemplates( 'wp_template_part' );
				await admin.visitSiteEditor( {
					postId: `woocommerce/woocommerce//${ blockData.slug }`,
					postType: 'wp_template',
				} );
				await editorUtils.enterEditMode();
				await editor.setContent( '' );
			}
		);

		test.afterEach( async ( { requestUtils } ) => {
			await requestUtils.deleteAllTemplates( 'wp_template' );
			await requestUtils.deleteAllTemplates( 'wp_template_part' );
		} );

		test( 'should be rendered on the editor side', async ( {
			editorUtils,
			editor,
		} ) => {
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			const block = await editorUtils.getBlockByName( blockData.name );

			await expect( block ).toBeVisible();
		} );

		test( 'should be rendered on the frontend side', async ( {
			frontendUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				editor.page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const block = await frontendUtils.getBlockByName( blockData.name );

			await expect( block.first() ).toBeVisible();
		} );

		test( `should be not rendered when the product isn't on sale the frontend side`, async ( {
			frontendUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPageNotOnSale, {
				waitUntil: 'commit',
			} );

			const block = await frontendUtils.getBlockByName( blockData.name );

			await expect( block ).toBeHidden();
		} );

		test( 'should be aligned on the left', async ( {
			frontendUtils,
			editorUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			const block = await editorUtils.getBlockByName( blockData.name );

			await block.click();

			await editorUtils.setAlignOption( 'Align Left' );

			const editorBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
				isFrontend: false,
			} );

			await expect(
				editorBoundingClientRect.productSaleBadge.x - blockData.margin
			).toEqual( editorBoundingClientRect.productSaleBadgeContainer.x );

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const clientBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
				isFrontend: true,
			} );

			await expect(
				clientBoundingClientRect.productSaleBadge.x - blockData.margin
			).toEqual( clientBoundingClientRect.productSaleBadgeContainer.x );
		} );

		test( 'should be aligned on the center', async ( {
			frontendUtils,
			editorUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await pageObject.toggleFullScreenOnClickSetting( false );

			const block = await editorUtils.getBlockByName( blockData.name );

			await block.click();

			await editorUtils.setAlignOption( 'Align Center' );

			const editorBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
				isFrontend: false,
			} );

			await expect(
				editorBoundingClientRect.productSaleBadge.right
			).toBeLessThan(
				editorBoundingClientRect.productSaleBadgeContainer.right
			);

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const clientBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
				isFrontend: true,
			} );

			await expect(
				clientBoundingClientRect.productSaleBadge.right
			).toBeLessThan(
				clientBoundingClientRect.productSaleBadgeContainer.right
			);
		} );

		test( 'should be aligned on the right by default', async ( {
			frontendUtils,
			editorUtils,
			editor,
			page,
			pageObject,
		} ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );
			await pageObject.toggleFullScreenOnClickSetting( false );

			const editorBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
				isFrontend: false,
			} );

			await expect(
				editorBoundingClientRect.productSaleBadge.right +
					blockData.margin
			).toEqual(
				editorBoundingClientRect.productSaleBadgeContainer.right
			);

			await Promise.all( [
				editor.saveSiteEditorEntities(),
				page.waitForResponse( ( response ) =>
					response.url().includes( 'wp-json/wp/v2/templates/' )
				),
			] );

			await page.goto( blockData.productPage, {
				waitUntil: 'commit',
			} );

			const clientBoundingClientRect = await getBoundingClientRect( {
				frontendUtils,
				editorUtils,
				isFrontend: true,
			} );

			await expect(
				clientBoundingClientRect.productSaleBadge.right +
					blockData.margin
			).toEqual(
				clientBoundingClientRect.productSaleBadgeContainer.right
			);
		} );
	} );
} );
