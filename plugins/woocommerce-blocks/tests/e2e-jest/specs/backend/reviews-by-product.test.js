/**
 * External dependencies
 */
import { switchUserToAdmin, clickButton } from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Reviews by Product',
	slug: 'woocommerce/reviews-by-product',
	class: '.wc-block-reviews-by-product',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	it( 'shows product selector', async () => {
		await expect( page ).toMatchElement(
			`${ block.class } .woocommerce-search-list`
		);
	} );

	it.skip( 'can select a product and show reviews', async () => {
		// we focus on the block
		await page.click( block.class );
		await page.waitForSelector(
			`${ block.class } .woocommerce-search-list__item`
		);
		do {
			await page.click(
				`${ block.class } .woocommerce-search-list__item`
			);
		} while (
			await page.evaluate(
				( blockClass ) =>
					document
						.querySelector(
							`${ blockClass } .woocommerce-search-list__item`
						)
						.getAttribute( 'aria-checked' ) === 'false',
				block.class
			)
		);
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
		).toBeGreaterThanOrEqual( 3 ); // Fixture data has three reviews per product.
	} );
} );
