/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

const COLOR_ATTRIBUTE_VALUES = [ 'Blue', 'Gray', 'Green', 'Red', 'Yellow' ];
const COLOR_ATTRIBUTES_WITH_COUNTS = [
	'Blue (4)',
	'Gray (2)',
	'Green (3)',
	'Red (4)',
	'Yellow (1)',
];

const test = base.extend( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const template = await requestUtils.createTemplateFromFile(
			'archive-product_attribute-filter'
		);
		await use( template );
	},
} );

test.describe( 'Product Filter: Attribute Block', () => {
	test.describe( 'With default display style', () => {
		test.beforeEach( async ( { templateCompiler } ) => {
			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
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

		test( 'renders a checkbox list with the available attribute filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

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

			const button = page.getByRole( 'button', { name: 'Clear' } );

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

			const button = page.getByRole( 'button', { name: 'Clear' } );

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

			const button = page.getByRole( 'button', { name: 'Clear' } );

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

			const attributes = page.locator(
				'.wc-block-components-checkbox__label'
			);

			await expect( attributes ).toHaveCount( 5 );

			for ( let i = 0; i < COLOR_ATTRIBUTES_WITH_COUNTS.length; i++ ) {
				await expect( attributes.nth( i ) ).toHaveText(
					COLOR_ATTRIBUTES_WITH_COUNTS[ i ]
				);
			}
		} );
	} );

	test.describe( "With display style 'dropdown'", () => {
		test.beforeEach( async ( { templateCompiler } ) => {
			await templateCompiler.compile( {
				attributes: {
					attributeId: 1,
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

		test( 'renders a dropdown list with the available attribute filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

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
		} ) => {
			await page.goto( '/shop' );

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

		test( 'clear button appears after a filter is applied', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await expect( dropdownLocator ).toBeVisible();
			await dropdownLocator.click();

			const yellowOption = page.getByText( 'Yellow' );
			await yellowOption.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=yellow.*/ );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await dropdownLocator.click();

			const yellowOption = page.getByText( 'Yellow' );
			await yellowOption.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=yellow.*/ );

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
			await page.goto( '/shop' );

			const dropdownLocator = page.locator(
				'.wc-interactivity-dropdown'
			);

			await dropdownLocator.click();

			const yellowOption = page.getByText( 'Yellow' );
			await yellowOption.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=yellow.*/ );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			const placeholder = page.getByText( 'Select Color' );

			await expect( placeholder ).toBeVisible();
		} );
	} );
} );
