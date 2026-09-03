/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, request, tags } from '../../fixtures/fixtures';
import {
	createOutOfStockProduct,
	resetBISOptions,
	setBISOptions,
	signUpOnProductPage,
	test,
} from '../../utils/back-in-stock-notifications';
import { logInFromMyAccount } from '../../utils/login';

const MY_ACCOUNT_ENDPOINT = 'my-account/stock-notifications/';
const TABLE = '.woocommerce-customer-stock-notifications-table';
const PENDING_TABLE = `${ TABLE }--pending`;
const ACTIVE_TABLE = `${ TABLE }--active`;

/**
 * A customer account owned by a single test.
 *
 * The suite's shared customer accumulates sign-ups as the specs run, so row
 * counts and the empty state would depend on execution order. Each test gets a
 * throwaway account instead, and deletes it afterwards.
 */
type TestCustomer = {
	id: number;
	username: string;
	password: string;
};

/**
 * Create a customer account for one test to sign in as.
 *
 * @param {Object} restApi Authenticated REST client from the `restApi` fixture.
 */
async function createTestCustomer( restApi ): Promise< TestCustomer > {
	const suffix = `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;
	const username = `bis-my-account-${ suffix }`;
	const password = 'password';

	const response = await restApi.post< { id: number } >(
		`${ WC_API_PATH }/customers`,
		{
			email: `${ username }@woocommercecoree2etestsuite.com`,
			username,
			password,
		}
	);

	return { id: response.data.id, username, password };
}

test.describe(
	'Back in Stock Notifications — My Account',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.afterAll( async ( { baseURL } ) => {
			await resetBISOptions( request, baseURL! );
		} );

		test.describe( 'Logged-in customer with signups', () => {
			// Signed out, so each test can sign in as the account it owns.
			test.use( { storageState: { cookies: [], origins: [] } } );

			test( 'renders a pending and an active notification in the tab', async ( {
				page,
				baseURL,
				product,
				restApi,
			} ) => {
				const customer = await createTestCustomer( restApi );
				// Single opt-in so the first signup lands as "active" straight away.
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
				} );

				const secondProduct = await createOutOfStockProduct( restApi );

				try {
					await page.goto( 'my-account/' );
					await logInFromMyAccount(
						page,
						customer.username,
						customer.password
					);

					// Row 1: signup while double opt-in is off → active.
					await page.goto( product.permalink );
					await signUpOnProductPage( page );
					await expect(
						page.getByText(
							/You have successfully signed up|You have already joined this waitlist/i
						)
					).toBeVisible();

					// Row 2: flip double opt-in on and sign up again → pending.
					await setBISOptions( request, baseURL!, {
						allowSignups: true,
						doubleOptIn: true,
					} );
					await page.goto( secondProduct.permalink );
					await signUpOnProductPage( page );

					await page.goto( MY_ACCOUNT_ENDPOINT );

					await expect(
						page.getByRole( 'heading', {
							name: 'Stock notifications',
						} )
					).toBeVisible();

					// The unconfirmed signup sits in its own "Awaiting confirmation" table
					// with Resend email + Cancel actions.
					await expect(
						page.getByRole( 'heading', {
							name: 'Awaiting confirmation',
						} )
					).toBeVisible();
					const pendingTable = page.locator( PENDING_TABLE );
					await expect( pendingTable ).toBeVisible();
					const pendingRow = pendingTable
						.locator( 'tbody tr' )
						.filter( {
							has: page.getByRole( 'link', {
								name: secondProduct.name,
								exact: true,
							} ),
						} );
					await expect( pendingRow ).toBeVisible();
					await expect(
						pendingRow.getByRole( 'button', {
							name: 'Resend email',
						} )
					).toBeVisible();
					await expect(
						pendingRow.getByRole( 'button', { name: 'Cancel' } )
					).toBeVisible();
					await expect(
						pendingTable.locator( 'tbody tr' )
					).toHaveCount( 1 );

					// The confirmed signup sits in the "Active notifications" table.
					await expect(
						page.getByRole( 'heading', {
							name: 'Active notifications',
						} )
					).toBeVisible();
					const activeTable = page.locator( ACTIVE_TABLE );
					await expect( activeTable ).toBeVisible();
					const activeRow = activeTable
						.locator( 'tbody tr' )
						.filter( {
							has: page.getByRole( 'link', {
								name: product.name,
								exact: true,
							} ),
						} );
					await expect( activeRow ).toBeVisible();
					await expect(
						activeRow.getByRole( 'button', { name: 'Cancel' } )
					).toBeVisible();
					await expect(
						activeRow.getByRole( 'button', {
							name: 'Resend email',
						} )
					).toHaveCount( 0 );
					await expect(
						activeTable.locator( 'tbody tr' )
					).toHaveCount( 1 );
				} finally {
					await restApi.delete(
						`${ WC_API_PATH }/products/${ secondProduct.id }`,
						{ force: true }
					);
					await restApi.delete(
						`${ WC_API_PATH }/customers/${ customer.id }`,
						{ force: true }
					);
				}
			} );

			test( 'resend email click sends the verification email and stays on the tab', async ( {
				page,
				baseURL,
				product,
				restApi,
			} ) => {
				const customer = await createTestCustomer( restApi );
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
				} );

				try {
					await page.goto( 'my-account/' );
					await logInFromMyAccount(
						page,
						customer.username,
						customer.password
					);

					await page.goto( product.permalink );
					await signUpOnProductPage( page );

					await page.goto( MY_ACCOUNT_ENDPOINT );

					// Only a pending signup exists, so the pending table renders on its
					// own — no active table and no "nothing signed up" notice.
					await expect( page.locator( PENDING_TABLE ) ).toBeVisible();
					await expect( page.locator( ACTIVE_TABLE ) ).toHaveCount(
						0
					);
					await expect(
						page.getByText(
							"You haven't signed up for any back-in-stock notifications yet."
						)
					).toHaveCount( 0 );

					const row = page
						.locator( `${ PENDING_TABLE } tbody tr` )
						.filter( {
							has: page.getByRole( 'link', {
								name: product.name,
								exact: true,
							} ),
						} );
					await expect( row ).toBeVisible();

					await row
						.getByRole( 'button', { name: 'Resend email' } )
						.click();

					// The redirect after the POST lands us back on the same tab with a notice.
					await expect(
						page.getByRole( 'heading', {
							name: 'Stock notifications',
						} )
					).toBeVisible();
					await expect( page ).toHaveURL(
						new RegExp( `${ MY_ACCOUNT_ENDPOINT }$` )
					);
					await expect(
						page.getByText( /Verification email sent to/ )
					).toBeVisible();

					// The row is still pending and still offers the actions.
					await expect(
						row.getByRole( 'button', { name: 'Resend email' } )
					).toBeVisible();
				} finally {
					await restApi.delete(
						`${ WC_API_PATH }/customers/${ customer.id }`,
						{ force: true }
					);
				}
			} );

			test( 'cancel click removes the row from the My Account list', async ( {
				page,
				baseURL,
				product,
				restApi,
			} ) => {
				const customer = await createTestCustomer( restApi );
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
				} );

				try {
					await page.goto( 'my-account/' );
					await logInFromMyAccount(
						page,
						customer.username,
						customer.password
					);

					await page.goto( product.permalink );
					await signUpOnProductPage( page );

					await page.goto( MY_ACCOUNT_ENDPOINT );

					// Double opt-in is on, so the signup lands in the pending table.
					const row = page
						.locator( `${ PENDING_TABLE } tbody tr` )
						.filter( {
							has: page.getByRole( 'link', {
								name: product.name,
								exact: true,
							} ),
						} );
					await expect( row ).toBeVisible();

					await row.getByRole( 'button', { name: 'Cancel' } ).click();

					// The redirect after the POST lands us back on the same tab.
					await expect(
						page.getByRole( 'heading', {
							name: 'Stock notifications',
						} )
					).toBeVisible();

					// The cancelled row is filtered out — only PENDING/ACTIVE rows render.
					await expect( row ).toHaveCount( 0 );

					// And a notice confirms the cancellation.
					await expect(
						page.getByText(
							`Back in stock notification for "${ product.name }" cancelled.`
						)
					).toBeVisible();
				} finally {
					await restApi.delete(
						`${ WC_API_PATH }/customers/${ customer.id }`,
						{ force: true }
					);
				}
			} );
		} );

		test.describe( 'Logged-in customer with no signups', () => {
			test.use( { storageState: { cookies: [], origins: [] } } );

			test( 'renders the empty state and a catalog link', async ( {
				page,
				restApi,
			} ) => {
				const customer = await createTestCustomer( restApi );

				try {
					await page.goto( 'my-account/' );
					await logInFromMyAccount(
						page,
						customer.username,
						customer.password
					);

					await page.goto( MY_ACCOUNT_ENDPOINT );

					await expect(
						page.getByRole( 'heading', {
							name: 'Stock notifications',
						} )
					).toBeVisible();

					await expect(
						page.getByText(
							"You haven't signed up for any back-in-stock notifications yet."
						)
					).toBeVisible();

					await expect(
						page.getByRole( 'link', { name: 'Browse products' } )
					).toBeVisible();

					await expect( page.locator( TABLE ) ).toHaveCount( 0 );
				} finally {
					await restApi.delete(
						`${ WC_API_PATH }/customers/${ customer.id }`,
						{ force: true }
					);
				}
			} );
		} );

		test.describe( 'Anonymous visitor', () => {
			test.use( { storageState: { cookies: [], origins: [] } } );

			test( 'is redirected to the login form', async ( { page } ) => {
				await page.goto( MY_ACCOUNT_ENDPOINT );

				// Standard WC behaviour: unauthenticated account endpoints show
				// the login form on the My Account page.
				await expect(
					page.getByRole( 'heading', { name: 'Login' } )
				).toBeVisible();
				await expect(
					page.getByLabel( 'Username or email address' )
				).toBeVisible();
			} );
		} );
	}
);
