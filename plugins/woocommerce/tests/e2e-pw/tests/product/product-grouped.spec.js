/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';
import { checkCartContent } from '../../utils/cart';

const productPrice = '18.16';
const simpleProductName = 'Simple single product';
const groupedProductName = 'Grouped single product';

let simpleProductId, simpleProduct2Id, groupedProductId;

test.describe(
	'Grouped Product Page',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		const slug = groupedProductName.replace( / /gi, '-' ).toLowerCase();
		const simpleProduct1 = simpleProductName + ' 1';
		const simpleProduct2 = simpleProductName + ' 2';

		test.beforeAll( async ( { restApi } ) => {
			// add products
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: simpleProduct1,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					simpleProductId = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: simpleProduct2,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					simpleProduct2Id = response.data.id;
				} );
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: groupedProductName,
					type: 'grouped',
					grouped_products: [ simpleProductId, simpleProduct2Id ],
				} )
				.then( ( response ) => {
					groupedProductId = response.data.id;
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
			await page.goto( `product/${ slug }` );

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
						`${ simpleProduct1 }.*and.*${ simpleProduct2 }.*have been added to your cart`
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
							name: simpleProduct1,
							price: productPrice,
						},
						qty: 5,
					},
					{
						data: {
							name: simpleProduct2,
							price: productPrice,
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
			await page.goto( `product/${ slug }` );
			await page.locator( 'div.quantity input.qty >> nth=0' ).fill( '1' );
			await page.locator( 'div.quantity input.qty >> nth=1' ).fill( '1' );
			await page
				.getByRole( 'button', { name: 'Add to cart', exact: true } )
				.click();

			await expect(
				page.getByText(
					new RegExp(
						`${ simpleProduct1 }.*and.*${ simpleProduct2 }.*have been added to your cart`
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
