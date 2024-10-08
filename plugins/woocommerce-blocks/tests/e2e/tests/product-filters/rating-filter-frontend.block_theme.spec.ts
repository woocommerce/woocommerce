/**
 * External dependencies
 */
import { TemplateCompiler, test as base, expect } from '@woocommerce/e2e-utils';

const test = base.extend< { templateCompiler: TemplateCompiler } >( {
	templateCompiler: async ( { requestUtils }, use ) => {
		const compiler = await requestUtils.createTemplateFromFile(
			'archive-product_rating-filter'
		);
		await use( compiler );
	},
} );

test.describe.skip( 'Product Filter: Rating Filter Block', () => {
	test.describe( 'frontend', () => {
		test.beforeEach( async ( { requestUtils, templateCompiler } ) => {
			await requestUtils.activatePlugin(
				'woocommerce-blocks-test-enable-experimental-features'
			);
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

		test( 'clear button appears after a filter is applied', async ( {
			page,
		} ) => {
			await page.goto( '/shop?rating_filter=1' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeVisible();
		} );

		test( 'clear button hides after deselecting all filters', async ( {
			page,
		} ) => {
			await page.goto( '/shop?rating_filter=1' );

			const ratingCheckboxes = page.getByLabel(
				/Checkbox: Rated \d out of 5/
			);

			await ratingCheckboxes.nth( 0 ).uncheck();

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await expect( button ).toBeHidden();
		} );

		test( 'filters are cleared after clear button is clicked', async ( {
			page,
		} ) => {
			await page.goto( '/shop?rating_filter=1' );

			const button = page.getByRole( 'button', { name: 'Clear' } );

			await button.click();

			const ratingCheckbox = page.getByLabel(
				/Checkbox: Rated 1 out of 5/
			);

			await expect( ratingCheckbox ).toBeVisible();
			await expect( ratingCheckbox ).not.toBeChecked();
		} );

		test( 'Renders a checkbox list with the available ratings', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const ratingStars = page.getByLabel( /^Rated \d out of 5/ );
			await expect( ratingStars ).toHaveCount( 2 );

			//  See bin/scripts/parallel/reviews.sh for reviews data.
			await expect( ratingStars.nth( 0 ) ).toHaveAttribute(
				'aria-label',
				'Rated 1 out of 5'
			);
			await expect( ratingStars.nth( 1 ) ).toHaveAttribute(
				'aria-label',
				'Rated 5 out of 5'
			);
		} );

		test( 'Selecting a checkbox filters down the products', async ( {
			page,
		} ) => {
			await page.goto( '/shop' );

			const ratingCheckboxes = page.getByLabel(
				/Checkbox: Rated \d out of 5/
			);

			await ratingCheckboxes.nth( 0 ).check();

			// wait for navigation
			await page.waitForURL(
				( url ) => url.searchParams.get( 'rating_filter' ) === '1'
			);

			const products = page.locator( '.wc-block-product' );
			await expect( products ).toHaveCount( 1 );
		} );
	} );
} );
