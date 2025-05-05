/**
 * Internal dependencies
 */
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { getFakeCustomer, getFakeProduct } from '../../utils/data';
import { setFilterValue } from '../../utils/filters';
import { WC_API_PATH } from '../../utils/api-client';

const test = baseTest.extend( {
	product: async ( { restApi }, use ) => {
		let product;

		// Using dec: 0 to avoid small rounding issues
		await restApi
			.post( `${ WC_API_PATH }/products`, getFakeProduct() )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
	order: async ( { restApi, product }, use ) => {
		const customer = getFakeCustomer();
		let order;

		await restApi
			.post( `${ WC_API_PATH }/orders`, {
				status: 'processing',
				payment_method: 'bacs',
				payment_method_title: 'Direct Bank Transfer',
				set_paid: true,
				billing: customer.billing,
				shipping: customer.shipping,
				line_items: [
					{
						product_id: product.id,
						quantity: 1,
					},
				],
			} )
			.then( ( response ) => {
				order = response.data;
			} );

		await use( order );

		await restApi.delete( `${ WC_API_PATH }/orders/${ order.id }`, {
			force: true,
		} );
	},
} );

test( 'guest shopper can verify their email address after the grace period', async ( {
	page,
	order,
} ) => {
	await test.step( 'navigate to order confirmation page', async () => {
		await page.goto(
			`checkout/order-received/${ order.id }/?key=${ order.order_key }`
		);
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();
	} );

	await test.step( 'simulate cookies cleared, but within 10 minute grace period', async () => {
		// Let's simulate a new browser context (by dropping all cookies), and reload the page. This approximates a
		// scenario where the server can no longer identify the shopper. However, so long as we are within the 10 minute
		// grace period following initial order placement, the 'order received' page should still be rendered.
		await page.context().clearCookies();
		await page.reload();
		await expect(
			page.getByText( 'Your order has been received' )
		).toBeVisible();
	} );

	await test.step( 'simulate cookies cleared, outside 10 minute window', async () => {
		// Let's simulate a scenario where the 10 minute grace period has expired. This time, we expect the shopper to
		// be presented with a request to verify their email address.
		await setFilterValue(
			page,
			'woocommerce_order_email_verification_grace_period',
			0
		);

		// eslint-disable-next-line playwright/no-wait-for-timeout
		await page.waitForTimeout( 2000 ); // needs some time before reload for change to take effect.
		await page.reload();
		await expect(
			page.getByText(
				/confirm the email address linked to the order | verify the email address associated /
			)
		).toBeVisible();
	} );

	await test.step( 'supply incorrect email address for the order, error', async () => {
		// Supplying an email address other than the actual order billing email address will take them back to the same
		// page with an error message.
		await page
			.getByLabel( 'Email address' )
			.fill( 'incorrect@email.address' );
		await page.getByRole( 'button', { name: /Verify|Confirm/ } ).click();
		await expect(
			page.getByText(
				/confirm the email address linked to the order | verify the email address associated /
			)
		).toBeVisible();
		await expect(
			page.getByText( 'We were unable to verify the email address' )
		).toBeVisible();
	} );

	await test.step( 'supply the correct email address for the order, display order confirmation', async () => {
		// However if they supply the *correct* billing email address, they should see the order received page again.
		await page.getByLabel( 'Email address' ).fill( order.billing.email );
		await page.getByRole( 'button', { name: /Verify|Confirm/ } ).click();
		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();
	} );
} );
