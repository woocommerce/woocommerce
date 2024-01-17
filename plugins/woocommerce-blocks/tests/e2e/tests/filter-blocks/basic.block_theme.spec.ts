/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const filterBlocks = [
	{
		name: 'woocommerce/product-filters-price',
		title: 'Product Filters: Price',
		heading: 'Filter by Price',
	},
	{
		name: 'woocommerce/product-filters-stock-status',
		title: 'Product Filters: Stock Status',
		heading: 'Filter by Stock Status',
	},
	{
		name: 'woocommerce/product-filters-rating',
		title: 'Product Filters: Rating',
		heading: 'Filter by Rating',
	},
	{
		name: 'woocommerce/product-filters-attribute',
		title: 'Product Filters: Attribute',
		heading: 'Filter by Attribute',
	},
	{
		name: 'woocommerce/product-filters-active',
		title: 'Product Filters: Active Filters',
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
					.getByLabel( `Block: Product Filters` )
					.getByLabel( 'Block: Heading' )
					.and( editor.canvas.getByText( block.heading ) )
			).toBeVisible();
		}
	} );
} );
