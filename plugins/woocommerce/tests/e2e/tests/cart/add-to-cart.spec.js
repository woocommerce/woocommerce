/**
 * External dependencies
 */
import {
	addAProductToCart,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { checkCartContentInBlocksCart } from '../../utils/cart';

const productName = `Cart product test ${ Date.now() }`;
const productPrice = '13.99';

test.describe(
	'Add to Cart behavior',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		let productId;

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [ productId ],
			} );
		} );

		test(
			'should add only one product to the cart with AJAX add to cart buttons disabled and "Geolocate (with page caching support)" as the default customer location',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page, restApi } ) => {
				// Set settings combination that allowed reproducing the bug.
				// @see https://github.com/woocommerce/woocommerce/issues/33077
				await restApi.put(
					`${ WC_API_PATH }/settings/general/woocommerce_default_customer_address`,
					{
						value: 'geolocation_ajax',
					}
				);
				await restApi.put(
					`${ WC_API_PATH }/settings/products/woocommerce_enable_ajax_add_to_cart`,
					{
						value: 'no',
					}
				);
				await addAProductToCart( page, productId );
				await page.goto( 'cart/' );

				await checkCartContentInBlocksCart(
					page,
					[
						{
							data: { name: productName, price: productPrice },
							qty: 1,
						},
					],
					parseFloat( productPrice )
				);

				// Reset settings.
				await restApi.put(
					`${ WC_API_PATH }/settings/general/woocommerce_default_customer_address`,
					{
						value: 'base',
					}
				);
				await restApi.put(
					`${ WC_API_PATH }/settings/products/woocommerce_enable_ajax_add_to_cart`,
					{
						value: 'yes',
					}
				);
			}
		);

		test(
			'should be able to navigate and remove item from mini cart using keyboard',
			{ tag: [ tags.COULD_BE_LOWER_LEVEL_TEST ] },
			async ( { page } ) => {
				await test.step( 'Add product to cart and open mini cart', async () => {
					await addAProductToCart( page, productId );
					const miniCartButton = page.locator(
						'.wc-block-mini-cart__button'
					);
					await miniCartButton.click();
					await expect(
						page.locator( '.wc-block-mini-cart__drawer' )
					).toBeVisible();
				} );

				await test.step( 'Verify and interact with remove button', async () => {
					const removeButton = page.locator(
						'.wc-block-cart-item__remove-link'
					);
					await expect( removeButton ).toBeVisible();
					await removeButton.focus();
					await page.keyboard.press( 'Space' );
				} );

				await test.step( 'Verify cart is empty', async () => {
					await expect(
						page.locator(
							'.wc-block-mini-cart__empty-cart-wrapper'
						)
					).toBeVisible();
					await expect(
						page.locator(
							'.wc-block-mini-cart__empty-cart-wrapper'
						)
					).toContainText( 'Your cart is currently empty!' );
				} );
			}
		);
	}
);
