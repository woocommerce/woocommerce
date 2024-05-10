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

test.describe( 'Product Filter: Stock Status Block', () => {
	test.describe( 'With default display style', () => {
		test( 'clear button is not shown on initial page load', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

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

		test( 'clear button appears after a filter is applied', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?filter_stock_status=outofstock`
			);

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto(
				`${ defaultBlockPost.link }?filter_stock_status=outofstock`
			);

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

			const outOfStockCheckbox = page.getByText( 'Out of stock' );

			await expect( outOfStockCheckbox ).not.toBeChecked();
		} );
	} );

	test.describe( 'With dropdown display style', () => {
		test( 'clear button is not shown on initial page load', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

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

		test( 'clear button appears after a filter is applied', async ( {
			page,
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await dropdownLocator.click();

			await page.getByText( 'In stock' ).click();

			// wait for navigation
			await page.waitForURL( /.*filter_stock_status=instock.*/ );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
			dropdownBlockPost,
		} ) => {
			await page.goto(
				`${ dropdownBlockPost.link }?filter_stock_status=instock`
			);

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await dropdownLocator.click();

			const button = page.getByRole( 'button', { name: 'Clear' } );

			const removeFilter = page.locator(
				'.wc-interactivity-dropdown__badge-remove'
			);

			await removeFilter.click();

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
			dropdownBlockPost,
		} ) => {
			await page.goto(
				`${ dropdownBlockPost.link }?filter_stock_status=instock`
			);

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

			const placeholder = await page
				.locator( '.wc-interactivity-dropdown__placeholder' )
				.textContent();

			expect( placeholder ).toEqual( 'Select stock statuses' );
		} );
	} );
} );
