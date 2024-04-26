/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const filterBlocks = [
	{
		name: 'woocommerce/product-filter-price',
		title: 'Product Filter: Price (Beta)',
		heading: 'Filter by Price',
	},
	{
		name: 'woocommerce/product-filter-stock-status',
		title: 'Product Filter: Stock Status (Beta)',
		heading: 'Filter by Stock Status',
	},
	{
		name: 'woocommerce/product-filter-rating',
		title: 'Product Filter: Rating (Beta)',
		heading: 'Filter by Rating',
	},
	{
		name: 'woocommerce/product-filter-attribute',
		title: 'Product Filter: Attribute (Beta)',
		heading: 'Filter by Attribute',
	},
	{
		name: 'woocommerce/product-filter-active',
		title: 'Product Filter: Active Filters (Beta)',
		heading: 'Active Filters',
	},
];

test.describe( 'Filter blocks registration', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'Variations can be inserted through the inserter.', async ( {
		page,
		editorUtils,
	} ) => {
		for ( const block of filterBlocks ) {
			await editorUtils.insertBlockUsingGlobalInserter( block.title );

			await expect(
				page.getByLabel( `Block: ${ block.title }` )
			).toBeVisible();
		}
	} );

	test( 'Each filter block comes with a default title', async ( {
		editorUtils,
		page,
	} ) => {
		for ( const block of filterBlocks ) {
			await editorUtils.insertBlockUsingGlobalInserter( block.title );

			await expect(
				page
					.getByLabel( `Block: Product Filter` )
					.getByLabel( 'Block: Heading' )
					.and( page.getByText( block.heading ) )
			).toBeVisible();
		}
	} );
} );
