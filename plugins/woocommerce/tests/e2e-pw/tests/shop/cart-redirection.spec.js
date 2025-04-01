/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';
import { checkCartContent } from '../../utils/cart';

test.describe(
	'Cart > Redirect to cart from shop',
	{
		tag: [ tags.PAYMENTS, tags.NOT_E2E, tags.COULD_BE_LOWER_LEVEL_TEST ],
	},
	() => {
		let productId;
		const productName = 'A redirect product test';

		test.beforeAll( async ( { restApi } ) => {
			// add products
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: productName,
					type: 'simple',
					regular_price: '17.99',
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await restApi.put(
				`${ WC_API_PATH }/settings/products/woocommerce_cart_redirect_after_add`,
				{
					value: 'yes',
				}
			);
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
			await restApi.put(
				`${ WC_API_PATH }/settings/products/woocommerce_cart_redirect_after_add`,
				{
					value: 'no',
				}
			);
		} );

		test( 'can redirect user to cart from shop page', async ( {
			page,
		} ) => {
			await page.goto( 'shop/' );
			await page
				.locator(
					`a[data-product_id='${ productId }'][href*=add-to-cart]`
				)
				.click();

			await expect( page ).toHaveURL( /.*\/cart/ );
			await checkCartContent(
				false,
				page,
				[
					{
						data: {
							name: productName,
							price: '17.99',
						},
						qty: 1,
					},
				],
				0
			);
		} );

		test( 'can redirect user to cart from detail page', async ( {
			page,
		} ) => {
			await page.goto( 'shop/' );
			await page.locator( `text=${ productName }` ).click();

			await page.getByRole( 'button', { name: 'Add to cart' } ).click();

			await expect( page ).toHaveURL( /.*\/cart/ );
			await checkCartContent(
				false,
				page,
				[
					{
						data: {
							name: productName,
							price: '17.99',
						},
						qty: 1,
					},
				],
				0
			);
		} );
	}
);
