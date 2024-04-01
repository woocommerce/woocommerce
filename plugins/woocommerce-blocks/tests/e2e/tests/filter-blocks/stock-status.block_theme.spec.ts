/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';

const TEMPLATE_PATH = path.join( __dirname, './stock-status.handlebars' );

const test = base.extend< {
	dropdownBlockPost: Post;
	defaultBlockPost: Post;
} >( {
	defaultBlockPost: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Product Filter Stock Status Block' },
			TEMPLATE_PATH,
			{}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},

	dropdownBlockPost: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Product Filter Stock Status Block' },
			TEMPLATE_PATH,
			{
				attributes: {
					displayStyle: 'dropdown',
				},
			}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},
} );

test.describe( 'Product Filter: Stock Status Block', async () => {
	test.describe( 'With default display style', () => {
		test( 'renders a checkbox list with the available stock statuses', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const stockStatuses = page.locator(
				'.wc-block-components-checkbox__label'
			);

			await expect( stockStatuses ).toHaveCount( 2 );
			await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
			await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
		} );

		test( 'filters the list of products by selecting a stock status', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 1 );
		} );
	} );

	test.describe( 'With dropdown display style', () => {
		test( 'a dropdown is displayed with the available stock statuses', async ( {
			page,
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await expect( dropdownLocator ).toBeVisible();
			await dropdownLocator.click();

			await expect( page.getByText( 'In stock' ) ).toBeVisible();
			await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
		} );
	} );
} );
