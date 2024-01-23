/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const filterBlocks = [
	{
		name: 'woocommerce/product-filter-price',
		title: 'Product Filter: Price',
		heading: 'Filter by Price',
	},
	{
		name: 'woocommerce/product-filter-stock-status',
		title: 'Product Filter: Stock Status',
		heading: 'Filter by Stock Status',
	},
	{
		name: 'woocommerce/product-filter-rating',
		title: 'Product Filter: Rating',
		heading: 'Filter by Rating',
	},
	{
		name: 'woocommerce/product-filter-attribute',
		title: 'Product Filter: Attribute',
		heading: 'Filter by Attribute',
	},
	{
		name: 'woocommerce/product-filter-active',
		title: 'Product Filter: Active Filters',
		heading: 'Active Filters',
	},
];

test.describe( 'Filter blocks registration', async () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'Variations can be inserted through the inserter.', async ( {
		editor,
		editorUtils,
	} ) => {
		for ( const block of filterBlocks ) {
			await editorUtils.insertBlockUsingGlobalInserter( block.title );

			await expect(
				editor.canvas.getByLabel( `Block: ${ block.title }` )
			).toBeVisible();
		}
	} );

	test( 'Each filter block comes with a default title', async ( {
		editor,
		editorUtils,
	} ) => {
		for ( const block of filterBlocks ) {
			await editorUtils.insertBlockUsingGlobalInserter( block.title );

			await expect(
				editor.canvas
					.getByLabel( `Block: Product Filter` )
					.getByLabel( 'Block: Heading' )
					.and( editor.canvas.getByText( block.heading ) )
			).toBeVisible();
		}
	} );
} );
