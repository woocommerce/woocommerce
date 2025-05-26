/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const COLOR_ATTRIBUTE_VALUES = [ 'Blue', 'Red', 'Green', 'Gray', 'Yellow' ];
const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Red (4)',
	'Green (3)',
	'Gray (2)',
	'Yellow (1)',
];

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filter-attribute - Frontend', () => {
	test.describe( 'With default display style', () => {
		test.beforeEach( async ( { templateCompiler, page } ) => {
			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
				},
			} );

			await page.addInitScript( () => {
				// Mock the wc global variable.
				if ( typeof window.wc === 'undefined' ) {
					window.wc = {
						wcSettings: {
							getSetting() {
								return true;
							},
						},
					};
				}
			} );
		} );

		test( 'clear button is not shown on initial page load', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const button = page.getByRole( 'button', {
				name: 'Clear filters',
			} );

			await expect( button ).toBeHidden();
		} );

		test( 'renders a checkbox list with the available attribute filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const listItems = page
				.getByLabel( 'Filter Options' )
				.getByRole( 'listitem' );

			await expect( listItems ).toHaveCount( 5 );

			for ( let i = 0; i < COLOR_ATTRIBUTE_VALUES.length; i++ ) {
				await expect( listItems.nth( i ) ).toHaveText(
					COLOR_ATTRIBUTE_VALUES[ i ]
				);
			}
		} );

		test( 'filters the list of products by selecting an attribute', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 2 );
		} );

		test( 'clear button appears after a filter is applied', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const button = page.getByRole( 'button', {
				name: 'Clear filters',
			} );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			await grayCheckbox.click();

			const button = page.getByRole( 'button', {
				name: 'Clear filters',
			} );

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const button = page.getByRole( 'button', {
				name: 'Clear filters',
			} );

			await button.click();

			COLOR_ATTRIBUTE_VALUES.map( async ( color ) => {
				const element = page.locator(
					`input[value="${ color.toLowerCase() }"]`
				);

				await expect( element ).not.toBeChecked();
			} );
		} );
	} );

	test.describe( 'With show counts enabled', () => {
		test.beforeEach( async ( { templateCompiler } ) => {
			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
					showCounts: true,
				},
			} );
		} );

		test( 'Renders checkboxes with associated product counts', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const listItems = page
				.getByLabel( 'Filter Options' )
				.getByRole( 'listitem' );

			await expect( listItems ).toHaveCount( 5 );

			for ( let i = 0; i < COLOR_ATTRIBUTES_WITH_COUNTS.length; i++ ) {
				await expect( listItems.nth( i ) ).toHaveText(
					COLOR_ATTRIBUTES_WITH_COUNTS[ i ]
				);
			}
		} );
	} );
} );
