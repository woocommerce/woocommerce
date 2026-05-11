/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const COLOR_ATTRIBUTE_VALUES = [ 'Blue', 'Red', 'Green', 'Gray', 'Yellow' ];
const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Red (4)',
	'Green (3)',
	'Gray (2)',
	'Yellow (1)',
];

function attributeFilterSelect( page: Page ) {
	return page
		.locator( '.wc-block-product-filters' )
		.locator( '.wc-block-product-filter-dropdown__select' );
}

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter-dropdown'
		);
		await use( compiler );
	},
} );

test.describe( 'woocommerce/product-filter-attribute - Frontend (dropdown display)', () => {
	test.describe( 'With dropdown display style', () => {
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

		test( 'renders a dropdown with the available attribute filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const select = attributeFilterSelect( page );
			await expect( select ).toBeVisible();

			const options = select.locator( 'option' );
			await expect( options ).toHaveCount( 6 );

			for ( let i = 0; i < COLOR_ATTRIBUTE_VALUES.length; i++ ) {
				await expect( options.nth( i + 1 ) ).toHaveText(
					COLOR_ATTRIBUTE_VALUES[ i ]
				);
			}
		} );

		test( 'filters the list of products by selecting an attribute', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			await attributeFilterSelect( page ).selectOption( 'gray' );

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 2 );
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			await attributeFilterSelect( page ).selectOption( 'gray' );

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const button = page.getByRole( 'button', {
				name: 'Clear filters',
			} );

			await button.click();

			await expect( attributeFilterSelect( page ) ).toHaveValue( '' );
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

		test( 'Renders dropdown options with associated product counts', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const select = attributeFilterSelect( page );
			await expect( select ).toBeVisible();

			const options = select.locator( 'option' );
			await expect( options ).toHaveCount( 6 );

			for ( let i = 0; i < COLOR_ATTRIBUTES_WITH_COUNTS.length; i++ ) {
				await expect( options.nth( i + 1 ) ).toHaveText(
					COLOR_ATTRIBUTES_WITH_COUNTS[ i ]
				);
			}
		} );
	} );
} );
