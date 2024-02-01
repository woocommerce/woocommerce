/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import {
	GeneratedPost,
	createPostFromTemplate,
} from '../../utils/create-dynamic-content';

const TEMPLATE_PATH = path.join( __dirname, 'stock-status.handlebars' );

test.describe( 'Product Filter: Stock Status Block', async () => {
	let defaultBlockPost: GeneratedPost;
	let dropdownBlockPost: GeneratedPost;

	test.beforeAll( async () => {
		defaultBlockPost = await createPostFromTemplate(
			'Product Filter Stock Status Block',
			TEMPLATE_PATH,
			{}
		);

		dropdownBlockPost = await createPostFromTemplate(
			'Product Filter Stock Status Block Dropdown',
			TEMPLATE_PATH,
			{
				attributes: {
					displayStyle: 'dropdown',
				},
			}
		);
	} );

	test( 'By default it renders a checkbox list with the available stock statuses', async ( {
		page,
	} ) => {
		await page.goto( defaultBlockPost.url );

		const stockStatuses = page.locator(
			'.wc-block-components-checkbox__label'
		);

		await expect( stockStatuses ).toHaveCount( 2 );
		await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
		await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
	} );

	test( 'Selecting a stock status filters the list of products', async ( {
		page,
	} ) => {
		await page.goto( defaultBlockPost.url );

		const outOfStockCheckbox = page.getByLabel( 'Out of stock' );
		outOfStockCheckbox.click();

		// wait for navigation
		await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

		const products = page.locator( '.wc-block-product' );
		const productCount = await products.count();

		expect( productCount ).toBe( 1 );
	} );

	test( 'When displayStyle is dropdown, a dropdown is displayed with the available stock statuses', async ( {
		page,
	} ) => {
		await page.goto( dropdownBlockPost.url );

		const dropdownLocator = page.locator( '.wc-interactivity-dropdown' );

		await expect( dropdownLocator ).toBeVisible();
		await dropdownLocator.click();

		await expect( page.getByText( 'In stock' ) ).toBeVisible();
		await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
	} );

	test.afterAll( async () => {
		await defaultBlockPost.deletePost();
		await dropdownBlockPost.deletePost();
	} );
} );
