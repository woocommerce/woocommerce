/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
const blockData = {
	name: 'woocommerce/product-gallery-large-image',
	selectors: {
		frontend: {},
		editor: {},
	},
	slug: 'single-product',
	productPage: '/product/v-neck-t-shirt/',
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

	test( 'Renders Product Gallery Large Image block on the editor and frontend side', async ( {
		page,
		editor,
		editorUtils,
		frontendUtils,
	} ) => {
		await editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );

		const block = await editorUtils.getBlockByName( blockData.name );

		await expect( block ).toBeVisible();

		await Promise.all( [
			editor.saveSiteEditorEntities(),
			page.waitForResponse( ( response ) =>
				response.url().includes( 'wp-json/wp/v2/templates/' )
			),
		] );

		await page.goto( blockData.productPage, {
			waitUntil: 'commit',
		} );

		const blockFrontend = await frontendUtils.getBlockByName(
			blockData.name
		);

		await expect( blockFrontend ).toBeVisible();
	} );
} );
