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

		// Using dec: 0 to avoid small rounding issues. Virtual, so the totals
		// below are the product prices and the coupon, with no shipping line.
		// The money() helper in the logged-in title reads those totals in the
		// store's US dollar format: a leading $, a comma every three digits and
		// two decimal places.
		for ( let i = 0; i < 3; i++ ) {
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					...getFakeProduct( { dec: 0 } ),
					// A unique name per product, so the "not in the cart" check
					// on the third one cannot match one of the linked two.
					name: `Checkout link ${ i } ${ faker.string.alphanumeric(
						6
					) }`,
					virtual: true,
				} )
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
					page.getByText( 'Your cart is empty' )
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
				const linkedQuantities = [ 1, 3 ];
				const money = ( value: number ) =>
					`$${ value.toLocaleString( 'en-US', {
						minimumFractionDigits: 2,
						maximumFractionDigits: 2,
					} ) }`;
				const lineTotal = ( index: number ) =>
					Number( products[ index ].price ) *
					linkedQuantities[ index ];
				const subtotal = lineTotal( 0 ) + lineTotal( 1 );
				const discount = subtotal * 0.1;

				// An item already in the cart: the link owns the cart it
				// describes, so this one has to be gone afterwards.
				await page.goto( `/?add-to-cart=${ products[ 2 ].id }` );
				await page.goto( '/cart' );
				await expect(
					page.getByText( products[ 2 ].name ).first()
				).toBeVisible();

				const checkoutLink = `${ baseURL }/checkout-link?products=${ products[ 0 ].id },${ products[ 1 ].id }:${ linkedQuantities[ 1 ] }&coupon=${ coupon.code }`;
				await page.goto( checkoutLink );

				await expect( page ).toHaveURL( /\/checkout/ );
				expect(
					new URL( page.url() ).searchParams.get( 'session' )
				).toBeNull();

				const orderSummary = page
					.locator( '.wc-block-components-order-summary' )
					.first();
				const summaryItems = orderSummary.locator(
					'.wc-block-components-order-summary-item'
				);
				await expect(
					orderSummary.getByText( products[ 0 ].name ).first()
				).toBeVisible();
				await expect( summaryItems ).toHaveCount( 2 );

				for ( const index of [ 0, 1 ] ) {
					const item = summaryItems.filter( {
						hasText: products[ index ].name,
					} );
					await expect( item ).toHaveCount( 1 );
					await expect(
						item
							.locator(
								'.wc-block-components-order-summary-item__quantity'
							)
							.getByText( String( linkedQuantities[ index ] ), {
								exact: true,
							} )
					).toBeVisible();
					await expect(
						item.locator(
							'.wc-block-components-order-summary-item__total-price'
						)
					).toContainText( money( lineTotal( index ) ) );
				}

				await expect( orderSummary ).not.toContainText(
					products[ 2 ].name
				);

				await expect(
					page.getByText( `Coupon: ${ coupon.code }` )
				).toBeVisible();
				await expect(
					page.locator( '.wc-block-components-totals-discount' )
				).toContainText( `-${ money( discount ) }` );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( money( subtotal - discount ) );

				// Leave the shared customer session with an empty cart.
				await page.goto( '/cart' );
				for ( const name of [
					products[ 0 ].name,
					products[ 1 ].name,
				] ) {
					await page
						.getByLabel( `Remove ${ name } from cart` )
						.click();
				}
				await expect(
					page.getByText( 'Your cart is empty' )
				).toBeVisible();
			}
		);
	} );
} );
