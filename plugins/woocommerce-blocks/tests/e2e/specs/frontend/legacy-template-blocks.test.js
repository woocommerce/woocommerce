import { URL } from 'url';
import { activateTheme } from '@wordpress/e2e-test-utils';

import { BASE_URL } from '../../utils';

const SELECTORS = {
	productArchivePage: {
		paginationUI: '.woocommerce-pagination',
		productContainers: '.products .product',
		productsList: '.products',
		resultsCount: '.woocommerce-result-count',
		title: '.page-title',
	},
};

/**
 * Extracts data regarding pagination from the page
 *
 * @return {Promise<{ displayedCount: number, shouldHavePaginationUI: boolean }>} How many products are displayed
 * and whether there should be a pagination UI (i.e. the displayed products are lesser than the total
 * number of products).
 */
function extractPaginationData() {
	const { productArchivePage } = SELECTORS;

	return page.$eval( productArchivePage.resultsCount, ( $el ) => {
		const resultsCountRegEx = /1–(\d+)|\d+/;
		const matches = $el.textContent.match( resultsCountRegEx );

		// Depending on pagination, string can be either:
		// a) 'Showing x–y of z results'
		// b) 'Showing all x results'
		return {
			displayedCount: Number( matches[ 1 ] ) || Number( matches[ 0 ] ),
			shouldHavePaginationUI: !! matches[ 1 ],
		};
	} );
}

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

			const {
				displayedCount,
				shouldHavePaginationUI,
			} = await extractPaginationData();

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

	describe( 'Product Category block', () => {
		it( 'renders a list of products with their count, pagination and the category title', async () => {
			const CATEGORY_NAME = 'Uncategorized';
			const { productArchivePage } = SELECTORS;

			await page.goto(
				new URL(
					`/product-category/${ CATEGORY_NAME.toLowerCase() }`,
					BASE_URL
				)
			);

			await expect( page ).toMatchElement( productArchivePage.title, {
				text: CATEGORY_NAME,
			} );

			await page.waitForSelector( productArchivePage.productsList );
			await page.waitForSelector( productArchivePage.resultsCount );

			const {
				displayedCount,
				shouldHavePaginationUI,
			} = await extractPaginationData();

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

	describe( 'Product Tag block', () => {
		it( 'renders a list of products with their count, pagination and the tag title', async () => {
			const TAG_NAME = 'Newest';
			const { productArchivePage } = SELECTORS;

			await page.goto(
				new URL( `/product-tag/${ TAG_NAME.toLowerCase() }`, BASE_URL )
			);

			await expect( page ).toMatchElement( productArchivePage.title, {
				text: TAG_NAME,
			} );

			await page.waitForSelector( productArchivePage.productsList );
			await page.waitForSelector( productArchivePage.resultsCount );

			const {
				displayedCount,
				shouldHavePaginationUI,
			} = await extractPaginationData();

			if ( shouldHavePaginationUI ) {
				await expect( page ).toMatchElement(
					productArchivePage.paginationUI
				);
			}

			const $productElements = await page.$$(
				productArchivePage.productContainers
			);

			expect( $productElements ).toHaveLength( displayedCount );
		} );
	} );
} );
