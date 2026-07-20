/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

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
import { setGatewayEnabled } from '../../utils/payment-gateways';
import { wpCLI } from '../../utils/cli';

// A syntactically valid PayPal receiver email so the gateway passes its
// needs_setup() check and is offered at checkout.
const PAYPAL_TEST_EMAIL = 'paypal-standard-e2e@example.com';

/**
 * Adds a product to the session cart via the classic add-to-cart endpoint.
 *
 * The shared `addAProductToCart` util waits for a `wc/store/v1/cart` response,
 * which this store's mini-cart doesn't emit on the shop page, so we add the
 * product directly instead and confirm it landed in the cart.
 *
 * @param {Page}   page      The Playwright page object.
 * @param {number} productId The product ID to add to the cart.
 */
async function addProductToCart( page: Page, productId: number ) {
	await page.goto( `?add-to-cart=${ productId }` );
	await expect(
		page.getByRole( 'button', { name: /items? in the cart/i } )
	).toContainText( '1' );
}

const test = paypalTest.extend< {
	product: { id: number; name: string; price: string };
} >( {
	// Layer PayPal-Standard checkout setup on top of the paypal fixture, which
	// already toggles `_should_load` and provides the admin storage state used
	// by restApi. We enable the gateway and configure a receiver email so it is
	// available on the frontend, then restore the prior state afterwards.
	page: async ( { page, restApi }, use ) => {
		await createClassicCheckoutPage();

		const paypalWasEnabled = await setGatewayEnabled(
			restApi,
			'paypal',
			true
		);

		await wpCLI(
			`wp option patch update woocommerce_paypal_settings email '${ PAYPAL_TEST_EMAIL }'`
		);

		await page.context().clearCookies();
		await use( page );

		await setGatewayEnabled( restApi, 'paypal', paypalWasEnabled );
	},

	// A virtual product so checkout has no shipping requirements.
	product: async ( { restApi }, use ) => {
		const response = await restApi.post( `${ WC_API_PATH }/products`, {
			...getFakeProduct( { dec: 0 } ),
			virtual: true,
		} );
		const product = response.data;

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

test.describe(
	'PayPal Standard checkout flow',
	{ tag: [ tags.PAYMENTS, tags.PAYPAL ] },
	() => {
		// Exercise the customer-facing checkout as a guest.
		test.use( { storageState: { cookies: [], origins: [] } } );

		const visibilityOptions = { timeout: 30000 };

		// PayPal Standard is a classic gateway with no Cart/Checkout Blocks
		// payment integration, so it is only offered on the shortcode checkout.
		test( 'PayPal Standard is offered as a payment method at classic checkout', async ( {
			page,
			product,
		} ) => {
			await addProductToCart( page, product.id );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

			// Assert on the label: WooCommerce renders the payment-method radio
			// input visually hidden, so only the label is "visible".
			await expect(
				page.locator( 'label[for="payment_method_paypal"]' )
			).toBeVisible( visibilityOptions );
		} );

		test( 'Selecting PayPal at classic checkout changes the order button to "Proceed to PayPal"', async ( {
			page,
			product,
		} ) => {
			await addProductToCart( page, product.id );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

			await page.locator( 'label[for="payment_method_paypal"]' ).click();

			await expect(
				page.getByRole( 'button', { name: 'Proceed to PayPal' } )
			).toBeVisible( visibilityOptions );
		} );

		test( 'Guest checkout with PayPal Standard redirects to PayPal', async ( {
			page,
			product,
		} ) => {
			await addProductToCart( page, product.id );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

			await test.step( 'Fill guest billing details', async () => {
				const { billing } = getFakeCustomer();

				await page
					.getByRole( 'textbox', { name: 'First name' } )
					.fill( billing.first_name );
				await page
					.getByRole( 'textbox', { name: 'Last name' } )
					.fill( billing.last_name );
				await page
					.getByRole( 'textbox', { name: 'Street address' } )
					.fill( billing.address_1 );
				await page
					.getByRole( 'textbox', { name: 'Town / City' } )
					.fill( billing.city );
				await page
					.getByRole( 'textbox', { name: 'ZIP Code' } )
					.fill( billing.postcode );
				await page
					.getByRole( 'textbox', { name: 'Phone' } )
					.fill( billing.phone );
				await page
					.getByRole( 'textbox', { name: 'Email address' } )
					.fill( billing.email );
			} );

			await page.locator( 'label[for="payment_method_paypal"]' ).click();

			// Keep the test self-contained: capture the navigation request to
			// PayPal (which carries the cart redirect URL) and abort it so the
			// browser never actually leaves for PayPal's servers.
			await page.route( /paypal\.com/, ( route ) => route.abort() );
			const paypalRequestPromise = page.waitForRequest( /paypal\.com/ );

			await page
				.getByRole( 'button', { name: 'Proceed to PayPal' } )
				.click();

			const paypalUrl = ( await paypalRequestPromise ).url();

			expect( paypalUrl ).toContain( 'paypal.com' );
			expect( paypalUrl ).toContain( 'cmd=_cart' );
		} );
	}
);
