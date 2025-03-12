/**
 * External dependencies
 */
import {
	addAProductToCart,
	fillBillingCheckoutBlocks,
	fillShippingCheckoutBlocks,
} from '@woocommerce/e2e-utils-playwright';
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { getFakeCustomer, getFakeProduct } from '../../utils/data';
import {
	createBlocksCheckoutPage,
	BLOCKS_CHECKOUT_PAGE,
} from '../../utils/pages';
import { logInFromMyAccount } from '../../utils/login';
import { updateIfNeeded, resetValue } from '../../utils/settings';
import { WC_API_PATH } from '../../utils/api-client';

//todo handle other countries and states than the default (US, CA) when filling the addresses

const checkoutPages = [
	{ name: 'classic checkout', slug: 'checkout' },
	BLOCKS_CHECKOUT_PAGE,
];

/* region helpers */
function isBlocksCheckout( page ) {
	return page.url().includes( BLOCKS_CHECKOUT_PAGE.slug );
}

async function checkOrderDetails( page, product, qty, tax ) {
	const expectedTotalPrice = (
		parseFloat( product.price ) *
		qty *
		( 1 + parseFloat( tax.rate ) / 100 )
	).toLocaleString( 'en-US', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	} );

	if ( page.url().includes( BLOCKS_CHECKOUT_PAGE.slug ) ) {
		await expect(
			page.getByRole( 'heading', { name: product.name } )
		).toBeVisible();

		await expect(
			page.locator( '.wc-block-components-order-summary-item__quantity' )
		).toContainText( qty.toString() );

		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( expectedTotalPrice );
	} else {
		await expect( page.locator( 'td.product-name' ) ).toHaveText(
			`${ product.name } × ${ qty }`
		);
		await expect( page.locator( 'tr.order-total > td' ) ).toContainText(
			expectedTotalPrice
		);
	}
}

async function addProductToCartAndProceedToCheckout(
	pageSlug,
	page,
	product,
	qty,
	tax
) {
	await addAProductToCart( page, product.id, qty );
	await page.goto( pageSlug );
	await checkOrderDetails( page, product, qty, tax );
}

async function placeOrder( page ) {
	if ( isBlocksCheckout( page ) ) {
		await page.getByLabel( 'Add a note to your order' ).check();
		// this helps with flakiness on clicking the Place order button
		await page
			.getByPlaceholder( 'Notes about your order' )
			.fill( 'This order was created by an end-to-end test.' );
	}

	await page.getByRole( 'button', { name: 'Place order' } ).click();

	await expect(
		page.getByText( 'Your order has been received' )
	).toBeVisible();
}

async function fillBillingDetails( page, data, createAccount ) {
	if ( isBlocksCheckout( page ) ) {
		await page
			.getByRole( 'textbox', { name: 'Email address' } )
			.fill( data.email );

		await fillBillingCheckoutBlocks( page, {
			firstName: data.first_name,
			lastName: data.last_name,
			address: data.address_1,
			city: data.city,
			zip: data.postcode,
			phone: data.phone,
		} );

		if ( createAccount ) {
			await page
				.getByRole( 'checkbox', {
					name: 'Create an account with',
				} )
				.check();
		}
	} else {
		await page
			.getByRole( 'textbox', { name: 'First name' } )
			.fill( data.first_name );
		await page
			.getByRole( 'textbox', { name: 'Last name' } )
			.fill( data.last_name );
		await page
			.getByRole( 'textbox', { name: 'Street address' } )
			.fill( data.address_1 );
		await page
			.getByRole( 'textbox', { name: 'Town / City' } )
			.fill( data.city );
		await page
			.getByRole( 'textbox', { name: 'ZIP Code' } )
			.fill( data.postcode );
		await page.getByRole( 'textbox', { name: 'Phone' } ).fill( data.phone );
		await page
			.getByRole( 'textbox', { name: 'Email address' } )
			.fill( data.email );

		if ( createAccount ) {
			await page.getByText( 'Create an account?' ).check();
		}
	}
}
/* endregion */

