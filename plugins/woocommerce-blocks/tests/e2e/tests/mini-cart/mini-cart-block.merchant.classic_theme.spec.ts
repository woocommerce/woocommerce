/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { blockData } from './utils';

test.describe( 'Merchant → Mini Cart', () => {
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
