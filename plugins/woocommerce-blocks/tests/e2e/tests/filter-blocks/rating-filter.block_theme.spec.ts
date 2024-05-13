/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import path from 'path';

/**
 * Internal dependencies
 */
import { PRODUCT_CATALOG_LINK, PRODUCT_CATALOG_TEMPLATE_ID } from './constants';

const TEMPLATE_PATH = path.join( __dirname, './rating-filter.handlebars' );

test.describe( 'Product Filter: Rating Filter Block', () => {
	test.describe( 'frontend', () => {
		let testingTemplateId = '';

		test.beforeAll( async ( { requestUtils } ) => {
			const testingTemplate = await requestUtils.updateTemplateContents(
				PRODUCT_CATALOG_TEMPLATE_ID,
				TEMPLATE_PATH,
				{
					attributes: {
						attributeId: 1,
					},
				}
			);

			testingTemplateId = testingTemplate.id;
		} );

		test.afterAll( async ( { templateApiUtils } ) => {
			await templateApiUtils.revertTemplate( testingTemplateId );
		} );

		test( 'Renders a checkbox list with the available ratings', async ( {
			page,
		} ) => {
			await page.goto( PRODUCT_CATALOG_LINK );

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
			await page.goto( PRODUCT_CATALOG_LINK );

			const ratingCheckboxes = page.getByLabel(
				/Checkbox: Rated \d out of 5/
			);

			ratingCheckboxes.nth( 0 ).check();

			// wait for navigation
			await page.waitForURL(
				( url ) => url.searchParams.get( 'rating_filter' ) === '1'
			);

			const products = page.locator( '.wc-block-product' );
			await expect( products ).toHaveCount( 1 );
		} );
	} );
} );
