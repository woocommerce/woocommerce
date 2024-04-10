/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import { ExtendedTemplate } from '../../types/e2e-test-utils-playwright';

const TEMPLATE_PATH = path.join( __dirname, './attribute-filter.handlebars' );

const COLOR_ATTRIBUTE_VALUES = [ 'Blue', 'Gray', 'Green', 'Red', 'Yellow' ];

const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Gray (2)',
	'Green (3)',
	'Red (4)',
	'Yellow (1)',
];

const test = base.extend< {
	templateWithShowCounts: ExtendedTemplate;
	defaultBlockTemplate: ExtendedTemplate;
	dropdownBlockTemplate: ExtendedTemplate;
} >( {
	defaultBlockTemplate: async ( { requestUtils, templateApiUtils }, use ) => {
		const testingTemplate = await requestUtils.updateProductCatalogContent(
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
				},
			}
		);
		await use( testingTemplate );
		await templateApiUtils.revertTemplate( testingTemplate.id );
	},

	templateWithShowCounts: async (
		{ requestUtils, templateApiUtils },
		use
	) => {
		const testingTemplate = await requestUtils.updateProductCatalogContent(
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
					showCounts: true,
				},
			}
		);
		await use( testingTemplate );
		await templateApiUtils.revertTemplate( testingTemplate.id );
	},

	dropdownBlockTemplate: async (
		{ requestUtils, templateApiUtils },
		use
	) => {
		const testingTemplate = await requestUtils.updateProductCatalogContent(
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
					displayStyle: 'dropdown',
				},
			}
		);
		await use( testingTemplate );
		await templateApiUtils.revertTemplate( testingTemplate.id );
	},
} );

test.describe( 'Product Filter: Attribute Block', async () => {
	test.describe( 'With default display style', () => {
		test.describe( 'With show counts enabled', () => {
			test( 'Renders checkboxes with associated product counts', async ( {
				page,
				templateWithShowCounts,
			} ) => {
				await page.goto( templateWithShowCounts.link );

				const attributes = page.locator(
					'.wc-block-components-checkbox__label'
				);

				await expect( attributes ).toHaveCount( 5 );

				for (
					let i = 0;
					i < COLOR_ATTRIBUTES_WITH_COUNTS.length;
					i++
				) {
					await expect( attributes.nth( i ) ).toHaveText(
						COLOR_ATTRIBUTES_WITH_COUNTS[ i ]
					);
				}
			} );
		} );

		test( 'renders a checkbox list with the available attribute filters', async ( {
			page,
			defaultBlockTemplate,
		} ) => {
			await page.goto( defaultBlockTemplate.link );

			const attributes = page.locator(
				'.wc-block-components-checkbox__label'
			);

			await expect( attributes ).toHaveCount( 5 );

			for ( let i = 0; i < COLOR_ATTRIBUTE_VALUES.length; i++ ) {
				await expect( attributes.nth( i ) ).toHaveText(
					COLOR_ATTRIBUTE_VALUES[ i ]
				);
			}
		} );

		test( 'filters the list of products by selecting an attribute', async ( {
			page,
			defaultBlockTemplate,
		} ) => {
			await page.goto( defaultBlockTemplate.link );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 2 );
		} );
	} );

	test.describe( "With display style 'dropdown'", () => {
		test( 'renders a dropdown list with the available attribute filters', async ( {
			page,
			dropdownBlockTemplate,
		} ) => {
			await page.goto( dropdownBlockTemplate.link );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await expect( dropdownLocator ).toBeVisible();
			await dropdownLocator.click();

			for ( let i = 0; i < COLOR_ATTRIBUTE_VALUES.length; i++ ) {
				await expect(
					dropdownLocator.getByText( COLOR_ATTRIBUTE_VALUES[ i ] )
				).toBeVisible();
			}
		} );

		test( 'Clicking a dropdown option should filter the displayed products', async ( {
			page,
			dropdownBlockTemplate,
		} ) => {
			await page.goto( dropdownBlockTemplate.link );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await expect( dropdownLocator ).toBeVisible();
			await dropdownLocator.click();

			const yellowOption = page.getByText( 'Yellow' );
			await yellowOption.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=yellow.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 1 );
		} );
	} );
} );
