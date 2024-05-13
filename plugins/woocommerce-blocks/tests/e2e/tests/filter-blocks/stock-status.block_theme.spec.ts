/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import { PRODUCT_CATALOG_LINK, PRODUCT_CATALOG_TEMPLATE_ID } from './constants';

const TEMPLATE_PATH = path.join( __dirname, './stock-status.handlebars' );

test.describe( 'Product Filter: Stock Status Block', () => {
	test.describe( 'With default display style', () => {
		let testingTemplateId = '';

		test.beforeAll( async ( { requestUtils } ) => {
			const testingTemplate = await requestUtils.updateTemplateContents(
				PRODUCT_CATALOG_TEMPLATE_ID,
				TEMPLATE_PATH,
				{}
			);

			testingTemplateId = testingTemplate.id;
		} );

		test.afterAll( async ( { templateApiUtils } ) => {
			await templateApiUtils.revertTemplate( testingTemplateId );
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
	} );

	test.describe( 'With dropdown display style', () => {
		let testingTemplateId = '';

		test.beforeAll( async ( { requestUtils } ) => {
			const testingTemplate = await requestUtils.updateTemplateContents(
				PRODUCT_CATALOG_TEMPLATE_ID,
				TEMPLATE_PATH,
				{
					attributes: {
						displayStyle: 'dropdown',
					},
				}
			);

			testingTemplateId = testingTemplate.id;
		} );

		test.afterAll( async ( { templateApiUtils } ) => {
			await templateApiUtils.revertTemplate( testingTemplateId );
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
	} );
} );
