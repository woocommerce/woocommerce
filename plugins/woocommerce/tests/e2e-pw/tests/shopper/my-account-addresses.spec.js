const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const randomNum = new Date().getTime().toString();
const customer = {
	username: `customer${ randomNum }`,
	password: 'password',
	email: `customer${ randomNum }@woocommercecoree2etestsuite.com`,
};

test.describe( 'Customer can manage addresses in My Account > Addresses page', () => {
	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// create customer
		await api
			.post( 'customers', customer )
			.then( ( response ) => ( customer.id = response.data.id ) );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `customers/${ customer.id }`, { force: true } );
	} );

	test( 'can add billing address from my account', async ( { page } ) => {
		await page.goto( 'my-account/edit-address/' );
		// sign in as the "customer" user
		await page.locator( '#username' ).fill( customer.username );
		await page.locator( '#password' ).fill( customer.password );
		await page.locator( 'text=Log in' ).click();

		// verify that the page exists and that there are no added addresses
		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'Addresses'
		);
		await expect(
			page.locator( '.woocommerce-Address' ).first()
		).toContainText( 'You have not set up this type of address yet.' );

		// go to add billing address
		await page.goto( 'my-account/edit-address/billing' );
		await expect( page.locator( 'h3' ) ).toContainText( 'Billing address' );
		await page.locator( '#billing_first_name' ).fill( 'John' );
		await page.locator( '#billing_last_name' ).fill( 'Doe Billing' );
		await page
			.locator( '#billing_address_1' )
			.fill( '123 Evergreen Terrace' );
		await page.locator( '#billing_city' ).fill( 'Frisco' );
		await page.locator( '#billing_country' ).selectOption( 'US' );
		await page.locator( '#billing_state' ).selectOption( 'CA' );
		await page.locator( '#billing_postcode' ).fill( '97403' );
		await page.locator( '#billing_phone' ).fill( '555 555-5555' );
		await page.locator( 'text=Save address' ).click();

		// verify billing address has been applied
		await expect( page.locator( '.is-success' ) ).toContainText(
			'Address changed successfully.'
		);
		await expect(
			page.locator( `:text("John Doe Billing")` )
		).toBeVisible();
		await expect(
			page.locator( `:text("123 Evergreen Terrace")` )
		).toBeVisible();
		await expect(
			page.locator( `:text("Frisco, CA 97403")` )
		).toBeVisible();
	} );

	test( 'can add shipping address from my account', async ( { page } ) => {
		await page.goto( 'my-account/edit-address/' );
		// sign in as the "customer" user
		await page.locator( '#username' ).fill( customer.username );
		await page.locator( '#password' ).fill( customer.password );
		await page.locator( 'text=Log in' ).click();

		// verify that the page exists and that there are no added addresses
		await expect( page.locator( 'h1.entry-title' ) ).toContainText(
			'Addresses'
		);
		await expect(
			page.locator( '.woocommerce-Address' ).nth( 1 )
		).toContainText( 'You have not set up this type of address yet.' );

		// go to add shipping address
		await page.goto( 'my-account/edit-address/shipping' );
		await expect( page.locator( 'h3' ) ).toContainText(
			'Shipping address'
		);
		await page.locator( '#shipping_first_name' ).fill( 'John' );
		await page.locator( '#shipping_last_name' ).fill( 'Doe Shipping' );
		await page
			.locator( '#shipping_address_1' )
			.fill( '456 Nevergreen Terrace' );
		await page.locator( '#shipping_city' ).fill( 'New York' );
		await page.locator( '#shipping_country' ).selectOption( 'US' );
		await page.locator( '#shipping_state' ).selectOption( 'NY' );
		await page.locator( '#shipping_postcode' ).fill( '10010' );
		await page.locator( 'text=Save address' ).click();

		// verify shipping address has been applied
		await expect( page.locator( '.is-success' ) ).toContainText(
			'Address changed successfully.'
		);
		await expect(
			page.locator( `:text("John Doe Shipping")` )
		).toBeVisible();
		await expect(
			page.locator( `:text("456 Nevergreen Terrace")` )
		).toBeVisible();
		await expect(
			page.locator( `:text("New York, NY 10010")` )
		).toBeVisible();
	} );
} );
