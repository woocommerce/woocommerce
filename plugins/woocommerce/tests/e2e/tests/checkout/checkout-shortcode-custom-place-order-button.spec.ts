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
import { setGatewayEnabled } from '../../utils/payment-gateways';

const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await createClassicCheckoutPage();

		// Activating the custom place order button test plugin (mapped in .wp-env.json).
		await wpCLI(
			'wp plugin activate woocommerce-blocks-test-plugins/custom-place-order-button-test.php'
		);

		// The custom plugin comes with a custom gateway - enabling it through CLI to simplify our lives.
		await wpCLI(
			`wp option set woocommerce_test-custom-button_settings --format=json '{"enabled":"yes"}'`
		);

		// COD is enabled globally in site setup; guard defensively in case it is
		// somehow off so it can _also_ be used during checkout.
		const codWasEnabled = await setGatewayEnabled( restApi, 'cod', true );

		await page.context().clearCookies();
		await use( page );

		// Cleanup: deactivate the test plugin so its custom gateway stops being
		// registered on every checkout/order-pay page for the rest of the run.
		// The gateway hardcodes `enabled = 'yes'` in its constructor, so deleting
		// the option alone would NOT disable it — only deactivation does.
		await wpCLI(
			'wp plugin deactivate woocommerce-blocks-test-plugins/custom-place-order-button-test.php'
		);
		await wpCLI(
			`wp option delete woocommerce_test-custom-button_settings`
		);

		await setGatewayEnabled( restApi, 'cod', codWasEnabled );
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
	// The shared site setup attaches a free shipping method to zone 0, so every
	// cart needs shipping. On the shortcode checkout that renders the collapsed
	// "Ship to a different address?" block with hidden, empty required shipping
	// fields. A custom place-order button's client-side validate() counts those
	// hidden invalid fields and silently skips submission, so the order is never
	// placed. Remove the baseline shipping for this spec so the cart does not
	// need shipping, then restore it afterwards.
	// TODO: Remove this workaround once the validation fix is merged.
	// Bug fixed in https://github.com/woocommerce/woocommerce/pull/65933.
	test.beforeAll( async ( { restApi } ) => {
		const { data: methods } = await restApi.get<
			{ instance_id: number }[]
		>( `${ WC_API_PATH }/shipping/zones/0/methods` );

		for ( const method of methods ) {
			await restApi.delete(
				`${ WC_API_PATH }/shipping/zones/0/methods/${ method.instance_id }`,
				{ force: true }
			);
		}
	} );

	test.afterAll( async ( { restApi } ) => {
		// Restore the baseline free shipping method on zone 0 (mirrors site setup).
		await restApi.post( `${ WC_API_PATH }/shipping/zones/0/methods`, {
			method_id: 'free_shipping',
		} );
	} );

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
