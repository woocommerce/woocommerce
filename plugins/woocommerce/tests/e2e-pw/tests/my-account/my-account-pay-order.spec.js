/**
 * Internal dependencies
 */
import { tags, test, expect } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

const randomNum = new Date().getTime().toString();
const customer = {
	username: `customer${ randomNum }`,
	password: 'password',
	email: `customer${ randomNum }@woocommercecoree2etestsuite.com`,
};

test.describe(
	'Customer can pay for their order through My Account',
	{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
	() => {
		let productId, orderId;

		test.beforeAll( async ( { restApi } ) => {
			// add product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
					name: 'Pay Order My Account',
					type: 'simple',
					regular_price: '15.77',
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			// create customer
			await restApi
				.post( `${ WC_API_PATH }/customers`, customer )
				.then( ( response ) => ( customer.id = response.data.id ) );
			// create an order
			await restApi
				.post( `${ WC_API_PATH }/orders`, {
					set_paid: false,
					billing: {
						first_name: 'Jane',
						last_name: 'Smith',
						email: customer.email,
					},
					line_items: [
						{
							product_id: productId,
							quantity: 1,
						},
					],
				} )
				.then( ( response ) => {
					orderId = response.data.id;
				} );
			// once the order is created, assign it to our existing customer user
			await restApi.put( `${ WC_API_PATH }/orders/${ orderId }`, {
				customer_id: customer.id,
			} );
			// enable COD payment
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: true,
			} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
			await restApi.delete( `${ WC_API_PATH }/orders/${ orderId }`, {
				force: true,
			} );
			await restApi.delete(
				`${ WC_API_PATH }/customers/${ customer.id }`,
				{ force: true }
			);
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: false,
			} );
		} );

		test( 'allows customer to pay for their order in My Account', async ( {
			page,
		} ) => {
			await page.goto( 'my-account/orders/' );
			// sign in as the "customer" user
			await page.locator( '#username' ).fill( customer.username );
			await page.locator( '#password' ).fill( customer.password );
			await page.locator( 'text=Log in' ).click();

			await page.locator( 'a.pay' ).click();

			await expect(
				page.getByRole( 'button', { name: 'Pay for order' } )
			).toBeVisible();

			// Handle notice if present
			await page.addLocatorHandler(
				page.getByRole( 'link', { name: 'Dismiss' } ),
				async () => {
					await page.getByRole( 'link', { name: 'Dismiss' } ).click();
				}
			);

			await page.locator( '#place_order' ).click();

			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();
		} );
	}
);
