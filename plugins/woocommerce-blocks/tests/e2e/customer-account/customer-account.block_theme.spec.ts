/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'Customer Account',
	slug: 'woocommerce/customer-account',
	class: '.wc-block-catalog-sorting',
};

test.describe( `${ blockData.slug } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.openDocumentSettingsSidebar();
		await editor.insertBlock( { name: blockData.name } );
	} );
	test( 'should be visible on the frontend', async ( {
		editorUtils,
		frontendUtils,
	} ) => {
		await editorUtils.publishAndVisitPost();
		const blockLocator = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect( blockLocator ).toBeVisible();
	} );

	test( 'icon options can be set to Text-only', async ( {
		editorUtils,
		page,
		editor,
	} ) => {
		const blockLocator = await editorUtils.getBlockByName( blockData.slug );
		await editor.selectBlocks( blockLocator );
		await page.getByRole( 'option', { name: 'Text-only' } ).click();
		await expect( blockLocator.getByText( 'My Account' ) ).toBeVisible();
	} );

	test( 'icon options can be set to Icon-only', async ( {
		editorUtils,
		page,
		editor,
	} ) => {
		const blockLocator = await editorUtils.getBlockByName( blockData.slug );
		await editor.selectBlocks( blockLocator );
		await page.getByRole( 'option', { name: 'Icon-only' } ).click();
		await expect( blockLocator.locator( 'svg' ) ).toBeVisible();
	} );

	test( 'icon options can be set to Icon and text', async ( {
		editorUtils,
		page,
		editor,
	} ) => {
		const blockLocator = await editorUtils.getBlockByName( blockData.slug );
		await editor.selectBlocks( blockLocator );
		await page.getByRole( 'option', { name: 'Icon and text' } ).click();
		await expect( blockLocator.locator( 'svg' ) ).toBeVisible();
		await expect( blockLocator.getByText( 'My Account' ) ).toBeVisible();
	} );
} );
