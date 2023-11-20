/**
 * External dependencies
 */
import { switchUserToAdmin, clickButton } from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Reviews by Category',
	slug: 'woocommerce/reviews-by-category',
	class: '.wc-block-reviews-by-category',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	it( 'shows category selector', async () => {
		await expect( page ).toMatchElement(
			`${ block.class } .woocommerce-search-list`
		);
	} );

	it.skip( 'can select a category and show reviews', async () => {
		// we focus on the block
		await page.click( block.class );
		await page.waitForSelector(
			`${ block.class } .woocommerce-search-list__item`
		);
		await page.click( `${ block.class } .woocommerce-search-list__item` );
		await clickButton( 'Done' );
		// Selected.
		await page.waitForSelector(
			'.wc-block-review-list .wc-block-review-list-item__item:not(.is-loading)'
		);
		expect(
			await page.$$eval(
				'.wc-block-review-list .wc-block-review-list-item__item',
				( reviews ) => reviews.length
			)
		).toBeGreaterThanOrEqual( 6 ); // Fixture data has three reviews per product, and there are multiple products.
	} );
} );
