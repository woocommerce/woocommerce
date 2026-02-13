/**
 * External dependencies
 */
import {
	addAProductToCart,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { getFakeCustomer, getFakeProduct } from '../../utils/data';
import {
	createClassicCheckoutPage,
	CLASSIC_CHECKOUT_PAGE,
} from '../../utils/pages';
import { wpCLI } from '../../utils/cli';

const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await createClassicCheckoutPage();

		// Activating the custom place order button test plugin (mapped in .wp-env.json).
		await wpCLI( 'wp plugin activate custom-place-order-button-test' );

		// The custom plugin comes with a custom gateway - enabling it through CLI to simplify our lives.
		await wpCLI(
			`wp option set woocommerce_test-custom-button_settings --format=json '{"enabled":"yes"}'`
		);

		// Ensuring that COD is enabled, so it can _also_ be used during checkout.
		const codResponse = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/cod`
		);
		const codEnabled = codResponse.enabled;

		if ( ! codEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: true,
			} );
		}

		await page.context().clearCookies();
		await use( page );

		// Cleanup: restoring COD and removing the custom gateway
		await wpCLI(
			`wp option delete woocommerce_test-custom-button_settings`
		);

		if ( ! codEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: codEnabled,
			} );
		}
	},
	product: async ( { restApi }, use ) => {
		let product;

		await restApi
			.post( `${ WC_API_PATH }/products`, getFakeProduct( { dec: 0 } ) )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

test.describe( 'Shortcode Checkout Custom Place Order Button', () => {
	test(
		'clicking custom button triggers validation when form is invalid',
		{ tag: [ tags.PAYMENTS ] },
		async ( { page, product } ) => {
			await addAProductToCart( page, product.id, 1 );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

			// Selecting the custom button gateway without filling in the form.
			await page.getByText( 'Test Custom Button Payment' ).click();

			// Waiting for the custom button to appear.
			await expect(
				page.getByTestId( 'custom-place-order-button' )
			).toBeVisible();

			// Clicking the custom button without filling required fields.
			await page.getByTestId( 'custom-place-order-button' ).click();

			// Ensuring validation errors are shown.
			await expect(
				page.locator( '.woocommerce-invalid' ).first()
			).toBeVisible();

			// Ensuring we're still on checkout (order not submitted).
			await expect( page ).not.toHaveURL( /order-received/ );
		}
	);

	test(
		'switching between gateways shows/hides custom button',
		{ tag: [ tags.PAYMENTS ] },
		async ( { page, product } ) => {
			await addAProductToCart( page, product.id, 1 );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

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

			// Selecting Cash on Delivery first.
			await page.getByText( 'Cash on delivery' ).click();

			// Ensuring the default button is visible and the custom button is not.
			await expect( page.locator( '#place_order' ) ).toBeVisible();
			await expect(
				page.getByTestId( 'custom-place-order-button' )
			).toBeHidden();

			// Switching to the custom button gateway.
			await page.getByText( 'Test Custom Button Payment' ).click();

			await page.waitForFunction( () => {
				const form = document.querySelector( 'form.checkout' );
				return (
					form &&
					form.classList.contains( 'has-custom-place-order-button' )
				);
			} );

			// Ensuring the custom button is visible and the default one is hidden.
			await expect(
				page.getByTestId( 'custom-place-order-button' )
			).toBeVisible();
			await expect( page.locator( '#place_order' ) ).toBeHidden();

			// Switching back to Cash on Delivery.
			await page.getByText( 'Cash on delivery' ).click();

			await page.waitForFunction( () => {
				const form = document.querySelector( 'form.checkout' );
				return (
					form &&
					! form.classList.contains( 'has-custom-place-order-button' )
				);
			} );

			// Ensuring the default is visible and the custom one is hidden.
			await expect( page.locator( '#place_order' ) ).toBeVisible();
			await expect(
				page.getByTestId( 'custom-place-order-button' )
			).toBeHidden();
		}
	);

	test(
		'clicking custom button submits order when form is valid',
		{ tag: [ tags.PAYMENTS, tags.HPOS ] },
		async ( { page, product } ) => {
			await addAProductToCart( page, product.id, 1 );
			await page.goto( CLASSIC_CHECKOUT_PAGE.slug );

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

			// Selecting the custom button gateway.
			await page.getByText( 'Test Custom Button Payment' ).click();

			// Waiting for the custom button to appear.
			await expect(
				page.getByTestId( 'custom-place-order-button' )
			).toBeVisible();

			await page.getByTestId( 'custom-place-order-button' ).click();

			// Ensuring the order was placed successfully.
			await expect( page ).toHaveURL( /order-received/ );
			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();
		}
	);
} );
