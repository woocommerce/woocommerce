/* eslint-disable playwright/expect-expect */
/**
 * External dependencies
 */
import { faker } from '@faker-js/faker';
// @ts-expect-error - @woocommerce/e2e-utils-playwright is not typed
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';
import { Locator } from '@playwright/test';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { admin } from '../../test-data/data';
import { expectEmail, expectEmailContent } from '../../utils/email';
import { setFeatureEmailImprovementsFlag } from './helpers/set-email-improvements-feature-flag';

interface OrderBilling {
	email: string;
}

interface Order {
	id: number;
	billing: OrderBilling;
}

const test = baseTest.extend< { order: Order } >( {
	storageState: ADMIN_STATE_PATH,
	order: async ( { restApi }, use ) => {
		let order: Order;

		await restApi
			.post( `${ WC_API_PATH }/orders`, {
				status: 'processing',
				billing: { email: faker.internet.exampleEmail() },
			} )
			.then( ( response: { data: Order } ) => {
				order = response.data;
			} )
			.catch( ( error: Error ) => {
				console.error( error );
			} );

		await use( order! );

		await restApi.delete( `${ WC_API_PATH }/orders/${ order!.id }`, {
			force: true,
		} );
	},
} );

test.beforeEach( async ( { baseURL } ) => {
	await setFeatureEmailImprovementsFlag( baseURL as string, 'no' );
} );

interface EmailTestCase {
	status: string;
	role: string;
	subject: string;
	content: string;
}

const emailTestCases: EmailTestCase[] = [
	{
		status: 'processing',
		role: 'customer',
		subject: 'Your .+ order has been received!',
		content: 'Thank you for your order',
	},
	{
		status: 'processing',
		role: 'admin',
		subject: 'New order #ORDER_ID',
		content: 'Congratulations on the sale',
	},
	{
		status: 'completed',
		role: 'customer',
		subject: 'Your .+ order is now complete',
		content: 'Thanks for shopping with us',
	},
	{
		status: 'cancelled',
		role: 'admin',
		subject: 'Order #ORDER_ID has been cancelled',
		content: 'Thanks for reading',
	},
];

emailTestCases.forEach( ( { role, status, subject, content } ) => {
	test( `${ role } receives email for ${ status } order`, async ( {
		page,
		restApi,
		order,
	} ) => {
		// Inject the order id into the expected subject and make it a regex
		const subjectRegex = new RegExp(
			subject.replace( 'ORDER_ID', `${ order.id }` )
		);

		await restApi
			.put( `${ WC_API_PATH }/orders/${ order.id }`, {
				status,
			} )
			.catch( ( error: Error ) => {
				console.error( error );
			} );

		let orderStatus: string;
		await restApi
			.get( `${ WC_API_PATH }/orders/${ order.id }` )
			.then( ( response: { data: { status: string } } ) => {
				orderStatus = response.data.status;
			} );

		await expect( orderStatus! ).toEqual( status );

		let emailRow: Locator;
		await test.step( 'check the email exists', async () => {
			emailRow = await expectEmail(
				page,
				role === 'customer' ? order.billing.email : admin.email,
				subjectRegex
			);
		} );

		await test.step( 'check the email content', async () => {
			await emailRow.getByRole( 'button', { name: 'View log' } ).click();

			await expectEmailContent(
				page,
				role === 'customer' ? order.billing.email : admin.email,
				subjectRegex,
				content
			);
		} );
	} );
} );

test( 'Merchant can resend order details to customer', async ( {
	order,
	page,
} ) => {
	await page.goto(
		`wp-admin/admin.php?page=wc-orders&action=edit&id=${ order.id }`
	);
	await page
		.locator( 'li#actions > select' )
		.selectOption( 'send_order_details' );
	await page.locator( 'button.wc-reload' ).click();
	await expect(
		page.locator( '#message' ).filter( { hasText: 'Order updated' } )
	).toBeVisible();

	await expectEmail(
		page,
		order.billing.email,
		new RegExp( `Details for order #${ order.id }` )
	);
} );
