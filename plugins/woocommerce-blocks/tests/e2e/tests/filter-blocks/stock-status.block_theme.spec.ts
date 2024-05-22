/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import { PRODUCT_CATALOG_LINK, PRODUCT_CATALOG_TEMPLATE_ID } from './constants';
import { cli } from '../../utils';

const TEMPLATE_PATH = path.join( __dirname, './stock-status.handlebars' );

test.describe( 'Product Filter: Stock Status Block', () => {
	test.describe( 'With default display style', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			await cli(
				'npm run wp-env run tests-cli -- wp option update woocommerce_feature_experimental_blocks_enabled yes'
			);
			await requestUtils.updateTemplateContents(
				PRODUCT_CATALOG_TEMPLATE_ID,
				TEMPLATE_PATH,
				{}
			);
		} );

		test( 'clear button is not shown on initial page load', async ( {
			page,
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'renders a checkbox list with the available stock statuses', async ( {
			page,
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

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
			await page.goto( PRODUCT_CATALOG_LINK );

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 1 );
		} );

		test( 'clear button appears after a filter is applied', async ( {
			page,
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_stock_status=outofstock.*/ );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
		} ) => {
			await page.goto(
				`${ PRODUCT_CATALOG_LINK }?filter_stock_status=outofstock`
			);

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
		} ) => {
			await page.goto(
				`${ PRODUCT_CATALOG_LINK }?filter_stock_status=outofstock`
			);

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			const outOfStockCheckbox = page.getByText( 'Out of stock' );

			await expect( outOfStockCheckbox ).toBeVisible();

			await expect( outOfStockCheckbox ).not.toBeChecked();
		} );
	} );

	test.describe( 'With dropdown display style', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			await requestUtils.updateTemplateContents(
				PRODUCT_CATALOG_TEMPLATE_ID,
				TEMPLATE_PATH,
				{
					attributes: {
						displayStyle: 'dropdown',
					},
				}
			);
		} );

		test( 'clear button is not shown on initial page load', async ( {
			page,
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'a dropdown is displayed with the available stock statuses', async ( {
			page,
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

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
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

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
		} ) => {
			await page.goto(
				`${ PRODUCT_CATALOG_LINK }?filter_stock_status=instock`
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
		} ) => {
			await page.goto(
				`${ PRODUCT_CATALOG_LINK }?filter_stock_status=instock`
			);

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			const placeholder = page.locator(
				'.wc-interactivity-dropdown__placeholder'
			);

			await expect( placeholder ).toBeVisible();

			const placeholderText = await placeholder.textContent();

			expect( placeholderText ).toEqual( 'Select stock statuses' );
		} );
	} );
} );
