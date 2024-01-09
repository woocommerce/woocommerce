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

test.describe( 'Filter blocks registration', async () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'Wrapper block can be inserted through the inserter', async ( {
		editor,
		editorUtils,
	} ) => {
		await editorUtils.insertBlockUsingGlobalInserter( wrapperBlock.title );

		await expect(
			editor.canvas.getByLabel( `Block: ${ wrapperBlock.title }` )
		).toBeVisible();
	} );

	test( 'Wrapper block contains all filter blocks by default', async ( {
		editor,
		editorUtils,
	} ) => {
		await editorUtils.insertBlockUsingGlobalInserter( wrapperBlock.title );
		for ( const block of filterBlocks ) {
			await expect(
				editor.canvas.getByLabel( `Block: ${ block.title }` )
			).toBeVisible();
		}
	} );

	test( 'Each filter block comes with a default title', async ( {
		editor,
		editorUtils,
	} ) => {
		await editorUtils.insertBlockUsingGlobalInserter( wrapperBlock.title );
		for ( const block of filterBlocks ) {
			await expect(
				editor.canvas.getByText( block.heading )
			).toBeVisible();
		}
	} );

	test( 'Variations can be inserted through the inserter.', async ( {
		editor,
		editorUtils,
	} ) => {
		for ( const block of filterBlocks ) {
			await editorUtils.insertBlockUsingGlobalInserter( block.variation );

			await expect(
				editor.canvas.getByLabel( `Block: ${ block.title }` )
			).toBeVisible();
		}
	} );
} );
