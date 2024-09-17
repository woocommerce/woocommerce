/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_stock-status'
		);
		await use( compiler );
	},
} );

test.describe.skip( 'Product Filter: Stock Status Block', () => {
	test.describe( 'With default display style', () => {
		test.beforeEach( async ( { requestUtils, templateCompiler } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-enable-experimental-features'
			);
			await templateCompiler.compile();
		} );

		test( 'clear button is not shown on initial page load', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'renders a checkbox list with the available stock statuses', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const stockStatuses = page.locator(
				'.wc-block-interactivity-components-checkbox-list__label'
			);

			await expect( stockStatuses ).toHaveCount( 2 );
			await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
			await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
		} );

		test( 'filters the list of products by selecting a stock status', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

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
			await page.goto( '/shop' );

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
			await page.goto( '/shop?filter_stock_status=outofstock' );

			const outOfStockCheckbox = page.getByText( 'Out of stock' );
			await outOfStockCheckbox.click();

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
		} ) => {
			await page.goto( '/shop?filter_stock_status=outofstock' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			const outOfStockCheckbox = page.getByText( 'Out of stock' );

			await expect( outOfStockCheckbox ).toBeVisible();

			await expect( outOfStockCheckbox ).not.toBeChecked();
		} );
	} );

	test.describe( 'With dropdown display style', () => {
		test.beforeEach( async ( { requestUtils, templateCompiler } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-enable-experimental-features'
			);
			await templateCompiler.compile( {
				attributes: {
					displayStyle: 'dropdown',
				},
			} );
		} );

		test( 'clear button is not shown on initial page load', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'a dropdown is displayed with the available stock statuses', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

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
			await page.goto( '/shop' );

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
			await page.goto( '/shop?filter_stock_status=instock' );

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
			await page.goto( '/shop?filter_stock_status=instock' );

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
