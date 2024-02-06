/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import {
	TestingPost,
	createPostFromTemplate,
} from '../../utils/create-dynamic-content';

const TEMPLATE_PATH = path.join( __dirname, './stock-status.handlebars' );

const test = base.extend< {
	dropdownBlockPostPage: TestingPost;
	defaultBlockPostPage: TestingPost;
} >( {
	defaultBlockPostPage: async ( { requestUtils }, use ) => {
		const testingPost = await createPostFromTemplate(
			requestUtils,
			{ title: 'Product Filter Stock Status Block' },
			TEMPLATE_PATH,
			{}
		);

		await use( testingPost );
		await testingPost.deletePost();
	},

	dropdownBlockPostPage: async ( { requestUtils }, use ) => {
		const testingPost = await createPostFromTemplate(
			requestUtils,
			{ title: 'Product Filter Stock Status Block' },
			TEMPLATE_PATH,
			{
				attributes: {
					displayStyle: 'dropdown',
				},
			}
		);

		await use( testingPost );
		await testingPost.deletePost();
	},
} );

test.describe( 'Product Filter: Stock Status Block', async () => {
	test( 'By default it renders a checkbox list with the available stock statuses', async ( {
		page,
		defaultBlockPostPage,
	} ) => {
		await page.goto( defaultBlockPostPage.post.link );

		const stockStatuses = page.locator(
			'.wc-block-components-checkbox__label'
		);

		await expect( stockStatuses ).toHaveCount( 2 );
		await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
		await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
	} );

	test( 'Selecting a stock status filters the list of products', async ( {
		page,
		defaultBlockPostPage,
	} ) => {
		await page.goto( defaultBlockPostPage.post.link );

		const outOfStockCheckbox = page.getByLabel( 'Out of stock' );
		await outOfStockCheckbox.click();

		// wait for navigation
		await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

		const products = page.locator( '.wc-block-product' );

		await expect( products ).toHaveCount( 1 );
	} );

	test( 'When displayStyle is dropdown, a dropdown is displayed with the available stock statuses', async ( {
		page,
		dropdownBlockPostPage,
	} ) => {
		await page.goto( dropdownBlockPostPage.post.link );

		const dropdownLocator = page.locator( '.wc-interactivity-dropdown' );

		await expect( dropdownLocator ).toBeVisible();
		await dropdownLocator.click();

		await expect( page.getByText( 'In stock' ) ).toBeVisible();
		await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
	} );
} );
