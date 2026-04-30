/**
 * Internal dependencies
 */
import {
	expect,
	request,
	tags,
	test as baseTest,
} from '../../fixtures/fixtures';
import { CUSTOMER_STATE_PATH } from '../../playwright.config';
import {
	BIS_OPTIONS,
	createOutOfStockProduct,
	setBISOptions,
	signUpOnProductPage,
} from '../../utils/back-in-stock-notifications';
import { deleteOption } from '../../utils/options';

const test = baseTest.extend( {
	product: async ( { restApi }, use ) => {
		const product = await createOutOfStockProduct( restApi, {
			namePrefix: 'BIS MyAccount',
		} );
		await use( product );
		await product.cleanup();
	},
} );

test.describe(
	'Back in Stock Notifications — My Account',
	{ tag: [ tags.SERVICES ] },
	() => {
		test.afterEach( async ( { baseURL } ) => {
			for ( const option of Object.values( BIS_OPTIONS ) ) {
				await deleteOption( request, baseURL!, option );
			}
		} );

		test.describe( 'Logged-in customer with signups', () => {
			test.use( { storageState: CUSTOMER_STATE_PATH } );

			test.beforeEach( async ( { baseURL } ) => {
				// Single opt-in so the signup lands as "active" immediately —
				// gives us one active row. The second row is created with
				// double opt-in on, so it stays "pending".
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: false,
				} );
			} );

			test( 'renders a pending and an active notification in the tab', async ( {
				page,
				baseURL,
				product,
				restApi,
			} ) => {
				// Row 1: signup while double opt-in is off → active.
				await page.goto( product.permalink );
				await signUpOnProductPage( page );
				await expect(
					page.getByText(
						/You have successfully signed up|You have already joined this waitlist/i
					)
				).toBeVisible();

				// Row 2: flip double opt-in on, create a second product, sign
				// up again → pending.
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
				} );
				const secondProduct = await createOutOfStockProduct( restApi, {
					namePrefix: 'BIS MyAccount Pending',
				} );
				try {
					await page.goto( secondProduct.permalink );
					await signUpOnProductPage( page );

					await page.goto(
						'my-account/back-in-stock-notifications/'
					);

					// The page renders the expected heading.
					await expect(
						page.getByRole( 'heading', {
							name: 'Stock notifications',
						} )
					).toBeVisible();

					// Both products appear as rows.
					const table = page.locator(
						'.woocommerce-back-in-stock-notifications-table'
					);
					await expect( table ).toBeVisible();
					await expect(
						table.getByRole( 'link', {
							name: new RegExp( product.name, 'i' ),
						} )
					).toBeVisible();
					await expect(
						table.getByRole( 'link', {
							name: new RegExp( secondProduct.name, 'i' ),
						} )
					).toBeVisible();

					// Exactly two rows — both signups are PENDING/ACTIVE so neither is filtered out.
					await expect( table.locator( 'tbody tr' ) ).toHaveCount(
						2
					);
				} finally {
					await secondProduct.cleanup();
				}
			} );

			test( 'cancel click removes the row from the My Account list', async ( {
				page,
				baseURL,
				restApi,
			} ) => {
				// Arrange: create a product + one pending signup for the
				// logged-in customer.
				await setBISOptions( request, baseURL!, {
					allowSignups: true,
					doubleOptIn: true,
				} );
				const pendingProduct = await createOutOfStockProduct( restApi, {
					namePrefix: 'BIS MyAccount Cancel',
				} );
				try {
					await page.goto( pendingProduct.permalink );
					await signUpOnProductPage( page );

					await page.goto(
						'my-account/back-in-stock-notifications/'
					);

					const row = page
						.locator(
							'.woocommerce-back-in-stock-notifications-table tbody tr'
						)
						.filter( {
							has: page.getByRole( 'link', {
								name: new RegExp( pendingProduct.name, 'i' ),
							} ),
						} );
					await expect( row ).toBeVisible();

					await row.getByRole( 'button', { name: 'Cancel' } ).click();

					// The refresh after the POST lands us back on the same tab.
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
							new RegExp(
								`back in stock notification.*${ pendingProduct.name }.*cancelled`,
								'i'
							)
						)
					).toBeVisible();
				} finally {
					await pendingProduct.cleanup();
				}
			} );
		} );

		test.describe( 'Logged-in customer with no signups', () => {
			test.use( { storageState: CUSTOMER_STATE_PATH } );

			test( 'renders the empty state and a catalog link', async ( {
				page,
			} ) => {
				await page.goto( 'my-account/back-in-stock-notifications/' );

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

				await expect(
					page.locator(
						'.woocommerce-back-in-stock-notifications-table'
					)
				).toHaveCount( 0 );
			} );
		} );

		test.describe( 'Anonymous visitor', () => {
			test.use( { storageState: { cookies: [], origins: [] } } );

			test( 'is redirected to the login form', async ( { page } ) => {
				await page.goto( 'my-account/back-in-stock-notifications/' );

				// Standard WC behaviour: unauthenticated account endpoints show
				// the login form on the My Account page.
				await expect(
					page.getByRole( 'heading', { name: 'Login' } )
				).toBeVisible();
				await expect(
					page.getByLabel( /Username or email address/i )
				).toBeVisible();
			} );
		} );
	}
);
