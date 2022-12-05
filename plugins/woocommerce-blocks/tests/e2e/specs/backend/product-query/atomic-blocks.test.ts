/**
 * External dependencies
 */
import { canvas, setPostContent, insertBlock } from '@wordpress/e2e-test-utils';
import {
	visitBlockPage,
	saveOrPublish,
	shopper,
	insertInnerBlock,
	getFixtureProductsData,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	GUTENBERG_EDITOR_CONTEXT,
	describeOrSkip,
	waitForCanvas,
} from '../../../utils';

const block = {
	name: 'Products (Beta)',
	slug: 'woocommerce/product-query',
	class: '.wp-block-query',
};

const SELECTORS = {
	productButton: '.wc-block-components-product-button',
	productPrice: '.wc-block-components-product-price',
	productRating: '.wc-block-components-product-rating',
	productImage: {
		editor: 'li.block-editor-block-list__layout .wc-block-components-product-image',
	},
};

describeOrSkip( GUTENBERG_EDITOR_CONTEXT === 'gutenberg' )(
	'Product Query > Atomic blocks',
	() => {
		beforeEach( async () => {
			await visitBlockPage( `${ block.name } Block` );
			await waitForCanvas();
		} );

		afterAll( async () => {
			await visitBlockPage( `${ block.name } Block` );
			await setPostContent( '' );
			await insertBlock( 'Products (Beta)' );
			await saveOrPublish();
		} );

		it( 'Can add the Add to Cart Button block and render it on the front end', async () => {
			await insertInnerBlock(
				'Add to Cart Button',
				'core/post-template'
			);
			await expect( canvas() ).toMatchElement( SELECTORS.productButton, {
				text: 'Add to cart',
			} );
			await saveOrPublish();

			await shopper.block.goToBlockPage( block.name );
			await page.waitForSelector( SELECTORS.productButton );
			await expect( page ).toClick( 'button', {
				text: 'Add to cart',
			} );
			await shopper.block.goToCart();
			await page.waitForSelector( '.wc-block-cart-items__row' );
			expect(
				await page.$$eval(
					'.wc-block-cart-items__row',
					( rows ) => rows.length
				)
			).toEqual( 1 );
		} );

		it( 'Can add the Product Image block', async () => {
			await insertInnerBlock( 'Product Image', 'core/post-template' );
			expect(
				await canvas().$$eval(
					SELECTORS.productImage.editor,
					( images ) => images.length
				)
			).toEqual( 2 );
		} );

		it( 'Can add the Product Price block and render it on the front end', async () => {
			const fixturePrices = getFixtureProductsData( 'regular_price' );
			const testPrice =
				fixturePrices[
					Math.floor( Math.random() * fixturePrices.length )
				];
			await insertInnerBlock( 'Product Price', 'core/post-template' );
			await expect( canvas() ).toMatchElement( SELECTORS.productPrice, {
				text: testPrice,
			} );
			await saveOrPublish();

			await shopper.block.goToBlockPage( block.name );
			await page.waitForSelector( SELECTORS.productPrice );
			await expect( page ).toMatchElement( SELECTORS.productPrice, {
				text: testPrice,
			} );
		} );

		it( 'Can add the Product Ratings block and render it on the front end', async () => {
			await insertInnerBlock( 'Product Rating', 'core/post-template' );
			await expect( canvas() ).toMatchElement( SELECTORS.productRating );
			await saveOrPublish();

			await shopper.block.goToBlockPage( block.name );
			expect(
				await page.$$eval(
					SELECTORS.productRating,
					( rows ) => rows.length
				)
			).toEqual( 5 );
		} );
	}
);
