/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { checkCartContent } from '../../utils/cart';
import { getFakeProduct } from '../../utils/data';

let simpleProductId: number, simpleProduct2Id: number, groupedProductId: number;
let groupedProductSlug: string;

test.describe(
	'Grouped Product Page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		const simpleProduct1 = getFakeProduct();
		const simpleProduct2 = getFakeProduct();
		const groupedProduct = getFakeProduct( { type: 'grouped' } );

		test.beforeAll( async ( { restApi } ) => {
			// add products
			await restApi
				.post( `${ WC_API_PATH }/products`, simpleProduct1 )
				.then( ( response ) => {
					simpleProductId = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, simpleProduct2 )
				.then( ( response ) => {
					simpleProduct2Id = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...groupedProduct,
					grouped_products: [ simpleProductId, simpleProduct2Id ],
				} )
				.then( ( response ) => {
					groupedProductId = response.data.id;
					groupedProductSlug = response.data.slug;
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ simpleProductId }`,
				{
					force: true,
				}
			);
			await restApi.delete(
				`${ WC_API_PATH }/products/${ simpleProduct2Id }`,
				{
					force: true,
				}
			);
			await restApi.delete(
				`${ WC_API_PATH }/products/${ groupedProductId }`,
				{
					force: true,
				}
			);
		} );

		test( 'should be able to add grouped products to the cart', async ( {
			page,
		} ) => {
			await page.goto( `product/${ groupedProductSlug }` );

			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();
			await expect(
				page.getByText(
					'Please choose the quantity of items you wish to add to your cart'
				)
			).toBeVisible();

			await page.locator( 'div.quantity input.qty >> nth=0' ).fill( '5' );
			await page.locator( 'div.quantity input.qty >> nth=1' ).fill( '5' );
			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();
			await expect(
				page.getByText(
					new RegExp(
						`${ simpleProduct1.name }.*and.*${ simpleProduct2.name }.*have been added to your cart`
					)
				)
			).toBeVisible();
			await page.goto( 'cart/' );

			await checkCartContent(
				false,
				page,
				[
					{
						data: {
							name: simpleProduct1.name,
							price: simpleProduct1.regular_price,
						},
						qty: 5,
					},
					{
						data: {
							name: simpleProduct2.name,
							price: simpleProduct2.regular_price,
						},
						qty: 5,
					},
				],
				0
			);
		} );

		test( 'should be able to remove grouped products from the cart', async ( {
			page,
		} ) => {
			await page.goto( `product/${ groupedProductSlug }` );
			await page.locator( 'div.quantity input.qty >> nth=0' ).fill( '1' );
			await page.locator( 'div.quantity input.qty >> nth=1' ).fill( '1' );
			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();

			await expect(
				page.getByText(
					new RegExp(
						`${ simpleProduct1.name }.*and.*${ simpleProduct2.name }.*have been added to your cart`
					)
				)
			).toBeVisible();

			await page.goto( 'cart/' );
			await page
				.getByRole( 'button', { name: 'Remove' } )
				.first()
				.click();
			await page
				.getByRole( 'button', { name: 'Remove' } )
				.first()
				.click();

			await checkCartContent( false, page, [], 0 );
		} );
	}
);
