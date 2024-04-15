/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const blockData = {
	name: 'woocommerce/price-filter',
};

test.describe( `${ blockData.name } Block`, () => {
	test.beforeEach( async ( { admin, editor } ) => {
		await admin.createNewPost();
		await editor.insertBlock( {
			name: 'woocommerce/filter-wrapper',
			attributes: {
				filterType: 'price-filter',
				heading: 'Filter By Price',
			},
		} );
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
		const priceFilterControls = await editorUtils.getBlockByName(
			'woocommerce/price-filter'
		);
		await editor.selectBlocks( priceFilterControls );

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by minimum',
			} )
		).toBeVisible();

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by maximum',
			} )
		).toBeVisible();

		await page
			.getByLabel( 'Price Range Selector' )
			.getByText( 'Text' )
			.click();

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by minimum',
			} )
		).toBeHidden();

		await expect(
			priceFilterControls.getByRole( 'textbox', {
				name: 'Filter products by maximum',
			} )
		).toBeHidden();
	} );

	test( 'should allow toggling the visibility of the filter button', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const priceFilterControls = await editorUtils.getBlockByName(
			'woocommerce/price-filter'
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
