/**
 * External dependencies
 */
import { CLASSIC_THEME_SLUG } from '@woocommerce/e2e-utils';
import { expect, test } from '@woocommerce/e2e-playwright-utils';
import { BlockData } from '@woocommerce/e2e-types';

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
		test( 'can be inserted in a widget area', async ( { editorUtils } ) => {
			await editorUtils.openWidgetEditor();
			await editorUtils.openGlobalBlockInserter();

			await editorUtils.page
				.getByLabel( 'Search for blocks and patterns' )
				.fill( blockData.slug );

			const miniCartButton = editorUtils.page.getByRole( 'option', {
				name: blockData.name,
				exact: true,
			} );

			await expect( miniCartButton ).toBeVisible();

			await miniCartButton.click();

			await expect(
				await editorUtils.getBlockByName( blockData.slug )
			).toBeVisible();
		} );
		test( 'can only be inserted once', async ( { editorUtils } ) => {
			await editorUtils.openWidgetEditor();
			await editorUtils.openGlobalBlockInserter();

			await editorUtils.page
				.getByLabel( 'Search for blocks and patterns' )
				.fill( blockData.slug );

			const miniCartButton = editorUtils.page.getByRole( 'option', {
				name: blockData.name,
				exact: true,
			} );

			await expect( miniCartButton ).toBeHidden();
		} );
	} );
} );