/* region fixtures */
const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await createBlocksCheckoutPage( page.context().browser() );

		const calcTaxesState = await updateIfNeeded(
			'general/woocommerce_calc_taxes',
			'yes'
		);

		const loginAtCheckoutState = await updateIfNeeded(
			'account/woocommerce_enable_checkout_login_reminder',
			'yes'
		);

		const signUpAtCheckoutState = await updateIfNeeded(
			'account/woocommerce_enable_signup_and_login_from_checkout',
			'yes'
		);

		// Check id COD payment is enabled and enable it if it is not
		const codResponse = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/cod`
		);
		const codEnabled = codResponse.enabled;

		if ( ! codEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: true,
			} );
		}

		// Check id BACS payment is enabled and enable it if it is not
		const bacsResponse = await restApi.get(
			`${ WC_API_PATH }/payment_gateways/bacs`
		);
		const bacsEnabled = bacsResponse.enabled;

		if ( ! bacsEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
				enabled: true,
			} );
		}

		await page.context().clearCookies();
		await use( page );

		// revert the settings to initial state

		await resetValue( 'general/woocommerce_calc_taxes', calcTaxesState );

		await resetValue(
			'general/woocommerce_enable_checkout_login_reminder',
			loginAtCheckoutState
		);

		await resetValue(
			'general/woocommerce_enable_signup_and_login_from_checkout',
			signUpAtCheckoutState
		);

		if ( ! codEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/cod`, {
				enabled: codEnabled,
			} );
		}

		if ( ! bacsEnabled ) {
			await restApi.put( `${ WC_API_PATH }/payment_gateways/bacs`, {
				enabled: bacsEnabled,
			} );
		}
	},
	product: async ( { restApi }, use ) => {
		let product;

		// Using dec: 0 to avoid small rounding issues
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
	tax: async ( { restApi }, use ) => {
		let tax;
		await restApi
			.post( `${ WC_API_PATH }/taxes`, {
				country: 'US',
				state: '*',
				cities: '*',
				postcodes: '*',
				rate: '25',
				name: 'US Tax',
				shipping: false,
			} )
			.then( ( r ) => {
				tax = r.data;
			} );

		await use( tax );

		await restApi.delete( `${ WC_API_PATH }/taxes/${ tax.id }`, {
			force: true,
		} );
	},
	customer: async ( { restApi }, use ) => {
		const customerData = getFakeCustomer();
		let customer;

		await restApi
			.post( `${ WC_API_PATH }/customers`, customerData )
			.then( ( response ) => {
				customer = response.data;
				customer.password = customerData.password;
			} );

		// add a shipping zone and method for the customer
		let shippingZoneId;
		await restApi
			.post( `${ WC_API_PATH }/shipping/zones`, {
				name: `Free Shipping ${ customerData.shipping.city }`,
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await restApi.put(
			`${ WC_API_PATH }/shipping/zones/${ shippingZoneId }/locations`,
			[
				{
					code: `${ customerData.shipping.country }:${ customerData.shipping.state }`,
					type: 'state',
				},
			]
		);
		await restApi.post(
			`${ WC_API_PATH }/shipping/zones/${ shippingZoneId }/methods`,
			{
				method_id: 'free_shipping',
			}
		);

		await use( customer );

		await restApi.delete( `${ WC_API_PATH }/customers/${ customer.id }`, {
			force: true,
		} );
		await restApi.delete(
			`${ WC_API_PATH }/shipping/zones/${ shippingZoneId }`,
			{
				force: true,
			}
		);
	},
} );
/* endregion */

/* region tests */
checkoutPages.forEach( ( { name, slug } ) => {
	test(
		`guest can checkout paying with cash on delivery on ${ name }`,
		{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
		async ( { page, product, tax } ) => {
			await addProductToCartAndProceedToCheckout(
				slug,
				page,
				product,
				2,
				tax
			);
			const newCustomer = getFakeCustomer();
			await fillBillingDetails( page, newCustomer.billing, false );
			await page.getByText( 'Cash on delivery' ).click();
			await placeOrder( page );
			await page.goto( 'my-account/' );
			await expect( page.locator( '#username' ) ).toBeVisible();
		}
	);
} );

checkoutPages.forEach( ( { name, slug } ) => {
	test(
		`guest can create an account at checkout on ${ name }`,
		{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
		async ( { page, product, tax } ) => {
			await addProductToCartAndProceedToCheckout(
				slug,
				page,
				product,
				2,
				tax
			);
			const newCustomer = getFakeCustomer();
			await fillBillingDetails( page, newCustomer.billing, true );
			await page.getByText( 'Direct bank transfer' ).click();
			await placeOrder( page );
			await page.goto( 'my-account/' );
			await expect(
				page
					.getByLabel( 'Account pages' )
					.getByRole( 'link', { name: 'Log out' } )
			).toBeVisible();
		}
	);
} );

checkoutPages.forEach( ( { name, slug } ) => {
	test(
		`logged in customer can checkout with default addresses and direct bank transfer on ${ name }`,
		{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
		async ( { page, product, customer, tax } ) => {
			await page.goto( 'my-account/' );
			await logInFromMyAccount( page, customer.email, customer.password );
			await addProductToCartAndProceedToCheckout(
				slug,
				page,
				product,
				2,
				tax
			);

			await page.getByText( 'Direct bank transfer' ).click();
			await placeOrder( page );
		}
	);
} );

checkoutPages.forEach( ( { name, slug } ) => {
	test(
		`customer can login at checkout and place the order with a different shipping address ${ name }`,
		{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
		async ( { page, product, tax, customer } ) => {
			const qty = 3;
			await addProductToCartAndProceedToCheckout(
				slug,
				page,
				product,
				qty,
				tax
			);

			await page
				.getByRole( 'link', {
					name: 'Click here to login',
				} )
				.or(
					page.getByRole( 'link', {
						name: 'Log in',
					} )
				)
				.click();

			await logInFromMyAccount(
				page,
				customer.email,
				customer.password,
				false
			);
			await checkOrderDetails( page, product, qty, tax );

			const shippingAddress = {
				firstName: faker.person.firstName(),
				lastName: faker.person.lastName(),
				address: faker.location.streetAddress(),
				city: faker.location.city(),
				zip: faker.location.zipCode( '#####' ),
			};

			if ( isBlocksCheckout( page ) ) {
				await page
					.getByRole( 'button', { name: 'Edit shipping address' } )
					.click();
				await fillShippingCheckoutBlocks( page, shippingAddress );
			} else {
				await page.getByText( 'Ship to a different address?' ).click();

				await page
					.locator( '#shipping_first_name' )
					.fill( shippingAddress.firstName );
				await page
					.locator( '#shipping_last_name' )
					.fill( shippingAddress.lastName );
				await page
					.locator( '#shipping_address_1' )
					.fill( shippingAddress.address );
				await page
					.locator( '#shipping_city' )
					.fill( shippingAddress.city );
				await page
					.locator( '#shipping_postcode' )
					.fill( shippingAddress.zip );
			}

			await page.getByText( 'Cash on delivery' ).click();
			await placeOrder( page );
		}
	);
} );

checkoutPages.forEach( ( { name, slug } ) => {
	test(
		`existing customer can update the billing address and place the order with direct bank transfer on ${ name }`,
		{ tag: [ tags.PAYMENTS, tags.SERVICES, tags.HPOS ] },
		async ( { page, product, tax, customer } ) => {
			await page.goto( 'my-account/' );
			await logInFromMyAccount( page, customer.email, customer.password );
			await addProductToCartAndProceedToCheckout(
				slug,
				page,
				product,
				1,
				tax
			);

			const billingAddress = {
				first_name: customer.first_name,
				last_name: customer.last_name,
				address_1: faker.location.streetAddress(),
				city: faker.location.city(),
				postcode: faker.location.zipCode( '#####' ),
				phone: faker.phone.number(),
				email: faker.internet.email(),
			};

			if ( isBlocksCheckout( page ) ) {
				await page
					.getByRole( 'checkbox', {
						name: 'Use same address for billing',
					} )
					.uncheck();
				await fillBillingCheckoutBlocks( page, {
					firstName: billingAddress.first_name,
					lastName: billingAddress.last_name,
					address: billingAddress.address_1,
					city: billingAddress.city,
					zip: billingAddress.postcode,
					phone: billingAddress.phone,
					email: billingAddress.email,
				} );
			} else {
				await fillBillingDetails( page, billingAddress, false );
			}

			await page.getByText( 'Direct bank transfer' ).click();
			await placeOrder( page );
		}
	);
} );

/* endregion */
