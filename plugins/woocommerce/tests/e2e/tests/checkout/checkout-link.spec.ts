/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { CUSTOMER_STATE_PATH } from '../../playwright.config';
import { getFakeProduct } from '../../utils/data';
import { guestFile } from '../../utils/blocks/constants';

const test = baseTest.extend( {
	products: async ( { restApi }, use ) => {
		const products = [];

		// Using dec: 0 to avoid small rounding issues
		for ( let i = 0; i < 2; i++ ) {
			await restApi
				.post(
					`${ WC_API_PATH }/products`,
					getFakeProduct( { dec: 0 } )
				)
				.then( ( response ) => {
					products.push( response.data );
				} );
		}

		await use( products );

		// Clean up products
		for ( const product of products ) {
			await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
				force: true,
			} );
		}
	},
	coupon: async ( { restApi }, use ) => {
		let coupon;
		// Unique code per worker so parallel runs don't collide on a duplicate
		// coupon code (WC rejects duplicates with a 400).
		const code = `E2ECOUPON${ faker.string
			.alphanumeric( 6 )
			.toUpperCase() }`;
		await restApi
			.post( `${ WC_API_PATH }/coupons`, {
				code,
				discount_type: 'percent',
				amount: '10',
			} )
			.then( ( response ) => {
				coupon = response.data;
			} );

		await use( coupon );

		// Clean up coupon
		await restApi.delete( `${ WC_API_PATH }/coupons/${ coupon.id }`, {
			force: true,
		} );
	},
} );

test.describe( 'Checkout Link Endpoint', () => {
	test.describe( 'Guest user', () => {
		test.use( { storageState: guestFile } );

		test(
			'Guest user redirected to checkout with correct cart',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products, coupon } ) => {
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }&coupon=${ coupon.code }`;
				await page.goto( checkoutLink );

				await expect( page ).toHaveURL( /\/checkout/ );
				const session = new URL( page.url() ).searchParams.get(
					'session'
				);
				expect( session ).not.toBeNull();
				expect( session ).not.toBe( '' );

				const cartItems = page.locator(
					'.wc-block-components-order-summary'
				);
				await expect( cartItems ).toContainText( products[ 0 ].name );
				await expect( cartItems ).toContainText( products[ 1 ].name );

				await expect(
					page.getByText( `Coupon: ${ coupon.code }` )
				).toBeVisible();
			}
		);

		test(
			'Guest user sees error when invalid link is provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL } ) => {
				const checkoutLink = `${ baseURL }/checkout-link?products=abc`;
				await page.goto( checkoutLink );

				await expect( page ).toHaveURL( /\/cart/ );

				await expect(
					page.getByText(
						'The provided checkout link was out of date or invalid. No products were added to the cart.'
					)
				).toBeVisible();

				await expect(
					page.getByText( 'Your cart is currently empty!' )
				).toBeVisible();
			}
		);
	} );

	test.describe( 'Logged-in user', () => {
		test.use( { storageState: CUSTOMER_STATE_PATH } );

		test(
			'Logged-in user redirected to checkout with correct cart',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products, coupon } ) => {
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }&coupon=${ coupon.code }`;
				await page.goto( checkoutLink );

				await expect( page ).toHaveURL( /\/checkout/ );
				expect(
					new URL( page.url() ).searchParams.get( 'session' )
				).toBeNull();

				const cartItems = page.locator(
					'.wc-block-components-order-summary'
				);
				await expect( cartItems ).toContainText( products[ 0 ].name );
				await expect( cartItems ).toContainText( products[ 1 ].name );

				await expect(
					page.getByText( `Coupon: ${ coupon.code }` )
				).toBeVisible();
			}
		);
	} );
} );
