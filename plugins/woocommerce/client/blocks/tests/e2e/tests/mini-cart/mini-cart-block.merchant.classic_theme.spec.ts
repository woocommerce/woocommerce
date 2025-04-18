/**
 * External dependencies
 */
import {
	expect,
	test,
	CLASSIC_THEME_SLUG,
	BlockData,
} from '@woocommerce/e2e-utils';

const blockData: BlockData = {
	name: 'Mini-Cart',
	slug: 'woocommerce/mini-cart',
	mainClass: '.wc-block-minicart',
	selectors: {
		frontend: {},
		editor: {},
	},
};

test.describe( 'Merchant → Mini Cart', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );

	test.describe( 'in widget editor', () => {
		test( 'can be inserted only once', async ( { admin, editor } ) => {
			await admin.visitWidgetEditor();
			await editor.openGlobalBlockInserter();

			await editor.page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( blockData.slug );

			const miniCartButton = editor.page.getByRole( 'option', {
				name: blockData.name,
				exact: true,
			} );

			await expect( miniCartButton ).toBeVisible();

			await miniCartButton.click();

			await expect(
				await editor.getBlockByName( blockData.slug )
			).toBeVisible();

			await editor.openGlobalBlockInserter();

			await editor.page
				.getByRole( 'searchbox', { name: 'Search' } )
				.fill( blockData.slug );

			await expect( miniCartButton ).toBeDisabled();
		} );
	} );
} );
