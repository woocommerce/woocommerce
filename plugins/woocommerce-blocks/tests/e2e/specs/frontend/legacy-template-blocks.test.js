import { URL } from 'url';
import { activateTheme } from '@wordpress/e2e-test-utils';

import { BASE_URL } from '../../utils';

const SELECTORS = {
	productArchivePage: {
		paginationUI: '.woocommerce-pagination',
		productContainers: '.products .product',
		productsList: '.products',
		resultsCount: '.woocommerce-result-count',
	},
};

describe( 'Legacy Template blocks', () => {
	beforeAll( async () => {
		await activateTheme( 'emptytheme' );
	} );

	afterAll( async () => {
		await activateTheme( 'twentytwentyone' );
	} );

	describe( 'Product Archive block', () => {
		it( 'renders a list of products with their count and pagination', async () => {
			const { productArchivePage } = SELECTORS;

			await page.goto( new URL( '/?post_type=product', BASE_URL ) );

			await page.waitForSelector( productArchivePage.productsList );
			await page.waitForSelector( productArchivePage.resultsCount );

			const { displayedCount, shouldHavePaginationUI } = await page.$eval(
				productArchivePage.resultsCount,
				( $el ) => {
					const resultsCountRegEx = /1–(\d+)|\d+/;
					const matches = $el.textContent.match( resultsCountRegEx );

					// Depending on pagination, string can be either:
					// a) 'Showing x–y of z results'
					// b) 'Showing all x results'
					return {
						displayedCount: matches[ 1 ] || matches[ 0 ],
						shouldHavePaginationUI: !! matches[ 1 ],
					};
				}
			);

			if ( shouldHavePaginationUI ) {
				await expect( page ).toMatchElement(
					productArchivePage.paginationUI
				);
			}

			const $productElements = await page.$$(
				productArchivePage.productContainers
			);

			expect( $productElements ).toHaveLength( Number( displayedCount ) );
		} );
	} );
} );
