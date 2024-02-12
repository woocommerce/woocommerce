/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
const TEMPLATE_PATH = path.join( __dirname, './stock-status.handlebars' );

test.describe( 'Product Filter: Stock Status Block', async () => {
	test.describe( 'with default settings', () => {
		test.use( {
			page: async ( { page, requestUtils }, use ) => {
				const { link } = await requestUtils.createPostFromTemplate(
					{ title: 'Product Filter Stock Status Block' },
					TEMPLATE_PATH,
					{}
				);

				await page.goto( link );
				await use( page );
			},
		} );

		test( 'renders a checkbox list with the available stock statuses', async ( {
			page,
		} ) => {
			const stockStatuses = page.locator(
				'.wc-block-components-checkbox__label'
			);

			await expect( stockStatuses ).toHaveCount( 2 );
			await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
			await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
		} );

		test( 'filters the list of products by selecting a stock status', async ( {
			page,
		} ) => {
			const outOfStockCheckbox = page.getByLabel( 'Out of stock' );
			await outOfStockCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 1 );
		} );
	} );

	test.describe( 'with displayStyle set to dropdown', () => {
		test.use( {
			page: async ( { page, requestUtils }, use ) => {
				const { link } = await requestUtils.createPostFromTemplate(
					{ title: 'Product Filter Stock Status Block' },
					TEMPLATE_PATH,
					{ displayStyle: 'dropdown' }
				);

				await page.goto( link );
				await use( page );
			},
		} );

		test( 'a dropdown is displayed with the available stock statuses', async ( {
			page,
		} ) => {
			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await expect( dropdownLocator ).toBeVisible();
			await dropdownLocator.click();

			await expect( page.getByText( 'In stock' ) ).toBeVisible();
			await expect( page.getByText( 'Out of stock' ) ).toBeVisible();
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplatePosts();
	} );
} );
