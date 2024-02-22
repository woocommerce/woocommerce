/**
 * Internal dependencies
 */
import { shopper } from '../../../utils';
import { getTextContent } from '../../page-utils';

// These tests are skipped and previously relied on GUTENBERG_EDITOR_CONTEXT.
describe.skip( `Shopper → Product Search`, () => {
	beforeEach( async () => {
		await shopper.block.goToBlockPage( 'Product Search' );
		await page.waitForSelector( '.wp-block-search' );
	} );

	it( 'should render product variation', async () => {
		const [ postType ] = await getTextContent(
			'.wp-block-search input[name="post_type"]'
		);
		await expect( postType ).toBe( 'product' );
	} );

	it( 'should be able to search for products', async () => {
		await page.type( '.wp-block-search input[name="s"]', 'Stick' );

		await Promise.all( [
			page.waitForNavigation(),
			page.keyboard.press( 'Enter' ),
		] );

		const products = await page.$$( 'ul.products.columns-3 > li' );

		expect( products ).toHaveLength( 2 );

		const productTitles = await getTextContent(
			'ul.products.columns-3 .woocommerce-loop-product__title'
		);

		expect( productTitles ).toContain( '32GB USB Stick' );
		expect( productTitles ).toContain( '128GB USB Stick' );
	} );
} );
