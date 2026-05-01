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
	name: string;
};

type Order = {
	id: number;
	order_key: string;
};

const productPrice = '15.99';

test.describe(
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
					name: response.data.name,
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
			for ( const product of products ) {
				await restApi.delete(
					`${ WC_API_PATH }/products/${ product.id }`,
					{ force: true }
				);
			}
			await restApi.delete( `${ WC_API_PATH }/orders/${ order.id }`, {
				force: true,
			} );
		} );

		test( 'guest customer rates two of three products and submits successfully', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			await expect(
				page.getByRole( 'heading', { name: 'Review your order' } )
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
				page.waitForResponse(
					( response ) =>
						response.url().includes( 'admin-ajax.php' ) &&
						response.request().method() === 'POST'
				),
				submit.click(),
			] );

			// First two rows should report success status.
			await expect(
				rows
					.nth( 0 )
					.locator( '.woocommerce-review-order__item-status' )
			).toBeVisible();
			await expect(
				rows
					.nth( 1 )
					.locator( '.woocommerce-review-order__item-status' )
			).toBeVisible();
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
