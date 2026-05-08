/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';

type OrderProduct = {
	id: number;
};

type Order = {
	id: number;
	order_key: string;
};

const productPrice = '15.99';

test.describe.serial(
	'Customer Review Order page: rate, submit, and verify',
	{ tag: [ tags.HPOS ] },
	() => {
		const products: OrderProduct[] = [];
		let order: Order;
		const customerEmail = `review-${ Date.now() }@example.test`;

		test.beforeAll( async ( { restApi } ) => {
			for ( let i = 0; i < 3; i++ ) {
				const response = await restApi.post(
					`${ WC_API_PATH }/products`,
					{
						name: `Reviewable Product ${ i + 1 }`,
						type: 'simple',
						regular_price: productPrice,
						reviews_allowed: true,
					}
				);
				products.push( {
					id: response.data.id,
				} );
			}

			const orderResponse = await restApi.post(
				`${ WC_API_PATH }/orders`,
				{
					status: 'completed',
					billing: {
						first_name: 'Review',
						last_name: 'Customer',
						email: customerEmail,
					},
					line_items: products.map( ( p ) => ( {
						product_id: p.id,
						quantity: 1,
					} ) ),
				}
			);
			order = orderResponse.data;
		} );

		test.afterAll( async ( { restApi } ) => {
			// Best-effort cleanup: a beforeAll() failure may have left
			// $order or $products partially populated. Swallow per-resource
			// errors so the original test failure stays visible.
			for ( const product of products ) {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/products/${ product.id }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
			if ( order && order.id ) {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/orders/${ order.id }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
		} );

		test( 'guest customer rates two of three products and submits successfully', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			// Block themes render the page title in `wp-block-post-title` with
			// the same text, so scope to the shortcode-rendered heading.
			await expect(
				page.locator( '.woocommerce-review-order__title' )
			).toBeVisible();

			const rows = page.locator( '.woocommerce-review-order__item' );
			await expect( rows ).toHaveCount( products.length );

			// Rate the first product 5 stars by clicking the wrapping label.
			await rows.nth( 0 ).locator( 'label[for$="-5"]' ).click();

			// Rate the second product 4 stars and add review text.
			await rows.nth( 1 ).locator( 'label[for$="-4"]' ).click();
			await rows
				.nth( 1 )
				.locator(
					'textarea.woocommerce-review-order__item-review-textarea'
				)
				.fill( 'Solid build. Recommended.' );

			// Leave the third product untouched.

			// Submit gate should be enabled now.
			const submit = page.locator( '.woocommerce-review-order__submit' );
			await expect( submit ).toBeEnabled();
			await Promise.all( [
				// The form posts as FormData (multipart), so request.postData()
				// is unreliable for action matching. Use the URL + the JSON
				// payload shape (woocommerce_submit_order_reviews always returns
				// { success, data: { results } }) to identify the right response.
				page.waitForResponse( async ( response ) => {
					if (
						! response.url().includes( 'admin-ajax.php' ) ||
						response.request().method() !== 'POST' ||
						! response.ok()
					) {
						return false;
					}
					try {
						const body = await response.json();
						return (
							body &&
							typeof body === 'object' &&
							body.data &&
							Object.prototype.hasOwnProperty.call(
								body.data,
								'results'
							)
						);
					} catch ( _err ) {
						return false;
					}
				} ),
				submit.click(),
			] );

			// First two rows should report a successful submission. Either
			// '--ok' (auto-approved) or '--pending_moderation' (when the
			// comment_moderation option is on) counts as success; '--error'
			// would indicate a real failure. Test environment-agnostic so
			// it doesn't depend on global comment_moderation state.
			const successSelector =
				'.woocommerce-review-order__item-status--ok, .woocommerce-review-order__item-status--pending_moderation';
			await expect(
				rows.nth( 0 ).locator( successSelector )
			).toBeVisible();
			await expect(
				rows.nth( 1 ).locator( successSelector )
			).toBeVisible();
			// Third row was never rated and should have no status note at all.
			await expect(
				rows
					.nth( 2 )
					.locator( '.woocommerce-review-order__item-status' )
			).toHaveCount( 0 );
		} );

		test( 'reloading the page locks the previously reviewed rows', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			// The first two products should now render the locked variant.
			await expect(
				page.locator( '.woocommerce-review-order__item--reviewed' )
			).toHaveCount( 2 );

			// The third product should still be a form row.
			await expect(
				page.locator(
					'.woocommerce-review-order__item:not(.woocommerce-review-order__item--reviewed)'
				)
			).toHaveCount( 1 );
		} );

		test( 'mismatched key renders a 404 page', async ( { page } ) => {
			const response = await page.goto(
				`/review-order/${ order.id }/?key=wc_order_definitelywrong`
			);
			expect( response?.status() ).toBe( 404 );
		} );
	}
);
