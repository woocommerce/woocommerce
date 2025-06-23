/**
 * Internal dependencies
 */
import {
	expect,
	tags,
	test as baseTest,
	guestFile,
} from '../../fixtures/fixtures';
import { getFakeProduct } from '../../utils/data';
import { WC_API_PATH } from '../../utils/api-client';

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
		await restApi
			.post( `${ WC_API_PATH }/coupons`, {
				code: 'E2ECOUPON',
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
				// Visit checkout-link
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }&coupon=${ coupon.code }`;
				await page.goto( checkoutLink );

				// Should redirect to checkout
				await expect( page ).toHaveURL( /\/checkout/ );

				// Assert both products are in the cart
				const cartItems = page.locator(
					'.wc-block-components-order-summary'
				);
				await expect( cartItems ).toContainText( products[ 0 ].name );
				await expect( cartItems ).toContainText( products[ 1 ].name );

				// Assert coupon is applied
				await expect(
					page.getByText( 'Coupon: E2ECOUPON' )
				).toBeVisible();
			}
		);

		test(
			'Guest user sees error when invalid coupon is applied',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products } ) => {
				// Visit checkout-link with invalid coupon
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }&coupon=INVALID_COUPON`;
				await page.goto( checkoutLink );

				// Should redirect to checkout
				await expect( page ).toHaveURL( /\/checkout/ );

				// Assert both products are in the cart
				const cartItems = page.locator(
					'.wc-block-components-order-summary'
				);
				await expect( cartItems ).toContainText( products[ 0 ].name );
				await expect( cartItems ).toContainText( products[ 1 ].name );

				// Assert error notice is shown for invalid coupon
				await expect(
					page.getByText(
						'Coupon "INVALID_COUPON" cannot be applied because it does not exist.'
					)
				).toBeVisible();
			}
		);

		test(
			'Guest user sees error when invalid products are provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products } ) => {
				// Visit checkout-link with invalid product ID in list.
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },999999`;
				await page.goto( checkoutLink );

				// Should redirect to checkout
				await expect( page ).toHaveURL( /\/checkout/ );

				// Assert error notice is shown for invalid product
				await expect(
					page.getByText(
						'Product with ID "999999" was not found and cannot be added to the cart.'
					)
				).toBeVisible();
			}
		);

		test(
			'Guest user sees error when invalid product is provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL } ) => {
				// Visit checkout-link with invalid product ID only.
				const checkoutLink = `${ baseURL }/checkout-link?products=999999`;
				await page.goto( checkoutLink );

				// Should redirect to cart if cart is empty.
				await expect( page ).toHaveURL( /\/cart/ );

				// Assert error notice is shown for invalid product
				await expect(
					page.getByText(
						'Product with ID "999999" was not found and cannot be added to the cart.'
					)
				).toBeVisible();

				// Cart should be empty
				await expect(
					page.getByText( 'Your cart is currently empty!' )
				).toBeVisible();
			}
		);

		test(
			'Guest user sees error when invalid link is provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL } ) => {
				// Visit checkout-link with invalid product ID only.
				const checkoutLink = `${ baseURL }/checkout-link?products=abc`;
				await page.goto( checkoutLink );

				// Should redirect to cart if cart is empty.
				await expect( page ).toHaveURL( /\/cart/ );

				// Assert error notice is shown for invalid product
				await expect(
					page.getByText(
						'The provided checkout link was out of date or invalid. No products were added to the cart.'
					)
				).toBeVisible();

				// Cart should be empty
				await expect(
					page.getByText( 'Your cart is currently empty!' )
				).toBeVisible();
			}
		);
	} );

	test.describe( 'Logged-in user', () => {
		test(
			'Logged-in user redirected to checkout with correct cart',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products, coupon } ) => {
				// Visit checkout-link
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }&coupon=${ coupon.code }`;
				await page.goto( checkoutLink );

				// Should redirect to checkout
				await expect( page ).toHaveURL( /\/checkout/ );

				// Assert both products are in the cart
				const cartItems = page.locator(
					'.wc-block-components-order-summary'
				);
				await expect( cartItems ).toContainText( products[ 0 ].name );
				await expect( cartItems ).toContainText( products[ 1 ].name );

				// Assert coupon is applied
				await expect(
					page.getByText( 'Coupon: E2ECOUPON' )
				).toBeVisible();
			}
		);

		test(
			'Logged-in user sees error when invalid coupon is applied',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products } ) => {
				// Visit checkout-link with invalid coupon
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }&coupon=INVALID_COUPON`;
				await page.goto( checkoutLink );

				// Should redirect to checkout
				await expect( page ).toHaveURL( /\/checkout/ );

				// Assert both products are in the cart
				const cartItems = page.locator(
					'.wc-block-components-order-summary'
				);
				await expect( cartItems ).toContainText( products[ 0 ].name );
				await expect( cartItems ).toContainText( products[ 1 ].name );

				// Assert error notice is shown for invalid coupon
				await expect(
					page.getByText(
						'Coupon "INVALID_COUPON" cannot be applied because it does not exist.'
					)
				).toBeVisible();
			}
		);

		test(
			'Logged-in user sees error when invalid products are provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL, products } ) => {
				// Visit checkout-link with invalid product ID in list.
				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },999999`;
				await page.goto( checkoutLink );

				// Should redirect to checkout
				await expect( page ).toHaveURL( /\/checkout/ );

				// Assert error notice is shown for invalid product
				await expect(
					page.getByText(
						'Product with ID "999999" was not found and cannot be added to the cart.'
					)
				).toBeVisible();
			}
		);

		test(
			'Logged-in user sees error when invalid product is provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL } ) => {
				// Visit checkout-link with invalid product ID only.
				const checkoutLink = `${ baseURL }/checkout-link?products=999999`;
				await page.goto( checkoutLink );

				// Should redirect to cart if cart is empty.
				await expect( page ).toHaveURL( /\/cart/ );

				// Assert error notice is shown for invalid product
				await expect(
					page.getByText(
						'Product with ID "999999" was not found and cannot be added to the cart.'
					)
				).toBeVisible();

				// Cart should be empty
				await expect(
					page.getByText( 'Your cart is currently empty!' )
				).toBeVisible();
			}
		);

		test(
			'Logged-in user sees error when invalid link is provided',
			{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
			async ( { page, baseURL } ) => {
				// Visit checkout-link with invalid product ID only.
				const checkoutLink = `${ baseURL }/checkout-link?products=abc`;
				await page.goto( checkoutLink );

				// Should redirect to cart if cart is empty.
				await expect( page ).toHaveURL( /\/cart/ );

				// Assert error notice is shown for invalid product
				await expect(
					page.getByText(
						'The provided checkout link was out of date or invalid. No products were added to the cart.'
					)
				).toBeVisible();

				// Cart should be empty
				await expect(
					page.getByText( 'Your cart is currently empty!' )
				).toBeVisible();
			}
		);
	} );
} );
