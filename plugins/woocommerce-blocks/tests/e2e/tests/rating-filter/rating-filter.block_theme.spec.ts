/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'Filter by Rating',
	slug: 'woocommerce/rating-filter',
};

test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { admin, editor, editorUtils } ) => {
		await admin.createNewPost();
		await editorUtils.insertBlockUsingGlobalInserter( blockData.name );
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
		const stockFilter = await editorUtils.getBlockByName( blockData.slug );
		await editor.selectBlocks( stockFilter );

		await expect(
			page.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
		).toBeVisible();

		await page.getByLabel( 'DropDown' ).click();

		await expect(
			stockFilter.getByRole( 'checkbox', {
				name: 'In Stock',
			} )
		).toBeHidden();

		await expect(
			page.getByRole( 'checkbox', { name: 'Rated 1 out of 5' } )
		).toBeHidden();

		await expect( page.getByRole( 'combobox' ) ).toBeVisible();
	} );

	test( 'should allow toggling the visibility of the filter button', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const priceFilterControls = await editorUtils.getBlockByName(
			blockData.slug
		);
		await editor.selectBlocks( priceFilterControls );

		await expect(
			priceFilterControls.getByRole( 'button', {
				name: 'Apply',
			} )
		).toBeHidden();

		await page.getByText( "Show 'Apply filters' button" ).click();

		await expect(
			priceFilterControls.getByRole( 'button', {
				name: 'Apply',
			} )
		).toBeVisible();
	} );
} );
