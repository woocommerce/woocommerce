/**
 * External dependencies
 */
import { canvas } from '@wordpress/e2e-test-utils';
import {
	saveOrPublish,
	shopper,
	insertInnerBlock,
	getFixtureProductsData,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	block,
	SELECTORS,
	resetProductQueryBlockPage,
	getProductElementNodesCount,
	getEditorProductElementNodesCount,
} from './common';

// These tests are skipped and previously relied on GUTENBERG_EDITOR_CONTEXT.
describe.skip( `${ block.name } > Atomic blocks`, () => {
	beforeEach( async () => {
		await resetProductQueryBlockPage();
	} );

	afterAll( async () => {
		await resetProductQueryBlockPage();
	} );

	it( 'Can add the Add to Cart Button block and render it on the front end', async () => {
		await page.waitForSelector( SELECTORS.productButton );
		await expect( canvas() ).toMatchElement( SELECTORS.productButton, {
			text: 'Add to cart',
		} );
		await insertInnerBlock( 'Add to Cart Button', 'core/post-template' );
		expect(
			await getEditorProductElementNodesCount( SELECTORS.productButton )
		).toEqual( 2 );

		await shopper.block.goToBlockPage( block.name );
		await page.waitForSelector( SELECTORS.productButton );
		await expect( page ).toClick( 'button', {
			text: 'Add to cart',
		} );
		await shopper.block.goToCart();
		await page.waitForSelector( '.wc-block-cart-items__row' );
		expect(
			await getProductElementNodesCount( SELECTORS.cartItemRow )
		).toEqual( 1 );
	} );

	it( 'Can add the Product Image block', async () => {
		await page.waitForSelector( SELECTORS.productImage );
		await insertInnerBlock( 'Product Image', 'core/post-template' );
		expect(
			await getEditorProductElementNodesCount( SELECTORS.productImage )
		).toEqual( 2 );
	} );

	it( 'Can add the Product Price block and render it on the front end', async () => {
		const fixturePrices = getFixtureProductsData( 'regular_price' );
		const testPrice =
			fixturePrices[ Math.floor( Math.random() * fixturePrices.length ) ];
		await page.waitForSelector( SELECTORS.productPrice );
		await expect( canvas() ).toMatchElement( SELECTORS.productPrice, {
			text: testPrice,
		} );
		await insertInnerBlock( 'Product Price', 'core/post-template' );
		expect(
			await getEditorProductElementNodesCount( SELECTORS.productPrice )
		).toEqual( 2 );

		await shopper.block.goToBlockPage( block.name );
		await page.waitForSelector( SELECTORS.productPrice );
		await expect( page ).toMatchElement( SELECTORS.productPrice, {
			text: testPrice,
		} );
	} );

	it( 'Can add the Product Ratings block and render it on the front end', async () => {
		await insertInnerBlock( 'Product Rating', 'core/post-template' );
		expect(
			await getEditorProductElementNodesCount( SELECTORS.productRating )
		).toEqual( 1 );
		await saveOrPublish();
		await shopper.block.goToBlockPage( block.name );
		expect(
			await getProductElementNodesCount( SELECTORS.productRating )
		).toEqual( getFixtureProductsData().length );
	} );
} );
