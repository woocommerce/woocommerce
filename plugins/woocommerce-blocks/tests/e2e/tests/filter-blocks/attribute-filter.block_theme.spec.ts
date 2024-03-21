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

test.describe( 'Product Filter: Attribute Block', async () => {
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
	} );

	test.describe( "With display style 'dropdown'", () => {
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
	} );
} );
