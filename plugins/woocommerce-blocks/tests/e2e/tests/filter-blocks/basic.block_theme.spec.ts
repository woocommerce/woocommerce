/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

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

	test( 'Variations cannot be inserted through the inserter.', async ( {
		page,
		editor,
	} ) => {
		for ( const block of filterBlocks ) {
			await editor.openGlobalBlockInserter();
			await page.getByPlaceholder( 'Search' ).fill( block.title );
			const filterBlock = page.getByRole( 'option', {
				name: block.title,
				exact: true,
			} );

			await expect( filterBlock ).toBeHidden();
		}
	} );
} );
