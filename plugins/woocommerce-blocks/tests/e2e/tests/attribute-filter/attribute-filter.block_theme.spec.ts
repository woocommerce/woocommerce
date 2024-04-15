/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'Filter by Attribute',
	slug: 'woocommerce/attribute-filter',
};

test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { admin, editor, editorUtils } ) => {
		await admin.createNewPost();
		await editorUtils.insertBlockUsingGlobalInserter( blockData.name );
		const attributeFilter = await editorUtils.getBlockByName(
			'woocommerce/attribute-filter'
		);

		await attributeFilter.getByText( 'Size' ).click();
		await attributeFilter.getByText( 'Done' ).click();
		await editor.openDocumentSettingsSidebar();
	} );

	test( "should allow changing the block's title", async ( { page } ) => {
		const textSelector =
			'.wp-block-woocommerce-filter-wrapper .wp-block-heading';

		const title = 'New Title';

		await page.fill( textSelector, title );

		await expect( page.locator( textSelector ) ).toHaveText( title );

		expect( true ).toBe( true );
	} );

	test( 'should allow changing the display style', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const attributeFilter = await editorUtils.getBlockByName(
			blockData.slug
		);
		await editor.selectBlocks( attributeFilter );

		await expect(
			page.getByRole( 'checkbox', { name: 'Small' } )
		).toBeVisible();

		await page.getByLabel( 'DropDown' ).click();

		await expect(
			attributeFilter.getByRole( 'checkbox', {
				name: 'Small',
			} )
		).toBeHidden();

		await expect(
			page.getByRole( 'checkbox', { name: 'Small' } )
		).toBeHidden();

		await expect( page.getByRole( 'combobox' ) ).toBeVisible();
	} );

	test( 'should allow toggling the visibility of the filter button', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const attributeFilter = await editorUtils.getBlockByName(
			blockData.slug
		);
		await editor.selectBlocks( attributeFilter );

		await expect(
			attributeFilter.getByRole( 'button', {
				name: 'Apply',
			} )
		).toBeHidden();

		await page.getByText( "Show 'Apply filters' button" ).click();

		await expect(
			attributeFilter.getByRole( 'button', {
				name: 'Apply',
			} )
		).toBeVisible();
	} );
} );
