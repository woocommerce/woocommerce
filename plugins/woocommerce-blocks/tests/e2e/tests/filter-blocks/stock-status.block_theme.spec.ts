/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';

/**
 * Internal dependencies
 */
import { Template } from '../../types/e2e-test-utils-playwright';

type ExtendedTemplate = Template & { link: string };

const TEMPLATE_PATH = path.join( __dirname, './stock-status.handlebars' );

const productCatalogTemplateId = 'woocommerce/woocommerce//archive-product';
const productCatalogLink = '/shop';

const test = base.extend< {
	dropdownBlockTemplate: ExtendedTemplate;
	defaultBlockTemplate: ExtendedTemplate;
} >( {
	defaultBlockTemplate: async ( { requestUtils, templateApiUtils }, use ) => {
		const testingTemplate = await requestUtils.updateTemplatesContent(
			{ id: productCatalogTemplateId },
			TEMPLATE_PATH,
			{}
		);

		testingTemplate.link = productCatalogLink;

		await use( testingTemplate );
		await templateApiUtils.revertTemplate( testingTemplate.id );
	},

	dropdownBlockTemplate: async (
		{ requestUtils, templateApiUtils },
		use
	) => {
		const testingTemplate = await requestUtils.updateTemplatesContent(
			{ id: productCatalogTemplateId },
			TEMPLATE_PATH,
			{
				attributes: {
					displayStyle: 'dropdown',
				},
			}
		);

		testingTemplate.link = productCatalogLink;

		await use( testingTemplate );
		await templateApiUtils.revertTemplate( testingTemplate.id );
	},
} );

test.describe( 'Product Filter: Stock Status Block', async () => {
	test.describe( 'With default display style', () => {
		test( 'renders a checkbox list with the available stock statuses', async ( {
			page,
			defaultBlockTemplate,
		} ) => {
			await page.goto( defaultBlockTemplate.link );

			const stockStatuses = page.locator(
				'.wc-block-components-checkbox__label'
			);

			await expect( stockStatuses ).toHaveCount( 2 );
			await expect( stockStatuses.nth( 0 ) ).toHaveText( 'In stock' );
			await expect( stockStatuses.nth( 1 ) ).toHaveText( 'Out of stock' );
		} );

		test( 'filters the list of products by selecting a stock status', async ( {
			page,
			defaultBlockTemplate,
		} ) => {
			await page.goto( defaultBlockTemplate.link );

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
			dropdownBlockTemplate,
		} ) => {
			await page.goto( dropdownBlockTemplate.link );

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
