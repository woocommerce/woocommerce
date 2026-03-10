/**
 * External dependencies
 */
import { addAProductToCart, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags } from '../../fixtures/fixtures';
import { test as paypalTest } from '../../fixtures/paypal-fixtures';
import { getFakeCustomer, getFakeProduct } from '../../utils/data';
import {
	createClassicCheckoutPage,
	CLASSIC_CHECKOUT_PAGE,
} from '../../utils/pages';
import { wpCLI } from '../../utils/cli';

const PAYPAL_TEST_EMAIL = 'paypal-test@example.com';

const checkoutPages = [
	{ name: 'blocks checkout', slug: 'checkout' },
	CLASSIC_CHECKOUT_PAGE,
];

const test = paypalTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await createClassicCheckoutPage();

		// Enable the PayPal Standard gateway if it is not already enabled.
		const paypalGateway = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/paypal`
		);
		const paypalWasEnabled = paypalGateway.enabled;

		if ( ! paypalWasEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/paypal`, {
				enabled: true,
			} );
		}

		// Ensure a valid PayPal email is configured so the gateway passes
		// the needs_setup() check used by the Orders v2 path.
		await wpCLI(
			`wp option patch update woocommerce_paypal_settings email '${ PAYPAL_TEST_EMAIL }'`
		);

		await page.context().clearCookies();
		await use( page );

		// Restore the gateway's enabled state.
		if ( ! paypalWasEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/paypal`, {
				enabled: false,
			} );
		}
	},

	product: async ( { restApi }, use ) => {
		let product;

		// Use a virtual product to avoid shipping zone requirements.
		await restApi
			.post( `${ WC_API_PATH }/products`, {
				...getFakeProduct( { dec: 0 } ),
				virtual: true,
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );
	},
} );

test.describe(
	'PayPal Standard checkout flow',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		// Run all tests in this suite as a guest (no admin session).
		test.use( { storageState: { cookies: [], origins: [] } } );

		checkoutPages.forEach( ( { name, slug } ) => {
			test(
				`PayPal Standard is available as a payment option at ${ name }`,
				async ( { page, product } ) => {
					await addAProductToCart( page, product.id );
					await page.goto( slug );

					await expect(
						page.getByText( 'PayPal', { exact: true } )
					).toBeVisible( { timeout: 30000 } );
				}
			);
		} );

		test(
			'Selecting PayPal at classic checkout changes the order button to "Proceed to PayPal"',
			async ( { page, product } ) => {
				await addAProductToCart( page, product.id );
				await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

				await page
					.locator( 'label[for="payment_method_paypal"]' )
					.click();

				await expect(
					page.getByRole( 'button', { name: 'Proceed to PayPal' } )
				).toBeVisible( { timeout: 15000 } );
			}
		);

		test(
			'Guest can initiate PayPal payment at classic checkout and is redirected to PayPal',
			async ( { page, product } ) => {
				await addAProductToCart( page, product.id );
				await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

				await test.step( 'Fill in guest billing details', async () => {
					const customer = getFakeCustomer();

					await page
						.getByRole( 'textbox', { name: 'First name' } )
						.fill( customer.billing.first_name );
					await page
						.getByRole( 'textbox', { name: 'Last name' } )
						.fill( customer.billing.last_name );
					await page
						.getByRole( 'textbox', { name: 'Street address' } )
						.fill( customer.billing.address_1 );
					await page
						.getByRole( 'textbox', { name: 'Town / City' } )
						.fill( customer.billing.city );
					await page
						.getByRole( 'textbox', { name: 'ZIP Code' } )
						.fill( customer.billing.postcode );
					await page
						.getByRole( 'textbox', { name: 'Phone' } )
						.fill( customer.billing.phone );
					await page
						.getByRole( 'textbox', { name: 'Email address' } )
						.fill( customer.billing.email );
				} );

				await page
					.locator( 'label[for="payment_method_paypal"]' )
					.click();

				// Capture the checkout AJAX response before the browser navigates
				// away to PayPal, so we can verify the redirect URL without leaving
				// the test environment.
				const checkoutResponsePromise = page.waitForResponse(
					( response ) =>
						response.url().includes( '?wc-ajax=checkout' ) &&
						response.status() === 200,
					{ timeout: 30000 }
				);

				// Abort any navigation to PayPal to keep the test contained.
				await page.route( /paypal\.com/, ( route ) => route.abort() );

				await page
					.getByRole( 'button', { name: 'Proceed to PayPal' } )
					.click();

				const checkoutResponse = await checkoutResponsePromise;
				const checkoutData = await checkoutResponse.json();

				await test.step(
					'Checkout response contains a PayPal redirect URL',
					async () => {
						expect( checkoutData.result ).toBe( 'success' );
						expect( checkoutData.redirect ).toContain(
							'paypal.com'
						);
						expect( checkoutData.redirect ).toContain(
							'cmd=_cart'
						);
					}
				);
			}
		);
	}
);
