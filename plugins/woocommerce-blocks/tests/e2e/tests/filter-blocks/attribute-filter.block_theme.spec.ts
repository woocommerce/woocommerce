/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';

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
	postWithShowCounts: Post;
	defaultBlockPost: Post;
	dropdownBlockPost: Post;
} >( {
	defaultBlockPost: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Product Filter: Attribute Block - Color' },
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
				},
			}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},

	postWithShowCounts: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Product Filter: Attribute Block - Color' },
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
					showCounts: true,
				},
			}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},

	dropdownBlockPost: async ( { requestUtils }, use ) => {
		const testingPost = await requestUtils.createPostFromTemplate(
			{ title: 'Product Filter: Attribute Block' },
			TEMPLATE_PATH,
			{
				attributes: {
					attributeId: 1,
					displayStyle: 'dropdown',
				},
			}
		);

		await use( testingPost );
		await requestUtils.deletePost( testingPost.id );
	},
} );

test.describe( 'Product Filter: Attribute Block', () => {
	test.describe( 'With default display style', () => {
		test.describe( 'With show counts enabled', () => {
			test( 'Renders checkboxes with associated product counts', async ( {
				page,
				postWithShowCounts,
			} ) => {
				await page.goto( postWithShowCounts.link );

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

		test( 'clear button is not shown on initial page load', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'renders a checkbox list with the available attribute filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

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
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const products = page.locator( '.wc-block-product' );

			await expect( products ).toHaveCount( 2 );
		} );

		test( 'clear button appears after a filter is applied', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

			const grayCheckbox = page.getByText( 'Gray' );
			await grayCheckbox.click();

			// wait for navigation
			await page.waitForURL( /.*filter_color=gray.*/ );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

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
			defaultBlockPost,
		} ) => {
			await page.goto( defaultBlockPost.link );

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

	test.describe( "With display style 'dropdown'", () => {
		test( 'clear button is not shown on initial page load', async ( {
			page,
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'renders a dropdown list with the available attribute filters', async ( {
			page,
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

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
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

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
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

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
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

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
			dropdownBlockPost,
		} ) => {
			await page.goto( dropdownBlockPost.link );

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
