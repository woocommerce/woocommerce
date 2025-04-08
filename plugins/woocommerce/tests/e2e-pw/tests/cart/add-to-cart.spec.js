/**
 * External dependencies
 */
import { addAProductToCart } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, test } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';
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
	}
);
