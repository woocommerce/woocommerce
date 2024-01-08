/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const wrapperBlock = {
	name: 'woocommerce/collection-filters',
	title: 'Collection Filters',
};
const filterBlocks = [
	{
		name: 'woocommerce/collection-price-filter',
		title: 'Collection Price Filter',
		variation: 'Filter Products by Price',
		heading: 'Filter by Price',
	},
	{
		name: 'woocommerce/collection-stock-filter',
		title: 'Collection Stock Filter',
		variation: 'Filter Products by Stock',
		heading: 'Filter by Stock Status',
	},
	{
		name: 'woocommerce/collection-rating-filter',
		title: 'Collection Rating Filter',
		variation: 'Filter Products by Rating',
		heading: 'Filter by Rating',
	},
	{
		name: 'woocommerce/collection-attribute-filter',
		title: 'Collection Attribute Filter',
		variation: 'Filter Products by Attribute',
		heading: 'Filter Products by Attribute',
	},
	{
		name: 'woocommerce/collection-active-filters',
		title: 'Collection Active Filters',
		variation: 'Active Product Filters',
		heading: 'Active Filters',
	},
];

test.describe( 'Filter blocks registration and rendering', async () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'Wrapper block can be inserted through the inserter', async ( {
		page,
		editorUtils,
	} ) => {
		await editorUtils.openGlobalBlockInserter();
		await page.getByPlaceholder( 'Search' ).fill( wrapperBlock.title );

		await expect(
			page.getByRole( 'listbox', { name: 'Blocks' } )
		).toContainText( wrapperBlock.title );

		await page.getByRole( 'option', { name: wrapperBlock.title } ).click();

		expect(
			await editorUtils.getBlockByName( wrapperBlock.title )
		).toBeTruthy();
	} );

	test( 'Wrapper block contains all filter blocks by default', async ( {
		editorUtils,
	} ) => {
		await editorUtils.insertBlock( {
			name: wrapperBlock.name,
		} );
		for ( const block of filterBlocks ) {
			expect(
				await editorUtils.getBlockByName( block.title )
			).toBeTruthy();
		}
	} );

	test( 'Each filter block comes with a default title', async ( {
		editor,
		editorUtils,
	} ) => {
		await editorUtils.insertBlock( {
			name: wrapperBlock.name,
		} );
		for ( const block of filterBlocks ) {
			expect( editor.canvas.getByText( block.heading ) ).toBeTruthy();
		}
	} );

	test( 'Variations can be inserted through the inserter.', async ( {
		page,
		editorUtils,
	} ) => {
		for ( const block of filterBlocks ) {
			await editorUtils.openGlobalBlockInserter();
			await page.getByPlaceholder( 'Search' ).fill( block.variation );

			await expect(
				page.getByRole( 'listbox', { name: 'Blocks' } )
			).toContainText( block.variation );

			await page.getByRole( 'option', { name: block.variation } ).click();

			expect(
				await editorUtils.getBlockByName( block.title )
			).toBeTruthy();
		}
	} );
} );
