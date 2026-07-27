/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import {
	addAProductToCart,
	fillBillingCheckoutBlocks,
	fillShippingCheckoutBlocks,
	WC_API_PATH,
} from '@woocommerce/e2e-utils-playwright';
import { faker } from '@faker-js/faker';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { getFakeCustomer, getFakeProduct } from '../../utils/data';
import {
	createClassicCheckoutPage,
	CLASSIC_CHECKOUT_PAGE,
} from '../../utils/pages';
import { logInFromMyAccount } from '../../utils/login';
import { setGatewayEnabled } from '../../utils/payment-gateways';
import { updateIfNeeded, resetValue } from '../../utils/settings';
import {
	assertTaxCalculationEnabled,
	withScopedTaxClass,
} from '../../utils/taxes';

//todo handle other countries and states than the default (US, CA) when filling the addresses

const checkoutPages = [
	{ name: 'blocks checkout', slug: 'checkout' },
	CLASSIC_CHECKOUT_PAGE,
];

/* region helpers */
function isClassicCheckout( page: Page ) {
	return page.url().includes( CLASSIC_CHECKOUT_PAGE.slug );
}

async function checkOrderDetails( page: Page, product, qty: number, tax ) {
	const expectedTotalPrice = (
		parseFloat( product.price ) *
		qty *
		( 1 + parseFloat( tax.rate ) / 100 )
	).toLocaleString( 'en-US', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	} );

	if ( isClassicCheckout( page ) ) {
		await expect( page.locator( 'td.product-name' ) ).toHaveText(
			`${ product.name } × ${ qty }`
		);
		await expect( page.locator( 'tr.order-total > td' ) ).toContainText(
			expectedTotalPrice
		);
	} else {
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
	}
}

async function addProductToCartAndProceedToCheckout(
	pageSlug: string,
	page: Page,
	product,
	qty: number,
	tax
) {
	await addAProductToCart( page, product.id, qty );
	await page.goto( pageSlug );
	await checkOrderDetails( page, product, qty, tax );
}

async function placeOrder( page: Page ) {
	if ( ! isClassicCheckout( page ) ) {
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

async function fillBillingDetails( page: Page, data, createAccount: boolean ) {
	if ( isClassicCheckout( page ) ) {
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
	} else {
		await page
			.getByRole( 'textbox', { name: 'Email address' } )
			.fill( data.email );

		const addressDetails = {
			firstName: data.first_name,
			lastName: data.last_name,
			address: data.address_1,
			city: data.city,
			zip: data.postcode,
			phone: data.phone,
		};

		// In the block checkout the address form shown depends on whether the
		// cart has a shipping method available: with one, it renders a shipping
		// address (billing mirrors it via the checked "Use same address for
		// billing"); with none, it renders a billing-only address. Fill whichever
		// address group is rendered rather than assuming billing.
		const shippingGroup = page.getByRole( 'group', {
			name: 'Shipping address',
		} );
		const billingGroup = page.getByRole( 'group', {
			name: 'Billing address',
		} );
		await shippingGroup
			.or( billingGroup )
			.first()
			.waitFor( { state: 'visible' } );

		if ( await shippingGroup.isVisible() ) {
			await fillShippingCheckoutBlocks( page, addressDetails );
		} else {
			await fillBillingCheckoutBlocks( page, addressDetails );
		}

		if ( createAccount ) {
			await page
				.getByRole( 'checkbox', {
					name: 'Create an account with',
				} )
				.check();
		}
	}
}
/* endregion */

/* region fixtures */
const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await createClassicCheckoutPage();

		await assertTaxCalculationEnabled( restApi );

		const loginAtCheckoutState = await updateIfNeeded(
			'account/woocommerce_enable_checkout_login_reminder',
			'yes'
		);

		const signUpAtCheckoutState = await updateIfNeeded(
			'account/woocommerce_enable_signup_and_login_from_checkout',
			'yes'
		);

		// COD and BACS are enabled globally in site setup; guard defensively in
		// case they are somehow off, and restore their prior state afterwards.
		const codWasEnabled = await setGatewayEnabled( restApi, 'cod', true );
		const bacsWasEnabled = await setGatewayEnabled( restApi, 'bacs', true );

		await page.context().clearCookies();
		await use( page );

		// revert the settings to initial state

		await resetValue(
			'account/woocommerce_enable_checkout_login_reminder',
			loginAtCheckoutState
		);

		await resetValue(
			'account/woocommerce_enable_signup_and_login_from_checkout',
			signUpAtCheckoutState
		);

		await setGatewayEnabled( restApi, 'cod', codWasEnabled );
		await setGatewayEnabled( restApi, 'bacs', bacsWasEnabled );
	},
	product: async ( { restApi, tax }, use ) => {
		let product;

		// Using dec: 0 to avoid small rounding issues
		await restApi
			.post( `${ WC_API_PATH }/products`, {
				...getFakeProduct( { dec: 0 } ),
				// Assign to this spec's own tax class so only this product is
				// taxed; other workers' products use the standard class, which
				// has no rate under the taxes-on baseline.
				tax_class: tax.taxClassSlug,
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
		// 	force: true,
		// } );
	},
	tax: async ( { restApi }, use ) => {
		// The `product` fixture is assigned to this scoped class so only this
		// spec's product is taxed; concurrent workers are unaffected.
		await withScopedTaxClass( restApi, 'Checkout Spec', use );
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

		// Shipping is provided by the baseline free-shipping zone in site setup,
		// so this fixture no longer creates its own zone (concurrent zone churn
		// made shipping availability non-deterministic at checkout).

		await use( customer );

		await restApi.delete( `${ WC_API_PATH }/customers/${ customer.id }`, {
			force: true,
		} );
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

			// The at-checkout login is submitted with assertSuccess=false, so it
			// isn't fully awaited, and its redirect target is unreliable
			// (https://github.com/woocommerce/woocommerce/issues/56205). Under
			// parallel load the session can lag, leaving the checkout in a guest
			// state with an empty contact email that blocks the order. Confirm the
			// session is established via My account before returning to checkout.
			await page.goto( 'my-account/' );
			await expect(
				page
					.getByLabel( 'Account pages' )
					.getByRole( 'link', { name: 'Log out' } )
			).toBeVisible();

			await page.goto( slug );
			await expect( page.url() ).toContain( slug );

			// Block checkout hydrates the logged-in customer's contact email
			// asynchronously from the Store API cart; under parallel load that
			// can lag behind navigation, leaving the field empty and blocking
			// the order. Wait for it to populate before placing the order.
			if ( ! isClassicCheckout( page ) ) {
				await expect(
					page
						.getByRole( 'textbox', { name: 'Email address' } )
						.first()
				).not.toHaveValue( '' );
			}

			await checkOrderDetails( page, product, qty, tax );

			const shippingAddress = {
				firstName: faker.person.firstName(),
				lastName: faker.person.lastName(),
				address: faker.location.streetAddress(),
				city: faker.location.city(),
				zip: faker.location.zipCode( '#####' ),
			};

			if ( isClassicCheckout( page ) ) {
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
			} else {
				await fillShippingCheckoutBlocks( page, shippingAddress );
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
				phone: faker.phone.number( { style: 'international' } ),
				email: faker.internet.email(),
			};

			if ( isClassicCheckout( page ) ) {
				await fillBillingDetails( page, billingAddress, false );
			} else {
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
			}

			await page.getByText( 'Direct bank transfer' ).click();
			await placeOrder( page );
		}
	);
} );

/* endregion */
