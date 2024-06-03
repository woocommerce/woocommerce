/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

const filterBlocks = [
	{
		name: 'woocommerce/product-filter-price',
		title: 'Product Filter: Price (Experimental)',
		heading: 'Filter by Price',
	},
	{
		name: 'woocommerce/product-filter-stock-status',
		title: 'Product Filter: Stock Status (Experimental)',
		heading: 'Filter by Stock Status',
	},
	{
		name: 'woocommerce/product-filter-rating',
		title: 'Product Filter: Rating (Experimental)',
		heading: 'Filter by Rating',
	},
	{
		name: 'woocommerce/product-filter-attribute',
		title: 'Product Filter: Attribute (Experimental)',
		heading: 'Filter by Attribute',
	},
	{
		name: 'woocommerce/product-filter-active',
		title: 'Product Filter: Active Filters (Experimental)',
		heading: 'Active Filters',
	},
];

test.describe( 'Filter blocks registration', () => {
	test.beforeEach( async ( { admin, requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-enable-experimental-features'
		);
		await admin.createNewPost();
	} );

	test( 'Variations can be inserted through the inserter.', async ( {
		page,
		editor,
	} ) => {
		for ( const block of filterBlocks ) {
			await editor.insertBlockUsingGlobalInserter( block.title );

			await expect(
				page.getByLabel( `Block: ${ block.title }` )
			).toBeVisible();
		}
	} );

	test( 'Each filter block comes with a default title', async ( {
		editor,
		page,
	} ) => {
		for ( const block of filterBlocks ) {
			await editor.insertBlockUsingGlobalInserter( block.title );

			await expect(
				page
					.getByLabel( `Block: Product Filter` )
					.getByLabel( 'Block: Heading' )
					.and( page.getByText( block.heading ) )
			).toBeVisible();
		}
	} );
} );
